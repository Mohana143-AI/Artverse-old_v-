<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$listing_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$listing_id) {
    header("Location: explore.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT l.*, c.name as cat_name, c.slug as cat_slug,
               u.full_name as creator_name, u.username as creator_username,
               cp.bio as creator_bio, cp.location
        FROM listings l
        JOIN categories c ON l.category_id = c.id
        JOIN users u ON l.creator_id = u.id
        LEFT JOIN creator_profiles cp ON u.id = cp.user_id
        WHERE l.id = ?
    ");
    $stmt->execute([$listing_id]);
    $listing = $stmt->fetch();
    if (!$listing) {
        header("Location: explore.php");
        exit;
    }
} catch (PDOException $e) {
    header("Location: explore.php");
    exit;
}

// Track view (non-owners only)
if (!is_logged_in() || $_SESSION['user_id'] != $listing['creator_id']) {
    $pdo->prepare("UPDATE listings SET views = views + 1 WHERE id = ?")->execute([$listing_id]);
}

// ── Handle Save for Later (AJAX + POST) ────────────────────
$wishlist_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!is_logged_in()) {
        echo json_encode(['error' => 'login_required']);
        exit;
    }
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'invalid_token']);
        exit;
    }
    $uid = (int) $_SESSION['user_id'];
    if ($_POST['action'] === 'toggle_wishlist') {
        // Check if already saved
        $chk = $pdo->prepare("SELECT id FROM wishlists WHERE user_id=? AND listing_id=?");
        $chk->execute([$uid, $listing_id]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM wishlists WHERE user_id=? AND listing_id=?")->execute([$uid, $listing_id]);
            echo json_encode(['saved' => false, 'message' => 'Removed from wishlist']);
        } else {
            $pdo->prepare("INSERT INTO wishlists (user_id, listing_id) VALUES (?,?)")->execute([$uid, $listing_id]);
            echo json_encode(['saved' => true, 'message' => 'Saved to wishlist!']);
        }
        exit;
    }
}

// Check wishlist status for current user
$is_saved = false;
if (is_logged_in()) {
    $wchk = $pdo->prepare("SELECT id FROM wishlists WHERE user_id=? AND listing_id=?");
    $wchk->execute([$_SESSION['user_id'], $listing_id]);
    $is_saved = (bool) $wchk->fetch();
}

// Use shared 300-image pool (30 per category)
require_once 'includes/image_pools.php';

function listing_image($image_url, $cat_slug, $listing_type, $listing_id = 0)
{
    return get_category_image($image_url, $cat_slug, $listing_type, $listing_id);
}

// Build set of 3 additional gallery preview images from same category
$main_image = listing_image($listing['image_url'] ?? '', $listing['cat_slug'], $listing['listing_type'], $listing_id);
$gallery = [];
for ($i = 1; $i <= 3; $i++) {
    $gallery[] = listing_image('', $listing['cat_slug'], $listing['listing_type'], $listing_id + $i);
}

$page_title = $listing['title'] . ' | ArtSphere';
require_once 'includes/header.php';
require_once 'includes/navigation.php';

$badge = 'Customizable';
$icon = 'ph-faders';
if ($listing['listing_type'] === 'ready_made') {
    $badge = 'Ready-made';
    $icon = 'ph-check-circle';
} elseif ($listing['listing_type'] === 'service') {
    $badge = 'Service';
    $icon = 'ph-paint-brush';
}

$is_owner = (is_logged_in() && $_SESSION['user_id'] == $listing['creator_id']);
?>

<style>
    /* ── Listing Detail Page ─────────────────────────── */
    .main-img {
        width: 100%;
        aspect-ratio: 4/3;
        object-fit: cover;
        border-radius: 16px;
        cursor: zoom-in;
        transition: transform 0.4s ease;
    }

    .gallery-thumb {
        width: 100%;
        aspect-ratio: 1;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.25s ease;
        filter: brightness(0.7);
    }

    .gallery-thumb:hover,
    .gallery-thumb.active {
        border-color: var(--accent-pink);
        filter: brightness(1);
        transform: scale(1.03);
    }

    /* Save/Heart button */
    #wish-btn {
        transition: all 0.25s ease;
    }

    #wish-btn.saved i {
        color: #F20A68;
    }

    #wish-btn.saved {
        border-color: #F20A68 !important;
        color: #F20A68 !important;
    }

    #wish-btn:not(.saved) i {
        color: inherit;
    }

    /* Toast notification */
    #wish-toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        padding: 12px 22px;
        border-radius: 12px;
        font-size: 0.88rem;
        font-weight: 500;
        opacity: 0;
        transform: translateY(12px);
        transition: all 0.3s ease;
        pointer-events: none;
    }

    #wish-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    #wish-toast.saved-toast {
        background: #111;
        border: 1px solid #F20A68;
        color: #F20A68;
    }

    #wish-toast.removed-toast {
        background: #111;
        border: 1px solid #444;
        color: #aaa;
    }
</style>

<!-- Toast -->
<div id="wish-toast"></div>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="explore.php" class="text-secondary text-decoration-none">Explore</a>
            </li>
            <li class="breadcrumb-item"><a href="explore.php?category=<?= $listing['cat_slug'] ?>"
                    class="text-secondary text-decoration-none"><?= htmlspecialchars($listing['cat_name']) ?></a></li>
            <li class="breadcrumb-item active text-white text-truncate" style="max-width:200px;">
                <?= htmlspecialchars($listing['title']) ?></li>
        </ol>
    </nav>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success fade-in-up">
            <i class="ph ph-check-circle me-2"></i> Listing published successfully!
        </div>
    <?php endif; ?>

    <div class="row g-5">
        <!-- ── Left: Image Gallery ───────────────────── -->
        <div class="col-lg-7 fade-in-up">
            <div class="sticky-top" style="top:90px;">
                <!-- Main Image -->
                <div style="border-radius:16px; overflow:hidden; background:#111; border:1px solid #222;">
                    <img id="main-img" src="<?= htmlspecialchars($main_image) ?>"
                        alt="<?= htmlspecialchars($listing['title']) ?>" class="main-img"
                        onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=1200&q=90';">
                </div>

                <!-- Thumbnail strip -->
                <div class="row g-2 mt-2">
                    <div class="col-3">
                        <img src="<?= htmlspecialchars($main_image) ?>" class="gallery-thumb active"
                            onclick="switchImg(this, '<?= htmlspecialchars($main_image) ?>')" alt="View 1">
                    </div>
                    <?php foreach ($gallery as $idx => $gimg): ?>
                        <div class="col-3">
                            <img src="<?= htmlspecialchars($gimg) ?>" class="gallery-thumb"
                                onclick="switchImg(this, '<?= htmlspecialchars($gimg) ?>')" alt="View <?= $idx + 2 ?>"
                                onerror="this.parentElement.style.display='none'">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Right: Listing Details ─────────────────── -->
        <div class="col-lg-5 fade-in-up" style="animation-delay:.1s;">

            <!-- Badge + Edit -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge px-3 py-2"
                    style="background:rgba(242,10,104,0.12);color:#F20A68;border:1px solid rgba(242,10,104,0.3);font-size:.8rem;">
                    <i class="ph <?= $icon ?> me-1"></i><?= $badge ?>
                </span>
                <?php if ($is_owner): ?>
                    <a href="edit_listing.php?id=<?= $listing['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="ph ph-pencil-simple me-1"></i>Edit
                    </a>
                <?php endif; ?>
            </div>

            <p class="text-secondary small mb-1"
                style="text-transform:uppercase;letter-spacing:.08em;font-size:.75rem;">
                <?= htmlspecialchars($listing['cat_name']) ?>
            </p>
            <h1 class="fw-bold mb-2" style="font-size:1.75rem;"><?= htmlspecialchars($listing['title']) ?></h1>

            <div class="d-flex align-items-baseline gap-3 mb-4">
                <?php if ($listing['price']): ?>
                    <h2 class="mb-0" style="color:#F20A68;font-weight:700;">₹<?= number_format($listing['price'], 2) ?></h2>
                <?php else: ?>
                    <h4 class="mb-0 text-secondary">Request Quote</h4>
                <?php endif; ?>
                <?php if ($listing['views']): ?>
                    <span class="text-secondary small"><i class="ph ph-eye me-1"></i><?= number_format($listing['views']) ?>
                        views</span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="card mb-4" style="background:#0f0f0f;border:1px solid #222;">
                <div class="card-body p-4">
                    <h6 class="mb-3" style="color:#ccc;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;">
                        About this creation</h6>
                    <p class="mb-0" style="color:#aaa;white-space:pre-line;line-height:1.7;font-size:.93rem;">
                        <?= htmlspecialchars($listing['description']) ?></p>
                </div>
            </div>

            <!-- CTA Card -->
            <?php if (!$is_owner): ?>
                <div class="card mb-4" style="background:#0f0f0f;border:1px solid #2a2a2a;">
                    <div class="card-body p-4">
                        <h5 class="text-center text-white">Interested in this?</h5>
                        <p class="text-secondary small mb-4">Contact the creator to discuss your requirements or place an
                            order.</p>

                        <a href="<?= is_logged_in() ? 'request_customization.php?listing_id=' . $listing['id'] : 'login.php' ?>"
                            class="btn btn-primary-custom w-100 btn-lg mb-2">
                            <i class="ph ph-chat-circle-text me-2"></i>Request Customization
                        </a>

                        <!-- Save for Later (AJAX toggle) -->
                        <button id="wish-btn" class="btn btn-outline-custom w-100 <?= $is_saved ? 'saved' : '' ?>"
                            data-listing="<?= $listing_id ?>"
                            data-csrf="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <i class="ph <?= $is_saved ? 'ph-heart-fill' : 'ph-heart' ?> me-2"></i>
                            <span id="wish-label"><?= $is_saved ? 'Saved to Wishlist' : 'Save for Later' ?></span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4" style="background:#0f0f0f;border:1px solid #222;">
                    <div class="card-body p-4 text-center">
                        <p class="text-secondary mb-0"><i class="ph ph-info me-1"></i>This is your listing.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Creator Info -->
            <div class="d-flex align-items-center gap-3 p-4 rounded-4"
                style="background:#0f0f0f;border:1px solid #222;">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                    style="width:54px;height:54px;background:linear-gradient(135deg,#F20A68,#c9075a);font-size:1.2rem;">
                    <?= strtoupper(substr($listing['creator_name'], 0, 1)) ?>
                </div>
                <div class="flex-grow-1">
                    <p class="text-secondary small mb-0">Created by</p>
                    <a href="profile.php?username=<?= urlencode($listing['creator_username']) ?>"
                        class="fw-bold text-decoration-none hover-pink d-block" style="color:#fff;">
                        <?= htmlspecialchars($listing['creator_name']) ?>
                    </a>
                    <?php if (!empty($listing['creator_bio'])): ?>
                        <p class="text-secondary small mb-0 mt-1" style="font-size:.8rem;">
                            <?= htmlspecialchars(substr($listing['creator_bio'], 0, 80)) ?>...
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($listing['location'])): ?>
                        <span class="text-secondary small"><i
                                class="ph ph-map-pin me-1"></i><?= htmlspecialchars($listing['location']) ?></span>
                    <?php endif; ?>
                </div>
                <a href="profile.php?username=<?= urlencode($listing['creator_username']) ?>"
                    class="btn btn-sm btn-outline-secondary flex-shrink-0">View Profile</a>
            </div>
        </div>
    </div>
</div>

<script>
    // ── Gallery thumbnail switcher ───────────────────────────────
    function switchImg(thumb, src) {
        document.getElementById('main-img').src = src;
        document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
    }

    // ── Save for Later (AJAX wishlist toggle) ────────────────────
    (function () {
        const btn = document.getElementById('wish-btn');
        if (!btn) return;

        const toast = document.getElementById('wish-toast');

        function showToast(msg, saved) {
            toast.textContent = (saved ? '♥ ' : '✕ ') + msg;
            toast.className = 'show ' + (saved ? 'saved-toast' : 'removed-toast');
            clearTimeout(toast._t);
            toast._t = setTimeout(() => toast.className = '', 2600);
        }

        btn.addEventListener('click', function () {
            const listingId = btn.dataset.listing;
            const csrf = btn.dataset.csrf;

            <?php if (!is_logged_in()): ?>
                window.location.href = 'login.php?redirect=listing.php%3Fid=<?= $listing_id ?>';
                return;
            <?php endif; ?>

            btn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'toggle_wishlist');
            fd.append('csrf_token', csrf);

            fetch(`listing.php?id=${listingId}`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        if (data.error === 'login_required') {
                            window.location.href = 'login.php';
                        }
                        return;
                    }
                    const icon = btn.querySelector('i');
                    const label = document.getElementById('wish-label');
                    if (data.saved) {
                        btn.classList.add('saved');
                        icon.className = 'ph ph-heart-fill me-2';
                        label.textContent = 'Saved to Wishlist';
                    } else {
                        btn.classList.remove('saved');
                        icon.className = 'ph ph-heart me-2';
                        label.textContent = 'Save for Later';
                    }
                    showToast(data.message, data.saved);
                })
                .catch(() => showToast('Something went wrong.', false))
                .finally(() => btn.disabled = false);
        });
    })();
</script>

<?php require_once 'includes/footer.php'; ?>
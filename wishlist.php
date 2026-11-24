<?php
$page_title = 'My Wishlist | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

// Handle remove from wishlist (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'Invalid token']); exit;
    }
    if ($_POST['action'] === 'remove') {
        $lid = (int)($_POST['listing_id'] ?? 0);
        $pdo->prepare("DELETE FROM wishlists WHERE user_id=? AND listing_id=?")
            ->execute([$user['id'], $lid]);
        echo json_encode(['ok' => true, 'listing_id' => $lid]);
        exit;
    }
}

// Fetch all wishlisted items with listing details
$stmt = $pdo->prepare("
    SELECT w.id as wish_id, w.created_at as saved_at,
           l.id as listing_id, l.title, l.price, l.listing_type, l.image_url,
           c.name as cat_name, c.slug as cat_slug,
           u.full_name as creator_name, u.username as creator_username
    FROM wishlists w
    JOIN listings l ON w.listing_id = l.id
    JOIN categories c ON l.category_id = c.id
    JOIN users u ON l.creator_id = u.id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$user['id']]);
$wishlist = $stmt->fetchAll();

// Use shared 300-image pool (30 per category)
require_once 'includes/image_pools.php';

function wish_image($image_url, $cat_slug, $listing_type, $listing_id = 0) {
    return get_category_image($image_url, $cat_slug, $listing_type, $listing_id);
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<style>
.wish-card {
    background: #0f0f0f;
    border: 1px solid #222;
    border-radius: 14px;
    overflow: hidden;
    transition: all 0.25s ease;
    position: relative;
}
.wish-card:hover {
    border-color: #333;
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.5);
}
.wish-img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.wish-card:hover .wish-img { transform: scale(1.04); }

.remove-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 34px; height: 34px;
    border-radius: 50% !important;
    background: rgba(0,0,0,0.7) !important;
    border: 1px solid #333 !important;
    color: #aaa !important;
    display: flex; align-items: center; justify-content: center;
    padding: 0 !important;
    font-size: .9rem;
    transition: all 0.2s ease;
    z-index: 5;
}
.remove-btn:hover {
    background: rgba(220,53,69,0.85) !important;
    border-color: #dc3545 !important;
    color: #fff !important;
    transform: scale(1.1) !important;
}
.wish-price { color: #F20A68; font-weight: 700; font-size: 1rem; }
</style>

<div class="container py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold mb-1">
                <i class="ph ph-heart-fill me-2" style="color:#F20A68;"></i>My Wishlist
            </h1>
            <p class="text-secondary mb-0"><?= count($wishlist) ?> saved item<?= count($wishlist) !== 1 ? 's' : '' ?></p>
        </div>
        <a href="explore.php" class="btn btn-outline-custom">
            <i class="ph ph-compass me-2"></i>Explore More
        </a>
    </div>

    <?php if (empty($wishlist)): ?>
    <!-- Empty State -->
    <div class="text-center py-5">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
             style="width:100px;height:100px;background:rgba(242,10,104,0.08);font-size:2.8rem;">
            <i class="ph ph-heart" style="color:#F20A68;"></i>
        </div>
        <h3 class="mb-2">Your wishlist is empty</h3>
        <p class="text-secondary mb-4">Browse creations and click <strong>"Save for Later"</strong> on any listing to add it here.</p>
        <a href="explore.php" class="btn btn-primary-custom btn-lg">
            <i class="ph ph-compass me-2"></i>Start Exploring
        </a>
    </div>

    <?php else: ?>
    <!-- Wishlist Grid -->
    <div class="row g-4" id="wishlist-grid">
        <?php foreach ($wishlist as $idx => $item):
            $img = wish_image($item['image_url'], $item['cat_slug'], $item['listing_type'], $item['listing_id']);
            $type_badge = ['ready_made'=>['label'=>'Ready-made','icon'=>'ph-check-circle'], 'service'=>['label'=>'Service','icon'=>'ph-paint-brush'], 'customizable'=>['label'=>'Customizable','icon'=>'ph-faders']];
            $tb = $type_badge[$item['listing_type']] ?? $type_badge['customizable'];
        ?>
        <div class="col-md-6 col-lg-4 col-xl-3 fade-in-up wish-item"
             id="wish-item-<?= $item['listing_id'] ?>"
             style="animation-delay:<?= ($idx % 4) * 0.07 ?>s;">
            <div class="wish-card h-100">
                <!-- Remove button -->
                <button class="remove-btn btn"
                        onclick="removeFromWishlist(<?= $item['listing_id'] ?>, this)"
                        title="Remove from wishlist">
                    <i class="ph ph-x"></i>
                </button>

                <!-- Image -->
                <a href="listing.php?id=<?= $item['listing_id'] ?>">
                    <div style="overflow:hidden;">
                        <img src="<?= htmlspecialchars($img) ?>"
                             class="wish-img"
                             alt="<?= htmlspecialchars($item['title']) ?>"
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';">
                    </div>
                </a>

                <!-- Info -->
                <div class="p-3">
                    <p class="mb-1" style="font-size:.72rem;color:#666;text-transform:uppercase;letter-spacing:.07em;">
                        <?= htmlspecialchars($item['cat_name']) ?>
                    </p>
                    <a href="listing.php?id=<?= $item['listing_id'] ?>" class="text-decoration-none">
                        <h6 class="mb-1 text-truncate hover-pink" style="color:#fff;font-size:.95rem;">
                            <?= htmlspecialchars($item['title']) ?>
                        </h6>
                    </a>
                    <p class="mb-2 text-secondary" style="font-size:.8rem;">
                        By <?= htmlspecialchars($item['creator_name']) ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="wish-price">
                            <?= $item['price'] ? '₹'.number_format($item['price'],2) : 'Request Quote' ?>
                        </span>
                        <span style="font-size:.72rem;background:rgba(242,10,104,.1);color:#F20A68;padding:3px 8px;border-radius:20px;border:1px solid rgba(242,10,104,.25);">
                            <i class="ph <?= $tb['icon'] ?> me-1"></i><?= $tb['label'] ?>
                        </span>
                    </div>
                    <div class="mt-3 pt-3" style="border-top:1px solid #1e1e1e;">
                        <a href="listing.php?id=<?= $item['listing_id'] ?>" class="btn btn-primary-custom w-100 btn-sm">
                            View Listing
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

<script>
function removeFromWishlist(listingId, btn) {
    const card = document.getElementById('wish-item-' + listingId);
    if (!card) return;

    btn.disabled = true;
    const fd = new FormData();
    fd.append('action', 'remove');
    fd.append('listing_id', listingId);
    fd.append('csrf_token', document.getElementById('csrf-token').value);

    fetch('wishlist.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                card.style.transition = 'all 0.35s ease';
                card.style.opacity    = '0';
                card.style.transform  = 'scale(0.88)';
                setTimeout(() => {
                    card.remove();
                    // Update count
                    const remaining = document.querySelectorAll('.wish-item').length;
                    const countEl = document.querySelector('p.text-secondary.mb-0');
                    if (countEl) countEl.textContent = remaining + ' saved item' + (remaining !== 1 ? 's' : '');
                    // Show empty state if none left
                    if (remaining === 0) location.reload();
                }, 360);
            }
        })
        .catch(() => btn.disabled = false);
}
</script>

<?php require_once 'includes/footer.php'; ?>

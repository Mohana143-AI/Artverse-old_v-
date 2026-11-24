<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/image_pools.php';

$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if (empty($username)) {
    header("Location: creators.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.username, u.role, 
               COALESCE(u.bio, cp.bio) as bio, 
               COALESCE(u.location, cp.location) as location, 
               cp.specialties, 
               COALESCE(u.profile_image, cp.profile_image) as profile_image, 
               COALESCE(u.cover_image, cp.cover_image) as cover_image, 
               u.created_at
        FROM users u
        LEFT JOIN creator_profiles cp ON u.id = cp.user_id
        WHERE u.username = ? AND u.account_status = 'active'
    ");
    $stmt->execute([$username]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        die("Profile not found.");
    }
    
    // Fetch listings for this creator
    $listings = [];
    if ($profile['role'] === 'creator') {
        $stmt_listings = $pdo->prepare("
            SELECT l.*, c.name as cat_name, c.slug as cat_slug 
            FROM listings l 
            JOIN categories c ON l.category_id = c.id 
            WHERE l.creator_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmt_listings->execute([$profile['id']]);
        $listings = $stmt_listings->fetchAll();
    }
    
} catch (PDOException $e) {
    die("An error occurred.");
}

$page_title = $profile['full_name'] . ' | ArtSphere';
require_once 'includes/header.php';
require_once 'includes/navigation.php';

$avatar_img = get_creator_avatar((int)$profile['id'], $profile['profile_image'] ?? '');
$cover_img  = get_creator_cover((int)$profile['id'], $profile['cover_image'] ?? '');
$is_owner   = (is_logged_in() && $_SESSION['user_id'] == $profile['id']);
?>

<!-- Profile Header Banner -->
<div class="position-relative" style="height: 280px; overflow: hidden; background: #121212;">
    <img src="<?= htmlspecialchars($cover_img) ?>" class="w-100 h-100" style="object-fit: cover;" alt="Cover Banner" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=1920&q=80&auto=format&fit=crop';">
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, rgba(8,8,8,0.2) 0%, rgba(8,8,8,0.95) 100%);"></div>
</div>

<div class="container pb-5" style="margin-top: -90px; position: relative; z-index: 10;">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Profile Sidebar -->
            <div class="card custom-card sticky-top" style="top: 90px; background: #121212; border: 1px solid #242424; border-radius: 16px;">
                <div class="card-body-custom text-center pb-4 p-4">
                    <img src="<?= htmlspecialchars($avatar_img) ?>" class="rounded-circle border border-4 border-dark mb-3 shadow" style="width: 140px; height: 140px; object-fit: cover; background: #1f1f1f;" alt="<?= htmlspecialchars($profile['full_name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=82&auto=format&fit=crop';">
                    
                    <h3 class="text-white mb-1 fw-bold"><?= htmlspecialchars($profile['full_name']) ?></h3>
                    <p class="text-secondary mb-2">@<?= htmlspecialchars($profile['username']) ?></p>
                    <span class="badge mb-3 px-3 py-1 text-capitalize" style="background: #1a1a1a; border: 1px solid #333; color: #bbb; font-weight: 500;">
                        <i class="ph <?= $profile['role'] === 'creator' ? 'ph-sparkle text-pink' : 'ph-user' ?> me-1"></i><?= htmlspecialchars($profile['role']) ?>
                    </span>
                    
                    <?php if($is_owner): ?>
                        <div class="mt-2 mb-4">
                            <a href="edit_profile.php" class="btn btn-outline-custom w-100"><i class="ph ph-pencil-simple me-2"></i>Edit Profile</a>
                        </div>
                    <?php else: ?>
                        <?php if($profile['role'] === 'creator'): ?>
                        <div class="mt-2 mb-4">
                            <a href="messages.php?creator_id=<?= $profile['id'] ?>" class="btn btn-primary-custom w-100"><i class="ph ph-chat-circle-text me-2"></i>Request Quote</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <hr class="border-secondary mb-4" style="border-color: #242424 !important;">
                    
                    <div class="text-start">
                        <?php if(!empty($profile['location'])): ?>
                            <div class="mb-3 d-flex align-items-center text-secondary">
                                <i class="ph ph-map-pin fs-5 me-3" style="color: #F20A68;"></i>
                                <span class="text-white"><?= htmlspecialchars($profile['location']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3 d-flex align-items-center text-secondary">
                            <i class="ph ph-calendar-blank fs-5 me-3" style="color: #F20A68;"></i>
                            <span class="text-white">Joined <?= date('F Y', strtotime($profile['created_at'])) ?></span>
                        </div>
                        
                        <?php if(!empty($profile['specialties'])): ?>
                            <div class="mt-4">
                                <h6 class="text-white mb-2 fw-semibold">Specialties</h6>
                                <?php 
                                $specs = explode(',', $profile['specialties']);
                                foreach($specs as $spec):
                                    if(empty(trim($spec))) continue;
                                ?>
                                    <span class="badge bg-dark border border-secondary text-light fw-normal me-1 mb-1 p-2" style="background:#1a1a1a !important; border-color:#333 !important;"><?= htmlspecialchars(trim($spec)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- Bio Section -->
            <?php if(!empty($profile['bio'])): ?>
            <div class="card custom-card mb-4 fade-in-up" style="background: #121212; border: 1px solid #242424; border-radius: 16px;">
                <div class="card-body p-4">
                    <h5 class="text-white mb-3 fw-bold"><i class="ph ph-user-circle me-2" style="color: #F20A68;"></i>About</h5>
                    <p class="text-light mb-0" style="white-space: pre-line; line-height: 1.7;"><?= htmlspecialchars($profile['bio']) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Portfolio / Listings Section (For Creators) -->
            <?php if($profile['role'] === 'creator'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                <h4 class="text-white mb-0 fw-bold">
                    Portfolio & Offerings
                </h4>
                <div class="d-flex align-items-center gap-2">
                    <?php if($is_owner): ?>
                        <a href="add_listing.php" class="btn btn-sm btn-primary-custom"><i class="ph ph-plus me-1"></i> Add Offering</a>
                    <?php endif; ?>
                    <span class="badge rounded-pill px-3 py-2" style="background: #1f1f1f; border: 1px solid #333; color: #fff; font-size: 0.85rem;">
                        <?= count($listings) ?> <?= count($listings) === 1 ? 'Item' : 'Items' ?>
                    </span>
                </div>
            </div>
            
            <div class="row g-4">
                <?php if(empty($listings)): ?>
                    <div class="col-12">
                        <div class="card custom-card p-5 text-center" style="background: #121212; border: 1px solid #242424; border-radius: 16px;">
                            <i class="ph ph-image text-secondary mb-3" style="font-size: 3.5rem;"></i>
                            <h5 class="text-white mb-2">No portfolio items yet</h5>
                            <p class="text-secondary mb-4">This creator hasn't uploaded any listings or portfolio items yet.</p>
                            <?php if($is_owner): ?>
                                <div><a href="add_listing.php" class="btn btn-primary-custom"><i class="ph ph-plus me-1"></i>Add Your First Creation</a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($listings as $index => $item): 
                        $badge = 'Customizable';
                        $icon = 'ph-faders';
                        if ($item['listing_type'] === 'ready_made') {
                            $badge = 'Ready-made';
                            $icon = 'ph-check-circle';
                        } elseif ($item['listing_type'] === 'service') {
                            $badge = 'Service';
                            $icon = 'ph-paint-brush';
                        }
                        
                        $card_image = get_category_image(
                            $item['image_url'] ?? '',
                            $item['cat_slug'] ?? '',
                            $item['listing_type'] ?? '',
                            (int)$item['id']
                        );
                    ?>
                    <div class="col-md-6 fade-in-up" style="animation-delay: <?= ($index % 4) * 0.08 ?>s;">
                        <a href="listing.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                            <div class="card custom-card h-100" style="background: #121212; border: 1px solid #242424; border-radius: 16px; overflow: hidden;">
                                <div class="card-img-wrapper" style="position: relative; aspect-ratio: 4/3; width: 100%; overflow: hidden; background: #1a1a1a;">
                                    <img src="<?= htmlspecialchars($card_image) ?>" 
                                         alt="<?= htmlspecialchars($item['title']) ?>" 
                                         loading="lazy" 
                                         onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <span class="badge px-3 py-2 rounded-pill" style="background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.72rem;">
                                            <i class="ph <?= $icon ?> me-1"></i><?= $badge ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body-custom d-flex flex-column" style="flex-grow: 1; padding: 18px 20px 20px;">
                                    <p class="text-secondary small mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: #888 !important;"><?= htmlspecialchars($item['cat_name']) ?></p>
                                    <h5 class="card-title-custom text-truncate mb-2 text-white" style="font-size: 1.05rem; font-weight: 600;"><?= htmlspecialchars($item['title']) ?></h5>
                                    <div class="d-flex justify-content-between align-items-center mt-auto pt-3" style="border-top: 1px solid #242424;">
                                        <span class="text-white fw-bold" style="font-size: 1.05rem;">
                                            <?= $item['price'] ? '₹' . number_format($item['price'], 2) : '<span class="text-secondary" style="font-size:0.85rem;">Request Quote</span>' ?>
                                        </span>
                                        <span style="color: #F20A68; font-size: 0.9rem;">
                                            <i class="ph ph-arrow-right"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <!-- Customer Member Section -->
            <div class="card custom-card p-4 fade-in-up" style="background: #121212; border: 1px solid #242424; border-radius: 16px;">
                <h5 class="text-white mb-3 fw-bold"><i class="ph ph-user-check me-2 text-pink"></i>ArtSphere Community Member</h5>
                <p class="text-secondary mb-4">Welcome to <?= htmlspecialchars($profile['full_name']) ?>'s profile. Connect with creators and customize bespoke artisan crafts across ArtSphere.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="explore.php" class="btn btn-primary-custom"><i class="ph ph-compass me-2"></i>Explore Creations</a>
                    <?php if($is_owner): ?>
                        <a href="wishlist.php" class="btn btn-outline-custom"><i class="ph ph-heart me-2" style="color: #F20A68;"></i>My Wishlist</a>
                        <a href="my_requests.php" class="btn btn-outline-custom"><i class="ph ph-folder me-2"></i>My Requests</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

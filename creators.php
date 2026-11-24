<?php
$page_title = 'Find Creators | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/image_pools.php';

// Fetch creators from database
try {
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, u.username, cp.bio, cp.location, cp.specialties, cp.profile_image
        FROM users u
        JOIN creator_profiles cp ON u.id = cp.user_id
        WHERE u.role = 'creator' AND u.account_status = 'active'
        ORDER BY u.created_at DESC
    ");
    $creators = $stmt->fetchAll();
} catch (PDOException $e) {
    $creators = [];
    error_log("Creators Query Error: " . $e->getMessage());
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="text-center mb-5 fade-in-up">
        <h1 class="display-5 fw-bold mb-3">Discover Local Talent</h1>
        <p class="text-secondary mx-auto" style="max-width: 600px;">Connect directly with independent artists, makers, and interior designers to bring your vision to life.</p>
    </div>

    <!-- Featured / Hidden Gems Creators -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-white mb-0">Hidden Gems</h3>
            <span class="text-secondary small">Recently joined creators</span>
        </div>
        <div class="row g-4">
            
            <?php if(empty($creators)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-secondary">No creators found. Please run the database migration.</p>
                </div>
            <?php else: ?>
                <?php foreach($creators as $index => $creator): 
                    $creator_avatar = get_creator_avatar((int)$creator['id'], $creator['profile_image'] ?? '');
                    $creator_cover  = get_creator_cover((int)$creator['id']);
                ?>
                <div class="col-lg-4 col-md-6 fade-in-up" style="animation-delay: <?= ($index % 3) * 0.2 ?>s;">
                    <div class="card custom-card h-100">
                        <div class="position-relative" style="height: 120px; overflow: hidden; background: #121212;">
                            <img src="<?= htmlspecialchars($creator_cover) ?>" class="w-100 h-100" style="object-fit: cover;" alt="Cover" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=1920&q=80&auto=format&fit=crop';">
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(20,20,20,1) 100%);"></div>
                        </div>
                        <div class="card-body-custom pt-0 position-relative text-center d-flex flex-column h-100">
                            <img src="<?= htmlspecialchars($creator_avatar) ?>" class="rounded-circle border border-4 border-dark" style="width: 80px; height: 80px; object-fit: cover; margin-top: -40px; background: #2a2a2a; position:relative; z-index:2;" alt="<?= htmlspecialchars($creator['full_name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=82&auto=format&fit=crop';">
                            
                            <h5 class="text-white mt-3 mb-1"><?= htmlspecialchars($creator['full_name']) ?></h5>
                            <p class="text-secondary small mb-2 text-truncate">@<?= htmlspecialchars($creator['username']) ?></p>
                            
                            <?php if(!empty($creator['specialties'])): ?>
                                <p class="text-white small mb-3 fst-italic">"<?= htmlspecialchars($creator['specialties']) ?>"</p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-center align-items-center gap-2 mb-4">
                                <?php if(!empty($creator['location'])): ?>
                                    <span class="badge bg-dark border border-secondary text-secondary"><i class="ph ph-map-pin me-1"></i><?= htmlspecialchars($creator['location']) ?></span>
                                <?php endif; ?>
                                <span class="badge bg-dark border border-secondary text-warning"><i class="ph ph-sparkle me-1"></i>New</span>
                            </div>
                            
                            <div class="mt-auto">
                                <a href="profile.php?username=<?= urlencode($creator['username']) ?>" class="btn btn-outline-custom w-100">View Portfolio</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
    </div>
    
    <!-- Smart Art Match Promo -->
    <div class="card bg-card border-secondary mt-5 p-5 text-center fade-in-up">
        <div class="mx-auto mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background-color: rgba(242, 10, 104, 0.1); color: var(--accent-pink); font-size: 2rem;">
            <i class="ph ph-magic-wand"></i>
        </div>
        <h3 class="text-white mb-3">Need help finding the right creator?</h3>
        <p class="text-secondary mb-4 mx-auto" style="max-width: 600px;">Tell us about your project requirements, budget, and style preferences. Our rule-based Smart Art Match will recommend the perfect local talent for your custom creation based on their category and specialties.</p>
        <div>
            <button class="btn btn-primary-custom btn-lg">Start Smart Match</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

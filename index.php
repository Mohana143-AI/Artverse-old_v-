<?php
$page_title = 'ArtSphere | Your Idea. Your Creator. Your Creation.';
require_once 'config/database.php';
require_once 'includes/image_pools.php';

try {
    $featured_stmt = $pdo->query("
        SELECT l.*, c.name as cat_name, c.slug as cat_slug 
        FROM listings l 
        JOIN categories c ON l.category_id = c.id 
        ORDER BY l.id ASC 
        LIMIT 4
    ");
    $featured_items = $featured_stmt->fetchAll();
} catch (Exception $e) {
    $featured_items = [];
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-10 fade-in-up">
                <span class="badge badge-custom mb-3">Premium Creator Marketplace</span>
                <h1 class="hero-title">Your Idea.<br>Your Creator.<br><span style="color: var(--accent-pink);">Your Creation.</span></h1>
                <p class="hero-subtitle mx-auto">Discover local talent. Customize every detail. Create something uniquely yours—from custom furniture to digital art.</p>
                
                <div class="d-flex justify-content-center gap-3 flex-wrap mt-5">
                    <a href="explore.php" class="btn btn-primary-custom btn-lg"><i class="ph ph-compass me-2"></i>Explore Creations</a>
                    <a href="creators.php" class="btn btn-outline-custom btn-lg"><i class="ph ph-users me-2"></i>Find Creators</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Customization Workflow Explanation -->
<section class="section-padding bg-card" style="background-color: #0a0a0a;">
    <div class="container">
        <div class="text-center mb-5 fade-in-up">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">Bring your vision to life in four simple steps</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3 col-sm-6 fade-in-up" style="animation-delay: 0.1s;">
                <div class="text-center p-4">
                    <div class="mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background-color: rgba(242, 10, 104, 0.1); color: var(--accent-pink); font-size: 2rem;">
                        <i class="ph ph-magnifying-glass"></i>
                    </div>
                    <h4 class="text-white mb-3">1. Discover</h4>
                    <p class="text-secondary small">Find the perfect local creator or browse customizable products in our marketplace.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in-up" style="animation-delay: 0.2s;">
                <div class="text-center p-4">
                    <div class="mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background-color: rgba(242, 10, 104, 0.1); color: var(--accent-pink); font-size: 2rem;">
                        <i class="ph ph-chat-circle-text"></i>
                    </div>
                    <h4 class="text-white mb-3">2. Request</h4>
                    <p class="text-secondary small">Submit your reference images and requirements to request a custom quote.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in-up" style="animation-delay: 0.3s;">
                <div class="text-center p-4">
                    <div class="mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background-color: rgba(242, 10, 104, 0.1); color: var(--accent-pink); font-size: 2rem;">
                        <i class="ph ph-pencil-line"></i>
                    </div>
                    <h4 class="text-white mb-3">3. Revise</h4>
                    <p class="text-secondary small">Review design drafts directly with your creator and approve the final vision.</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in-up" style="animation-delay: 0.4s;">
                <div class="text-center p-4">
                    <div class="mb-4 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background-color: rgba(242, 10, 104, 0.1); color: var(--accent-pink); font-size: 2rem;">
                        <i class="ph ph-package"></i>
                    </div>
                    <h4 class="text-white mb-3">4. Receive</h4>
                    <p class="text-secondary small">Track the progress of your project until your custom creation is delivered.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trending Artwork & Products -->
<section class="section-padding">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5 fade-in-up">
            <div>
                <h2 class="section-title mb-0">Trending Creations</h2>
                <p class="text-secondary mt-2 mb-0">Explore customizable ready-made items</p>
            </div>
            <a href="explore.php" class="text-white text-decoration-none border-bottom border-white pb-1">View All <i class="ph ph-arrow-right"></i></a>
        </div>
        
        <div class="row g-4">
            <?php foreach($featured_items as $index => $item): 
                $badge = 'Customizable';
                $icon  = 'ph-faders';
                if ($item['listing_type'] === 'ready_made') {
                    $badge = 'Ready-made';
                    $icon  = 'ph-check-circle';
                } elseif ($item['listing_type'] === 'service') {
                    $badge = 'Service';
                    $icon  = 'ph-paint-brush';
                }
                $img_url = get_category_image($item['image_url'] ?? '', $item['cat_slug'] ?? '', $item['listing_type'] ?? '', (int)$item['id']);
            ?>
            <div class="col-lg-3 col-md-6 fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s;">
                <a href="listing.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                    <div class="card custom-card h-100">
                        <div class="card-img-wrapper" style="position: relative; aspect-ratio: 4/3; overflow: hidden; background: #1a1a1a;">
                            <img src="<?= htmlspecialchars($img_url) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';" style="width: 100%; height: 100%; object-fit: cover;">
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge px-3 py-2 rounded-pill" style="background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.72rem;">
                                    <i class="ph <?= $icon ?> me-1"></i><?= $badge ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body-custom d-flex flex-column" style="flex-grow: 1;">
                            <p class="text-secondary small mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: #888 !important;"><?= htmlspecialchars($item['cat_name']) ?></p>
                            <h5 class="card-title-custom text-truncate mb-2 text-white" style="font-size: 1rem; font-weight: 600;"><?= htmlspecialchars($item['title']) ?></h5>
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2" style="border-top: 1px solid #242424;">
                                <span class="text-white fw-bold"><?= $item['price'] ? '₹' . number_format($item['price'], 2) : '<span class="text-secondary" style="font-size:0.85rem;">Request Quote</span>' ?></span>
                                <span style="color: #F20A68; font-size: 0.85rem;"><i class="ph ph-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Categories -->
<section class="section-padding bg-card" style="background-color: #0a0a0a;">
    <div class="container">
        <div class="text-center mb-5 fade-in-up">
            <h2 class="section-title">Explore by Category</h2>
        </div>
        
        <div class="d-flex flex-wrap justify-content-center gap-2 fade-in-up">
            <a href="categories.php" class="category-pill active">All Categories</a>
            <a href="#" class="category-pill">Interior Design</a>
            <a href="#" class="category-pill">Custom Furniture</a>
            <a href="#" class="category-pill">Digital Art</a>
            <a href="#" class="category-pill">Handmade Crafts</a>
            <a href="#" class="category-pill">Photography</a>
            <a href="#" class="category-pill">Jewellery</a>
        </div>
        
        <div class="text-center mt-5">
            <a href="categories.php" class="btn btn-outline-custom">View All Categories</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

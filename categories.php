<?php
$page_title = 'Categories | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/image_pools.php';

// Fetch categories from database
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    error_log("Categories Query Error: " . $e->getMessage());
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="text-center mb-5 fade-in-up">
        <h1 class="display-5 fw-bold mb-3">Browse by Category</h1>
        <p class="text-secondary mx-auto" style="max-width: 600px;">Find exactly what you need by browsing our specialized creative categories. Each category connects you with expert local makers and artists.</p>
    </div>

    <div class="row g-4">
        <?php if(empty($categories)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-secondary">No categories found. Please run the database migration.</p>
            </div>
        <?php else: ?>
            <?php foreach($categories as $index => $cat): 
                $cat_img = get_category_image($cat['image_url'] ?? '', $cat['slug'] ?? '', '', 0);
            ?>
            <div class="col-xl-3 col-lg-4 col-md-6 fade-in-up" style="animation-delay: <?= ($index % 4) * 0.1 ?>s;">
                <a href="explore.php?category=<?= urlencode($cat['slug']) ?>" class="text-decoration-none">
                    <div class="card bg-card border-secondary h-100 hover-card overflow-hidden" style="transition: all 0.3s ease; border-radius:12px;">
                        <div style="height:160px; overflow:hidden; position:relative;">
                            <img src="<?= htmlspecialchars($cat_img) ?>" 
                                 alt="<?= htmlspecialchars($cat['name']) ?>" 
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';"
                                 style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s ease;">
                            <div class="position-absolute bottom-0 start-0 w-100 p-2" style="background:linear-gradient(transparent, rgba(0,0,0,0.8));"></div>
                        </div>
                        <div class="card-body p-4 text-center">
                            <h5 class="text-white mb-2"><?= htmlspecialchars($cat['name']) ?></h5>
                            <p class="text-secondary small mb-0"><?= htmlspecialchars($cat['description']) ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-card:hover {
    transform: translateY(-5px);
    border-color: var(--accent-pink) !important;
    background-color: #1a1a1a;
}
.hover-card:hover i {
    color: var(--accent-pink);
}
</style>

<?php require_once 'includes/footer.php'; ?>

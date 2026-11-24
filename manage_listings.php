<?php
$page_title = 'Manage Portfolio | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/image_pools.php';

require_login();
$user = get_logged_in_user();

if ($user['role'] !== 'creator') {
    die("Unauthorized access.");
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $delete_id = (int)$_POST['listing_id'];
        $stmt = $pdo->prepare("DELETE FROM listings WHERE id = ? AND creator_id = ?");
        $stmt->execute([$delete_id, $user['id']]);
        header("Location: manage_listings.php?msg=deleted");
        exit;
    }
}

// Fetch user's listings
$stmt = $pdo->prepare("
    SELECT l.*, c.name as cat_name, c.slug as cat_slug 
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    WHERE l.creator_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$user['id']]);
$listings = $stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="text-white fw-bold mb-1">Manage Portfolio</h2>
            <p class="text-secondary mb-0">Add, edit, or remove your creative listings and services.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="add_listing.php" class="btn btn-primary-custom"><i class="ph ph-plus me-2"></i>Add New Listing</a>
        </div>
    </div>
    
    <?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success bg-dark border-success text-success fade-in-up">
            <i class="ph ph-check-circle me-2"></i> Listing deleted successfully.
        </div>
    <?php endif; ?>

    <div class="card bg-card fade-in-up" style="background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px; overflow: hidden;">
        <div class="card-body p-0" style="background-color: #121212 !important;">
            <?php if(empty($listings)): ?>
                <div class="p-5 text-center">
                    <i class="ph ph-image text-secondary mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-white">Your portfolio is empty</h5>
                    <p class="text-secondary mb-4">Start showcasing your work to attract customers.</p>
                    <a href="add_listing.php" class="btn btn-outline-custom">Add First Listing</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-4">Listing</th>
                                <th scope="col">Category</th>
                                <th scope="col">Type</th>
                                <th scope="col">Price</th>
                                <th scope="col" class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach($listings as $item): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php $item_img = get_category_image($item['image_url'] ?? '', $item['cat_slug'] ?? '', $item['listing_type'] ?? '', (int)$item['id']); ?>
                                        <img src="<?= htmlspecialchars($item_img) ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="Thumbnail" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';">
                                        <div>
                                            <a href="listing.php?id=<?= $item['id'] ?>" class="text-white fw-bold text-decoration-none hover-pink"><?= htmlspecialchars($item['title']) ?></a>
                                            <div class="text-secondary small">Added <?= date('M d, Y', strtotime($item['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-dark border border-secondary text-secondary"><?= htmlspecialchars($item['cat_name']) ?></span></td>
                                <td><span class="text-light text-capitalize"><?= str_replace('_', '-', $item['listing_type']) ?></span></td>
                                <td><span class="text-white"><?= $item['price'] ? '₹'.number_format($item['price'], 2) : 'Quote' ?></span></td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="edit_listing.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="ph ph-pencil-simple"></i></a>
                                        <form method="POST" action="manage_listings.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this listing?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="listing_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ph ph-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

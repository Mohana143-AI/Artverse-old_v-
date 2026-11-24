<?php
$page_title = 'Edit Listing | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/image_pools.php';

require_login();
$user = get_logged_in_user();

if ($user['role'] !== 'creator') {
    die("Unauthorized access.");
}

$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$listing_id) {
    header("Location: manage_listings.php");
    exit;
}

$error = '';
$success = '';

// Fetch existing listing
$stmt = $pdo->prepare("
    SELECT l.*, c.slug as cat_slug 
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    WHERE l.id = ? AND l.creator_id = ?
");
$stmt->execute([$listing_id, $user['id']]);
$listing = $stmt->fetch();

if (!$listing) {
    die("Listing not found or you do not have permission to edit it.");
}

// Fetch categories for dropdown
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();

$current_image = get_category_image($listing['image_url'] ?? '', $listing['cat_slug'] ?? '', $listing['listing_type'] ?? '', $listing['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (!empty($_POST['price']) && is_numeric($_POST['price'])) ? (float)$_POST['price'] : null;
        $listing_type = $_POST['listing_type'] ?? 'customizable';
        $image_url = trim($_POST['image_url'] ?? '');
        
        // Find category slug
        $cat_slug = '';
        foreach ($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $cat_slug = $cat['slug'];
                break;
            }
        }

        // Basic validation
        if (empty($title) || empty($category_id) || empty($description)) {
            $error = "Title, Category, and Description are required.";
        } elseif (!in_array($listing_type, ['ready_made', 'customizable', 'service'])) {
            $error = "Invalid listing type.";
        } else {
            // Handle Image File Upload if uploaded
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($_FILES['image_file']['name']);
                $ext = strtolower($file_info['extension'] ?? '');
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array($ext, $allowed_exts)) {
                    $upload_dir = __DIR__ . '/uploads/listings/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $new_filename = 'listing_' . $user['id'] . '_' . time() . '.' . $ext;
                    $dest = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
                        $image_url = 'uploads/listings/' . $new_filename;
                    }
                } else {
                    $error = "Image must be a valid JPG, PNG, WEBP, or GIF file.";
                }
            }

            // Keep existing image if new one is empty
            if (empty($error) && empty($image_url)) {
                $image_url = $listing['image_url'];
            }
            
            if (empty($error)) {
                try {
                    $update_stmt = $pdo->prepare("
                        UPDATE listings 
                        SET category_id = ?, title = ?, description = ?, price = ?, listing_type = ?, image_url = ?
                        WHERE id = ? AND creator_id = ?
                    ");
                    $update_stmt->execute([
                        $category_id, $title, $description, $price, $listing_type, $image_url,
                        $listing_id, $user['id']
                    ]);
                    
                    $success = "Listing updated successfully!";
                    
                    // Update local array so form reflects changes
                    $listing['title'] = $title;
                    $listing['category_id'] = $category_id;
                    $listing['description'] = $description;
                    $listing['price'] = $price;
                    $listing['listing_type'] = $listing_type;
                    $listing['image_url'] = $image_url;
                    $listing['cat_slug'] = $cat_slug;

                    $current_image = get_category_image($image_url, $cat_slug, $listing_type, $listing['id']);
                    
                } catch (PDOException $e) {
                    error_log("Edit Listing Error: " . $e->getMessage());
                    $error = "An error occurred while updating your listing.";
                }
            }
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item"><a href="manage_listings.php" class="text-secondary text-decoration-none">Manage Portfolio</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Edit Listing</li>
              </ol>
            </nav>

            <div class="card custom-card p-4 p-md-5 fade-in-up" style="background:#121212; border:1px solid #242424; border-radius:16px;">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                    <div>
                        <h3 class="text-white mb-1">Edit Offering</h3>
                        <p class="text-secondary small mb-0">Update listing details, pricing, and picture</p>
                    </div>
                    <a href="listing.php?id=<?= $listing['id'] ?>" class="btn btn-sm btn-outline-custom" target="_blank"><i class="ph ph-arrow-square-out me-1"></i>View Listing</a>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger bg-dark border-danger text-danger mb-4" role="alert">
                        <i class="ph ph-warning-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success bg-dark border-success text-success mb-4" role="alert">
                        <i class="ph ph-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="edit_listing.php?id=<?= $listing['id'] ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="mb-4">
                        <label for="title" class="form-label text-light">Listing Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="title" name="title" required value="<?= htmlspecialchars($listing['title']) ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="category_id" class="form-label text-light">Category <span class="text-danger">*</span></label>
                            <select class="form-select bg-dark border-secondary text-light shadow-none" id="category_id" name="category_id" required>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $listing['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="listing_type" class="form-label text-light">Listing Type <span class="text-danger">*</span></label>
                            <select class="form-select bg-dark border-secondary text-light shadow-none" id="listing_type" name="listing_type" required>
                                <option value="customizable" <?= $listing['listing_type'] === 'customizable' ? 'selected' : '' ?>>Customizable Product</option>
                                <option value="ready_made" <?= $listing['listing_type'] === 'ready_made' ? 'selected' : '' ?>>Ready-made Product</option>
                                <option value="service" <?= $listing['listing_type'] === 'service' ? 'selected' : '' ?>>Creative Service / Consultation</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="price" class="form-label text-light">Base Price (₹) (Optional)</label>
                        <div class="form-text text-secondary small mb-2">Leave blank to display "Request Quote" instead of a set price.</div>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-white fw-bold">₹</span>
                            <input type="number" step="0.01" min="0" class="form-control bg-dark border-secondary text-light shadow-none" id="price" name="price" placeholder="0.00" value="<?= htmlspecialchars($listing['price'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label text-light">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control bg-dark border-secondary text-light shadow-none" id="description" name="description" rows="5" required><?= htmlspecialchars($listing['description']) ?></textarea>
                    </div>
                    
                    <!-- ── Picture for Portfolio & Offering ── -->
                    <div class="mb-4 p-3 rounded" style="background: #181818; border: 1px solid #262626;">
                        <label class="form-label text-white fw-semibold mb-2">
                            <i class="ph ph-image me-1 text-pink"></i> Picture for Portfolio & Offering
                        </label>
                        <p class="text-secondary small mb-3">Upload a new photo or paste an image URL, or leave blank to keep current picture.</p>
                        
                        <div class="mb-3 text-center" id="imgPreviewWrapper">
                            <img src="<?= htmlspecialchars($current_image) ?>" id="imgPreview" class="rounded" style="max-height: 200px; max-width: 100%; object-fit: cover; border: 1px solid #333;" alt="Preview" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=700&q=82&auto=format&fit=crop';">
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small">Upload New Photo File</label>
                                <input type="file" name="image_file" id="imageFileInput" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small">Or New Image Web URL</label>
                                <input type="text" name="image_url" id="imageUrlInput" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" placeholder="https://images.unsplash.com/..." value="<?= htmlspecialchars($listing['image_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-5 border-top border-secondary pt-4">
                        <a href="manage_listings.php" class="btn btn-outline-custom">Cancel</a>
                        <button type="submit" class="btn btn-primary-custom"><i class="ph ph-floppy-disk me-1"></i> Update Offering</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<script>
document.getElementById('imageFileInput')?.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const p = document.getElementById('imgPreview');
            p.src = evt.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('imageUrlInput')?.addEventListener('input', function() {
    if (this.value.trim().startsWith('http')) {
        const p = document.getElementById('imgPreview');
        p.src = this.value.trim();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

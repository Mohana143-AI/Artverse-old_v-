<?php
$page_title = 'Edit Profile | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/image_pools.php';

require_login();
$user = get_logged_in_user();
$error = '';
$success = '';

// Fetch user profile data
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.username, u.role, 
           COALESCE(u.bio, cp.bio) as bio, 
           COALESCE(u.location, cp.location) as location, 
           cp.specialties, 
           COALESCE(u.profile_image, cp.profile_image) as profile_image, 
           COALESCE(u.cover_image, cp.cover_image) as cover_image
    FROM users u
    LEFT JOIN creator_profiles cp ON u.id = cp.user_id
    WHERE u.id = ?
");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    die("User not found.");
}

// Current images for preview
$current_avatar = get_creator_avatar((int)$user['id'], $profile['profile_image'] ?? '');
$current_cover  = get_creator_cover((int)$user['id'], $profile['cover_image'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $full_name   = trim($_POST['full_name'] ?? $user['full_name']);
        $bio         = trim($_POST['bio'] ?? '');
        $location    = trim($_POST['location'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');
        
        $profile_image = trim($_POST['profile_image'] ?? '');
        $cover_image   = trim($_POST['cover_image'] ?? '');

        // Allowed image upload types
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $upload_dir = __DIR__ . '/uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Handle Avatar File Upload
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['avatar_file']['name']);
            $ext = strtolower($file_info['extension'] ?? '');
            if (in_array($ext, $allowed_exts)) {
                $new_filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $dest)) {
                    $profile_image = 'uploads/profiles/' . $new_filename;
                }
            } else {
                $error = "Profile photo must be a JPG, PNG, WEBP, or GIF image.";
            }
        }

        // Handle Cover File Upload
        if (empty($error) && isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $file_info = pathinfo($_FILES['cover_file']['name']);
            $ext = strtolower($file_info['extension'] ?? '');
            if (in_array($ext, $allowed_exts)) {
                $new_filename = 'cover_' . $user['id'] . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $dest)) {
                    $cover_image = 'uploads/profiles/' . $new_filename;
                }
            } else {
                $error = "Cover image must be a JPG, PNG, WEBP, or GIF image.";
            }
        }

        if (empty($error)) {
            // Keep existing image if not changed
            if (empty($profile_image)) {
                $profile_image = $profile['profile_image'] ?? null;
            }
            if (empty($cover_image)) {
                $cover_image = $profile['cover_image'] ?? null;
            }

            try {
                // Update users table
                $u_stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, bio = ?, location = ?, profile_image = ?, cover_image = ?
                    WHERE id = ?
                ");
                $u_stmt->execute([$full_name, $bio, $location, $profile_image, $cover_image, $user['id']]);

                // Update creator_profiles if creator
                if ($user['role'] === 'creator') {
                    $chk = $pdo->prepare("SELECT user_id FROM creator_profiles WHERE user_id = ?");
                    $chk->execute([$user['id']]);
                    if ($chk->fetch()) {
                        $cp_stmt = $pdo->prepare("
                            UPDATE creator_profiles 
                            SET bio = ?, location = ?, specialties = ?, profile_image = ?, cover_image = ?
                            WHERE user_id = ?
                        ");
                        $cp_stmt->execute([$bio, $location, $specialties, $profile_image, $cover_image, $user['id']]);
                    } else {
                        $cp_stmt = $pdo->prepare("
                            INSERT INTO creator_profiles (user_id, bio, location, specialties, profile_image, cover_image)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $cp_stmt->execute([$user['id'], $bio, $location, $specialties, $profile_image, $cover_image]);
                    }
                }

                $success = "Profile updated successfully!";
                
                // Refresh local profile data
                $profile['full_name'] = $full_name;
                $profile['bio'] = $bio;
                $profile['location'] = $location;
                $profile['specialties'] = $specialties;
                $profile['profile_image'] = $profile_image;
                $profile['cover_image'] = $cover_image;

                $current_avatar = get_creator_avatar((int)$user['id'], $profile_image ?? '');
                $current_cover  = get_creator_cover((int)$user['id'], $cover_image ?? '');

            } catch (PDOException $e) {
                error_log("Profile Update Error: " . $e->getMessage());
                $error = "An error occurred while updating your profile.";
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
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-secondary text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="profile.php?username=<?= urlencode($user['username']) ?>" class="text-secondary text-decoration-none">My Profile</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Edit Profile</li>
              </ol>
            </nav>

            <div class="card custom-card p-4 p-md-5 fade-in-up" style="background:#121212; border:1px solid #242424; border-radius:16px;">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                    <div>
                        <h3 class="text-white mb-1">Edit Profile</h3>
                        <p class="text-secondary small mb-0">Manage your avatar, background cover image, and bio</p>
                    </div>
                    <a href="profile.php?username=<?= urlencode($user['username']) ?>" class="btn btn-sm btn-outline-custom">View Public Profile</a>
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
                
                <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <!-- ── Background Cover Image Section ── -->
                    <div class="mb-4 p-3 rounded" style="background: #181818; border: 1px solid #262626;">
                        <label class="form-label text-white fw-semibold mb-2">
                            <i class="ph ph-image me-1 text-pink"></i> Background Cover Image
                        </label>
                        <p class="text-secondary small mb-3">Upload a custom banner image or paste an image URL (recommended: 1920×400 or widescreen ratio).</p>
                        
                        <div class="mb-3 position-relative rounded overflow-hidden" style="height: 140px; background: #0d0d0d;">
                            <img src="<?= htmlspecialchars($current_cover) ?>" id="coverPreview" class="w-100 h-100" style="object-fit: cover;" alt="Cover Preview" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?w=1920&q=80&auto=format&fit=crop';">
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small">Upload Cover File</label>
                                <input type="file" name="cover_file" id="coverFileInput" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small">Or Cover Image URL</label>
                                <input type="text" name="cover_image" id="coverUrlInput" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" placeholder="https://images.unsplash.com/..." value="<?= htmlspecialchars($profile['cover_image'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ── Profile Photo Section ── -->
                    <div class="mb-4 p-3 rounded" style="background: #181818; border: 1px solid #262626;">
                        <label class="form-label text-white fw-semibold mb-2">
                            <i class="ph ph-user-circle me-1 text-pink"></i> Profile Photo (Avatar)
                        </label>
                        <p class="text-secondary small mb-3">Upload a headshot/logo or paste an image URL (square aspect ratio recommended).</p>

                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?= htmlspecialchars($current_avatar) ?>" id="avatarPreview" class="rounded-circle border border-3 border-dark" style="width: 80px; height: 80px; object-fit: cover; background:#222;" alt="Avatar Preview" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=82&auto=format&fit=crop';">
                            <div class="flex-grow-1">
                                <span class="text-white small fw-bold">Current Avatar</span>
                                <p class="text-secondary small mb-0">Shown across listings, profile page, and messages.</p>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-secondary small">Upload Photo File</label>
                                <input type="file" name="avatar_file" id="avatarFileInput" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary small">Or Photo Image URL</label>
                                <input type="text" name="profile_image" id="avatarUrlInput" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" placeholder="https://images.unsplash.com/..." value="<?= htmlspecialchars($profile['profile_image'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ── Display Name & Account Info ── -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label text-light">Full / Display Name</label>
                            <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="full_name" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? $user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light">Username & Role</label>
                            <input type="text" class="form-control bg-dark border-secondary text-secondary shadow-none" value="@<?= htmlspecialchars($user['username']) ?> (<?= ucfirst($user['role']) ?>)" disabled>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="location" class="form-label text-light">Location / City</label>
                        <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="location" name="location" value="<?= htmlspecialchars($profile['location'] ?? '') ?>" placeholder="e.g. Chennai, Tamil Nadu or Paramathi">
                    </div>
                    
                    <?php if($user['role'] === 'creator'): ?>
                    <div class="mb-4">
                        <label for="specialties" class="form-label text-light">Creative Specialties</label>
                        <div class="form-text text-secondary small mb-2">Separate multiple specialties with commas (e.g. Oil Painting, Digital Arts, Woodworking, Pottery)</div>
                        <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="specialties" name="specialties" value="<?= htmlspecialchars($profile['specialties'] ?? '') ?>" placeholder="e.g. Portraits, Digital Arts, Custom Furniture">
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="bio" class="form-label text-light">About You (Bio)</label>
                        <textarea class="form-control bg-dark border-secondary text-light shadow-none" id="bio" name="bio" rows="5" placeholder="Tell visitors about yourself, your artistic style, background, or interests..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-5 border-top border-secondary pt-4">
                        <a href="profile.php?username=<?= urlencode($user['username']) ?>" class="btn btn-outline-custom">Cancel</a>
                        <button type="submit" class="btn btn-primary-custom"><i class="ph ph-floppy-disk me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<script>
// Live previews for file inputs
document.getElementById('avatarFileInput')?.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('avatarPreview').src = evt.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('coverFileInput')?.addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('coverPreview').src = evt.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('avatarUrlInput')?.addEventListener('input', function() {
    if (this.value.trim().startsWith('http')) {
        document.getElementById('avatarPreview').src = this.value.trim();
    }
});

document.getElementById('coverUrlInput')?.addEventListener('input', function() {
    if (this.value.trim().startsWith('http')) {
        document.getElementById('coverPreview').src = this.value.trim();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

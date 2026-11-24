<?php
$page_title = 'Join ArtSphere';
// Since we updated header.php to include DB and Auth, we just need to handle the logic before outputting headers.
// However, we need DB and Auth for the logic *before* header.php is loaded.
// So let's include them here, and use require_once so they don't crash when header.php includes them again.
require_once 'config/database.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'customer';
        $terms = isset($_POST['terms']) ? true : false;

        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!$terms) {
            $error = "You must accept the Terms of Service.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!in_array($role, ['customer', 'creator'])) {
            $error = "Invalid account type selected.";
        } else {
            // Check for duplicate username or email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                $error = "Username or email is already registered.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$full_name, $username, $email, $password_hash, $role]);
                    
                    if ($role === 'creator') {
                        $user_id = $pdo->lastInsertId();
                        $stmt = $pdo->prepare("INSERT INTO creator_profiles (user_id) VALUES (?)");
                        $stmt->execute([$user_id]);
                    }
                    $pdo->commit();
                    $success = "Registration successful! You can now log in.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Registration error: " . $e->getMessage());
                    $error = "An error occurred during registration. Please try again later.";
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
        <div class="col-lg-6 col-md-8">
            <div class="card custom-card p-4 p-md-5 fade-in-up">
                <div class="text-center mb-4">
                    <h2 class="text-white fw-bold">Join ArtSphere</h2>
                    <p class="text-secondary">Create your account to start your journey.</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger bg-dark border-danger text-danger" role="alert">
                        <i class="ph ph-warning-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success bg-dark border-success text-success" role="alert">
                        <i class="ph ph-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                        <div class="mt-2"><a href="login.php" class="alert-link text-success text-decoration-underline">Click here to sign in.</a></div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="register.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label text-light">Account Type</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input bg-dark border-secondary" type="radio" name="role" id="roleCustomer" value="customer" <?= (isset($_POST['role']) && $_POST['role'] === 'customer') || !isset($_POST['role']) ? 'checked' : '' ?>>
                                    <label class="form-check-label text-secondary" for="roleCustomer">Customer</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input bg-dark border-secondary" type="radio" name="role" id="roleCreator" value="creator" <?= isset($_POST['role']) && $_POST['role'] === 'creator' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-secondary" for="roleCreator">Creator</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label text-light">Full Name</label>
                            <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label text-light">Username</label>
                                <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label text-light">Email Address</label>
                                <input type="email" class="form-control bg-dark border-secondary text-light shadow-none" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label text-light">Password</label>
                                <input type="password" class="form-control bg-dark border-secondary text-light shadow-none" id="password" name="password" required>
                                <div class="form-text text-secondary small">Min 8 characters.</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="confirm_password" class="form-label text-light">Confirm Password</label>
                                <input type="password" class="form-control bg-dark border-secondary text-light shadow-none" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input bg-dark border-secondary" id="terms" name="terms" required>
                            <label class="form-check-label text-secondary small" for="terms">I accept the <a href="#" class="text-decoration-underline text-secondary">Terms of Service</a> and <a href="#" class="text-decoration-underline text-secondary">Privacy Policy</a>.</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom w-100 mb-3">Create Account</button>
                        
                        <div class="text-center text-secondary small">
                            Already have an account? <a href="login.php" class="text-white fw-bold">Sign In</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

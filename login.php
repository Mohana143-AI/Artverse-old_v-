<?php
$page_title = 'Sign In | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $login_id = trim($_POST['login_id'] ?? ''); // Email or username
        $password = $_POST['password'] ?? '';

        if (empty($login_id) || empty($password)) {
            $error = "Please enter both email/username and password.";
        } else {
            // In a real application, implement login attempt throttling here
            
            try {
                $stmt = $pdo->prepare("SELECT id, username, password_hash, role, full_name, account_status FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$login_id, $login_id]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['account_status'] !== 'active') {
                        $error = "Your account is currently " . htmlspecialchars($user['account_status']) . ".";
                    } else {
                        // Prevent session fixation
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['full_name'] = $user['full_name'];
                        
                        $redirect = $_SESSION['login_redirect'] ?? 'dashboard.php';
                        unset($_SESSION['login_redirect']);
                        
                        header("Location: " . $redirect);
                        exit;
                    }
                } else {
                    $error = "Invalid credentials. Please try again.";
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "An error occurred during sign in. Please try again later.";
            }
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card custom-card p-4 p-md-5 fade-in-up">
                <div class="text-center mb-4">
                    <h2 class="text-white fw-bold">Welcome Back</h2>
                    <p class="text-secondary">Sign in to continue to ArtSphere.</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-danger bg-dark border-danger text-danger" role="alert">
                        <i class="ph ph-warning-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="mb-3">
                        <label for="login_id" class="form-label text-light">Email or Username</label>
                        <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="login_id" name="login_id" value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label for="password" class="form-label text-light">Password</label>
                            <a href="#" class="text-secondary small text-decoration-underline">Forgot password?</a>
                        </div>
                        <input type="password" class="form-control bg-dark border-secondary text-light shadow-none" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary-custom w-100 mb-4">Sign In</button>
                    
                    <div class="text-center text-secondary small">
                        New to ArtSphere? <a href="register.php" class="text-white fw-bold">Create an account</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

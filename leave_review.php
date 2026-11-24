<?php
$page_title = 'Leave a Review | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if (!$project_id) { header("Location: customer_projects.php"); exit; }

// Must be customer on this completed project
$stmt = $pdo->prepare("
    SELECT p.*, u_crea.full_name AS creator_name, u_crea.username AS creator_username
    FROM projects p
    JOIN users u_crea ON p.creator_id = u_crea.id
    WHERE p.id = ? AND p.customer_id = ? AND p.status = 'completed'
");
$stmt->execute([$project_id, $user['id']]);
$project = $stmt->fetch();

if (!$project) die("Project not found, not yours, or not yet completed.");

// Check no existing review
$existing = $pdo->prepare("SELECT id FROM reviews WHERE project_id = ?");
$existing->execute([$project_id]);
if ($existing->fetchColumn()) {
    header("Location: customer_projects.php?msg=already_reviewed");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Invalid token.");
    $rating = (int)($_POST['rating'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5.";
    } else {
        try {
            $pdo->prepare("INSERT INTO reviews (project_id, customer_id, creator_id, rating, feedback) VALUES (?, ?, ?, ?, ?)")
                ->execute([$project_id, $user['id'], $project['creator_id'], $rating, $feedback]);
            header("Location: customer_projects.php?msg=review_submitted");
            exit;
        } catch (PDOException $e) {
            error_log("Review error: " . $e->getMessage());
            $error = "An error occurred while submitting your review.";
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card custom-card p-4 p-md-5 fade-in-up">
                <h3 class="text-white mb-2">Leave a Review</h3>
                <p class="text-secondary mb-4 pb-3 border-bottom border-secondary">
                    Rate your experience with <strong class="text-light"><?= htmlspecialchars($project['creator_name']) ?></strong>.
                </p>

                <?php if ($error): ?>
                    <div class="alert alert-danger bg-dark border-danger text-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="mb-4">
                        <label class="form-label text-light">Rating <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 mt-1" id="star-picker">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="star-label" style="font-size:2rem; cursor:pointer; color: #555;" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">
                                <input type="radio" name="rating" value="<?= $i ?>" required style="display:none;">
                                <i class="ph ph-star"></i>
                            </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="feedback" class="form-label text-light">Your Feedback</label>
                        <textarea class="form-control bg-dark border-secondary text-light shadow-none" id="feedback" name="feedback" rows="5"
                            placeholder="Share your experience — quality of work, communication, and delivery..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-3 border-top border-secondary pt-4">
                        <a href="customer_projects.php" class="btn btn-outline-custom">Cancel</a>
                        <button type="submit" class="btn btn-primary-custom">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.star-label').forEach((label, index) => {
    label.addEventListener('click', () => {
        document.querySelectorAll('.star-label').forEach((l, i) => {
            l.style.color = i <= index ? 'gold' : '#555';
            l.querySelector('i').className = i <= index ? 'ph ph-star-fill' : 'ph ph-star';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

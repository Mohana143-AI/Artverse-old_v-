<?php
$page_title = 'Request Customization | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

$listing_id = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
if (!$listing_id) {
    header("Location: explore.php");
    exit;
}

// Fetch listing & creator info
$stmt = $pdo->prepare("
    SELECT l.*, u.full_name as creator_name, u.username as creator_username 
    FROM listings l 
    JOIN users u ON l.creator_id = u.id 
    WHERE l.id = ?
");
$stmt->execute([$listing_id]);
$listing = $stmt->fetch();

if (!$listing) {
    die("Listing not found.");
}

// Prevent requesting from oneself
if ($listing['creator_id'] == $user['id']) {
    die("You cannot request customization on your own listing.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token.";
    } else {
        $requirements = trim($_POST['requirements'] ?? '');
        $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
        $preferred_date = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;
        
        if (empty($requirements)) {
            $error = "Please describe your requirements.";
        } else {
            try {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO customization_requests (customer_id, creator_id, listing_id, requirements, budget, preferred_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'submitted')
                ");
                $insert_stmt->execute([
                    $user['id'], 
                    $listing['creator_id'], 
                    $listing['id'], 
                    $requirements, 
                    $budget, 
                    $preferred_date
                ]);
                
                $request_id = $pdo->lastInsertId();
                header("Location: view_request.php?id=" . $request_id . "&msg=created");
                exit;
            } catch (PDOException $e) {
                error_log("Request Creation Error: " . $e->getMessage());
                $error = "An error occurred while submitting your request.";
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
                <li class="breadcrumb-item"><a href="explore.php" class="text-secondary text-decoration-none">Explore</a></li>
                <li class="breadcrumb-item"><a href="listing.php?id=<?= $listing['id'] ?>" class="text-secondary text-decoration-none"><?= htmlspecialchars($listing['title']) ?></a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Request Customization</li>
              </ol>
            </nav>

            <div class="card custom-card p-4 p-md-5 fade-in-up">
                <h3 class="text-white mb-2">Request Customization</h3>
                <p class="text-secondary mb-4 pb-3 border-bottom border-secondary">You are sending a request to <strong class="text-light"><?= htmlspecialchars($listing['creator_name']) ?></strong> regarding <strong><?= htmlspecialchars($listing['title']) ?></strong>.</p>
                
                <?php if($error): ?>
                    <div class="alert alert-danger bg-dark border-danger text-danger" role="alert">
                        <i class="ph ph-warning-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="request_customization.php?listing_id=<?= $listing['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="mb-4">
                        <label for="requirements" class="form-label text-light">Project Requirements <span class="text-danger">*</span></label>
                        <div class="form-text text-secondary small mb-2">Describe what you are looking for in detail. Include dimensions, colors, materials, or specific styles.</div>
                        <textarea class="form-control bg-dark border-secondary text-light shadow-none" id="requirements" name="requirements" rows="6" required placeholder="I would like this desk, but made of oak with a width of 120cm..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="budget" class="form-label text-light">Estimated Budget (₹) (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-white fw-bold">₹</span>
                                <input type="number" step="0.01" min="0" class="form-control bg-dark border-secondary text-light shadow-none" id="budget" name="budget" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label for="preferred_date" class="form-label text-light">Preferred Completion Date (Optional)</label>
                            <input type="date" class="form-control bg-dark border-secondary text-light shadow-none" id="preferred_date" name="preferred_date">
                        </div>
                    </div>
                    
                    <div class="alert alert-dark bg-dark border-secondary text-secondary small mb-4">
                        <i class="ph ph-info me-2 text-primary"></i> Submitting a request is free and does not commit you to a purchase. The creator will review your requirements and reply with a quotation.
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="listing.php?id=<?= $listing['id'] ?>" class="btn btn-outline-custom">Cancel</a>
                        <button type="submit" class="btn btn-primary-custom">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

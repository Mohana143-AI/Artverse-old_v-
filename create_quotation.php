<?php
$page_title = 'Create Quotation | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

if ($user['role'] !== 'creator') {
    die("Unauthorized access.");
}

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;

// Fetch Request
$stmt = $pdo->prepare("SELECT * FROM customization_requests WHERE id = ? AND creator_id = ?");
$stmt->execute([$request_id, $user['id']]);
$req = $stmt->fetch();

if (!$req) {
    die("Request not found or unauthorized.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token.";
    } else {
        $title = trim($_POST['title'] ?? 'Custom Quotation');
        $work_description = trim($_POST['work_description'] ?? '');
        $base_cost = (float)($_POST['base_cost'] ?? 0);
        $customization_cost = (float)($_POST['customization_cost'] ?? 0);
        $material_cost = (float)($_POST['material_cost'] ?? 0);
        $additional_charges = (float)($_POST['additional_charges'] ?? 0);
        $estimated_days = (int)($_POST['estimated_days'] ?? 0);
        $revisions_included = (int)($_POST['revisions_included'] ?? 0);
        $terms = trim($_POST['terms'] ?? '');
        
        $total_cost = $base_cost + $customization_cost + $material_cost + $additional_charges;
        
        if (empty($work_description)) {
            $error = "Work description is required.";
        } else {
            // Find current version
            $stmt_v = $pdo->prepare("SELECT MAX(version) FROM quotations WHERE request_id = ?");
            $stmt_v->execute([$request_id]);
            $current_v = $stmt_v->fetchColumn();
            $new_version = $current_v ? $current_v + 1 : 1;
            
            $pdo->beginTransaction();
            try {
                // Insert Quotation
                $q_stmt = $pdo->prepare("
                    INSERT INTO quotations (request_id, creator_id, title, work_description, base_cost, customization_cost, material_cost, additional_charges, total_cost, estimated_days, revisions_included, terms, version, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent')
                ");
                $q_stmt->execute([
                    $request_id, $user['id'], $title, $work_description, $base_cost, $customization_cost, $material_cost, $additional_charges, $total_cost, $estimated_days, $revisions_included, $terms, $new_version
                ]);
                
                // Update Request status
                $pdo->prepare("UPDATE customization_requests SET status = 'quotation_sent' WHERE id = ?")->execute([$request_id]);
                
                $pdo->commit();
                header("Location: view_request.php?id=" . $request_id . "&msg=quoted");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Quotation Creation Error: " . $e->getMessage());
                $error = "An error occurred while creating the quotation.";
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
                <li class="breadcrumb-item"><a href="incoming_requests.php" class="text-secondary text-decoration-none">Incoming Requests</a></li>
                <li class="breadcrumb-item"><a href="view_request.php?id=<?= $request_id ?>" class="text-secondary text-decoration-none">Request #<?= $request_id ?></a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Create Quotation</li>
              </ol>
            </nav>

            <div class="card custom-card p-4 p-md-5 fade-in-up">
                <h3 class="text-white mb-2">Quotation Builder</h3>
                <p class="text-secondary mb-4 pb-3 border-bottom border-secondary">Create a formal proposal and pricing structure for Request #<?= $request_id ?>.</p>
                
                <?php if($error): ?>
                    <div class="alert alert-danger bg-dark border-danger text-danger" role="alert">
                        <i class="ph ph-warning-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="create_quotation.php?request_id=<?= $request_id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="mb-4">
                        <label for="title" class="form-label text-light">Quotation Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-dark border-secondary text-light shadow-none" id="title" name="title" required placeholder="e.g. Custom Oak Dining Table Proposal">
                    </div>
                    
                    <div class="mb-4">
                        <label for="work_description" class="form-label text-light">Scope of Work <span class="text-danger">*</span></label>
                        <textarea class="form-control bg-dark border-secondary text-light shadow-none" id="work_description" name="work_description" rows="5" required placeholder="Describe what you will be delivering..."></textarea>
                    </div>
                    
                    <h5 class="text-white mt-5 mb-3">Cost Breakdown</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-light small">Base Cost (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control bg-dark border-secondary text-light shadow-none" name="base_cost" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small">Customization Cost (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control bg-dark border-secondary text-light shadow-none" name="customization_cost" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small">Material Cost (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control bg-dark border-secondary text-light shadow-none" name="material_cost" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small">Additional Charges (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control bg-dark border-secondary text-light shadow-none" name="additional_charges" placeholder="0.00">
                        </div>
                    </div>
                    
                    <h5 class="text-white mt-5 mb-3">Timeline & Terms</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-light small">Estimated Days to Complete</label>
                            <input type="number" min="1" class="form-control bg-dark border-secondary text-light shadow-none" name="estimated_days" placeholder="e.g. 14">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small">Number of Included Revisions</label>
                            <input type="number" min="0" class="form-control bg-dark border-secondary text-light shadow-none" name="revisions_included" value="1">
                        </div>
                        <div class="col-12 mt-3">
                            <label class="form-label text-light small">Terms & Conditions (Optional)</label>
                            <textarea class="form-control bg-dark border-secondary text-light shadow-none" name="terms" rows="3" placeholder="e.g. 50% upfront required, shipping not included."></textarea>
                        </div>
                    </div>
                    
                    <div class="alert alert-dark bg-dark border-secondary text-secondary small mb-4">
                        <i class="ph ph-calculator me-2"></i> The Total Cost will be calculated automatically based on the breakdown above.
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top border-secondary">
                        <a href="view_request.php?id=<?= $request_id ?>" class="btn btn-outline-custom">Cancel</a>
                        <button type="submit" class="btn btn-primary-custom">Send Quotation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

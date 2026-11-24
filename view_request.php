<?php
$page_title = 'View Request | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$request_id) {
    header("Location: dashboard.php");
    exit;
}

// Fetch Request
$stmt = $pdo->prepare("
    SELECT cr.*, l.title as listing_title, l.image_url, 
           u_cust.full_name as customer_name, u_cust.username as customer_username,
           u_crea.full_name as creator_name, u_crea.username as creator_username
    FROM customization_requests cr
    JOIN listings l ON cr.listing_id = l.id
    JOIN users u_cust ON cr.customer_id = u_cust.id
    JOIN users u_crea ON cr.creator_id = u_crea.id
    WHERE cr.id = ?
");
$stmt->execute([$request_id]);
$req = $stmt->fetch();

if (!$req) {
    die("Request not found.");
}

// Authorization check
if ($user['id'] != $req['customer_id'] && $user['id'] != $req['creator_id']) {
    die("Unauthorized access.");
}

$is_creator = ($user['id'] == $req['creator_id']);

// Handle Actions
$action_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $action_error = "Security token mismatch. Please refresh the page and try again.";
    } else {
        $action = $_POST['action'];

        if ($is_creator && $action === 'decline_request') {
            $pdo->prepare("UPDATE customization_requests SET status = 'declined' WHERE id = ?")->execute([$request_id]);
            header("Location: view_request.php?id=" . $request_id . "&msg=declined");
            exit;
        }

        if (!$is_creator && $action === 'request_revision') {
            $quotation_id = (int)$_POST['quotation_id'];
            try {
                $pdo->prepare("UPDATE quotations SET status = 'revision_requested', updated_at = NOW() WHERE id = ?")
                    ->execute([$quotation_id]);
                $pdo->prepare("UPDATE customization_requests SET status = 'under_review', updated_at = NOW() WHERE id = ?")
                    ->execute([$request_id]);
                header("Location: view_request.php?id=" . $request_id . "&msg=revision_requested");
                exit;
            } catch (Exception $e) {
                error_log("Revision Request Error: " . $e->getMessage());
                $action_error = "Could not submit revision request. Please try again.";
            }
        }

        if (!$is_creator && $action === 'accept_quotation') {
            $quotation_id = (int)$_POST['quotation_id'];

            // Fetch the quotation to verify it belongs to this request
            $q_check = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND request_id = ?");
            $q_check->execute([$quotation_id, $request_id]);
            $q_row = $q_check->fetch();

            if (!$q_row) {
                $action_error = "Quotation not found.";
            } else {
                $pdo->beginTransaction();
                try {
                    // 1. Mark quotation as accepted
                    $pdo->prepare("UPDATE quotations SET status = 'accepted', updated_at = NOW() WHERE id = ?")
                        ->execute([$quotation_id]);

                    // 2. Mark request as accepted
                    $pdo->prepare("UPDATE customization_requests SET status = 'accepted', updated_at = NOW() WHERE id = ?")
                        ->execute([$request_id]);

                    // 3. Create project record (required by projects FK)
                    $proj_check = $pdo->prepare("SELECT id FROM projects WHERE request_id = ?");
                    $proj_check->execute([$request_id]);
                    if (!$proj_check->fetch()) {
                        $completion_date = $q_row['estimated_days']
                            ? date('Y-m-d', strtotime('+' . (int)$q_row['estimated_days'] . ' days'))
                            : null;
                        $pdo->prepare("
                            INSERT INTO projects (request_id, quotation_id, customer_id, creator_id, agreed_amount, agreed_scope, expected_completion, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'agreement_confirmed')
                        ")->execute([
                            $request_id,
                            $quotation_id,
                            $req['customer_id'],
                            $req['creator_id'],
                            $q_row['total_cost'],
                            $q_row['work_description'],
                            $completion_date
                        ]);
                    }

                    $pdo->commit();
                    header("Location: view_request.php?id=" . $request_id . "&msg=accepted");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("Accept Quotation Error: " . $e->getMessage());
                    $action_error = "An error occurred while accepting the quotation. Please try again.";
                }
            }
        }
    }
}

// Fetch Quotations (if any)
$stmt_q = $pdo->prepare("SELECT * FROM quotations WHERE request_id = ? ORDER BY version DESC, created_at DESC");
$stmt_q->execute([$request_id]);
$quotations = $stmt_q->fetchAll();
$latest_quotation = $quotations[0] ?? null;

require_once 'includes/header.php';
require_once 'includes/navigation.php';

$image_seed = !empty($req['image_url']) ? urlencode($req['image_url']) : 'defaultart';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php" class="text-secondary text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= $is_creator ? 'incoming_requests.php' : 'my_requests.php' ?>" class="text-secondary text-decoration-none"><?= $is_creator ? 'Incoming Requests' : 'My Requests' ?></a></li>
        <li class="breadcrumb-item active text-white" aria-current="page">Request #<?= $req['id'] ?></li>
      </ol>
    </nav>
    
    <?php if(isset($_GET['msg'])): ?>
        <?php
            $alert_class = 'alert-success border-success text-success';
            $alert_icon  = 'ph-check-circle';
            $alert_msg   = 'Action completed successfully.';
            if ($_GET['msg'] === 'accepted') {
                $alert_msg = '🎉 Quotation accepted! A project has been created. You can now proceed with the work.';
            } elseif ($_GET['msg'] === 'declined') {
                $alert_class = 'alert-warning border-warning text-warning';
                $alert_icon  = 'ph-x-circle';
                $alert_msg   = 'Request has been declined.';
            } elseif ($_GET['msg'] === 'quoted') {
                $alert_msg = 'Quotation sent successfully to the customer.';
            } elseif ($_GET['msg'] === 'revision_requested') {
                $alert_class = 'alert-warning border-warning text-warning';
                $alert_icon  = 'ph-arrows-clockwise';
                $alert_msg   = 'Revision requested. The creator will update the quotation.';
            }
        ?>
        <div class="alert bg-dark <?= $alert_class ?> fade-in-up" role="alert">
            <i class="ph <?= $alert_icon ?> me-2"></i> <?= htmlspecialchars($alert_msg) ?>
        </div>
    <?php endif; ?>
    <?php if($action_error): ?>
        <div class="alert alert-danger bg-dark border-danger text-danger fade-in-up" role="alert">
            <i class="ph ph-warning-circle me-2"></i> <?= htmlspecialchars($action_error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Main Request Details -->
        <div class="col-lg-8">
            <div class="card bg-card fade-in-up mb-4" style="background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px; overflow: hidden;">
                <div class="card-header border-bottom border-secondary d-flex justify-content-between align-items-center p-4" style="background-color: #161616 !important;">
                    <h5 class="text-white mb-0">Request Details</h5>
                    <span class="badge bg-secondary text-capitalize fs-6 px-3 py-2"><?= str_replace('_', ' ', $req['status']) ?></span>
                </div>
                <div class="card-body p-4" style="background-color: #121212 !important;">
                    <div class="d-flex align-items-center gap-4 mb-4 pb-4 border-bottom border-secondary">
                        <img src="<?= !empty($req['image_url']) ? htmlspecialchars($req['image_url']) : 'assets/img/default.jpg' ?>" 
                             onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?w=200&q=80';"
                             class="rounded border border-secondary" alt="Thumbnail" style="width:90px;height:90px;object-fit:cover;">
                        <div>
                            <p class="text-secondary small mb-1">Regarding Listing</p>
                            <h4 class="text-white mb-2"><a href="listing.php?id=<?= $req['listing_id'] ?>" class="text-white text-decoration-none hover-pink"><?= htmlspecialchars($req['listing_title']) ?></a></h4>
                            <p class="text-secondary mb-0">
                                <?= $is_creator ? 'Requested by <strong>' . htmlspecialchars($req['customer_name']) . '</strong>' : 'Sent to <strong>' . htmlspecialchars($req['creator_name']) . '</strong>' ?>
                            </p>
                        </div>
                    </div>
                    
                    <h6 class="text-light mb-3">Project Requirements</h6>
                    <p class="text-secondary" style="white-space: pre-line; line-height: 1.6;"><?= htmlspecialchars($req['requirements']) ?></p>
                    
                    <div class="row mt-4 pt-4 border-top border-secondary">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <p class="text-secondary small mb-1">Estimated Budget</p>
                            <h5 class="text-white mb-0"><?= $req['budget'] ? '₹' . number_format($req['budget'], 2) : 'Not specified' ?></h5>
                        </div>
                        <div class="col-md-6">
                            <p class="text-secondary small mb-1">Preferred Completion</p>
                            <h5 class="text-white mb-0"><?= $req['preferred_date'] ? date('F d, Y', strtotime($req['preferred_date'])) : 'Flexible' ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quotations Area -->
            <?php if($latest_quotation): ?>
                <div class="card mb-4 fade-in-up" style="animation-delay: 0.1s; background-color: #121212 !important; border: 1.5px solid var(--accent-pink) !important; border-radius: 14px; overflow: hidden;">
                    <div class="card-header border-bottom border-secondary p-4 d-flex justify-content-between align-items-center" style="background-color: #161616 !important;">
                        <h4 class="text-white mb-0">Quotation (v<?= $latest_quotation['version'] ?>)</h4>
                        <span class="badge <?= $latest_quotation['status'] === 'accepted' ? 'bg-success' : 'bg-warning text-dark' ?> text-capitalize fs-6 px-3 py-2"><?= $latest_quotation['status'] ?></span>
                    </div>
                    <div class="card-body p-4" style="background-color: #121212 !important;">
                        <h5 class="text-white mb-3"><?= htmlspecialchars($latest_quotation['title']) ?></h5>
                        <p class="text-secondary mb-4" style="white-space: pre-line;"><?= htmlspecialchars($latest_quotation['work_description']) ?></p>
                        
                        <div class="p-4 rounded mb-4 border border-secondary" style="background-color: #0d0d0d !important;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Base Cost</span>
                                <span class="text-white">₹<?= number_format($latest_quotation['base_cost'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Customization</span>
                                <span class="text-white">₹<?= number_format($latest_quotation['customization_cost'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Materials</span>
                                <span class="text-white">₹<?= number_format($latest_quotation['material_cost'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 border-bottom border-secondary pb-3">
                                <span class="text-secondary">Additional</span>
                                <span class="text-white">₹<?= number_format($latest_quotation['additional_charges'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong class="text-white fs-5">Total Estimated Cost</strong>
                                <strong class="text-white fs-5" style="color: var(--accent-pink) !important;">₹<?= number_format($latest_quotation['total_cost'], 2) ?></strong>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <span class="text-secondary small d-block">Timeline</span>
                                <strong class="text-white"><?= $latest_quotation['estimated_days'] ?> Days</strong>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <span class="text-secondary small d-block">Included Revisions</span>
                                <strong class="text-white"><?= $latest_quotation['revisions_included'] ?></strong>
                            </div>
                        </div>
                        
                        <?php if(!$is_creator && in_array($latest_quotation['status'], ['sent', 'revised'])): ?>
                            <div class="mt-4 pt-4 border-top border-secondary d-flex gap-3 flex-wrap">
                                <form method="POST" action="view_request.php?id=<?= $request_id ?>" onsubmit="return confirm('Accepting this quotation confirms the scope and price. Proceed?');" class="flex-grow-1">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="accept_quotation">
                                    <input type="hidden" name="quotation_id" value="<?= $latest_quotation['id'] ?>">
                                    <button type="submit" class="btn btn-primary-custom w-100 btn-lg">
                                        <i class="ph ph-check-circle me-2"></i>Accept Quotation
                                    </button>
                                </form>
                                <form method="POST" action="view_request.php?id=<?= $request_id ?>" class="flex-shrink-0" onsubmit="return confirm('Request a revision from the creator?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="action" value="request_revision">
                                    <input type="hidden" name="quotation_id" value="<?= $latest_quotation['id'] ?>">
                                    <button type="submit" class="btn btn-outline-custom btn-lg">
                                        <i class="ph ph-arrows-clockwise me-2"></i>Request Revision
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if($latest_quotation['status'] === 'accepted'): ?>
                            <div class="mt-4 pt-4 border-top border-secondary">
                                <div class="alert bg-dark border-success text-success mb-0">
                                    <i class="ph ph-check-circle me-2"></i> <strong>Quotation Accepted.</strong> A project has been created and work can now begin.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar Actions -->
        <div class="col-lg-4">
            <div class="card custom-card sticky-top" style="top: 100px; background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px;">
                <div class="card-body p-4" style="background-color: #121212 !important;">
                    <h5 class="text-white border-bottom border-secondary pb-3 mb-4">Actions</h5>
                    
                    <a href="chat.php?request_id=<?= $request_id ?>" class="btn btn-outline-custom w-100 mb-3"><i class="ph ph-chat-circle-dots me-2"></i>Message <?= $is_creator ? 'Customer' : 'Creator' ?></a>
                    
                    <?php if($is_creator && $req['status'] !== 'declined' && $req['status'] !== 'cancelled' && $req['status'] !== 'accepted'): ?>
                        <hr class="border-secondary mb-3">
                        <a href="create_quotation.php?request_id=<?= $request_id ?>" class="btn btn-primary-custom w-100 mb-3">
                            <i class="ph ph-receipt me-2"></i><?= $latest_quotation ? 'Revise Quotation' : 'Create Quotation' ?>
                        </a>
                        
                        <?php if(!$latest_quotation): ?>
                            <form method="POST" action="view_request.php?id=<?= $request_id ?>" onsubmit="return confirm('Are you sure you want to decline this request?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="decline_request">
                                <button type="submit" class="btn btn-outline-danger w-100">Decline Request</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

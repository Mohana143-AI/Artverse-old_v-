<?php
$page_title = 'My Projects | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();
$is_creator = ($user['role'] === 'creator');

if ($is_creator) {
    $stmt = $pdo->prepare("
        SELECT p.*, cr.requirements,
               u_cust.full_name AS customer_name, l.title AS listing_title
        FROM projects p
        JOIN customization_requests cr ON p.request_id = cr.id
        JOIN users u_cust ON p.customer_id = u_cust.id
        JOIN listings l ON cr.listing_id = l.id
        WHERE p.creator_id = ?
        ORDER BY p.updated_at DESC
    ");
    $stmt->execute([$user['id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, cr.requirements,
               u_crea.full_name AS creator_name, l.title AS listing_title
        FROM projects p
        JOIN customization_requests cr ON p.request_id = cr.id
        JOIN users u_crea ON p.creator_id = u_crea.id
        JOIN listings l ON cr.listing_id = l.id
        WHERE p.customer_id = ?
        ORDER BY p.updated_at DESC
    ");
    $stmt->execute([$user['id']]);
}
$projects = $stmt->fetchAll();

// Handle project status update (creator only)
if ($is_creator && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $valid_statuses = ['design_in_progress','design_review','design_approved','production','quality_check','ready_for_delivery','completed'];
    $new_status = $_POST['new_status'] ?? '';
    $project_id  = (int)($_POST['project_id'] ?? 0);
    if (in_array($new_status, $valid_statuses)) {
        $pdo->prepare("UPDATE projects SET status = ? WHERE id = ? AND creator_id = ?")->execute([$new_status, $project_id, $user['id']]);
    }
    header("Location: creator_projects.php");
    exit;
}

$page_file = $is_creator ? 'creator_projects.php' : 'customer_projects.php';
$page_title = ($is_creator ? 'My Projects' : 'My Orders') . ' | ArtSphere';

require_once 'includes/header.php';
require_once 'includes/navigation.php';

$status_labels = [
    'agreement_confirmed' => ['label' => 'Confirmed', 'badge' => 'bg-info text-dark'],
    'design_in_progress'  => ['label' => 'Design In Progress', 'badge' => 'bg-primary'],
    'design_review'       => ['label' => 'Design Review', 'badge' => 'bg-warning text-dark'],
    'design_approved'     => ['label' => 'Design Approved', 'badge' => 'bg-success'],
    'production'          => ['label' => 'In Production', 'badge' => 'bg-primary'],
    'quality_check'       => ['label' => 'Quality Check', 'badge' => 'bg-warning text-dark'],
    'ready_for_delivery'  => ['label' => 'Ready for Delivery', 'badge' => 'bg-info text-dark'],
    'completed'           => ['label' => 'Completed', 'badge' => 'bg-success'],
    'cancelled'           => ['label' => 'Cancelled', 'badge' => 'bg-danger'],
];
?>

<div class="container py-5">
    <h2 class="text-white mb-4"><?= $is_creator ? 'My Projects' : 'My Orders & Projects' ?></h2>

    <div class="card fade-in-up" style="background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.6);">
        <div class="card-body p-0" style="background-color: #121212 !important;">
            <?php if (empty($projects)): ?>
                <div class="p-5 text-center" style="background-color: #121212 !important;">
                    <i class="ph ph-folder-open text-secondary mb-3" style="font-size:3.5rem; color: #666 !important;"></i>
                    <h5 class="text-white mb-2">No active projects yet</h5>
                    <p class="text-secondary mb-4"><?= $is_creator ? 'Projects are created when a customer accepts your quotation.' : 'Your projects will appear here once a quotation is accepted.' ?></p>
                    <a href="<?= $is_creator ? 'incoming_requests.php' : 'explore.php' ?>" class="btn btn-outline-custom">
                        <?= $is_creator ? 'View Incoming Requests' : 'Explore Creations' ?>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $i => $proj):
                    $sl = $status_labels[$proj['status']] ?? ['label' => $proj['status'], 'badge' => 'bg-secondary'];
                    $title = !empty($proj['listing_title']) ? $proj['listing_title'] : 'Custom Art Project #' . $proj['id'];
                ?>
                <div class="p-4 border-bottom border-secondary last-no-border" style="background-color: #121212 !important; border-color: #242424 !important;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge <?= $sl['badge'] ?> px-3 py-1 rounded-pill" style="font-size: 0.78rem; letter-spacing: 0.04em;"><?= $sl['label'] ?></span>
                                <span class="text-secondary small">Project #<?= $proj['id'] ?></span>
                            </div>
                            <h5 class="text-white mb-2" style="font-weight: 600; font-size: 1.2rem; letter-spacing: -0.01em;">
                                <?= htmlspecialchars($title) ?>
                            </h5>
                            <p class="text-secondary small mb-2">
                                <?= $is_creator ? 'Customer: <strong class="text-white">' . htmlspecialchars($proj['customer_name']) . '</strong>' : 'Creator: <strong class="text-white">' . htmlspecialchars($proj['creator_name']) . '</strong>' ?>
                                &nbsp;·&nbsp; Agreed: <strong style="color: #F20A68; font-size: 0.95rem;">₹<?= number_format($proj['agreed_amount'], 2) ?></strong>
                            </p>
                            <?php if ($proj['expected_completion']): ?>
                                <p class="text-secondary small mb-0"><i class="ph ph-calendar-blank me-1"></i>Expected: <span class="text-light"><?= date('M d, Y', strtotime($proj['expected_completion'])) ?></span></p>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <a href="design_workspace.php?request_id=<?= $proj['request_id'] ?>" class="btn btn-sm btn-outline-custom">
                                <i class="ph ph-paint-brush me-1"></i> Design Workspace
                            </a>
                            <a href="chat.php?request_id=<?= $proj['request_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="ph ph-chat-circle me-1"></i> Chat
                            </a>
                            <?php if ($is_creator && $proj['status'] !== 'completed' && $proj['status'] !== 'cancelled'): ?>
                                <form method="POST" class="d-flex gap-1 ms-1">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
                                    <select name="new_status" class="form-select form-select-sm bg-dark border-secondary text-light shadow-none" style="border-radius: 999px;">
                                        <?php foreach ($status_labels as $k => $v): ?>
                                            <option value="<?= $k ?>" <?= $k === $proj['status'] ? 'selected' : '' ?>><?= $v['label'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary-custom" style="border-radius: 999px;">Update</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

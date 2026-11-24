<?php
$page_title = 'Design Workspace | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;

// If no request_id, redirect creators to their project list
if (!$request_id) {
    header("Location: creator_projects.php");
    exit;
}

// Fetch request — must be participant
$stmt = $pdo->prepare("
    SELECT cr.*, 
           u_cust.full_name AS customer_name, u_cust.username AS customer_username,
           u_crea.full_name AS creator_name,
           l.title AS listing_title,
           q.title AS quotation_title, q.total_cost, q.revisions_included, q.id AS quotation_id
    FROM customization_requests cr
    JOIN users u_cust ON cr.customer_id = u_cust.id
    JOIN users u_crea ON cr.creator_id  = u_crea.id
    JOIN listings l ON cr.listing_id = l.id
    LEFT JOIN quotations q ON (q.request_id = cr.id AND q.status = 'accepted')
    WHERE cr.id = ? AND (cr.customer_id = ? OR cr.creator_id = ?)
");
$stmt->execute([$request_id, $user['id'], $user['id']]);
$req = $stmt->fetch();

if (!$req) {
    die("Unauthorized or request not found.");
}

$is_creator = ($user['id'] == $req['creator_id']);

// Handle design upload (creator only)
$upload_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_version') {
    if (!$is_creator) die("Unauthorized.");
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Invalid token.");

    $notes = trim($_POST['creator_notes'] ?? '');
    $upload_dir = __DIR__ . '/uploads/designs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $file = $_FILES['design_file'] ?? null;
    $file_path = null;

    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowed_types)) {
            $upload_error = "Invalid file type. Allowed: JPEG, PNG, WebP, GIF, PDF.";
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $upload_error = "File too large (max 10 MB).";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe_name = 'dv_' . $request_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
            move_uploaded_file($file['tmp_name'], $upload_dir . $safe_name);
            $file_path = 'uploads/designs/' . $safe_name;
        }
    }

    if (!$upload_error) {
        // Get next version number
        $vnum_stmt = $pdo->prepare("SELECT COALESCE(MAX(version_number), 0) + 1 FROM design_versions WHERE request_id = ?");
        $vnum_stmt->execute([$request_id]);
        $next_version = $vnum_stmt->fetchColumn();

        $pdo->prepare("
            INSERT INTO design_versions (request_id, creator_id, version_number, file_path, creator_notes, status)
            VALUES (?, ?, ?, ?, ?, 'pending_review')
        ")->execute([$request_id, $user['id'], $next_version, $file_path, $notes]);

        $pdo->prepare("UPDATE customization_requests SET status = 'under_review' WHERE id = ?")->execute([$request_id]);

        header("Location: design_workspace.php?request_id=" . $request_id . "&msg=uploaded");
        exit;
    }
}

// Handle design approval (customer only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_version') {
    if ($is_creator) die("Unauthorized.");
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) die("Invalid token.");

    $version_id = (int)$_POST['version_id'];
    $pdo->beginTransaction();
    try {
        // Supersede all previous pending versions
        $pdo->prepare("UPDATE design_versions SET status = 'superseded' WHERE request_id = ? AND status = 'pending_review'")->execute([$request_id]);
        // Approve this specific version
        $pdo->prepare("UPDATE design_versions SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND request_id = ?")->execute([$user['id'], $version_id, $request_id]);
        $pdo->prepare("UPDATE customization_requests SET status = 'accepted' WHERE id = ?")->execute([$request_id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    header("Location: design_workspace.php?request_id=" . $request_id . "&msg=approved");
    exit;
}

// Fetch design versions (newest first)
$dv_stmt = $pdo->prepare("SELECT * FROM design_versions WHERE request_id = ? ORDER BY version_number DESC");
$dv_stmt->execute([$request_id]);
$versions = $dv_stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="view_request.php?id=<?= $request_id ?>" class="text-secondary text-decoration-none">Request #<?= $request_id ?></a></li>
            <li class="breadcrumb-item active text-white">Design Workspace</li>
        </ol>
    </nav>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success bg-dark border-success text-success fade-in-up">
            <i class="ph ph-check-circle me-2"></i>
            <?php
            $msgs = ['uploaded'=>'Design version uploaded.','approved'=>'Design approved! The project agreement is now confirmed.'];
            echo $msgs[$_GET['msg']] ?? 'Action completed.';
            ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Workspace Main -->
        <div class="col-lg-8">
            <div class="card bg-card fade-in-up mb-4" style="background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px; overflow: hidden;">
                <div class="card-header border-bottom border-secondary p-4" style="background-color: #161616 !important;">
                    <h5 class="text-white mb-0">
                        <i class="ph ph-stack me-2"></i>Design Versions
                        <span class="badge bg-dark border border-secondary text-secondary ms-2"><?= count($versions) ?></span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($versions)): ?>
                        <div class="text-center py-4">
                            <i class="ph ph-image text-secondary mb-3" style="font-size:2.5rem;"></i>
                            <p class="text-secondary mb-0">No design versions uploaded yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($versions as $v):
                            $is_img = $v['file_path'] && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $v['file_path']);
                            $status_colors = [
                                'pending_review' => 'bg-info text-dark',
                                'approved'       => 'bg-success',
                                'revision_requested' => 'bg-warning text-dark',
                                'superseded'     => 'bg-secondary',
                            ];
                            $badge = $status_colors[$v['status']] ?? 'bg-secondary';
                        ?>
                        <div class="border border-secondary rounded-4 p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="text-white mb-1">Version <?= $v['version_number'] ?></h5>
                                    <p class="text-secondary small mb-0"><?= date('M d, Y h:i A', strtotime($v['created_at'])) ?></p>
                                </div>
                                <span class="badge <?= $badge ?> text-capitalize px-3 py-2"><?= str_replace('_', ' ', $v['status']) ?></span>
                            </div>

                            <?php if ($is_img): ?>
                                <img src="<?= htmlspecialchars(BASE_URL . $v['file_path']) ?>" class="img-fluid rounded mb-3 border border-secondary" alt="Design Version <?= $v['version_number'] ?>" style="max-height:350px; object-fit:contain; width:100%;">
                            <?php elseif ($v['file_path']): ?>
                                <a href="<?= htmlspecialchars(BASE_URL . $v['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mb-3">
                                    <i class="ph ph-file-pdf me-2"></i>View File
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($v['creator_notes'])): ?>
                                <p class="text-secondary small mb-0" style="white-space:pre-line;"><?= htmlspecialchars($v['creator_notes']) ?></p>
                            <?php endif; ?>

                            <?php if (!$is_creator && $v['status'] === 'pending_review'): ?>
                                <hr class="border-secondary mt-4">
                                <div class="d-flex gap-3">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="approve_version">
                                        <input type="hidden" name="version_id" value="<?= $v['id'] ?>">
                                        <button type="submit" class="btn btn-primary-custom" onclick="return confirm('Approve this design version?')">
                                            <i class="ph ph-check me-2"></i>Approve This Version
                                        </button>
                                    </form>
                                    <a href="chat.php?request_id=<?= $request_id ?>" class="btn btn-outline-custom">
                                        <i class="ph ph-chat-circle-dots me-2"></i>Request Revision
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ($v['status'] === 'approved'): ?>
                                <div class="alert alert-success bg-dark border-success text-success small mt-3 mb-0">
                                    <i class="ph ph-seal-check me-2"></i>
                                    Approved by customer on <?= date('M d, Y', strtotime($v['approved_at'])) ?>.
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Project Summary Card -->
            <div class="card bg-card mb-4 sticky-top fade-in-up" style="top:100px; background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px;">
                <div class="card-body p-4" style="background-color: #121212 !important;">
                    <h5 class="text-white border-bottom border-secondary pb-3 mb-4">Project Summary</h5>
                    <p class="text-secondary small mb-1">Listing</p>
                    <p class="text-white mb-3"><?= htmlspecialchars($req['listing_title']) ?></p>
                    <?php if ($req['quotation_id']): ?>
                        <p class="text-secondary small mb-1">Agreed Amount</p>
                        <p class="text-white mb-3 fw-bold">₹<?= number_format($req['total_cost'], 2) ?></p>
                        <p class="text-secondary small mb-1">Revisions Included</p>
                        <p class="text-white mb-4"><?= $req['revisions_included'] ?></p>
                    <?php endif; ?>

                    <a href="chat.php?request_id=<?= $request_id ?>" class="btn btn-outline-custom w-100 mb-3">
                        <i class="ph ph-chat-circle-dots me-2"></i>Open Chat
                    </a>

                    <?php if ($is_creator): ?>
                        <!-- Upload New Version -->
                        <hr class="border-secondary my-3">
                        <h6 class="text-light mb-3"><i class="ph ph-upload me-2"></i>Upload New Version</h6>
                        <?php if ($upload_error): ?>
                            <div class="alert alert-danger bg-dark border-danger text-danger small"><?= htmlspecialchars($upload_error) ?></div>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="upload_version">
                            <div class="mb-3">
                                <input type="file" name="design_file" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf">
                                <div class="form-text text-secondary small">JPEG, PNG, WebP, GIF, PDF · Max 10 MB</div>
                            </div>
                            <div class="mb-3">
                                <textarea name="creator_notes" class="form-control bg-dark border-secondary text-light shadow-none form-control-sm" rows="3" placeholder="Notes for this version..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary-custom w-100">Upload Version</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

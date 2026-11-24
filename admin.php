<?php
$page_title = 'Admin Dashboard | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

if ($user['role'] !== 'admin') {
    die("Unauthorized access. Admin only.");
}

// Real stats
$total_users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_creators = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'creator'")->fetchColumn();
$total_listings = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$total_requests = $pdo->query("SELECT COUNT(*) FROM customization_requests")->fetchColumn();
$total_projects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'completed'")->fetchColumn();

// Recent users
$recent_users_stmt = $pdo->query("SELECT id, full_name, username, email, role, account_status, created_at FROM users ORDER BY created_at DESC LIMIT 10");
$recent_users = $recent_users_stmt->fetchAll();

// Handle user suspension/activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $target_id = (int)$_POST['target_user_id'];
    $action = $_POST['action'];

    if ($target_id === $user['id']) die("Cannot modify your own admin account.");

    if ($action === 'suspend') {
        $pdo->prepare("UPDATE users SET account_status = 'suspended' WHERE id = ?")->execute([$target_id]);
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE users SET account_status = 'active' WHERE id = ?")->execute([$target_id]);
    }
    header("Location: admin.php");
    exit;
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="text-white mb-1">Admin Dashboard</h2>
            <p class="text-secondary mb-0">Logged in as <strong class="text-light"><?= htmlspecialchars($user['full_name']) ?></strong></p>
        </div>
        <span class="badge bg-danger fs-6 px-3 py-2">Admin Panel</span>
    </div>

    <!-- Platform Stats -->
    <div class="row g-4 mb-5">
        <?php
        $admin_stats = [
            ['label' => 'Total Users',     'value' => $total_users,    'icon' => 'ph-users'],
            ['label' => 'Creators',        'value' => $total_creators, 'icon' => 'ph-paint-brush'],
            ['label' => 'Listings',        'value' => $total_listings, 'icon' => 'ph-image'],
            ['label' => 'Requests',        'value' => $total_requests, 'icon' => 'ph-inbox'],
            ['label' => 'Completed Projects', 'value' => $total_projects, 'icon' => 'ph-seal-check'],
        ];
        foreach ($admin_stats as $stat):
        ?>
        <div class="col-sm-6 col-lg">
            <div class="card bg-card border-secondary fade-in-up">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:48px;height:48px;background:rgba(242,10,104,.12);color:var(--accent-pink);font-size:1.4rem;">
                        <i class="ph <?= $stat['icon'] ?>"></i>
                    </div>
                    <div>
                        <h4 class="text-white mb-0"><?= $stat['value'] ?></h4>
                        <p class="text-secondary small mb-0"><?= $stat['label'] ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- User Management -->
    <h4 class="text-white mb-3">Recent Users</h4>
    <div class="card bg-card border-secondary fade-in-up">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 align-middle">
                    <thead><tr>
                        <th class="ps-4">User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr></thead>
                    <tbody class="border-top-0">
                        <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="text-white fw-bold"><?= htmlspecialchars($u['full_name']) ?></div>
                                <div class="text-secondary small">@<?= htmlspecialchars($u['username']) ?></div>
                            </td>
                            <td class="text-secondary small"><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-secondary text-capitalize"><?= $u['role'] ?></span></td>
                            <td>
                                <span class="badge <?= $u['account_status'] === 'active' ? 'bg-success' : 'bg-danger' ?> text-capitalize">
                                    <?= $u['account_status'] ?>
                                </span>
                            </td>
                            <td class="text-secondary small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td class="text-end pe-4">
                                <?php if ($u['id'] !== $user['id']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $u['account_status'] === 'active' ? 'suspend' : 'activate' ?>">
                                    <button type="submit" class="btn btn-sm <?= $u['account_status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            onclick="return confirm('Are you sure?')">
                                        <?= $u['account_status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

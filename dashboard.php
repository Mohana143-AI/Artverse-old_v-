<?php
$page_title = 'Dashboard | ArtSphere';

require_once 'config/database.php';
require_once 'includes/auth.php';

// Protect this page
require_login();

$user = get_logged_in_user();

// Fetch role-specific dashboard metrics
if ($user['role'] === 'creator') {
    // Incoming requests count (any status)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM customization_requests WHERE creator_id = ?');
    $stmt->execute([$user['id']]);
    $incoming_requests = $stmt->fetchColumn();
    // Active projects count (exclude completed/cancelled)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE creator_id = ? AND status NOT IN (\'completed\',\'cancelled\')');
    $stmt->execute([$user['id']]);
    $active_projects = $stmt->fetchColumn();
    // Unread messages count (messages not sent by the creator)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages m JOIN customization_requests cr ON m.request_id = cr.id WHERE cr.creator_id = ? AND m.is_read = 0 AND m.sender_id != ?');
    $stmt->execute([$user['id'], $user['id']]);
    $unread_messages = $stmt->fetchColumn();
} else {
    // Customer dashboard metrics
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM customization_requests WHERE customer_id = ?');
    $stmt->execute([$user['id']]);
    $my_requests = $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM quotations WHERE creator_id = ?');
    $stmt->execute([$user['id']]);
    $my_quotations = $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE customer_id = ?');
    $stmt->execute([$user['id']]);
    $my_projects = $stmt->fetchColumn();
}

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4 col-lg-3 mb-4">
            <!-- Sidebar -->
            <div class="card bg-card border-secondary sticky-top" style="top: 100px;">
                <div class="card-body text-center pt-4">
                    <?php 
                    require_once 'includes/image_pools.php';
                    $dash_avatar = get_creator_avatar((int)$user['id'], $user['profile_image'] ?? ''); 
                    ?>
                    <div class="rounded-circle mx-auto mb-3 overflow-hidden shadow" style="width: 80px; height: 80px; border: 2px solid var(--accent-pink); background: #1a1a1a;">
                        <img src="<?= htmlspecialchars($dash_avatar) ?>" class="w-100 h-100" style="object-fit: cover;" alt="Avatar" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400&q=82&auto=format&fit=crop';">
                    </div>
                    <h5 class="text-white mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
                    <p class="text-secondary small mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                    <span class="badge bg-dark border border-secondary text-light mb-4 text-capitalize"><?= htmlspecialchars($user['role']) ?> Account</span>
                    
                    <div class="d-grid gap-2">
                        <a href="profile.php?username=<?= urlencode($user['username']) ?>" class="btn btn-outline-custom btn-sm"><i class="ph ph-user me-2"></i>View Public Profile</a>
                        <a href="edit_profile.php" class="btn btn-primary-custom btn-sm"><i class="ph ph-pencil-simple me-2"></i>Edit Profile & Pictures</a>
                        <?php if($user['role'] === 'creator'): ?>
                            <a href="manage_listings.php" class="btn btn-outline-custom btn-sm"><i class="ph ph-image me-2"></i>Manage Portfolio</a>
                            <a href="add_listing.php" class="btn btn-outline-custom btn-sm"><i class="ph ph-plus me-2"></i>Add Offering</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-outline-custom btn-sm">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8 col-lg-9">
            <h2 class="text-white mb-4">Welcome to your Dashboard, <?= htmlspecialchars(explode(' ', trim($user['full_name']))[0]) ?></h2>
            
            <?php if($user['role'] === 'creator'): ?>
                <!-- Creator Specific Dashboard -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-card border-secondary h-100 fade-in-up">
                            <div class="card-body text-center">
                                <h3 class="text-white"><?php echo $incoming_requests ?? 0; ?></h3>
                                <p class="text-secondary small mb-0">Incoming Requests</p>
                                <a href="incoming_requests.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-card border-secondary h-100 fade-in-up" style="animation-delay: 0.1s;">
                            <div class="card-body text-center">
                                <h3 class="text-white">0</h3>
                                <p class="text-secondary small mb-0">Active Projects</p>
                                <a href="creator_projects.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-card border-secondary h-100 fade-in-up" style="animation-delay: 0.2s;">
                            <div class="card-body text-center">
                                <h3 class="text-white">0</h3>
                                <p class="text-secondary small mb-0">Unread Messages</p>
                                <a href="messages.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-card border-secondary fade-in-up" style="animation-delay: 0.3s;">
                    <div class="card-body">
                        <h5 class="text-white border-bottom border-secondary pb-2 mb-3"><i class="ph ph-briefcase me-2"></i>Recent Activity</h5>
                        <p class="text-secondary small">No recent activity to show.</p>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Customer Specific Dashboard -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card bg-card border-secondary h-100 fade-in-up">
                            <div class="card-body">
                                <h5 class="text-white border-bottom border-secondary pb-2 mb-3"><i class="ph ph-paint-brush-broad me-2"></i>My Customization Requests</h5>
                                <p class="text-secondary small">You haven't requested any custom work yet.</p>
                                <a href="explore.php" class="btn btn-outline-custom btn-sm mt-2">Find a Creator</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-card border-secondary h-100 fade-in-up" style="animation-delay: 0.1s;">
                            <div class="card-body">
                                <h5 class="text-white border-bottom border-secondary pb-2 mb-3"><i class="ph ph-folder-open me-2"></i>My Projects</h5>
                                <p class="text-secondary small">No active projects.</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

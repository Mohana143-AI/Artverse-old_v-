<?php
$page_title = 'My Requests | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

// Fetch requests made by this user
$stmt = $pdo->prepare("
    SELECT cr.*, l.title as listing_title, u.full_name as creator_name, l.image_url
    FROM customization_requests cr
    JOIN listings l ON cr.listing_id = l.id
    JOIN users u ON cr.creator_id = u.id
    WHERE cr.customer_id = ?
    ORDER BY cr.updated_at DESC
");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <h2 class="text-white mb-4">My Customization Requests</h2>
    
    <div class="card bg-card fade-in-up" style="background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px; overflow: hidden;">
        <div class="card-body p-0" style="background-color: #121212 !important;">
            <?php if(empty($requests)): ?>
                <div class="p-5 text-center">
                    <i class="ph ph-paint-brush-broad text-secondary mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-white">No active requests</h5>
                    <p class="text-secondary mb-4">You haven't requested any custom work yet.</p>
                    <a href="explore.php" class="btn btn-outline-custom">Explore Creations</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-4">Request</th>
                                <th scope="col">Creator</th>
                                <th scope="col">Date Submitted</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach($requests as $req): 
                                $status_badge = 'bg-secondary';
                                if ($req['status'] === 'submitted') $status_badge = 'bg-info text-dark';
                                if ($req['status'] === 'quotation_sent') $status_badge = 'bg-warning text-dark';
                                if ($req['status'] === 'accepted') $status_badge = 'bg-success';
                                if ($req['status'] === 'declined') $status_badge = 'bg-danger';
                            ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <a href="view_request.php?id=<?= $req['id'] ?>" class="text-white fw-bold text-decoration-none hover-pink d-block">
                                        <?= htmlspecialchars($req['listing_title']) ?>
                                    </a>
                                </td>
                                <td><span class="text-light"><?= htmlspecialchars($req['creator_name']) ?></span></td>
                                <td><span class="text-secondary"><?= date('M d, Y', strtotime($req['created_at'])) ?></span></td>
                                <td><span class="badge <?= $status_badge ?> text-capitalize"><?= str_replace('_', ' ', $req['status']) ?></span></td>
                                <td class="text-end pe-4">
                                    <a href="view_request.php?id=<?= $req['id'] ?>" class="btn btn-sm btn-outline-custom">View Details</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

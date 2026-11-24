<?php
$page_title = 'My Quotations | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

$stmt = $pdo->prepare("
    SELECT q.*, cr.listing_id,
           u_cust.full_name AS customer_name,
           u_crea.full_name AS creator_name,
           l.title AS listing_title
    FROM quotations q
    JOIN customization_requests cr ON q.request_id = cr.id
    JOIN users u_cust ON cr.customer_id = u_cust.id
    JOIN users u_crea ON cr.creator_id = u_crea.id
    JOIN listings l ON cr.listing_id = l.id
    WHERE cr.customer_id = ? OR cr.creator_id = ?
    ORDER BY q.updated_at DESC
");
$stmt->execute([$user['id'], $user['id']]);
$quotations = $stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navigation.php';

$status_badges = [
    'draft' => 'bg-secondary',
    'sent' => 'bg-info text-dark',
    'revision_requested' => 'bg-warning text-dark',
    'revised' => 'bg-primary',
    'accepted' => 'bg-success',
    'declined' => 'bg-danger',
    'expired' => 'bg-secondary',
];
?>

<div class="container py-5">
    <h2 class="text-white mb-4"><?= $user['role'] === 'creator' ? 'Sent Quotations' : 'My Quotations' ?></h2>

    <div class="card bg-card fade-in-up" style="background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px; overflow: hidden;">
        <div class="card-body p-0" style="background-color: #121212 !important;">
            <?php if (empty($quotations)): ?>
                <div class="p-5 text-center">
                    <i class="ph ph-receipt text-secondary mb-3" style="font-size:3rem;"></i>
                    <h5 class="text-white">No quotations yet</h5>
                    <p class="text-secondary mb-0">
                        <?= $user['role'] === 'creator' ? 'Accept a request and create a quotation to get started.' : 'Quotations will appear here once a creator responds to your request.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead><tr>
                            <th class="ps-4">Quotation</th>
                            <th>Listing</th>
                            <th><?= $user['role'] === 'creator' ? 'Customer' : 'Creator' ?></th>
                            <th>Total</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr></thead>
                        <tbody class="border-top-0">
                            <?php foreach ($quotations as $q):
                                $badge = $status_badges[$q['status']] ?? 'bg-secondary';
                                $other_name = $user['role'] === 'creator' ? $q['customer_name'] : $q['creator_name'];
                            ?>
                            <tr>
                                <td class="ps-4 py-3 text-white"><?= htmlspecialchars($q['title']) ?></td>
                                <td class="text-secondary small"><?= htmlspecialchars($q['listing_title']) ?></td>
                                <td class="text-light"><?= htmlspecialchars($other_name) ?></td>
                                <td class="text-white fw-bold">₹<?= number_format($q['total_cost'], 2) ?></td>
                                <td class="text-secondary">v<?= $q['version'] ?></td>
                                <td><span class="badge <?= $badge ?> text-capitalize"><?= str_replace('_', ' ', $q['status']) ?></span></td>
                                <td class="text-end pe-4">
                                    <a href="view_request.php?id=<?= $q['request_id'] ?>" class="btn btn-sm btn-outline-custom">View Request</a>
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

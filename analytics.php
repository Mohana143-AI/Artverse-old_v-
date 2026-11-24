<?php
$page_title = 'Creator Analytics | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();
if ($user['role'] !== 'creator') die("Unauthorized access.");

// Real counts from DB
$counts = [];

$counts['listings'] = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE creator_id = ?");
$counts['listings']->execute([$user['id']]);
$counts['listings'] = $counts['listings']->fetchColumn();

$counts['requests'] = $pdo->prepare("SELECT COUNT(*) FROM customization_requests WHERE creator_id = ?");
$counts['requests']->execute([$user['id']]);
$counts['requests'] = $counts['requests']->fetchColumn();

$counts['quotations_sent'] = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE creator_id = ? AND status IN ('sent','revised','accepted')");
$counts['quotations_sent']->execute([$user['id']]);
$counts['quotations_sent'] = $counts['quotations_sent']->fetchColumn();

$counts['accepted'] = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE creator_id = ? AND status = 'accepted'");
$counts['accepted']->execute([$user['id']]);
$counts['accepted'] = $counts['accepted']->fetchColumn();

$counts['completed'] = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE creator_id = ? AND status = 'completed'");
$counts['completed']->execute([$user['id']]);
$counts['completed'] = $counts['completed']->fetchColumn();

$counts['reviews'] = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE creator_id = ?");
$counts['reviews']->execute([$user['id']]);
$counts['reviews'] = $counts['reviews']->fetchColumn();

$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE creator_id = ?");
$avg_rating_stmt->execute([$user['id']]);
$avg_rating = round($avg_rating_stmt->fetchColumn(), 1);

// Top listings by views
$top_listings_stmt = $pdo->prepare("SELECT title, views, listing_type FROM listings WHERE creator_id = ? ORDER BY views DESC LIMIT 5");
$top_listings_stmt->execute([$user['id']]);
$top_listings = $top_listings_stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <h2 class="text-white mb-4">Creator Analytics</h2>
    <p class="text-secondary mb-5">All figures are based on real platform data. Metrics for untracked actions are noted clearly.</p>

    <div class="row g-4 mb-5">
        <?php
        $stats = [
            ['label' => 'Active Listings', 'value' => $counts['listings'], 'icon' => 'ph-image', 'link' => 'manage_listings.php'],
            ['label' => 'Requests Received', 'value' => $counts['requests'], 'icon' => 'ph-inbox', 'link' => 'incoming_requests.php'],
            ['label' => 'Quotations Sent', 'value' => $counts['quotations_sent'], 'icon' => 'ph-receipt', 'link' => 'manage_quotations.php'],
            ['label' => 'Accepted Quotations', 'value' => $counts['accepted'], 'icon' => 'ph-check-circle', 'link' => 'creator_projects.php'],
            ['label' => 'Completed Projects', 'value' => $counts['completed'], 'icon' => 'ph-seal-check', 'link' => 'creator_projects.php'],
            ['label' => 'Verified Reviews', 'value' => $counts['reviews'], 'icon' => 'ph-star', 'link' => 'creator_projects.php'],
        ];
        foreach ($stats as $stat):
        ?>
        <div class="col-sm-6 col-lg-4">
            <a href="<?= $stat['link'] ?>" class="text-decoration-none">
                <div class="card bg-card border-secondary h-100 hover-card fade-in-up">
                    <div class="card-body d-flex align-items-center gap-4 p-4">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:56px;height:56px;background:rgba(242,10,104,0.12);color:var(--accent-pink);font-size:1.6rem;">
                            <i class="ph <?= $stat['icon'] ?>"></i>
                        </div>
                        <div>
                            <h3 class="text-white mb-0"><?= $stat['value'] ?></h3>
                            <p class="text-secondary small mb-0"><?= $stat['label'] ?></p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Average Rating -->
    <?php if ($counts['reviews'] > 0): ?>
    <div class="card bg-card border-secondary mb-5 p-4 d-inline-flex align-items-center gap-4 fade-in-up">
        <div>
            <p class="text-secondary small mb-1">Average Rating</p>
            <h3 class="text-white mb-0"><?= $avg_rating ?> <span class="text-secondary fs-6">/ 5</span></h3>
            <p class="text-secondary small mb-0">Based on <?= $counts['reviews'] ?> verified review<?= $counts['reviews'] > 1 ? 's' : '' ?></p>
        </div>
        <div class="d-flex gap-1" style="font-size:1.8rem;color:gold;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="ph <?= $i <= round($avg_rating) ? 'ph-star-fill' : 'ph-star' ?>"></i>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Listings -->
    <h4 class="text-white mb-3">Listing Performance</h4>
    <div class="card bg-card border-secondary fade-in-up">
        <div class="card-body p-0">
            <?php if (empty($top_listings)): ?>
                <div class="p-4 text-center text-secondary">No listings yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle">
                        <thead><tr>
                            <th class="ps-4">Listing Title</th>
                            <th>Type</th>
                            <th>Views <span class="text-secondary small">(tracked)</span></th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($top_listings as $tl): ?>
                            <tr>
                                <td class="ps-4 py-3 text-white"><?= htmlspecialchars($tl['title']) ?></td>
                                <td class="text-secondary text-capitalize"><?= str_replace('_', ' ', $tl['listing_type']) ?></td>
                                <td class="text-white"><?= $tl['views'] ?></td>
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

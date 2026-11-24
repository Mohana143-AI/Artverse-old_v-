<?php
$page_title = 'Messages | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

// Fetch all conversations (distinct requests the user participates in)
$stmt = $pdo->prepare("
    SELECT cr.id AS request_id, l.title AS listing_title,
           u_other.full_name AS other_name, u_other.username AS other_username,
           (SELECT COUNT(*) FROM messages m WHERE m.request_id = cr.id AND m.sender_id != ? AND m.is_read = 0) AS unread_count,
           (SELECT message_text FROM messages m WHERE m.request_id = cr.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
           (SELECT m.created_at FROM messages m WHERE m.request_id = cr.id ORDER BY m.created_at DESC LIMIT 1) AS last_at
    FROM customization_requests cr
    JOIN listings l ON cr.listing_id = l.id
    JOIN users u_other ON u_other.id = IF(cr.customer_id = ?, cr.creator_id, cr.customer_id)
    WHERE cr.customer_id = ? OR cr.creator_id = ?
    ORDER BY last_at DESC
");
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container py-5">
    <h2 class="text-white mb-4">Messages</h2>

    <div class="card bg-card fade-in-up" style="background-color: #121212 !important; border: 1px solid #262626 !important; border-radius: 14px; overflow: hidden;">
        <div class="card-body p-0" style="background-color: #121212 !important;">
            <?php if (empty($conversations)): ?>
                <div class="p-5 text-center">
                    <i class="ph ph-chat-circle text-secondary mb-3" style="font-size:3rem;"></i>
                    <h5 class="text-white">No conversations yet</h5>
                    <p class="text-secondary mb-4">Messages are linked to customization requests. Start by exploring and requesting a creator.</p>
                    <a href="explore.php" class="btn btn-outline-custom">Find a Creator</a>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <a href="chat.php?request_id=<?= $conv['request_id'] ?>" class="text-decoration-none">
                    <div class="d-flex align-items-center gap-4 p-4 border-bottom border-secondary hover-card" style="transition: background .2s;">
                        <div class="rounded-circle bg-dark border border-secondary d-flex align-items-center justify-content-center flex-shrink-0 text-white fw-bold"
                             style="width:52px;height:52px;font-size:1.2rem;"><?= strtoupper(substr($conv['other_name'], 0, 1)) ?></div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="text-white mb-0"><?= htmlspecialchars($conv['other_name']) ?></h6>
                                <?php if ($conv['last_at']): ?>
                                    <span class="text-secondary small"><?= date('h:i A', strtotime($conv['last_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-secondary small mb-0 text-truncate" style="max-width:420px;">
                                <?php if ($conv['last_message']): ?>
                                    <?= htmlspecialchars(substr($conv['last_message'], 0, 80)) ?>
                                <?php else: ?>
                                    <em>No messages yet — <?= htmlspecialchars($conv['listing_title']) ?></em>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span class="badge rounded-pill text-white fw-bold" style="background:var(--accent-pink);"><?= $conv['unread_count'] ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

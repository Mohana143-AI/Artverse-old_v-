<?php
$page_title = 'Chat | ArtSphere';
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();
$user = get_logged_in_user();

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
if (!$request_id) { header("Location: dashboard.php"); exit; }

// Verify access
$stmt = $pdo->prepare("
    SELECT cr.*,
           u_cust.full_name AS customer_name, u_cust.username AS customer_username,
           u_crea.full_name AS creator_name,  u_crea.username AS creator_username,
           l.title AS listing_title
    FROM customization_requests cr
    JOIN users u_cust ON cr.customer_id = u_cust.id
    JOIN users u_crea ON cr.creator_id  = u_crea.id
    JOIN listings l ON cr.listing_id = l.id
    WHERE cr.id = ? AND (cr.customer_id = ? OR cr.creator_id = ?)
");
$stmt->execute([$request_id, $user['id'], $user['id']]);
$req = $stmt->fetch();
if (!$req) { die("Unauthorized or request not found."); }

$is_creator = ($user['id'] == $req['creator_id']);
$other_name = $is_creator ? $req['customer_name'] : $req['creator_name'];

/* ── AJAX: poll for new messages ─────────────────────────── */
if (isset($_GET['poll'])) {
    header('Content-Type: application/json');
    $since = (int)($_GET['since'] ?? 0);

    // Mark other party's messages as delivered & read
    $pdo->prepare("UPDATE messages SET is_delivered=1, is_read=1 WHERE request_id=? AND sender_id!=? AND created_at >= FROM_UNIXTIME(?)")
        ->execute([$request_id, $user['id'], max(0, $since - 5)]);

    $poll_stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.message_text, m.created_at,
               m.is_read, m.is_delivered,
               u.full_name AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.request_id = ? AND UNIX_TIMESTAMP(m.created_at) > ?
        ORDER BY m.created_at ASC
    ");
    $poll_stmt->execute([$request_id, $since]);
    echo json_encode($poll_stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ── AJAX: mark all as read when user focuses window ─────── */
if (isset($_GET['mark_read']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $pdo->prepare("UPDATE messages SET is_read=1, is_delivered=1 WHERE request_id=? AND sender_id!=?")
        ->execute([$request_id, $user['id']]);
    echo json_encode(['ok' => true]);
    exit;
}

/* ── Send message (AJAX POST) ────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_text'])) {
    header('Content-Type: application/json');
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['error' => 'Invalid token']); exit;
    }
    $text = trim($_POST['message_text'] ?? '');
    if ($text === '') { echo json_encode(['error' => 'Empty']); exit; }

    $ins = $pdo->prepare("INSERT INTO messages (request_id, sender_id, message_text, is_delivered) VALUES (?,?,?,0)");
    $ins->execute([$request_id, $user['id'], $text]);
    $new_id = $pdo->lastInsertId();

    $row = $pdo->prepare("SELECT m.*, u.full_name AS sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.id=?");
    $row->execute([$new_id]);
    echo json_encode($row->fetch(PDO::FETCH_ASSOC));
    exit;
}

/* ── Initial page load: fetch all messages ───────────────── */
$pdo->prepare("UPDATE messages SET is_read=1, is_delivered=1 WHERE request_id=? AND sender_id!=?")
    ->execute([$request_id, $user['id']]);

$msgs_stmt = $pdo->prepare("
    SELECT m.*, u.full_name AS sender_name
    FROM messages m JOIN users u ON m.sender_id=u.id
    WHERE m.request_id=? ORDER BY m.created_at ASC
");
$msgs_stmt->execute([$request_id]);
$messages = $msgs_stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navigation.php';

// Helper: tick SVG
function tick_svg($double = false, $colored = false) {
    $c = $colored ? '#53d86a' : '#aaaaaa';
    if (!$double) {
        // single tick
        return '<svg viewBox="0 0 16 16" fill="none" class="chat-tick"><polyline points="2,8 6,12 14,4" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
    // double tick
    return '
    <svg viewBox="0 0 22 16" fill="none" class="chat-tick" style="width:18px">
        <polyline points="1,8 5,12 13,4" stroke="'.$c.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <polyline points="7,8 11,12 19,4" stroke="'.$c.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>';
}

$last_ts = 0;
if (!empty($messages)) {
    $last_ts = strtotime(end($messages)['created_at']);
}
?>
<!– Chat page inlined inside the layout –>
<style>
/* ── Chat-specific styles ── */
#chat-window {
    height: calc(100vh - 280px);
    min-height: 400px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 20px;
    background: #0d0d0d;
    scrollbar-width: thin;
    scrollbar-color: #222 transparent;
}
#chat-window::-webkit-scrollbar { width: 4px; }
#chat-window::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 2px; }

.chat-header {
    background: #111 !important;
    border-bottom: 1px solid #222 !important;
    padding: 14px 20px !important;
    display: flex;
    align-items: center;
    gap: 12px;
}
.chat-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F20A68, #c9075a);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; color: #fff; font-size: 1rem;
    flex-shrink: 0;
}
.chat-status-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: #25d366; border: 2px solid #111;
    position: absolute; bottom: 1px; right: 1px;
}

.msg-row { display: flex; align-items: flex-end; gap: 8px; margin-bottom: 2px; }
.msg-row.mine { flex-direction: row-reverse; }

.msg-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: #2a2a2a;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.72rem; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.msg-avatar.mine-av { background: linear-gradient(135deg,#F20A68,#c9075a); }

.bubble-wrap { max-width: min(72%, 480px); }

.bubble {
    padding: 9px 14px;
    font-size: 0.92rem;
    line-height: 1.5;
    word-break: break-word;
    position: relative;
}
.bubble-mine  { background: linear-gradient(135deg,#F20A68,#b5055a); color:#fff; border-radius:18px 18px 4px 18px; }
.bubble-other { background:#1e1e1e; color:#f0f0f0; border-radius:18px 18px 18px 4px; border:1px solid #2a2a2a; }

.msg-meta {
    font-size: 0.67rem;
    color: #666;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 3px;
}
.msg-meta.mine { justify-content: flex-end; }

/* Ticks */
.tick-wrap { display:inline-flex; align-items:center; gap:0; }
.tick-wrap svg { width:14px; height:11px; }

/* Date separator */
.date-sep {
    text-align: center;
    position: relative;
    margin: 12px 0 6px;
}
.date-sep span {
    background: #1a1a1a;
    color: #555;
    font-size: 0.72rem;
    padding: 3px 12px;
    border-radius: 20px;
    border: 1px solid #2a2a2a;
}

/* Footer input */
.chat-footer {
    background: #0f0f0f !important;
    border-top: 1px solid #222 !important;
    padding: 12px 16px !important;
}
#msg-input {
    background: #1a1a1a !important;
    border: 1px solid #2a2a2a !important;
    color: #fff !important;
    border-radius: 24px !important;
    padding: 10px 18px !important;
    resize: none !important;
    font-size: 0.92rem !important;
    max-height: 120px;
    overflow-y: auto;
    line-height: 1.5;
    transition: border-color 0.2s ease;
}
#msg-input:focus {
    outline: none;
    border-color: #F20A68 !important;
    box-shadow: 0 0 0 3px rgba(242,10,104,0.12) !important;
}
#send-btn {
    width: 46px; height: 46px;
    border-radius: 50% !important;
    background: #F20A68;
    border: none;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
    font-size: 1.1rem;
}
#send-btn:hover { background: #c9075a; transform: scale(1.07); box-shadow: 0 4px 16px rgba(242,10,104,0.4); }
#send-btn.sending { opacity: 0.6; pointer-events: none; }

.online-badge { color: #25d366; font-size: 0.75rem; }
</style>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-secondary text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="view_request.php?id=<?= $request_id ?>" class="text-secondary text-decoration-none">Request #<?= $request_id ?></a></li>
            <li class="breadcrumb-item active text-white">Chat</li>
        </ol>
    </nav>

    <div class="row justify-content-center g-0">
        <div class="col-lg-8 col-xl-7">
            <div class="card border-0 overflow-hidden" style="border-radius:16px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">

                <!-- Chat Header -->
                <div class="chat-header card-header">
                    <div class="position-relative">
                        <div class="chat-avatar"><?= strtoupper(substr($other_name, 0, 1)) ?></div>
                        <div class="chat-status-dot"></div>
                    </div>
                    <div>
                        <div class="fw-bold text-white" style="font-size:1rem;"><?= htmlspecialchars($other_name) ?></div>
                        <div class="online-badge">● Online</div>
                    </div>
                    <div class="ms-auto">
                        <a href="view_request.php?id=<?= $request_id ?>" class="btn btn-sm" style="background:#1e1e1e;border:1px solid #333;color:#aaa;border-radius:8px!important;padding:6px 14px!important;">
                            <i class="ph ph-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>

                <!-- Messages Window -->
                <div id="chat-window" class="card-body p-0">

                    <?php
                    $prev_date = '';
                    foreach ($messages as $msg):
                        $mine = ($msg['sender_id'] == $user['id']);
                        $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        $label = ($msg_date === $today) ? 'Today'
                               : ($msg_date === $yesterday ? 'Yesterday'
                               : date('d M Y', strtotime($msg['created_at'])));

                        if ($msg_date !== $prev_date):
                            $prev_date = $msg_date;
                    ?>
                    <div class="date-sep"><span><?= $label ?></span></div>
                    <?php endif; ?>

                    <div class="msg-row <?= $mine ? 'mine' : '' ?>" data-msg-id="<?= $msg['id'] ?>">
                        <div class="msg-avatar <?= $mine ? 'mine-av' : '' ?>">
                            <?= strtoupper(substr($msg['sender_name'], 0, 1)) ?>
                        </div>
                        <div class="bubble-wrap">
                            <div class="bubble <?= $mine ? 'bubble-mine' : 'bubble-other' ?>">
                                <?= nl2br(htmlspecialchars($msg['message_text'])) ?>
                            </div>
                            <div class="msg-meta <?= $mine ? 'mine' : '' ?>">
                                <span><?= date('h:i A', strtotime($msg['created_at'])) ?></span>
                                <?php if ($mine): ?>
                                <span class="tick-wrap" data-status="<?= $msg['is_read'] ? 'read' : ($msg['is_delivered'] ? 'delivered' : 'sent') ?>">
                                    <?php
                                    if (!$msg['is_delivered']) {
                                        // Single tick – sent, not yet delivered
                                        echo '<svg viewBox="0 0 16 16" fill="none" style="width:14px;height:11px"><polyline points="2,8 6,12 14,4" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    } elseif ($msg['is_delivered'] && !$msg['is_read']) {
                                        // Double tick grey – delivered not read
                                        echo '<svg viewBox="0 0 22 14" fill="none" style="width:18px;height:11px"><polyline points="1,7 5,11 13,3" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="7,7 11,11 19,3" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    } else {
                                        // Double tick green – read
                                        echo '<svg viewBox="0 0 22 14" fill="none" style="width:18px;height:11px"><polyline points="1,7 5,11 13,3" stroke="#53d86a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="7,7 11,11 19,3" stroke="#53d86a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                                    }
                                    ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($messages)): ?>
                    <div class="text-center py-5 px-4" style="color:#444; margin: auto;">
                        <i class="ph ph-chat-dots mb-3" style="font-size:3rem; color:#333;"></i>
                        <p class="mb-0" style="font-size:0.9rem;">No messages yet. Say hello! 👋</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Input Area -->
                <div class="chat-footer card-footer">
                    <div class="d-flex align-items-end gap-3">
                        <div id="msg-input" contenteditable="true" role="textbox" aria-multiline="true"
                             data-placeholder="Type a message..."
                             style="flex:1; min-height:46px;"
                             class="form-control"></div>
                        <button id="send-btn" title="Send">
                            <i class="ph ph-paper-plane-right"></i>
                        </button>
                    </div>
                    <input type="hidden" id="csrf-token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const chatWindow   = document.getElementById('chat-window');
    const msgInput     = document.getElementById('msg-input');
    const sendBtn      = document.getElementById('send-btn');
    const csrfToken    = document.getElementById('csrf-token').value;
    const REQUEST_ID   = <?= (int)$request_id ?>;
    const MY_USER_ID   = <?= (int)$user['id'] ?>;
    const MY_INITIAL   = <?= json_encode(strtoupper(substr($user['full_name'], 0, 1))) ?>;
    const OTHER_INITIAL= <?= json_encode(strtoupper(substr($other_name, 0, 1))) ?>;

    let lastTimestamp  = <?= $last_ts ?: 0 ?>;
    let pollInterval;

    // ── Scroll to bottom ──────────────────────────────────────
    function scrollBottom(smooth = false) {
        chatWindow.scrollTo({ top: chatWindow.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
    }
    scrollBottom();

    // ── Placeholder behaviour for contenteditable ─────────────
    msgInput.addEventListener('focus', () => msgInput.classList.add('focused'));
    msgInput.addEventListener('blur',  () => msgInput.classList.remove('focused'));
    msgInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    // ── Render a tick SVG string ──────────────────────────────
    function tickSVG(status) {
        if (status === 'sent') {
            return `<svg viewBox="0 0 16 16" fill="none" style="width:14px;height:11px"><polyline points="2,8 6,12 14,4" stroke="#888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        }
        const c = status === 'read' ? '#53d86a' : '#aaaaaa';
        return `<svg viewBox="0 0 22 14" fill="none" style="width:18px;height:11px">
            <polyline points="1,7 5,11 13,3" stroke="${c}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="7,7 11,11 19,3" stroke="${c}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`;
    }

    // ── Format time ───────────────────────────────────────────
    function fmtTime(ts) {
        const d = new Date(ts * 1000);
        let h = d.getHours(), m = d.getMinutes(), ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${String(m).padStart(2,'0')} ${ampm}`;
    }

    // ── Build message HTML ────────────────────────────────────
    function buildMsgRow(msg) {
        const mine = parseInt(msg.sender_id) === MY_USER_ID;
        const text = msg.message_text.replace(/\n/g,'<br>').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const ts   = parseInt(msg.created_at) || Math.floor(new Date(msg.created_at).getTime()/1000);
        const status = msg.is_read == 1 ? 'read' : (msg.is_delivered == 1 ? 'delivered' : 'sent');
        const initial = mine ? MY_INITIAL : OTHER_INITIAL;

        const tickHtml = mine ? `<span class="tick-wrap" data-status="${status}">${tickSVG(status)}</span>` : '';

        return `
        <div class="msg-row ${mine?'mine':''}" data-msg-id="${msg.id}">
            <div class="msg-avatar ${mine?'mine-av':''}">${initial}</div>
            <div class="bubble-wrap">
                <div class="bubble ${mine?'bubble-mine':'bubble-other'}">${text}</div>
                <div class="msg-meta ${mine?'mine':''}">
                    <span>${fmtTime(ts)}</span>
                    ${tickHtml}
                </div>
            </div>
        </div>`;
    }

    // ── Append a message to the window ────────────────────────
    function appendMsg(msg, scroll=true) {
        // Remove empty state placeholder
        const empty = chatWindow.querySelector('.text-center.py-5');
        if (empty) empty.remove();

        chatWindow.insertAdjacentHTML('beforeend', buildMsgRow(msg));
        const ts = parseInt(msg.created_at) || Math.floor(new Date(msg.created_at).getTime()/1000);
        if (ts > lastTimestamp) lastTimestamp = ts;
        if (scroll) scrollBottom(true);
    }

    // ── Send message ──────────────────────────────────────────
    function sendMessage() {
        const text = msgInput.innerText.trim();
        if (!text) return;
        msgInput.innerText = '';
        sendBtn.classList.add('sending');

        const fd = new FormData();
        fd.append('message_text', text);
        fd.append('csrf_token', csrfToken);

        fetch(`chat.php?request_id=${REQUEST_ID}`, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(msg => {
                if (msg && msg.id) appendMsg(msg);
            })
            .catch(console.error)
            .finally(() => sendBtn.classList.remove('sending'));
    }

    sendBtn.addEventListener('click', sendMessage);

    // ── Update tick status of existing messages ───────────────
    function updateTicks(msgs) {
        msgs.forEach(msg => {
            const row = chatWindow.querySelector(`[data-msg-id="${msg.id}"]`);
            if (!row) return;
            const tw = row.querySelector('.tick-wrap');
            if (!tw) return;
            const status = msg.is_read == 1 ? 'read' : (msg.is_delivered == 1 ? 'delivered' : 'sent');
            if (tw.dataset.status !== status) {
                tw.dataset.status = status;
                tw.innerHTML = tickSVG(status);
            }
        });
    }

    // ── Poll for new messages every 2 seconds ─────────────────
    function poll() {
        fetch(`chat.php?request_id=${REQUEST_ID}&poll=1&since=${lastTimestamp}`)
            .then(r => r.json())
            .then(msgs => {
                if (!Array.isArray(msgs)) return;
                msgs.forEach(msg => {
                    const exists = chatWindow.querySelector(`[data-msg-id="${msg.id}"]`);
                    if (!exists) {
                        appendMsg(msg, true);
                    }
                });
                // Also refresh tick status of all my messages
                updateTicks(msgs);
            })
            .catch(console.error);
    }

    // ── Mark messages as read when tab/window is active ───────
    function markRead() {
        const fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fetch(`chat.php?request_id=${REQUEST_ID}&mark_read=1`, { method:'POST', body: fd }).catch(()=>{});
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) markRead();
    });

    // Start polling
    pollInterval = setInterval(poll, 2000);

    // Placeholder CSS injection for contenteditable
    const style = document.createElement('style');
    style.textContent = `
        #msg-input:empty:before {
            content: attr(data-placeholder);
            color: #555;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
})();
</script>

<?php require_once 'includes/footer.php'; ?>

<?php
$pageTitle = 'Messages — Interlinked Marketplace';
require_once 'includes/session.php';
require_once 'config/database.php';
requireLogin('auth/login.php');

$uid = $_SESSION['user_id'];
$error = '';
$success = '';

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired.';
    } else {
        $to = intval($_POST['receiver_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');

        if ($to && $body) {
            $stmt = $con->prepare("INSERT INTO messages (sender_id, receiver_id, body) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $uid, $to, $body);
            if ($stmt->execute()) {
                $success = 'Message sent.';
            } else {
                $error = 'Failed to send message.';
            }
        }
    }
}

// Get conversation partner
$partnerId = intval($_GET['to'] ?? 0);

// Get conversations
$convos = mysqli_query($con, "
    SELECT DISTINCT 
        CASE WHEN sender_id = $uid THEN receiver_id ELSE sender_id END as partner_id,
        u.username, u.avatar
    FROM messages m
    JOIN users u ON u.user_id = CASE WHEN sender_id = $uid THEN receiver_id ELSE sender_id END
    WHERE sender_id = $uid OR receiver_id = $uid
    ORDER BY (SELECT MAX(created_at) FROM messages WHERE (sender_id=$uid AND receiver_id=partner_id) OR (sender_id=partner_id AND receiver_id=$uid)) DESC
    LIMIT 20
");

// Get active conversation
$messages = null;
$partner = null;
if ($partnerId) {
    $pStmt = $con->prepare("SELECT user_id, username, avatar FROM users WHERE user_id = ?");
    $pStmt->bind_param("i", $partnerId);
    $pStmt->execute();
    $partner = $pStmt->get_result()->fetch_assoc();

    if ($partner) {
        // Mark as read
        $con->query("UPDATE messages SET is_read=1 WHERE sender_id=$partnerId AND receiver_id=$uid");

        $mStmt = $con->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id=u.user_id WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?) ORDER BY m.created_at ASC LIMIT 50");
        $mStmt->bind_param("iiii", $uid, $partnerId, $partnerId, $uid);
        $mStmt->execute();
        $messages = $mStmt->get_result();
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<div class="container py-5">
  <h2 class="fw-800 mb-4" style="font-family:'Sora',sans-serif">💬 Messages</h2>

  <?php if ($error): ?><div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success mb-3"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <div class="row g-4">
    <!-- Conversations List -->
    <div class="col-lg-4">
      <div class="card p-3">
        <h6 class="fw-700 mb-3">Conversations</h6>
        <?php if ($convos && mysqli_num_rows($convos) > 0): ?>
          <?php while ($c = mysqli_fetch_assoc($convos)): ?>
          <a href="?to=<?= $c['partner_id'] ?>" class="d-flex align-items-center gap-3 p-2 rounded-3 text-decoration-none <?= $partnerId == $c['partner_id'] ? 'bg-primary bg-opacity-10' : '' ?>" style="color:inherit">
            <img src="<?= url('uploads/avatars/' . ($c['avatar'] ?? 'default.png')) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover" onerror="this.src='<?= url('assets/images/default-avatar.png') ?>">
            <div>
              <div class="fw-700 small"><?= htmlspecialchars($c['username']) ?></div>
            </div>
          </a>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="small text-center py-4">No conversations yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Chat Area -->
    <div class="col-lg-8">
      <?php if ($partner): ?>
      <div class="card">
        <div class="p-3 border-bottom border-secondary border-opacity-10 d-flex align-items-center gap-3">
          <img src="<?= url('uploads/avatars/' . ($partner['avatar'] ?? 'default.png')) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover" onerror="this.src='<?= url('assets/images/default-avatar.png') ?>">
          <div class="fw-700"><?= htmlspecialchars($partner['username']) ?></div>
        </div>

        <div class="p-4" style="max-height:400px;overflow-y:auto;background:rgba(0,0,0,0.2)">
          <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
            <?php while ($m = mysqli_fetch_assoc($messages)): ?>
            <div class="mb-3 d-flex <?= $m['sender_id'] == $uid ? 'justify-content-end' : '' ?>">
              <div class="p-3 rounded-3 <?= $m['sender_id'] == $uid ? 'bg-primary' : 'bg-secondary' ?>" style="max-width:70%;color:#fff">
                <div class="small fw-600 mb-1"><?= htmlspecialchars($m['username']) ?></div>
                <div><?= nl2br(htmlspecialchars($m['body'])) ?></div>
                <div class="text-end" style="font-size:.65rem;opacity:0.6"><?= date('M j, H:i', strtotime($m['created_at'])) ?></div>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="text-center py-4">No messages yet. Start the conversation!</p>
          <?php endif; ?>
        </div>

        <form method="POST" class="p-3 border-top border-secondary border-opacity-10">
          <?= csrfField() ?>
          <input type="hidden" name="receiver_id" value="<?= $partnerId ?>">
          <div class="input-group">
            <textarea name="body" class="form-control" rows="1" placeholder="Type a message..." required></textarea>
            <button type="submit" class="btn btn-primary">Send</button>
          </div>
        </form>
      </div>
      <?php else: ?>
      <div class="card p-5 text-center">
        <i data-feather="message-circle" style="width:48px;height:48px;opacity:0.2" class="mb-3"></i>
        <p>Select a conversation or contact a seller from a product page.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php
$pageTitle = "Messages | Lost & Found";
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/messaging.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$user_id = (int)$_SESSION['user_id'];
$success = $error = '';

// Get list of conversations
$chats = getMessageChats($conn, $user_id);

// If viewing specific conversation
$conversation = [];
$other_user = null;
if (isset($_GET['with'])) {
    $other_user_id = (int)$_GET['with'];
    
    // Get other user info
    $user_stmt = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ?");
    $user_stmt->bind_param("i", $other_user_id);
    $user_stmt->execute();
    $other_user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
    
    if ($other_user) {
        $conversation = getConversation($conn, $user_id, $other_user_id);
        markMessagesAsRead($conn, $user_id, $other_user_id);
        
        // Handle sending message
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
            $message_text = trim($_POST['message'] ?? '');
            if (!empty($message_text)) {
                sendMessage($conn, $user_id, $other_user_id, $message_text);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row g-3" style="min-height: 600px;">
        <!-- Conversation List -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Messages</h5>
                </div>
                <div class="card-body p-0" style="overflow-y: auto; max-height: 500px;">
                    <?php if (empty($chats)): ?>
                        <p class="text-muted p-3 mb-0">No messages yet. Start a conversation!</p>
                    <?php else: ?>
                        <?php foreach ($chats as $chat): ?>
                            <a href="?with=<?= $chat['other_user_id'] ?>" class="list-group-item list-group-item-action <?= isset($_GET['with']) && $_GET['with'] == $chat['other_user_id'] ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($chat['full_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars(substr($chat['last_message'], 0, 40)) ?>...</small>
                                    </div>
                                    <?php if ($chat['unread_count'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?= $chat['unread_count'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Conversation View -->
        <div class="col-lg-8">
            <?php if ($other_user): ?>
                <div class="card h-100 d-flex flex-column">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?= htmlspecialchars($other_user['full_name']) ?></h5>
                    </div>
                    <div class="card-body flex-grow-1" style="overflow-y: auto; max-height: 400px;">
                        <?php foreach ($conversation as $msg): ?>
                            <div class="mb-3 <?= $msg['sender_id'] == $user_id ? 'text-end' : 'text-start' ?>">
                                <div class="d-inline-block p-2 rounded <?= $msg['sender_id'] == $user_id ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 70%;">
                                    <small><?= htmlspecialchars($msg['message_text']) ?></small>
                                </div>
                                <div class="small text-muted"><?= date('M j, g:i A', strtotime($msg['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer bg-light">
                        <form method="POST" class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm" name="message" placeholder="Type a message..." required>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send"></i></button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center text-muted">
                        <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                        <p class="mt-3">Select a conversation or start a new one</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

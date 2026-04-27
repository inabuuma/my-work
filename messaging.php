<?php
// Messaging System - Secure in-app messaging between finder and owner

function sendMessage($conn, $sender_id, $recipient_id, $message_text, $item_id = null, $attachment = null) {
    global $error;
    
    if (empty($message_text)) {
        $error = "Message cannot be empty.";
        return false;
    }
    
    $attachment_path = '';
    if ($attachment && isset($attachment['error']) && $attachment['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $filename = $attachment['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newname = uniqid() . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/messages/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            if (move_uploaded_file($attachment['tmp_name'], $upload_dir . $newname)) {
                $attachment_path = $newname;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, item_id, message_text, attachment_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $sender_id, $recipient_id, $item_id, $message_text, $attachment_path);
    
    if ($stmt->execute()) {
        $message_id = $conn->insert_id;
        $stmt->close();
        
        // Create notification
        createNotification($conn, $recipient_id, "$sender_id sent you a message", 'message', $item_id);
        
        return $message_id;
    } else {
        $error = "Error sending message.";
        $stmt->close();
        return false;
    }
}

function getConversation($conn, $user_id, $other_user_id, $item_id = null, $limit = 50) {
    if ($item_id) {
        $stmt = $conn->prepare("
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE item_id = ? AND 
                  ((sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?))
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("iiiii", $item_id, $user_id, $other_user_id, $other_user_id, $user_id, $limit);
    } else {
        $stmt = $conn->prepare("
            SELECT m.*, u.full_name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("iiiii", $user_id, $other_user_id, $other_user_id, $user_id, $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = array_reverse($result->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    
    return $messages;
}

function getUnreadMessagesCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['unread'];
}

function markMessagesAsRead($conn, $user_id, $sender_id = null) {
    if ($sender_id) {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE recipient_id = ? AND sender_id = ?");
        $stmt->bind_param("ii", $user_id, $sender_id);
    } else {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE recipient_id = ?");
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $stmt->close();
}

function getMessageChats($conn, $user_id) {
    // Get unique conversations with latest message
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            CASE 
                WHEN sender_id = ? THEN recipient_id
                ELSE sender_id
            END as other_user_id,
            u.full_name,
            MAX(m.created_at) as last_message_time,
            MAX(m.message_text) as last_message,
            COUNT(CASE WHEN m.recipient_id = ? AND m.is_read = 0 THEN 1 END) as unread_count
        FROM messages m
        JOIN users u ON (CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END) = u.user_id
        WHERE sender_id = ? OR recipient_id = ?
        GROUP BY other_user_id, u.full_name
        ORDER BY last_message_time DESC
    ");
    $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chats = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $chats;
}

function createNotification($conn, $user_id, $message, $type = 'system', $item_id = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, notification_type, related_item_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $message, $type, $item_id);
    $stmt->execute();
    $stmt->close();
}

function monitorConversations($conn, $admin_id, $keyword = null, $limit = 100) {
    // Admin can view all conversations for moderation
    if ($keyword) {
        $stmt = $conn->prepare("
            SELECT m.*, 
                   u1.full_name as sender_name, 
                   u2.full_name as recipient_name,
                   fi.title as item_title
            FROM messages m
            JOIN users u1 ON m.sender_id = u1.user_id
            JOIN users u2 ON m.recipient_id = u2.user_id
            LEFT JOIN found_items fi ON m.item_id = fi.item_id
            WHERE m.message_text LIKE ? OR u1.full_name LIKE ? OR u2.full_name LIKE ?
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $search = "%$keyword%";
        $stmt->bind_param("sssi", $search, $search, $search, $limit);
    } else {
        $stmt = $conn->prepare("
            SELECT m.*, 
                   u1.full_name as sender_name, 
                   u2.full_name as recipient_name,
                   fi.title as item_title
            FROM messages m
            JOIN users u1 ON m.sender_id = u1.user_id
            JOIN users u2 ON m.recipient_id = u2.user_id
            LEFT JOIN found_items fi ON m.item_id = fi.item_id
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $messages;
}

?>

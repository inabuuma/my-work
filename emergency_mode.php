<?php
// Emergency Mode for High-Value Items
// Broadcast alerts instantly to all system users

function createEmergencyAlert($conn, $user_id, $item_id, $alert_title, $alert_description, $item_type = 'lost') {
    global $error;
    
    if (empty($alert_title) || empty($alert_description)) {
        $error = "Alert title and description are required.";
        return false;
    }
    
    $urgency = 'high';
    $is_resolved = 0;
    
    $stmt = $conn->prepare("
        INSERT INTO emergency_alerts (item_id, user_id, alert_title, alert_description, urgency_level, is_resolved)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisssi", $item_id, $user_id, $alert_title, $alert_description, $urgency, $is_resolved);
    
    if ($stmt->execute()) {
        $alert_id = $conn->insert_id;
        $stmt->close();
        
        // Broadcast to all users
        broadcastEmergencyAlert($conn, $alert_id, $user_id, $alert_title);
        
        // Get item details for SMS
        if ($item_type === 'lost') {
            $item_stmt = $conn->prepare("SELECT title, category, location, image_paths FROM lost_items WHERE item_id = ?");
        } else {
            $item_stmt = $conn->prepare("SELECT title, category, location, image_paths FROM found_items WHERE item_id = ?");
        }
        $item_stmt->bind_param("i", $item_id);
        $item_stmt->execute();
        $item = $item_stmt->get_result()->fetch_assoc();
        $item_stmt->close();
        
        // Send SMS alerts to relevant users
        sendEmergencySMS($conn, $user_id, $alert_title, $item);
        
        // Log activity
        logActivity($conn, $user_id, "emergency_alert_created", $item_id, null, 
            ['alert_id' => $alert_id, 'urgency' => $urgency]);
        
        return $alert_id;
    } else {
        $error = "Error creating emergency alert.";
        $stmt->close();
        return false;
    }
}

function broadcastEmergencyAlert($conn, $alert_id, $creator_id, $title) {
    // Get all users except the one who created it
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id != ? AND is_active = 1");
    $stmt->bind_param("i", $creator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Create notification for each user
    foreach ($users as $user) {
        $message = "🚨 EMERGENCY: $title";
        $notification_type = 'emergency';
        
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, notification_type, related_item_id)
            VALUES (?, ?, ?, ?)
        ");
        $notif_stmt->bind_param("issi", $user['user_id'], $message, $notification_type, $alert_id);
        $notif_stmt->execute();
        $notif_stmt->close();
    }
}

function getActiveEmergencyAlerts($conn, $hostel_id = null, $limit = 20) {
    if ($hostel_id) {
        $stmt = $conn->prepare("
            SELECT ea.*, u.full_name, u.phone, li.title, li.category, li.location
            FROM emergency_alerts ea
            JOIN users u ON ea.user_id = u.user_id
            LEFT JOIN lost_items li ON ea.item_id = li.item_id
            WHERE ea.is_resolved = 0 AND li.hostel_id = ?
            ORDER BY ea.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $hostel_id, $limit);
    } else {
        $stmt = $conn->prepare("
            SELECT ea.*, u.full_name, u.phone, li.title, li.category, li.location
            FROM emergency_alerts ea
            JOIN users u ON ea.user_id = u.user_id
            LEFT JOIN lost_items li ON ea.item_id = li.item_id
            WHERE ea.is_resolved = 0
            ORDER BY ea.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $alerts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $alerts;
}

function resolveEmergencyAlert($conn, $alert_id) {
    $now = date('Y-m-d H:i:s');
    $is_resolved = 1;
    
    $stmt = $conn->prepare("UPDATE emergency_alerts SET is_resolved = ?, resolved_at = ? WHERE alert_id = ?");
    $stmt->bind_param("isi", $is_resolved, $now, $alert_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Get alert details
        $alert_stmt = $conn->prepare("SELECT * FROM emergency_alerts WHERE alert_id = ?");
        $alert_stmt->bind_param("i", $alert_id);
        $alert_stmt->execute();
        $alert = $alert_stmt->get_result()->fetch_assoc();
        $alert_stmt->close();
        
        if ($alert) {
            logActivity($conn, null, "emergency_alert_resolved", $alert['item_id'], null, 
                ['alert_id' => $alert_id]);
        }
        
        return true;
    } else {
        $stmt->close();
        return false;
    }
}

function markItemAsHighValue($conn, $item_id, $is_high_value = true) {
    $stmt = $conn->prepare("UPDATE lost_items SET is_high_value = ? WHERE item_id = ?");
    $stmt->bind_param("ii", $is_high_value, $item_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function sendEmergencySMS($conn, $user_id, $title, $item_details) {
    // This would integrate with SMS API (e.g., Twilio, AWS SNS)
    // For now, we'll just log it
    
    // Get users with phone numbers
    $stmt = $conn->prepare("SELECT user_id, phone FROM users WHERE is_active = 1 AND phone IS NOT NULL AND user_id != ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipients = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($recipients as $recipient) {
        // SMS message
        $item_title = $item_details['title'] ?? 'Unknown Item';
        $item_location = $item_details['location'] ?? 'Unknown Location';
        $message = "🚨 ALERT: $title. Item: $item_title at $item_location. Check app for details.";
        
        // Log SMS queue
        $log_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, notification_type, is_sms_sent)
            VALUES (?, ?, 'emergency', 1)
        ");
        $sms_type = 'sms';
        $log_stmt->bind_param("is", $recipient['user_id'], $message);
        $log_stmt->execute();
        $log_stmt->close();
        
        // In production, send actual SMS here:
        // sendSMSViaAPI($recipient['phone'], $message);
    }
}

function requireEmergencyVerification($conn, $user_id) {
    // Users must verify emergency features (e.g., phone number, identity)
    $stmt = $conn->prepare("SELECT is_emergency_verified FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $user && $user['is_emergency_verified'];
}

function verifyUserForEmergency($conn, $user_id, $verification_code) {
    // In production, this would verify via SMS code, email, or ID verification
    $is_verified = 1;
    
    $stmt = $conn->prepare("UPDATE users SET is_emergency_verified = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $is_verified, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

?>

<?php
// Offline Reporting & Sync System
// Allows users to fill forms offline and sync when internet returns

function saveOfflineReport($conn, $user_id, $action_type, $action_data, $device_id) {
    global $error;
    
    $data_json = json_encode($action_data);
    $local_timestamp = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("
        INSERT INTO offline_sync_queue (user_id, action_type, action_data, device_id, local_timestamp)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $user_id, $action_type, $data_json, $device_id, $local_timestamp);
    
    if ($stmt->execute()) {
        $sync_id = $conn->insert_id;
        $stmt->close();
        
        // Store locally on device as well (via localStorage in JS)
        return ['sync_id' => $sync_id, 'status' => 'queued', 'local_timestamp' => $local_timestamp];
    } else {
        $error = "Error saving report for offline sync.";
        $stmt->close();
        return false;
    }
}

function syncOfflineReports($conn, $user_id, $device_id = null) {
    global $error;
    
    $synced_count = 0;
    $errors = [];
    
    if ($device_id) {
        $stmt = $conn->prepare("
            SELECT * FROM offline_sync_queue
            WHERE user_id = ? AND device_id = ? AND synced = 0
            ORDER BY local_timestamp ASC
        ");
        $stmt->bind_param("is", $user_id, $device_id);
    } else {
        $stmt = $conn->prepare("
            SELECT * FROM offline_sync_queue
            WHERE user_id = ? AND synced = 0
            ORDER BY local_timestamp ASC
        ");
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $queue_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    foreach ($queue_items as $item) {
        $action_data = json_decode($item['action_data'], true);
        
        try {
            if ($item['action_type'] === 'report_lost') {
                $synced = processSyncedLostReport($conn, $user_id, $action_data);
            } elseif ($item['action_type'] === 'report_found') {
                $synced = processSyncedFoundReport($conn, $user_id, $action_data);
            } elseif ($item['action_type'] === 'claim') {
                $synced = processSyncedClaim($conn, $user_id, $action_data);
            } else {
                $synced = false;
            }
            
            if ($synced) {
                markSyncQueueItemAsSynced($conn, $item['sync_id']);
                $synced_count++;
            } else {
                $errors[] = "Error syncing item {$item['sync_id']}";
            }
        } catch (Exception $e) {
            $errors[] = "Exception: " . $e->getMessage();
        }
    }
    
    return [
        'synced' => $synced_count,
        'total' => count($queue_items),
        'errors' => $errors,
        'status' => count($errors) === 0 ? 'success' : 'partial'
    ];
}

function processSyncedLostReport($conn, $user_id, $action_data) {
    $title = $action_data['title'] ?? '';
    $description = $action_data['description'] ?? '';
    $category = $action_data['category'] ?? '';
    $location = $action_data['location'] ?? '';
    $date_lost = $action_data['date_lost'] ?? date('Y-m-d');
    $image_path = $action_data['image_path'] ?? '';
    $hostel_id = $action_data['hostel_id'] ?? 1;
    
    if (empty($title) || empty($description) || empty($category) || empty($location)) {
        return false;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO lost_items (user_id, title, description, category, location, date_lost, image_path, hostel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssi", $user_id, $title, $description, $category, $location, $date_lost, $image_path, $hostel_id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        updateLocationHeatmap($conn, $location, $hostel_id, 'lost');
    }
    
    return $result;
}

function processSyncedFoundReport($conn, $user_id, $action_data) {
    $title = $action_data['title'] ?? '';
    $description = $action_data['description'] ?? '';
    $category = $action_data['category'] ?? '';
    $location = $action_data['location'] ?? '';
    $date_found = $action_data['date_found'] ?? date('Y-m-d');
    $image_path = $action_data['image_path'] ?? '';
    $hostel_id = $action_data['hostel_id'] ?? 1;
    
    if (empty($title) || empty($description) || empty($category) || empty($location)) {
        return false;
    }
    
    $stmt = $conn->prepare("
        INSERT INTO found_items (user_id, title, description, category, location, date_found, image_path, hostel_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssi", $user_id, $title, $description, $category, $location, $date_found, $image_path, $hostel_id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        updateLocationHeatmap($conn, $location, $hostel_id, 'found');
    }
    
    return $result;
}

function processSyncedClaim($conn, $user_id, $action_data) {
    $item_id = $action_data['item_id'] ?? null;
    $claim_type = $action_data['claim_type'] ?? null;
    $description = $action_data['description'] ?? '';
    
    if (!$item_id || !$claim_type) {
        return false;
    }
    
    return createClaimWithQuestions($conn, $item_id, $user_id, $claim_type, $description);
}

function markSyncQueueItemAsSynced($conn, $sync_id) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE offline_sync_queue SET synced = 1, synced_at = ? WHERE sync_id = ?");
    $stmt->bind_param("si", $now, $sync_id);
    $stmt->execute();
    $stmt->close();
}

function getPendingSyncReports($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT * FROM offline_sync_queue
        WHERE user_id = ? AND synced = 0
        ORDER BY local_timestamp DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reports = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $reports;
}

function getSyncStatus($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN synced = 0 THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN synced = 1 THEN 1 ELSE 0 END) as synced
        FROM offline_sync_queue
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

?>

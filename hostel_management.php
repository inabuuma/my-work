<?php
// Multi-Hostel / Multi-Branch Support System

function getHostels($conn, $active_only = true) {
    if ($active_only) {
        $stmt = $conn->prepare("SELECT * FROM hostels WHERE is_active = 1 ORDER BY hostel_name ASC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM hostels ORDER BY hostel_name ASC");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $hostels = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $hostels;
}

function getHostelById($conn, $hostel_id) {
    $stmt = $conn->prepare("SELECT * FROM hostels WHERE hostel_id = ?");
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $hostel = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $hostel;
}

function createHostel($conn, $hostel_name, $location, $manager_id = null) {
    global $error;
    
    if (empty($hostel_name)) {
        $error = "Hostel name is required.";
        return false;
    }
    
    $is_active = 1;
    $stmt = $conn->prepare("INSERT INTO hostels (hostel_name, location, manager_id, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $hostel_name, $location, $manager_id, $is_active);
    
    if ($stmt->execute()) {
        $hostel_id = $conn->insert_id;
        $stmt->close();
        
        // Log activity
        logActivity($conn, null, "hostel_created", null, null, 
            ['hostel_id' => $hostel_id, 'hostel_name' => $hostel_name]);
        
        return $hostel_id;
    } else {
        $error = "Error creating hostel.";
        $stmt->close();
        return false;
    }
}

function updateHostel($conn, $hostel_id, $hostel_name, $location, $manager_id = null) {
    $stmt = $conn->prepare("UPDATE hostels SET hostel_name = ?, location = ?, manager_id = ? WHERE hostel_id = ?");
    $stmt->bind_param("ssii", $hostel_name, $location, $manager_id, $hostel_id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function getHostelStatistics($conn, $hostel_id) {
    $stats = [];
    
    // Total items
    $result = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE hostel_id = $hostel_id"));
    $stats['total_lost'] = $result[0];
    
    $result = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE hostel_id = $hostel_id"));
    $stats['total_found'] = $result[0];
    
    // Recovered items
    $result = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items WHERE hostel_id = $hostel_id AND status = 'recovered'"));
    $stats['recovered'] = $result[0];
    
    // Claimed items
    $result = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM claims WHERE claim_status = 'approved'"));
    $stats['claimed'] = $result[0];
    
    // Active users
    $result = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE hostel_id = $hostel_id AND is_active = 1 AND role = 'resident'"));
    $stats['active_residents'] = $result[0];
    
    return $stats;
}

function getUsersByHostel($conn, $hostel_id, $role = null) {
    if ($role) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE hostel_id = ? AND role = ? AND is_active = 1 ORDER BY full_name ASC");
        $stmt->bind_param("is", $hostel_id, $role);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE hostel_id = ? AND is_active = 1 ORDER BY full_name ASC");
        $stmt->bind_param("i", $hostel_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $users;
}

function assignHostelManager($conn, $hostel_id, $user_id) {
    // Update hostel manager
    $stmt = $conn->prepare("UPDATE hostels SET manager_id = ? WHERE hostel_id = ?");
    $stmt->bind_param("ii", $user_id, $hostel_id);
    $stmt->execute();
    $stmt->close();
    
    // Update user role to hostel_manager
    $role = 'hostel_manager';
    $stmt2 = $conn->prepare("UPDATE users SET role = ?, hostel_id = ? WHERE user_id = ?");
    $stmt2->bind_param("sii", $role, $hostel_id, $user_id);
    $stmt2->execute();
    $stmt2->close();
    
    // Log activity
    logActivity($conn, null, "manager_assigned", null, $user_id, 
        ['hostel_id' => $hostel_id, 'manager_id' => $user_id]);
}

function getItemsByHostel($conn, $hostel_id, $item_type = 'all', $status = null) {
    if ($item_type === 'lost' || $item_type === 'all') {
        if ($status) {
            $stmt = $conn->prepare("
                SELECT *, 'lost' as type FROM lost_items 
                WHERE hostel_id = ? AND status = ?
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("is", $hostel_id, $status);
        } else {
            $stmt = $conn->prepare("
                SELECT *, 'lost' as type FROM lost_items 
                WHERE hostel_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("i", $hostel_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    
    if ($item_type === 'found' || $item_type === 'all') {
        if ($status) {
            $stmt = $conn->prepare("
                SELECT *, 'found' as type FROM found_items 
                WHERE hostel_id = ? AND status = ?
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("is", $hostel_id, $status);
        } else {
            $stmt = $conn->prepare("
                SELECT *, 'found' as type FROM found_items 
                WHERE hostel_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->bind_param("i", $hostel_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $found_items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        if ($item_type === 'all') {
            $items = array_merge($items, $found_items);
            usort($items, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        } else {
            $items = $found_items;
        }
    }
    
    return $items ?? [];
}

?>

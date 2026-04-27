<?php
// Advanced Role-Based Access Control System

define('ROLE_PERMISSIONS', [
    'resident' => [
        'report_lost' => true,
        'report_found' => true,
        'claim_item' => true,
        'message' => true,
        'search' => true,
        'view_my_items' => true,
        'view_profile' => true,
        'access_heatmap' => true,
        'view_leaderboard' => true,
        'access_auction' => true,
        'manage_admin' => false,
        'manage_hostels' => false,
        'manage_users' => false,
        'view_all_claims' => false,
        'moderate_messages' => false,
        'view_activity_logs' => false,
        'manage_roles' => false,
        'send_sms' => false
    ],
    'hostel_manager' => [
        'report_lost' => true,
        'report_found' => true,
        'claim_item' => true,
        'message' => true,
        'search' => true,
        'view_my_items' => true,
        'view_profile' => true,
        'access_heatmap' => true,
        'view_leaderboard' => true,
        'access_auction' => true,
        'manage_admin' => false,
        'manage_hostels' => true,
        'manage_users' => true,
        'view_all_claims' => true,
        'moderate_messages' => true,
        'view_activity_logs' => true,
        'manage_roles' => false,
        'send_sms' => true
    ],
    'security' => [
        'report_lost' => true,
        'report_found' => true,
        'claim_item' => false,
        'message' => true,
        'search' => true,
        'view_my_items' => true,
        'view_profile' => true,
        'access_heatmap' => true,
        'view_leaderboard' => true,
        'access_auction' => false,
        'manage_admin' => false,
        'manage_hostels' => false,
        'manage_users' => false,
        'view_all_claims' => true,
        'moderate_messages' => true,
        'view_activity_logs' => true,
        'manage_roles' => false,
        'send_sms' => true
    ],
    'admin' => [
        'report_lost' => true,
        'report_found' => true,
        'claim_item' => true,
        'message' => true,
        'search' => true,
        'view_my_items' => true,
        'view_profile' => true,
        'access_heatmap' => true,
        'view_leaderboard' => true,
        'access_auction' => true,
        'manage_admin' => true,
        'manage_hostels' => true,
        'manage_users' => true,
        'view_all_claims' => true,
        'moderate_messages' => true,
        'view_activity_logs' => true,
        'manage_roles' => true,
        'send_sms' => true
    ]
]);

function hasPermission($role, $permission) {
    if (!isset(ROLE_PERMISSIONS[$role])) {
        return false;
    }
    
    return ROLE_PERMISSIONS[$role][$permission] ?? false;
}

function requirePermission($role, $permission, $redirect_url = null) {
    if (!hasPermission($role, $permission)) {
        if ($redirect_url) {
            header("Location: $redirect_url?msg=permission_denied");
        } else {
            die("Access Denied: You don't have permission to access this resource.");
        }
        exit();
    }
}

function checkPermissionAjax($role, $permission) {
    if (!hasPermission($role, $permission)) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission Denied']);
        exit();
    }
}

function changeUserRole($conn, $user_id, $new_role, $admin_id) {
    global $error;
    
    $valid_roles = ['resident', 'hostel_manager', 'security', 'admin'];
    if (!in_array($new_role, $valid_roles)) {
        $error = "Invalid role.";
        return false;
    }
    
    // Get current user
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        $error = "User not found.";
        return false;
    }
    
    $old_role = $user['role'];
    
    // Update role
    $update = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $update->bind_param("si", $new_role, $user_id);
    
    if ($update->execute()) {
        $update->close();
        
        // Log activity
        logActivity($conn, $admin_id, "role_changed", null, $user_id, 
            ['old_role' => $old_role, 'new_role' => $new_role]);
        
        // Create notification
        createNotification($conn, $user_id, 
            "Your role has been changed from $old_role to $new_role", 'system');
        
        return true;
    } else {
        $error = "Error updating role.";
        $update->close();
        return false;
    }
}

function getRoleInfo($role) {
    $role_info = [
        'resident' => [
            'name' => 'Resident',
            'description' => 'Regular hostel resident - can report items, claim found items, and participate in the system.',
            'level' => 1
        ],
        'hostel_manager' => [
            'name' => 'Hostel Manager',
            'description' => 'Manages a specific hostel - can review claims, manage users, and view analytics.',
            'level' => 3
        ],
        'security' => [
            'name' => 'Security Personnel',
            'description' => 'Security staff - can review claims, moderate messages, and access activity logs.',
            'level' => 2
        ],
        'admin' => [
            'name' => 'Administrator',
            'description' => 'System administrator - has full access to all features and user management.',
            'level' => 4
        ]
    ];
    
    return $role_info[$role] ?? null;
}

function isAdminOrHigher($conn, $user_id) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) return false;
    
    return in_array($user['role'], ['admin', 'hostel_manager', 'security']);
}

function canManageUser($conn, $current_user_id, $target_user_id) {
    // Get current user role
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get target user role
    $stmt2 = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt2->bind_param("i", $target_user_id);
    $stmt2->execute();
    $target = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    
    if (!$current || !$target) return false;
    
    $current_level = getRoleInfo($current['role'])['level'] ?? 0;
    $target_level = getRoleInfo($target['role'])['level'] ?? 0;
    
    // Can only manage users with lower level
    return $current_level > $target_level;
}

?>

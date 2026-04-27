<?php
$pageTitle = "Activity Logs | Admin Dashboard";
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$filter_type = $_GET['type'] ?? 'all';
$limit = 200;

// Get activity logs
if ($filter_type === 'all') {
    $stmt = $conn->prepare("
        SELECT al.*, u.full_name as actor_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
} else {
    $stmt = $conn->prepare("
        SELECT al.*, u.full_name as actor_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        WHERE al.action_type = ?
        ORDER BY al.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("si", $filter_type, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$action_types = [
    'report_lost' => 'Report Lost Item',
    'report_found' => 'Report Found Item',
    'claim' => 'Submit Claim',
    'approve' => 'Approve Claim',
    'reject' => 'Reject Claim',
    'message' => 'Send Message',
    'verify_claim' => 'Verify Claim',
    'award_points' => 'Award Points',
    'role_changed' => 'Role Changed',
    'emergency_alert_created' => 'Emergency Alert Created',
    'auction_created' => 'Auction Created'
];
?>

<div class="container py-4">
    <h4 class="mb-4"><i class="bi bi-journal-text"></i> Activity Audit Trail</h4>
    
    <!-- Filters -->
    <div class="mb-3">
        <div class="btn-group" role="group">
            <a href="?type=all" class="btn <?= $filter_type === 'all' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">All</a>
            <a href="?type=report_lost" class="btn <?= $filter_type === 'report_lost' ? 'btn-danger' : 'btn-outline-danger' ?> btn-sm">Lost Reports</a>
            <a href="?type=report_found" class="btn <?= $filter_type === 'report_found' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">Found Reports</a>
            <a href="?type=claim" class="btn <?= $filter_type === 'claim' ? 'btn-warning' : 'btn-outline-warning' ?> btn-sm">Claims</a>
            <a href="?type=verify_claim" class="btn <?= $filter_type === 'verify_claim' ? 'btn-success' : 'btn-outline-success' ?> btn-sm">Verifications</a>
        </div>
    </div>
    
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <small><?= date('M j, g:i A', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <?php if ($log['actor_name']): ?>
                                    <small><?= htmlspecialchars($log['actor_name']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">[System]</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <span class="badge bg-<?= 
                                        strpos($log['action_type'], 'report') !== false ? 'info' :
                                        (strpos($log['action_type'], 'approve') !== false ? 'success' :
                                        (strpos($log['action_type'], 'reject') !== false ? 'danger' : 'secondary'))
                                    ?>">
                                        <?= $action_types[$log['action_type']] ?? ucfirst(str_replace('_', ' ', $log['action_type'])) ?>
                                    </span>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= htmlspecialchars($log['action']) ?>
                                    <?php if ($log['related_item_id']): ?>
                                        (Item: <?= $log['related_item_id'] ?>)
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-3 text-muted small">
        <p>Showing <?= count($logs) ?> activity logs (Last 200 records)</p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

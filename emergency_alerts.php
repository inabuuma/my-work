<?php
$pageTitle = "Emergency Alerts | Admin Dashboard";
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/emergency_mode.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$admin_id = (int)$_SESSION['user_id'];
$hostel_id = (int)($_GET['hostel_id'] ?? 1);

// Handle resolving alert
if ($_POST['action'] === 'resolve' && isset($_POST['alert_id'])) {
    $alert_id = (int)$_POST['alert_id'];
    resolveEmergencyAlert($conn, $alert_id);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Get active alerts
$stmt = $conn->prepare("
    SELECT ea.*, u.full_name as reporter_name, u.phone, li.title as item_title, li.category, li.location, li.image_paths
    FROM emergency_alerts ea
    JOIN users u ON ea.user_id = u.user_id
    LEFT JOIN lost_items li ON ea.item_id = li.item_id
    WHERE ea.is_resolved = 0
    ORDER BY ea.urgency_level DESC, ea.created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();
$active_alerts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get resolved alerts
$resolved_stmt = $conn->prepare("
    SELECT ea.*, u.full_name as reporter_name, li.title as item_title
    FROM emergency_alerts ea
    JOIN users u ON ea.user_id = u.user_id
    LEFT JOIN lost_items li ON ea.item_id = li.item_id
    WHERE ea.is_resolved = 1
    ORDER BY ea.resolved_at DESC
    LIMIT 20
");

$resolved_stmt->execute();
$resolved_result = $resolved_stmt->get_result();
$resolved_alerts = $resolved_result->fetch_all(MYSQLI_ASSOC);
$resolved_stmt->close();
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h4><i class="bi bi-exclamation-triangle"></i> Emergency Alerts</h4>
            <small class="text-danger">High-priority items requiring immediate attention</small>
        </div>
        <div class="col-auto">
            <span class="badge bg-danger fs-6"><?= count($active_alerts) ?> Active</span>
            <span class="badge bg-secondary fs-6"><?= count($resolved_alerts) ?> Resolved</span>
        </div>
    </div>
    
    <!-- Active Alerts -->
    <div class="mb-5">
        <h5 class="mb-3">🚨 Active Emergency Alerts</h5>
        <?php if (empty($active_alerts)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> No active emergency alerts at the moment.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($active_alerts as $alert): ?>
                    <div class="col-lg-6 mb-3">
                        <div class="card border-danger border-2">
                            <div class="card-header bg-danger text-white">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0">🚨 <?= htmlspecialchars($alert['alert_title']) ?></h6>
                                        <small>Reported by: <?= htmlspecialchars($alert['reporter_name']) ?></small>
                                    </div>
                                    <span class="badge bg-light text-dark">HIGH</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="small mb-2"><?= htmlspecialchars($alert['alert_description']) ?></p>
                                
                                <div class="bg-light p-2 rounded small mb-2">
                                    <strong>Item:</strong> <?= htmlspecialchars($alert['item_title']) ?><br>
                                    <strong>Category:</strong> <?= htmlspecialchars($alert['category']) ?><br>
                                    <strong>Location:</strong> <?= htmlspecialchars($alert['location']) ?><br>
                                    <strong>Contact:</strong> <?= htmlspecialchars($alert['phone']) ?>
                                </div>
                                
                                <div class="alert alert-warning small mb-2">
                                    <i class="bi bi-info-circle"></i> This alert has been broadcast to all residents.
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="alert_id" value="<?= $alert['alert_id'] ?>">
                                    <input type="hidden" name="action" value="resolve">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="bi bi-check-circle"></i> Mark Resolved
                                    </button>
                                </form>
                            </div>
                            <div class="card-footer text-muted small">
                                <?= date('M j, Y g:i A', strtotime($alert['created_at'])) ?>
                                (<?= round((time() - strtotime($alert['created_at'])) / 3600) ?> hours ago)
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Resolved Alerts -->
    <div>
        <h5 class="mb-3">✓ Recently Resolved</h5>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Alert</th>
                            <th>Item</th>
                            <th>Reporter</th>
                            <th>Reported</th>
                            <th>Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resolved_alerts as $alert): ?>
                            <tr>
                                <td><small><?= htmlspecialchars(substr($alert['alert_title'], 0, 30)) ?></small></td>
                                <td><small><?= htmlspecialchars($alert['item_title']) ?></small></td>
                                <td><small><?= htmlspecialchars($alert['reporter_name']) ?></small></td>
                                <td><small><?= date('M j, g:i A', strtotime($alert['created_at'])) ?></small></td>
                                <td><small><?= date('M j, g:i A', strtotime($alert['resolved_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<?php
$pageTitle = "My Reports | Lost & Found";
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$user_id = (int)$_SESSION['user_id'];
$filter = 'all';

if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
}

// Get user's items
$lost_items = [];
$found_items = [];

if ($filter === 'all' || $filter === 'lost') {
    $stmt = $conn->prepare("SELECT * FROM lost_items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lost_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if ($filter === 'all' || $filter === 'found') {
    $stmt = $conn->prepare("SELECT * FROM found_items WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $found_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-list-ul"></i> My Reports</h4>
        <div class="btn-group" role="group">
            <a href="?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
            <a href="?filter=lost" class="btn <?= $filter === 'lost' ? 'btn-danger' : 'btn-outline-danger' ?>">Lost</a>
            <a href="?filter=found" class="btn <?= $filter === 'found' ? 'btn-primary' : 'btn-outline-primary' ?>">Found</a>
        </div>
    </div>

    <?php if (empty($lost_items) && empty($found_items)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle fs-1"></i>
            <h5 class="mt-3">No Reports Yet</h5>
            <p>You haven't reported any lost or found items yet.</p>
            <div class="mt-3">
                <a href="report_lost.php" class="btn btn-danger me-2">Report Lost Item</a>
                <a href="report_found.php" class="btn btn-primary">Report Found Item</a>
            </div>
        </div>
    <?php else: ?>
        <?php if ($filter === 'all' || $filter === 'lost'): ?>
            <?php if (!empty($lost_items)): ?>
                <div class="mb-5">
                    <h5 class="mb-3 text-danger"><i class="bi bi-exclamation-circle"></i> Lost Items (<?= count($lost_items) ?>)</h5>
                    <div class="row">
                        <?php foreach ($lost_items as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="badge bg-danger">Lost</span>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($item['created_at'])) ?></small>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                        <p class="card-text small text-muted"><?= htmlspecialchars(substr($item['description'], 0, 80)) ?>...</p>
                                        <div class="small">
                                            <div><strong>Category:</strong> <?= htmlspecialchars($item['category']) ?></div>
                                            <div><strong>Location:</strong> <?= htmlspecialchars($item['location']) ?></div>
                                            <div><strong>Date Lost:</strong> <?= date('M j, Y', strtotime($item['date_lost'])) ?></div>
                                            <div><strong>Status:</strong> 
                                                <span class="badge bg-<?= $item['status'] === 'lost' ? 'warning' : 'success' ?>">
                                                    <?= ucfirst($item['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if ($item['image_path']): ?>
                                            <div class="mt-2">
                                                <img src="../uploads/<?= htmlspecialchars($item['image_path']) ?>" 
                                                     class="img-thumbnail" style="max-height: 100px;" alt="Item image">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($filter === 'all' || $filter === 'found'): ?>
            <?php if (!empty($found_items)): ?>
                <div class="mb-5">
                    <h5 class="mb-3 text-primary"><i class="bi bi-bag-check"></i> Found Items (<?= count($found_items) ?>)</h5>
                    <div class="row">
                        <?php foreach ($found_items as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">Found</span>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($item['created_at'])) ?></small>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                        <p class="card-text small text-muted"><?= htmlspecialchars(substr($item['description'], 0, 80)) ?>...</p>
                                        <div class="small">
                                            <div><strong>Category:</strong> <?= htmlspecialchars($item['category']) ?></div>
                                            <div><strong>Location:</strong> <?= htmlspecialchars($item['location']) ?></div>
                                            <div><strong>Date Found:</strong> <?= date('M j, Y', strtotime($item['date_found'])) ?></div>
                                            <div><strong>Status:</strong> 
                                                <span class="badge bg-<?= $item['status'] === 'found' ? 'warning' : 'success' ?>">
                                                    <?= ucfirst($item['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if ($item['image_path']): ?>
                                            <div class="mt-2">
                                                <img src="../uploads/<?= htmlspecialchars($item['image_path']) ?>" 
                                                     class="img-thumbnail" style="max-height: 100px;" alt="Item image">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

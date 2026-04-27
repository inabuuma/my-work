<?php
$pageTitle = "Lost Reports | Lost & Found";
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$category = '';
$status = '';

if (isset($_GET['category'])) {
    $category = $_GET['category'];
}
if (isset($_GET['status'])) {
    $status = $_GET['status'];
}

// Build query
$query = "SELECT li.*, u.full_name FROM lost_items li 
          JOIN users u ON li.user_id = u.user_id WHERE 1=1";
$params = [];
$types = "";

if (!empty($category)) {
    $query .= " AND li.category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($status)) {
    $query .= " AND li.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY li.created_at DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $lost_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $lost_items = [];
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-exclamation-circle"></i> Lost Reports</h4>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <option value="Electronics" <?= $category === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                        <option value="Clothing" <?= $category === 'Clothing' ? 'selected' : '' ?>>Clothing</option>
                        <option value="Books" <?= $category === 'Books' ? 'selected' : '' ?>>Books</option>
                        <option value="Jewelry" <?= $category === 'Jewelry' ? 'selected' : '' ?>>Jewelry</option>
                        <option value="Documents" <?= $category === 'Documents' ? 'selected' : '' ?>>Documents</option>
                        <option value="Keys" <?= $category === 'Keys' ? 'selected' : '' ?>>Keys</option>
                        <option value="Wallet" <?= $category === 'Wallet' ? 'selected' : '' ?>>Wallet</option>
                        <option value="Phone" <?= $category === 'Phone' ? 'selected' : '' ?>>Phone</option>
                        <option value="Other" <?= $category === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="lost" <?= $status === 'lost' ? 'selected' : '' ?>>Lost</option>
                        <option value="recovered" <?= $status === 'recovered' ? 'selected' : '' ?>>Recovered</option>
                        <option value="claimed" <?= $status === 'claimed' ? 'selected' : '' ?>>Claimed</option>
                    </select>
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($lost_items)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle fs-1"></i>
            <h5 class="mt-3">No Lost Reports Found</h5>
            <p>There are no lost reports matching your criteria.</p>
        </div>
    <?php else: ?>
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
                            <p class="card-text small text-muted"><?= htmlspecialchars(substr($item['description'], 0, 100)) ?>...</p>
                            <div class="small">
                                <div><strong>Reported by:</strong> <?= htmlspecialchars($item['full_name']) ?></div>
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
        
        <div class="text-center mt-3">
            <small class="text-muted">Showing <?= count($lost_items) ?> lost report<?= count($lost_items) > 1 ? 's' : '' ?></small>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>

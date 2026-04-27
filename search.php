<?php
$pageTitle = "Search Items | Lost & Found";
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$search_term = '';
$category = '';
$item_type = 'all';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search_term = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $item_type = trim($_GET['item_type'] ?? 'all');
    
    if (!empty($search_term) || !empty($category)) {
        $user_id = (int)$_SESSION['user_id'];
        
        // Build search query
        $params = [];
        $types = '';
        
        if ($item_type === 'lost' || $item_type === 'all') {
            $query = "SELECT 'lost' as type, item_id, title, description, category, location, date_lost as item_date, status, image_path, created_at 
                     FROM lost_items WHERE (title LIKE ? OR description LIKE ?)";
            $params = ["%$search_term%", "%$search_term%"];
            $types = "ss";
            
            if (!empty($category)) {
                $query .= " AND category = ?";
                $params[] = $category;
                $types .= "s";
            }
        }
        
        if ($item_type === 'found' || $item_type === 'all') {
            if ($item_type === 'all' && !empty($query)) {
                $query .= " UNION ";
            }
            
            $found_query = "SELECT 'found' as type, item_id, title, description, category, location, date_found as item_date, status, image_path, created_at 
                           FROM found_items WHERE (title LIKE ? OR description LIKE ?)";
            
            if ($item_type === 'all') {
                $found_params = ["%$search_term%", "%$search_term%"];
                $found_types = "ss";
                
                if (!empty($category)) {
                    $found_query .= " AND category = ?";
                    $found_params[] = $category;
                    $found_types .= "s";
                }
                
                $query .= $found_query;
                $params = array_merge($params, $found_params);
                $types .= $found_types;
            } else {
                $query = $found_query;
                $params = ["%$search_term%", "%$search_term%"];
                $types = "ss";
                
                if (!empty($category)) {
                    $query .= " AND category = ?";
                    $params[] = $category;
                    $types .= "s";
                }
            }
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-search"></i> Search Lost & Found Items</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Keywords</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search_term) ?>" 
                                   placeholder="Enter keywords...">
                        </div>
                        
                        <div class="col-md-3">
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
                        
                        <div class="col-md-3">
                            <label for="item_type" class="form-label">Item Type</label>
                            <select class="form-select" id="item_type" name="item_type">
                                <option value="all" <?= $item_type === 'all' ? 'selected' : '' ?>>All Items</option>
                                <option value="lost" <?= $item_type === 'lost' ? 'selected' : '' ?>>Lost Items</option>
                                <option value="found" <?= $item_type === 'found' ? 'selected' : '' ?>>Found Items</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && (empty($search_term) && empty($category))): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Enter search terms or select a category to find items.
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($results)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> No items found matching your search criteria.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($results as $item): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?= $item['type'] === 'lost' ? 'danger' : 'primary' ?>">
                                        <?= ucfirst($item['type']) ?>
                                    </span>
                                    <small class="text-muted"><?= date('M j, Y', strtotime($item['created_at'])) ?></small>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                    <p class="card-text small text-muted"><?= htmlspecialchars(substr($item['description'], 0, 100)) ?>...</p>
                                    <div class="small">
                                        <div><strong>Category:</strong> <?= htmlspecialchars($item['category']) ?></div>
                                        <div><strong>Location:</strong> <?= htmlspecialchars($item['location']) ?></div>
                                        <div><strong>Date:</strong> <?= date('M j, Y', strtotime($item['item_date'])) ?></div>
                                        <div><strong>Status:</strong> 
                                            <span class="badge bg-<?= $item['status'] === 'lost' || $item['status'] === 'found' ? 'warning' : 'success' ?>">
                                                <?= ucfirst($item['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

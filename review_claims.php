<?php
$pageTitle = "Claims Review | Admin Dashboard";
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/claim_verification.php';
requireAdmin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$admin_id = (int)$_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'verified_pending_approval';
$success = $error = '';

// Handle claim approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'])) {
    $claim_id = (int)$_POST['claim_id'];
    $action = $_POST['action'] ?? 'approve';
    
    if (verifyAndApproveClaim($conn, $claim_id, $admin_id, $action === 'approve')) {
        $success = "Claim has been " . ($action === 'approve' ? 'approved' : 'rejected') . ".";
    } else {
        $error = "Error processing claim.";
    }
}

// Get claims
if ($status_filter === 'all') {
    $stmt = $conn->prepare("
        SELECT c.*, u.full_name as claimant_name, li.title as item_title, li.category, li.location
        FROM claims c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN lost_items li ON c.item_id = li.item_id
        ORDER BY c.created_at DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT c.*, u.full_name as claimant_name, li.title as item_title, li.category, li.location
        FROM claims c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN lost_items li ON c.item_id = li.item_id
        WHERE c.claim_status = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("s", $status_filter);
}

$stmt->execute();
$result = $stmt->get_result();
$claims = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h4><i class="bi bi-person-check"></i> Review Claims</h4>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <a href="?status=pending" class="btn <?= $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">Pending</a>
                <a href="?status=verified_pending_approval" class="btn <?= $status_filter === 'verified_pending_approval' ? 'btn-warning' : 'btn-outline-warning' ?>">Verified - Need Approval</a>
                <a href="?status=approved" class="btn <?= $status_filter === 'approved' ? 'btn-success' : 'btn-outline-success' ?>">Approved</a>
                <a href="?status=all" class="btn <?= $status_filter === 'all' ? 'btn-secondary' : 'btn-outline-secondary' ?>">All</a>
            </div>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <?php foreach ($claims as $claim): ?>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1"><strong><?= htmlspecialchars($claim['claimant_name']) ?></strong></h6>
                                <small class="text-muted">Claiming: <?= htmlspecialchars($claim['item_title']) ?></small>
                            </div>
                            <span class="badge bg-<?= 
                                $claim['claim_status'] === 'approved' ? 'success' : 
                                ($claim['claim_status'] === 'rejected' ? 'danger' : 
                                ($claim['claim_status'] === 'verified_pending_approval' ? 'warning' : 'primary')) 
                            ?>">
                                <?= ucfirst(str_replace('_', ' ', $claim['claim_status'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="small mb-3"><strong>Claimant's Reason:</strong><br><?= htmlspecialchars(substr($claim['description'], 0, 150)) ?>...</p>
                        
                        <div class="bg-light p-3 rounded mb-3">
                            <h6 class="mb-2"><i class="bi bi-question-circle"></i> Verification Answers:</h6>
                            <p class="small mb-2"><strong>Q1: <?= htmlspecialchars($claim['question_1']) ?></strong><br>
                            A: <?= htmlspecialchars($claim['answer_1'] ?? '[Not answered]') ?></p>
                            <p class="small mb-2"><strong>Q2: <?= htmlspecialchars($claim['question_2']) ?></strong><br>
                            A: <?= htmlspecialchars($claim['answer_2'] ?? '[Not answered]') ?></p>
                            <p class="small mb-0"><strong>Q3: <?= htmlspecialchars($claim['question_3']) ?></strong><br>
                            A: <?= htmlspecialchars($claim['answer_3'] ?? '[Not answered]') ?></p>
                        </div>
                        
                        <?php if ($claim['claim_status'] !== 'approved' && $claim['claim_status'] !== 'rejected'): ?>
                            <div class="d-grid gap-2 d-md-flex">
                                <form method="POST" class="flex-grow-1">
                                    <input type="hidden" name="claim_id" value="<?= $claim['claim_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success w-100">✓ Approve</button>
                                </form>
                                <form method="POST" class="flex-grow-1">
                                    <input type="hidden" name="claim_id" value="<?= $claim['claim_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger w-100">✗ Reject</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                        <?= date('M j, Y g:i A', strtotime($claim['created_at'])) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($claims)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No claims found in this status.
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>

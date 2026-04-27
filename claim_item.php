<?php
$pageTitle = "Claim Item | Lost & Found";
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/claim_verification.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';

$success = '';
$error = '';
$claim_data = null;

if (isset($_GET['step']) && $_GET['step'] === 'verify') {
    // Step 2: Answer verification questions
    $claim_id = (int)($_GET['claim_id'] ?? 0);
    if ($claim_id) {
        $claim_data = getClaimWithQuestions($conn, $claim_id);
        if (!$claim_data || $claim_data['user_id'] != $_SESSION['user_id']) {
            header("Location: search.php");
            exit();
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answers'])) {
        $answers = $_POST['answers'];
        if (submitClaimAnswers($conn, $claim_id, $answers[1] ?? '', $answers[2] ?? '', $answers[3] ?? '')) {
            $success = "Thank you! Your answers have been submitted. An admin will review your claim.";
            $claim_data = null;
        }
    }
} else {
    // Step 1: Initial claim creation
    $item_id = (int)($_GET['item_id'] ?? 0);
    $claim_type = $_GET['type'] ?? 'found';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $item_id && in_array($claim_type, ['lost', 'found'])) {
        $description = trim($_POST['description'] ?? '');
        $user_id = (int)$_SESSION['user_id'];
        
        if (empty($description)) {
            $error = "Please provide a description.";
        } else {
            $claim_id = createClaimWithQuestions($conn, $item_id, $user_id, $claim_type, $description);
            if ($claim_id) {
                $claim_data = getClaimWithQuestions($conn, $claim_id);
                $success = "Claim initiated! Please answer the verification questions.";
            }
        }
    } elseif (!isset($_GET['item_id'])) {
        $error = "No item selected.";
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-shield-check"></i> Claim Item - Verification Required</h5>
                </div>
                <div class="card-body">
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
                    
                    <?php if ($claim_data && !isset($_GET['step'])): ?>
                        <!-- Initial Claim Description -->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Why are you claiming this item?</label>
                                <textarea class="form-control" name="description" rows="4" placeholder="Explain why this is your item or why you found it..." required></textarea>
                                <small class="text-muted">Be honest and detailed. Admin will verify based on your answers below.</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h6 class="mb-3"><i class="bi bi-question-circle"></i> Verification Questions</h6>
                            <p class="text-muted small">Answer these questions to prove ownership/finding:</p>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">1. <?= htmlspecialchars($claim_data['question_1']) ?></label>
                                <textarea class="form-control" name="answers[1]" rows="2" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">2. <?= htmlspecialchars($claim_data['question_2']) ?></label>
                                <textarea class="form-control" name="answers[2]" rows="2" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">3. <?= htmlspecialchars($claim_data['question_3']) ?></label>
                                <textarea class="form-control" name="answers[3]" rows="2" required></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <small><i class="bi bi-info-circle"></i> Your answers will be reviewed by our admin team. Be truthful - false claims may result in penalties.</small>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="search.php" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-warning">Submit Verification</button>
                            </div>
                        </form>
                    <?php elseif ($claim_data && isset($_GET['step']) && $_GET['step'] === 'verify'): ?>
                        <!-- Showing submitted claim -->
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Your claim has been submitted successfully!
                        </div>
                        <p>Your verification answers have been sent to the admin team. You'll receive a notification once your claim is reviewed.</p>
                        <a href="search.php" class="btn btn-primary">Back to Search</a>
                    <?php else: ?>
                        <p>To claim an item, search for it first and then click "Claim This Item".</p>
                        <a href="search.php" class="btn btn-primary"><i class="bi bi-search"></i> Search Items</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

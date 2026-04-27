<?php
$pageTitle = "Home | Zing Mooners Lost & Found";
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>
<div class="container py-5 text-center">
  <div class="py-4">
    <i class="bi bi-search-heart display-1 text-primary"></i>
    <h1 class="display-5 fw-bold text-primary mt-3">Lost &amp; Found System</h1>
    <p class="lead text-muted">Zing Mooners Zest Hostel &mdash; Kasindikwa, Fort Portal City</p>
    <p class="mb-4 text-secondary">Report lost items, submit found items, and track your belongings all in one place.</p>
    <a href="register.php" class="btn btn-primary btn-lg me-2"><i class="bi bi-person-plus"></i> Get Started</a>
    <a href="login.php" class="btn btn-outline-primary btn-lg"><i class="bi bi-box-arrow-in-right"></i> Login</a>
  </div>
  <div class="row g-4 mt-4">
    <div class="col-md-4"><div class="card p-4 h-100"><i class="bi bi-exclamation-circle fs-1 text-danger mb-2"></i><h5>Report Lost Items</h5><p class="text-muted small">Quickly report any item you have lost with a description and last known location.</p></div></div>
    <div class="col-md-4"><div class="card p-4 h-100"><i class="bi bi-bag-check fs-1 text-primary mb-2"></i><h5>Submit Found Items</h5><p class="text-muted small">Found something? Submit it so the rightful owner can be notified and reunited with it.</p></div></div>
    <div class="col-md-4"><div class="card p-4 h-100"><i class="bi bi-bell fs-1 text-success mb-2"></i><h5>Get Notified</h5><p class="text-muted small">Receive instant alerts when a match is found for your lost or submitted item.</p></div></div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>

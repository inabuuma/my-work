<?php
$pageTitle = "My Dashboard | Lost & Found";
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/header.php';
require_once '../includes/navbar.php';
$uid = (int)$_SESSION['user_id'];
$lost_count  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items  WHERE user_id=$uid"))[0];
$found_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM found_items WHERE user_id=$uid"))[0];
$recov_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM lost_items  WHERE user_id=$uid AND status='recovered'"))[0];
$notif_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM notifications WHERE user_id=$uid AND is_read=0"))[0];
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Welcome, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong></h4>
    <?php if ($notif_count > 0): ?>
      <span class="badge bg-danger fs-6"><i class="bi bi-bell"></i> <?= $notif_count ?> new notification<?= $notif_count > 1 ? 's' : '' ?></span>
    <?php endif; ?>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card stat-card lost      p-3 text-center"><h6 class="text-muted small">Lost Reports</h6>    <h2 class="text-danger mb-0"><?= $lost_count ?></h2></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card found     p-3 text-center"><h6 class="text-muted small">Found Reports</h6>   <h2 class="text-primary mb-0"><?= $found_count ?></h2></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card recovered p-3 text-center"><h6 class="text-muted small">Recovered</h6>        <h2 class="text-success mb-0"><?= $recov_count ?></h2></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card matched   p-3 text-center"><h6 class="text-muted small">Notifications</h6>   <h2 class="text-warning mb-0"><?= $notif_count ?></h2></div></div>
  </div>
  <div class="row g-3">
    <div class="col-6 col-md-3"><a href="report_lost.php"  class="btn btn-danger   w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-exclamation-circle fs-3"></i><small>Report Lost</small></a></div>
    <div class="col-6 col-md-3"><a href="report_found.php" class="btn btn-primary  w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-bag-check fs-3"></i><small>Report Found</small></a></div>
    <div class="col-6 col-md-3"><a href="my_items.php"     class="btn btn-secondary w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-list-ul fs-3"></i><small>My Reports</small></a></div>
    <div class="col-6 col-md-3"><a href="search.php"       class="btn btn-success  w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-search fs-3"></i><small>Search Items</small></a></div>
    <div class="col-6 col-md-3"><a href="messages.php"     class="btn btn-info    w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-chat-dots fs-3"></i><small>Messages</small></a></div>
    <div class="col-6 col-md-3"><a href="leaderboard.php"  class="btn btn-warning w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-trophy fs-3"></i><small>Leaderboard</small></a></div>
    <div class="col-6 col-md-3"><a href="heatmap.php"      class="btn btn-danger  w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-geo-alt fs-3"></i><small>Hotspots</small></a></div>
    <div class="col-6 col-md-3"><a href="rewards.php"      class="btn btn-success w-100 py-3 d-flex flex-column align-items-center gap-1"><i class="bi bi-gift fs-3"></i><small>Rewards</small></a></div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>

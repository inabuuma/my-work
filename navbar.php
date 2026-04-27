<?php require_once __DIR__ . '/auth.php'; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/lost_found_project/lost_found/index.php">
      <i class="bi bi-search-heart"></i> Zing Mooners L&F
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        <?php if (isLoggedIn()): ?>
          <li class="nav-item">
            <a class="nav-link" href="/lost_found_project/lost_found/resident/dashboard.php">
              <i class="bi bi-house"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/lost_found_project/lost_found/resident/report_lost.php">
              <i class="bi bi-exclamation-circle"></i> Report Lost
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/lost_found_project/lost_found/resident/report_found.php">
              <i class="bi bi-bag-check"></i> Report Found
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/lost_found_project/lost_found/resident/search.php">
              <i class="bi bi-search"></i> Search
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/lost_found_project/lost_found/resident/messages.php">
              <i class="bi bi-chat-dots"></i> Messages
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/lost_found_project/lost_found/resident/leaderboard.php">
              <i class="bi bi-trophy"></i> Leaderboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/lost_found_project/lost_found/resident/heatmap.php">
              <i class="bi bi-geo-alt"></i> Hotspots
            </a>
          </li>
          <?php if (isAdmin()): ?>
            <li class="nav-item">
              <a class="nav-link text-warning" href="/lost_found_project/lost_found/admin/dashboard.php">
                <i class="bi bi-shield-lock"></i> Admin
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link text-danger" href="/lost_found_project/lost_found/logout.php">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/lost_found_project/lost_found/index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="/lost_found_project/lost_found/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="/lost_found_project/lost_found/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

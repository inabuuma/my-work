<?php
$pageTitle = "Login | Zing Mooners Lost & Found";
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header("Location: resident/dashboard.php"); exit(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, password_hash, role FROM users WHERE email = ? AND is_active = 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        header("Location: " . ($user['role'] === 'admin' ? "admin/dashboard.php" : "resident/dashboard.php"));
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card">
        <div class="card-header bg-primary text-white text-center"><h5 class="mb-0"><i class="bi bi-box-arrow-in-right"></i> Login</h5></div>
        <div class="card-body p-4">
          <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
          <form method="POST">
            <div class="mb-3"><label class="form-label fw-semibold">Email Address</label><input type="email" name="email" class="form-control" required placeholder="you@example.com"/></div>
            <div class="mb-3"><label class="form-label fw-semibold">Password</label><input type="password" name="password" class="form-control" required placeholder="Enter password"/></div>
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Login</button>
          </form>
          <p class="text-center mt-3 mb-0 small">No account? <a href="register.php">Register here</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>

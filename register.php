<?php
$pageTitle = "Register | Zing Mooners Lost & Found";
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header("Location: resident/dashboard.php"); exit(); }
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $room    = trim($_POST['room_number'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, 's', $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "That email is already registered. <a href='login.php'>Login instead?</a>";
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins  = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, password_hash, room_number) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($ins, 'sssss', $name, $email, $phone, $hash, $room);
            if (mysqli_stmt_execute($ins)) {
                $success = "Account created! <a href='login.php'>Click here to login</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header bg-success text-white text-center"><h5 class="mb-0"><i class="bi bi-person-plus"></i> Create Resident Account</h5></div>
        <div class="card-body p-4">
          <?php if ($error):   ?><div class="alert alert-danger  alert-dismissible fade show"><?= $error ?>  <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
          <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= $success ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
          <form method="POST">
            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Full Name</label><input type="text" name="full_name" class="form-control" required/></div>
              <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Room Number</label><input type="text" name="room_number" class="form-control" placeholder="e.g. A12"/></div>
            </div>
            <div class="mb-3"><label class="form-label fw-semibold">Email Address</label><input type="email" name="email" class="form-control" required/></div>
            <div class="mb-3"><label class="form-label fw-semibold">Phone Number</label><input type="tel" name="phone" class="form-control" required placeholder="07XXXXXXXX"/></div>
            <div class="row">
              <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Password</label><input type="password" name="password" class="form-control" required/></div>
              <div class="col-md-6 mb-3"><label class="form-label fw-semibold">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required/></div>
            </div>
            <button type="submit" class="btn btn-success w-100"><i class="bi bi-person-check"></i> Register</button>
          </form>
          <p class="text-center mt-3 mb-0 small">Already have an account? <a href="login.php">Login here</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>

<?php
session_start();

if (isset($_SESSION['user'])) {
    header("Location: view_assignments.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == "teacher" && $password == "school123") {
        $_SESSION['user'] = $username;
        header("Location: submit_assignment.html");
    } else {
        echo "Invalid login!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .university-header {
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            width: 90%;
            max-width: 600px;
        }
        .university-header h1 {
            margin: 0;
            font-size: 28px;
            color: #2c3e50;
        }
        .university-header p {
            margin: 5px 0;
            font-size: 16px;
            color: #34495e;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-card h2 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        .login-card input {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .login-card input:focus {
            outline: none;
            border-color: #667eea;
        }
        .login-card button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .login-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .error {
            color: #e74c3c;
            margin: 10px 0;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="university-header">
        <h1>FORTPORTAL SECONDARY SCHOOL</h1>
        <p>The Fear of The Lord is The Beginning of Wisdom </p>
        <p>SCHOOL ASSIGNMENT MANAGEMENT SYSTEM</p>
    </div>
    <div class="login-card">
        <h2>Teacher Login</h2>
        <?php if (isset($_POST['username']) && !isset($_SESSION['user'])): ?>
            <div class="error">Invalid username or password.</div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
                <a href="index.php" class="back-home">← Back to Home</a>

        </form>
    </div>
</body>
</html>
<?php
session_start();
// Redirect whether the user is already logged in
if (isset($_SESSION['teacher_logged_in'])) 
    {
    header('Location: submit_assignment.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>SCHOOL ASSIGNMENT MANAGEMENT SYSTEM</title>
<style>
    /* Base Reset */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to right, #e0f2fe, #dbeafe);
        color: #222;
        line-height: 1.6;
    }

    /* Navbar */
    .navbar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 35px 20px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .navbar h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 5px;
        letter-spacing: 1px;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
    }
    .navbar p {
        font-size: 16px;
        opacity: 0.9;
        letter-spacing: 0.5px;
    }

    /* Hero Section */
    .hero {
        text-align: center;
        padding: 100px 20px;
        background: linear-gradient(135deg, #bfdbfe, #93c5fd);
        border-radius: 12px;
        margin: 30px auto;
        max-width: 900px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .hero:hover {
        transform: translateY(-3px);
    }
    .hero h2 {
        font-size: 46px;
        color: #1e3a8a;
        margin-bottom: 20px;
    }
    .hero p {
        max-width: 750px;
        margin: 0 auto 35px;
        font-size: 18px;
        color: #1e293b;
    }
    .btn {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 16px 36px;
        text-decoration: none;
        border-radius: 14px;
        font-size: 18px;
        font-weight: 600;
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        transition: 0.3s ease;
    }
    .btn:hover {
        background: #15318b;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }

    /* Features Section */
    .features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        padding: 60px 20px;
        max-width: 1100px;
        margin: auto;
    }
    .card {
        background: white;
        padding: 30px 25px;
        border-radius: 18px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.18);
    }
    .card h3 {
        margin-bottom: 18px;
        font-size: 22px;
        color: #631ce8;
    }
    .card p {
        font-size: 15px;
        color: #334155;
        line-height: 1.5;
    }

    /* Add icons to cards */
    .card::before {
        content: "";
        font-size: 30px;
        display: block;
        margin-bottom: 12px;
    }
    .card:nth-child(2)::before { content: ""; }
    .card:nth-child(3)::before { content: ""; }
    .card:nth-child(4)::before { content: ""; }

    /* Footer */
    footer {
        margin-top: 60px;
        text-align: center;
        padding: 25px;
        background: #e5e7eb;
        color: #444;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    /* Responsive */
    @media (max-width: 700px) {
        .hero h2 { font-size: 34px; }
        .hero p { font-size: 16px; }
    }
</style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <h1>SCHOOL ASSIGNMENT MANAGEMENT SYSTEM</h1>
        <p>Teachers feel at home</p>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <h2>Smart, Secure & Interactive Assignment Management</h2>
        <p>A professional platform to validate, store, search, and manage student assignments efficiently.</p>
        <a href="login.php" class="btn">Get Started</a>
    </section>

    <!-- Features Section -->
    <section class="features">
        <a href="login.php" class="card">
            <h3>Smart Validation</h3>
            <p>Ensures accurate student details and assignment data before submission.</p>
        </a>
        <a href="view_assignments.php" class="card">
            <h3>Fast Search</h3>
            <p>Search assignments quickly by student name or subject.</p>
        </a>
   
    </section>

    <!-- Footer -->
    <footer>
        Developed by BIT 2206 Group 1 | Mountains of the Moon University
    </footer>
</body>
</html>
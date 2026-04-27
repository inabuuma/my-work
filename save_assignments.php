<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
// Connect to database
$conn = mysqli_connect("localhost", "root", "", "school_assignments_db");

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get form data
$name = trim($_POST['student_name']);
$id = trim($_POST['student_id']);
$subject = $_POST['subject'];
$title = trim($_POST['assignment_title']);
$date = $_POST['due_date'];
$marks = $_POST['marks'];
$remarks = $_POST['remarks'];

// Validation
$errors = [];

// Name
if (empty($name) || !preg_match('/^[A-Za-z ]{3,}$/', $name)) {
    $errors[] = "Student name must be at least 3 letters and contain only letters";
}

// Student ID
if (!preg_match('/^[0-9]{8}$/', $id)) {
    $errors[] = "Student ID must be exactly 8 digits";
}

// Subject
if (empty($subject)) {
    $errors[] = "Please select a subject";
}

// Assignment Title
if (strlen($title) < 5) {
    $errors[] = "Assignment title must be at least 5 characters";
}

// Marks
if ($marks < 0 || $marks > 100) {
    $errors[] = "Marks must be between 0 and 100";
}

// Date
$today = date("Y-m-d");
if ($date < $today) {
    $errors[] = "Due date cannot be in the past";
}

// If errors exist
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<a href='submit_assignment.html'>Go Back</a>";
    exit();
}

// Insert into database
$sql = "INSERT INTO assignments 
(student_name, student_id, subject, assignment_title, due_date, marks, remarks)
VALUES 
('$name', '$id', '$subject', '$title', '$date', '$marks', '$remarks')";

if (mysqli_query($conn, $sql)) {
    echo "<h3>Assignment submitted successfully!</h3>";
    
    echo "<p><strong>Name:</strong> $name</p>";
    echo "<p><strong>ID:</strong> $id</p>";
    echo "<p><strong>Subject:</strong> $subject</p>";
    echo "<p><strong>Title:</strong> $title</p>";
    echo "<p><strong>Due Date:</strong> $date</p>";
    echo "<p><strong>Marks:</strong> $marks</p>";
    echo "<p><strong>Remarks:</strong> $remarks</p>";

    echo "<br><a href='view_assignments.php'>View All Assignments</a> | <a href='logout.php'>Logout</a>";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);

?>

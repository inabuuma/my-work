<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "school_assignments_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Search
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT * FROM assignments 
            WHERE student_name LIKE '%$search%' 
            OR subject LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM assignments";
}

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Assignments</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            min-height: 100vh;
        }
        .university-header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .university-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .university-header p {
            margin: 5px 0;
            font-size: 16px;
        }
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .logout-btn {
            float: right;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
        }
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .card h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
            min-width: 200px;
        }
        .search-bar button {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s ease;
        }
        .search-bar button:hover {
            transform: translateY(-2px);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e8ed;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 18px;
        }
        .add-link {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: transform 0.2s ease;
        }
        .add-link:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="university-header">
        <h1>FORTPORTAL SECONDARY SCHOOL</h1>
        <p>The Fear of The Lord is The Beginning of Wisdom </p>
        <p>SCHOOL ASSIGNMENT MANAGEMENT SYSTEM</p>
    </div>

    <div class="container">
        <a href="logout.php" class="logout-btn">Logout</a>
        <div class="card">
            <h2>View & Search Assignments</h2>

            <form class="search-bar" method="GET">
                <input type="text" name="search" placeholder="Search by student name or subject" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Subject</th>
                            <th>Title</th>
                            <th>Due Date</th>
                            <th>Marks</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td><?php echo htmlspecialchars($row['assignment_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['marks']); ?></td>
                                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No assignments found.</div>
            <?php endif; ?>

            <br>
            <a href="submit_assignment.html" class="add-link">Add New Assignment</a>
        </div>
    </div>

</body>
</html>

<?php
mysqli_close($conn);
?>
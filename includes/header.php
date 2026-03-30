<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Quiz Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-brand">QuizPro</a>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="manage_users.php" class="nav-link">Users</a>
                    <a href="manage_quizzes.php" class="nav-link">All Quizzes</a>
                    <a href="view_results.php" class="nav-link">Reports</a>
                <?php elseif (isTeacher()): ?>
                    <a href="teacher_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="manage_quizzes.php" class="nav-link">My Quizzes</a>
                    <a href="view_results.php" class="nav-link">Student Attempts</a>
                <?php else: ?>
                    <a href="student_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="my_results.php" class="nav-link">History</a>
                    <a href="leaderboard.php" class="nav-link">Leaderboard</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="container">

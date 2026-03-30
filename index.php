<?php
// index.php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

if (isAdmin()) {
    header("Location: admin_dashboard.php");
    exit;
} elseif (isTeacher()) {
    header("Location: teacher_dashboard.php");
    exit;
} else {
    header("Location: student_dashboard.php");
    exit;
}
?>

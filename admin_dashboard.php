<?php
// admin_dashboard.php
require_once 'includes/auth.php';
requireAdmin();

global $db;
$quizzes = $db->selectAll('quizzes');
$users = $db->selectAll('users');
$studentsCount = count(array_filter($users, fn($u) => $u['role'] === 'student'));
$teachersCount = count(array_filter($users, fn($u) => $u['role'] === 'teacher'));
$pendingTeachers = count(array_filter($users, fn($u) => $u['role'] === 'teacher' && isset($u['status']) && $u['status'] === 'pending'));
$results = $db->selectAll('results');

require_once 'includes/header.php';
?>
<div class="card">
    <h2 class="card-title">System Administrator Dashboard</h2>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <div class="card" style="text-align: center;">
        <h3>Total Quizzes</h3>
        <p style="font-size: 2rem; color: var(--primary-color); font-weight: bold;"><?php echo count($quizzes); ?></p>
    </div>
    <div class="card" style="text-align: center;">
        <h3>Students</h3>
        <p style="font-size: 2rem; color: var(--success-color); font-weight: bold;"><?php echo $studentsCount; ?></p>
    </div>
    <div class="card" style="text-align: center;">
        <h3>Teachers</h3>
        <p style="font-size: 2rem; color: #f59e0b; font-weight: bold;"><?php echo $teachersCount; ?></p>
        <?php if ($pendingTeachers > 0): ?>
            <p style="color: var(--danger-color); font-weight: 600;"><?php echo $pendingTeachers; ?> pending approvals</p>
        <?php endif; ?>
    </div>
    <div class="card" style="text-align: center;">
        <h3>Total Submissions</h3>
        <p style="font-size: 2rem; color: var(--primary-color); font-weight: bold;"><?php echo count($results); ?></p>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Quick Actions</h3>
    <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
    <a href="manage_quizzes.php" class="btn btn-primary">Manage Quizzes</a>
    <a href="view_results.php" class="btn btn-primary">View Global Results</a>
</div>

<?php require_once 'includes/footer.php'; ?>

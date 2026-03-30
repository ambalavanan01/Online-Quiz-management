<?php
// view_results.php
require_once 'includes/auth.php';
requireTeacher();

global $db;
$all_results = $db->selectAll('results');

if (isAdmin()) {
    $quizzes = $db->selectAll('quizzes');
    $results = $all_results;
} else {
    $quizzes = $db->findWhere('quizzes', 'created_by', $_SESSION['user_id']);
    $my_quiz_ids = array_map(fn($q) => $q['id'], $quizzes);
    $results = array_filter($all_results, function($r) use ($my_quiz_ids) {
        return in_array($r['quiz_id'], $my_quiz_ids);
    });
}
$users = $db->selectAll('users');

$userMap = [];
foreach ($users as $u) {
    if ($u['role'] === 'student') {
        $userMap[$u['id']] = $u['username'];
    }
}

$quizMap = [];
foreach ($quizzes as $q) {
    $quizMap[$q['id']] = $q['title'];
}

require_once 'includes/header.php';
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card-title"><?php echo isAdmin() ? 'All Student Submissions' : 'Attempts on My Quizzes'; ?></h2>
        <div>
            <a href="export_results.php" class="btn btn-secondary" style="background:var(--success-color); color:white; margin-right: 10px;">Export to CSV</a>
            <?php if (isTeacher() && !isAdmin()): ?>
                <a href="teacher_dashboard.php" class="btn btn-secondary" style="background:#e2e8f0; color:#1e293b;">Dashboard</a>
            <?php else: ?>
                <a href="admin_dashboard.php" class="btn btn-secondary" style="background:#e2e8f0; color:#1e293b;">Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Quiz Taken</th>
                    <th>Score</th>
                    <th>Date Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($results) as $r): ?>
                <tr>
                    <td><?php echo isset($userMap[$r['user_id']]) ? htmlspecialchars($userMap[$r['user_id']]) : 'Unknown'; ?></td>
                    <td><?php echo isset($quizMap[$r['quiz_id']]) ? htmlspecialchars($quizMap[$r['quiz_id']]) : 'Unknown'; ?></td>
                    <td>
                        <strong style="color: <?php echo ($r['total'] > 0 && $r['score']/$r['total'] >= 0.5) ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                            <?php echo $r['score']; ?> / <?php echo $r['total']; ?>
                        </strong>
                        (<?php echo $r['total'] > 0 ? round(($r['score']/$r['total'])*100, 1) : 0; ?>%)
                    </td>
                    <td><?php echo isset($r['submitted_at']) ? htmlspecialchars($r['submitted_at']) : 'N/A'; ?></td>
                    <td>
                        <a href="view_attempt.php?result_id=<?php echo $r['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.8rem;">Evaluate</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($results)): ?>
                <tr><td colspan="5" class="text-center">No submissions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

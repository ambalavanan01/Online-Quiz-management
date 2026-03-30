<?php
// student_dashboard.php
require_once 'includes/auth.php';
requireLogin();
if (isAdmin()) {
    header("Location: admin_dashboard.php");
    exit;
}

global $db;
$all_quizzes = $db->selectAll('quizzes');
$quizzes = array_filter($all_quizzes, function($q) {
    return (!isset($q['status']) || $q['status'] !== 'draft');
});
$results = $db->findWhere('results', 'user_id', $_SESSION['user_id']);

require_once 'includes/header.php';
?>

<div class="card">
    <h2 class="card-title">Student Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
</div>

<div class="card">
    <h3 class="card-title">Available Quizzes</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        <?php foreach ($quizzes as $quiz): ?>
            <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px;">
                <h4 style="margin-bottom: 10px; font-size: 1.2rem;">
                    <?php echo htmlspecialchars($quiz['title']); ?>
                    <?php if(!empty($quiz['password'])): ?>
                        <span title="Password Protected" style="font-size: 0.9rem;">🔒</span>
                    <?php endif; ?>
                </h4>
                <p style="color: var(--text-secondary); margin-bottom: 5px;">Duration: <?php echo (int)$quiz['duration_minutes']; ?> minutes</p>
                
                <?php
                    $attempts = count(array_filter($results, fn($r) => $r['quiz_id'] === $quiz['id']));
                    $max_attempts = isset($quiz['max_attempts']) ? (int)$quiz['max_attempts'] : 1;
                    
                    $now = new DateTime();
                    $start = !empty($quiz['schedule_start']) ? new DateTime($quiz['schedule_start']) : null;
                    $end = !empty($quiz['schedule_end']) ? new DateTime($quiz['schedule_end']) : null;

                    $can_take = true;
                    $reason = '';

                    if ($start && $now < $start) {
                        $can_take = false;
                        $reason = 'Starts: ' . $start->format('M j, g:i A');
                    } elseif ($end && $now > $end) {
                        $can_take = false;
                        $reason = 'Ended: ' . $end->format('M j, g:i A');
                    } elseif ($max_attempts > 0 && $attempts >= $max_attempts) {
                        $can_take = false;
                        $reason = 'Completed (Max ' . $max_attempts . ' attempts)';
                    }
                ?>
                
                <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 0.85rem;">
                    Attempts: <?php echo $attempts; ?><?php if($max_attempts>0) echo ' / ' . $max_attempts; ?>
                </p>

                <?php if ($can_take): ?>
                    <a href="take_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary">Start Quiz</a>
                <?php else: ?>
                    <span style="color: <?php echo ($attempts >= $max_attempts && $max_attempts > 0) ? 'var(--success-color)' : 'var(--danger-color)'; ?>; font-weight: 600;">
                        <?php echo htmlspecialchars($reason); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if(empty($quizzes)): ?>
        <p>No quizzes available yet.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

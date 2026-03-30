<?php
// view_attempt.php
require_once 'includes/auth.php';
requireLogin();

global $db;
$result_id = $_GET['result_id'] ?? null;
if (!$result_id) {
    header("Location: my_results.php");
    exit;
}

$result = $db->findById('results', $result_id);
if (!$result || ($result['user_id'] !== $_SESSION['user_id'] && !isAdmin() && !isTeacher())) {
    die("Attempt not found or unauthorized access.");
}

$quiz = $db->findById('quizzes', $result['quiz_id']);
$questions = $db->findWhere('questions', 'quiz_id', $result['quiz_id']);
// In a real production DB, questions shouldn't change after a quiz is taken, 
// here we load current state of questions.

$answers_selected = isset($result['answers_selected']) ? $result['answers_selected'] : [];

require_once 'includes/header.php';
?>

<div class="card" style="text-align: center;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card-title">Detailed Attempt Review</h2>
        <a href="my_results.php" class="btn btn-secondary" style="background:#e2e8f0; color:#1e293b;">Back to History</a>
    </div>
    
    <div style="margin: 30px 0; padding: 20px; background-color: var(--bg-color); border-radius: 8px;">
        <h3 style="margin-bottom: 20px;">Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h3>
        <p style="font-size: 3rem; font-weight: 700; color: <?php echo ($result['score']/$result['total'] >= 0.5) ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
            <?php echo $result['score']; ?> / <?php echo $result['total']; ?>
        </p>
        <p style="color: var(--text-secondary);">Taken on: <?php echo htmlspecialchars($result['submitted_at']); ?></p>
        <?php if(isset($result['raw_score']) && $result['raw_score'] < $result['score']): ?>
            <p style="color:var(--danger-color); font-size:0.9rem;">Negative penalty caps score to 0.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Question Breakdown</h3>
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <?php foreach ($questions as $index => $q): ?>
            <?php 
                $q_id = $q['id'];
                $correct = $q['correct_option'];
                $user_ans = isset($answers_selected[$q_id]) ? (int)$answers_selected[$q_id] : -1;
                
                $is_right = ($user_ans === $correct);
                $skipped = ($user_ans === -1);
                
                $borderC = $is_right ? 'var(--success-color)' : ($skipped ? '#94a3b8' : 'var(--danger-color)');
            ?>
            <div style="border-left: 4px solid <?php echo $borderC; ?>; padding: 15px; background: var(--surface-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 0 6px 6px 0;">
                <p style="font-weight: 600; margin-bottom: 8px;">Q<?php echo ($index + 1) . ': ' . nl2br(htmlspecialchars($q['text'])); ?></p>
                <div style="display: flex; gap: 20px; font-size: 0.95rem;">
                    <div style="flex: 1;">
                        <span style="color: var(--text-secondary);">Your Answer:</span>
                        <br>
                        <strong style="color: <?php echo $borderC; ?>;">
                            <?php echo $skipped ? 'Skipped / No Answer' : htmlspecialchars($q['options'][$user_ans] ?? 'Unknown'); ?>
                        </strong>
                    </div>
                    <?php if (!$is_right): ?>
                    <div style="flex: 1;">
                        <span style="color: var(--text-secondary);">Correct Answer:</span>
                        <br>
                        <strong style="color: var(--success-color);">
                            <?php echo htmlspecialchars($q['options'][$correct] ?? 'Unknown'); ?>
                        </strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if(empty($questions)): ?>
        <p>No question data available for this attempt.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

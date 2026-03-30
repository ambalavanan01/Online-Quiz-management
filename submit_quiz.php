<?php
// submit_quiz.php
require_once 'includes/auth.php';
requireLogin();

if (isAdmin() || isTeacher()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: student_dashboard.php");
    exit;
}

global $db;
$quiz_id = $_POST['quiz_id'] ?? null;
$answers = $_POST['answers'] ?? [];
$user_id = $_SESSION['user_id'];

if (!$quiz_id) {
    die("Invalid request");
}

$quiz = $db->findById('quizzes', $quiz_id);

$existing = $db->findWhere('results', 'user_id', $user_id);
$attempts = count(array_filter($existing, fn($r) => $r['quiz_id'] === $quiz_id));
$max_attempts = isset($quiz['max_attempts']) ? (int)$quiz['max_attempts'] : 1;
if ($max_attempts > 0 && $attempts >= $max_attempts) {
    die("You have already reached the maximum allowed attempts for this quiz.");
}

$questions = $db->findWhere('questions', 'quiz_id', $quiz_id);
$negative_penalty = isset($quiz['negative_marking']) ? (float)$quiz['negative_marking'] : 0;

$raw_score = 0;
$detailedFeedback = [];
$saved_answers = [];

foreach ($questions as $q) {
    $q_id = $q['id'];
    $type = $q['type'] ?? 'mcq';
    
    $is_right = false;
    $user_ans_text = 'Skipped/No answer';
    $correct_ans_text = '';
    $skipped = true;
    
    if ($type === 'mcq' || $type === 'tf') {
        $correct = $q['correct_option'];
        $user_ans = isset($answers[$q_id]) && $answers[$q_id] !== '' ? (int)$answers[$q_id] : -1;
        $saved_answers[$q_id] = $user_ans;
        
        $correct_ans_text = $q['options'][$correct] ?? '';
        if ($user_ans !== -1) {
            $skipped = false;
            $user_ans_text = $q['options'][$user_ans] ?? 'Unknown';
            if ($user_ans === $correct) {
                $raw_score++;
                $is_right = true;
            } elseif ($negative_penalty > 0) {
                $raw_score -= $negative_penalty;
            }
        }
    } elseif ($type === 'short_answer') {
        $user_ans = trim(isset($answers[$q_id]) ? (string)$answers[$q_id] : '');
        $saved_answers[$q_id] = $user_ans;
        $correct_text = trim((string)($q['correct_text'] ?? ''));
        
        $correct_ans_text = $correct_text;
        
        if ($user_ans !== '') {
            $skipped = false;
            $user_ans_text = $user_ans;
            if (strcasecmp($user_ans, $correct_text) === 0) {
                $raw_score++;
                $is_right = true;
            } elseif ($negative_penalty > 0) {
                $raw_score -= $negative_penalty;
            }
        }
    }

    $detailedFeedback[] = [
        'question_text' => $q['text'],
        'user_ans_text' => $user_ans_text,
        'correct_ans_text' => $correct_ans_text,
        'is_right' => $is_right,
        'skipped' => $skipped
    ];
}

$total = count($questions);
// Prevent negative final score total if desired, but conceptually it can be negative. Let's cap at 0 for UI niceness.
$final_score = max(0, $raw_score);

// Store result with advanced tracing
$result_id = $db->insert('results', [
    'user_id' => $user_id,
    'quiz_id' => $quiz_id,
    'score' => $final_score,
    'raw_score' => $raw_score,
    'total' => $total,
    'answers_selected' => $saved_answers,
    'submitted_at' => date('Y-m-d H:i:s')
]);

require_once 'includes/header.php';
?>

<div class="card" style="text-align: center;">
    <h2 class="card-title">Quiz Completed!</h2>
    <p>You have successfully submitted: <strong><?php echo htmlspecialchars($quiz['title']); ?></strong></p>
    
    <?php if (empty($quiz['hide_results'])): ?>
        <div style="margin: 30px 0; padding: 20px; background-color: var(--bg-color); border-radius: 8px;">
            <h3 style="margin-bottom: 20px;">Your Score</h3>
            <p style="font-size: 3rem; font-weight: 700; color: <?php echo ($final_score/$total >= 0.5) ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                <?php echo $final_score; ?> / <?php echo $total; ?>
            </p>
            <p style="color: var(--text-secondary);"><?php echo $total > 0 ? round(($final_score/$total)*100, 1) : 0; ?>%</p>
            <?php if ($negative_penalty > 0): ?>
                <p style="font-size: 0.9rem; margin-top: 10px; color: var(--danger-color);">Note: Negative marking applied.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="margin: 30px 0; padding: 20px; background-color: #f1f5f9; border-radius: 8px; color: var(--text-secondary);">
            <p>Your results have been recorded successfully. The score and detailed feedback for this quiz are hidden by the teacher.</p>
        </div>
    <?php endif; ?>
    
    <a href="student_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
    <a href="my_results.php" class="btn btn-secondary" style="background:var(--border-color); color:var(--text-primary); margin-left: 10px;">View History</a>
</div>

<?php if (empty($quiz['hide_results'])): ?>
    <div class="card">
        <h3 class="card-title">Detailed Feedback</h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($detailedFeedback as $index => $fb): ?>
                <?php 
                    $borderC = $fb['is_right'] ? 'var(--success-color)' : ($fb['skipped'] ? '#94a3b8' : 'var(--danger-color)');
                ?>
                <div style="border-left: 4px solid <?php echo $borderC; ?>; padding: 15px; background: var(--surface-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 0 6px 6px 0;">
                    <p style="font-weight: 600; margin-bottom: 8px;">Q<?php echo ($index + 1) . ': ' . nl2br(htmlspecialchars($fb['question_text'])); ?></p>
                    <div style="display: flex; gap: 20px; font-size: 0.95rem;">
                        <div style="flex: 1;">
                            <span style="color: var(--text-secondary);">Your Answer:</span>
                            <br>
                            <strong style="color: <?php echo $borderC; ?>;">
                                <?php echo htmlspecialchars($fb['user_ans_text']); ?>
                                <?php if (!$fb['is_right'] && !$fb['skipped'] && $negative_penalty > 0) echo " (-{$negative_penalty})"; ?>
                            </strong>
                        </div>
                        <?php if (!$fb['is_right']): ?>
                        <div style="flex: 1;">
                            <span style="color: var(--text-secondary);">Correct Answer:</span>
                            <br>
                            <strong style="color: var(--success-color);">
                                <?php echo htmlspecialchars($fb['correct_ans_text']); ?>
                            </strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

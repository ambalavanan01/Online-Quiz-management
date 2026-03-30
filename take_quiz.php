<?php
// take_quiz.php
require_once 'includes/auth.php';
requireLogin();

if (isAdmin() || isTeacher()) {
    header("Location: index.php");
    exit;
}

global $db;
$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id) {
    header("Location: student_dashboard.php");
    exit;
}

$quiz = $db->findById('quizzes', $quiz_id);
if (!$quiz) {
    die("Quiz not found.");
}

$results = $db->findWhere('results', 'user_id', $_SESSION['user_id']);
$attempts = count(array_filter($results, fn($r) => $r['quiz_id'] === $quiz['id']));
$max_attempts = isset($quiz['max_attempts']) ? (int)$quiz['max_attempts'] : 1;

if ($max_attempts > 0 && $attempts >= $max_attempts) {
    die("You have reached the maximum allowed attempts for this quiz. <a href='my_results.php'>View Results</a>");
}

$now = new DateTime();
$start = !empty($quiz['schedule_start']) ? new DateTime($quiz['schedule_start']) : null;
$end = !empty($quiz['schedule_end']) ? new DateTime($quiz['schedule_end']) : null;
if ($start && $now < $start) die("Quiz has not started yet.");
if ($end && $now > $end) die("Quiz has ended.");

if (!empty($quiz['password'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_password'])) {
        if ($_POST['quiz_password'] === $quiz['password']) {
            $_SESSION['unlocked_quiz_' . $quiz_id] = true;
        } else {
            $pass_error = "Incorrect password.";
        }
    }
    
    if (empty($_SESSION['unlocked_quiz_' . $quiz_id])) {
        require_once 'includes/header.php';
        echo '<div class="card" style="max-width:400px; margin: 50px auto; text-align:center;">';
        echo '<h3>Password Required</h3>';
        echo '<p>This quiz is protected by a password.</p>';
        if (isset($pass_error)) echo '<p style="color:var(--danger-color);">'.$pass_error.'</p>';
        echo '<form method="POST"><input type="password" name="quiz_password" class="form-control" required style="margin-bottom:15px;"><button type="submit" class="btn btn-primary" style="width:100%;">Unlock Quiz</button></form>';
        echo '</div>';
        require_once 'includes/footer.php';
        exit;
    }
}

$questions = $db->findWhere('questions', 'quiz_id', $quiz_id);
shuffle($questions); // Randomize
$duration_seconds = (int)$quiz['duration_minutes'] * 60;

require_once 'includes/header.php';
?>

<div class="card" style="position: sticky; top: 0; z-index: 100; border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin-bottom: 0; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title" style="margin: 0;"><?php echo htmlspecialchars($quiz['title']); ?></h2>
        <div style="font-size: 1.5rem; font-weight: bold; color: var(--danger-color);">
            Time Left: <span id="timer">Loading...</span>
        </div>
    </div>
    <?php if (isset($quiz['negative_marking']) && $quiz['negative_marking'] > 0): ?>
        <p style="color: var(--danger-color); font-weight: 600; font-size: 0.9rem; margin-top: 8px;">
            ⚠️ Negative Marking enabled: -<?php echo $quiz['negative_marking']; ?> per incorrect answer.
        </p>
    <?php endif; ?>
</div>

<form method="POST" action="submit_quiz.php" id="quizForm" class="card" style="border-top-left-radius: 0; border-top-right-radius: 0; padding-top: 30px;">
    <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz_id); ?>">
    
    <?php foreach ($questions as $index => $q): ?>
        <div style="margin-bottom: 30px; padding: 20px; border: 1px solid var(--border-color); border-radius: 8px;">
            <p style="font-weight: 600; font-size: 1.1rem; margin-bottom: 15px;">
                <?php echo ($index + 1) . '. ' . nl2br(htmlspecialchars($q['text'])); ?>
            </p>
            
            <?php if (!empty($q['media_path'])): ?>
                <div style="margin-bottom:15px; text-align: center; background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <?php if ($q['media_type'] === 'image' || !isset($q['media_type'])): ?>
                        <img src="<?php echo htmlspecialchars($q['media_path']); ?>" alt="Question Visual" style="max-width:100%; max-height:300px; border-radius:6px; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                    <?php elseif ($q['media_type'] === 'audio'): ?>
                        <audio controls style="width: 100%; max-width: 500px;">
                            <source src="<?php echo htmlspecialchars($q['media_path']); ?>">
                        </audio>
                    <?php elseif ($q['media_type'] === 'video'): ?>
                        <video controls style="max-width:100%; max-height:400px; border-radius:6px;">
                            <source src="<?php echo htmlspecialchars($q['media_path']); ?>">
                        </video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (!isset($q['type']) || $q['type'] === 'mcq' || $q['type'] === 'tf'): ?>
                    <?php if (isset($q['options']) && is_array($q['options'])): ?>
                        <?php foreach ($q['options'] as $i => $opt): ?>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $i; ?>" <?php echo empty($quiz['allow_skip']) ? 'required' : ''; ?>>
                                <span><?php echo htmlspecialchars($opt); ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php elseif ($q['type'] === 'short_answer'): ?>
                    <input type="text" name="answers[<?php echo $q['id']; ?>]" class="form-control" placeholder="Type your answer here..." <?php echo empty($quiz['allow_skip']) ? 'required' : ''; ?>>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($quiz['allow_skip'])): ?>
                <div style="margin-top: 10px; font-size: 0.8em; color: var(--text-secondary);">
                    You can skip this question.
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <?php if(empty($questions)): ?>
        <p>No questions found in this quiz.</p>
    <?php else: ?>
        <button type="submit" class="btn btn-primary" style="font-size: 1.2rem; padding: 12px 24px; width: 200px;">Submit Quiz</button>
    <?php endif; ?>
</form>

<script>
    let timeLeft = <?php echo $duration_seconds; ?>;
    const timerDisplay = document.getElementById('timer');
    const quizForm = document.getElementById('quizForm');

    function formatTime(seconds) {
        let m = Math.floor(seconds / 60);
        let s = seconds % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            timerDisplay.textContent = "00:00";
            alert("Time is up! Submitting your quiz automatically.");
            quizForm.submit();
        } else {
            timerDisplay.textContent = formatTime(timeLeft);
            timeLeft--;
        }
    }, 1000);

    window.addEventListener('beforeunload', function (e) {
        if (!quizForm.submitted) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    quizForm.addEventListener('submit', () => {
        quizForm.submitted = true;
    });

    <?php if(!empty($quiz['proctoring_strictness'])): ?>
    (function initProctoring() {
        let warnings = 0;
        const maxWarnings = 3;
        
        // Anti copy/paste
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('copy', e => e.preventDefault());
        document.addEventListener('paste', e => e.preventDefault());

        // Fullscreen enforcement on first click
        document.addEventListener('click', function reqFS() {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            }
            document.removeEventListener('click', reqFS);
        }, { once: true });

        function handleViolation(msg) {
            if (quizForm.submitted) return;
            warnings++;
            if (warnings >= maxWarnings) {
                alert("Anti-Cheat: Maximum violations reached! Auto-submitting quiz.");
                quizForm.submit();
            } else {
                alert(`WARNING ${warnings}/${maxWarnings}:\n${msg}\nContinuing to violate the rules will result in automatic submission.`);
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) handleViolation("You switched tabs or minimized the browser.");
        });

        window.addEventListener('blur', () => {
            handleViolation("You lost focus on the quiz window.");
        });
    })();
    <?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>

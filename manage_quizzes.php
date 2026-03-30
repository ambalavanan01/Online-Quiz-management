<?php
// manage_quizzes.php
require_once 'includes/auth.php';
requireTeacher();

global $db;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title = trim($_POST['title']);
        $duration = (int)$_POST['duration_minutes'];
        $difficulty = $_POST['difficulty'];
        $negative = (float)$_POST['negative_marking'];
        
        $schedule_start = !empty($_POST['schedule_start']) ? $_POST['schedule_start'] : null;
        $schedule_end = !empty($_POST['schedule_end']) ? $_POST['schedule_end'] : null;
        $password = trim($_POST['password'] ?? '');
        $status = $_POST['status'] ?? 'published';
        $max_attempts = (int)($_POST['max_attempts'] ?? 0);
        $hide_results = isset($_POST['hide_results']) ? true : false;
        $allow_skip = isset($_POST['allow_skip']) ? true : false;
        $proctoring_strictness = isset($_POST['proctoring_strictness']) ? true : false;
        
        if (!empty($title) && $duration > 0) {
            $db->insert('quizzes', [
                'title' => $title,
                'duration_minutes' => $duration,
                'difficulty' => $difficulty,
                'negative_marking' => $negative,
                'schedule_start' => $schedule_start,
                'schedule_end' => $schedule_end,
                'password' => $password,
                'status' => $status,
                'max_attempts' => $max_attempts,
                'hide_results' => $hide_results,
                'allow_skip' => $allow_skip,
                'proctoring_strictness' => $proctoring_strictness,
                'created_by' => $_SESSION['user_id']
            ]);
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['quiz_id'];
        $quiz = $db->findById('quizzes', $id);
        
        // Ensure ownership
        if (isAdmin() || ($quiz && $quiz['created_by'] === $_SESSION['user_id'])) {
            $db->delete('quizzes', $id);
            // Also delete associated questions
            $questions = $db->findWhere('questions', 'quiz_id', $id);
            foreach ($questions as $q) {
                $db->delete('questions', $q['id']);
            }
        }
    }
    header("Location: manage_quizzes.php");
    exit;
}

$all_quizzes = $db->selectAll('quizzes');
if (isAdmin()) {
    $quizzes = $all_quizzes;
} else {
    $quizzes = array_filter($all_quizzes, fn($q) => $q['created_by'] === $_SESSION['user_id']);
}

require_once 'includes/header.php';
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card-title">Manage Quizzes</h2>
        <?php if (isTeacher() && !isAdmin()): ?>
            <a href="teacher_dashboard.php" class="btn btn-secondary" style="background:#e2e8f0; color:#1e293b;">Back to Dashboard</a>
        <?php else: ?>
            <a href="admin_dashboard.php" class="btn btn-secondary" style="background:#e2e8f0; color:#1e293b;">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Create New Quiz</h3>
    <form method="POST" action="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: flex-end; margin-bottom: 20px;">
        <input type="hidden" name="action" value="create">
        
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Quiz Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" value="30" min="1" required>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Difficulty Level</label>
            <select name="difficulty" class="form-control" required>
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Negative Marking (Penalty per wrong ans)</label>
            <input type="number" step="0.25" name="negative_marking" class="form-control" value="0" min="0" required>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="published">Published</option>
                <option value="draft">Draft</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Max Attempts (0 for unlimited)</label>
            <input type="number" name="max_attempts" class="form-control" value="1" min="0" required>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Quiz Password (Optional)</label>
            <input type="text" name="password" class="form-control" placeholder="Leave empty for open access">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Schedule Start (Optional)</label>
            <input type="datetime-local" name="schedule_start" class="form-control">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Schedule End (Optional)</label>
            <input type="datetime-local" name="schedule_end" class="form-control">
        </div>

        <div style="grid-column: 1 / -1; display: flex; gap: 20px; flex-wrap: wrap; margin-top: 10px; margin-bottom: 10px;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="hide_results" value="1">
                Hide Results from Students
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="allow_skip" value="1" checked>
                Allow Skipping Questions
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="proctoring_strictness" value="1">
                Enable Anti-Cheat (Full Screen, Tab Disable)
            </label>
        </div>

        <div style="grid-column: 1 / -1;">
            <button type="submit" class="btn btn-primary" style="width: 200px;">Create Quiz</button>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="card-title"><?php echo isAdmin() ? 'All System Quizzes' : 'My Quizzes'; ?></h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Duration</th>
                    <th>Difficulty</th>
                    <th>Penalty</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                <tr>
                    <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                    <td><?php echo (int)$quiz['duration_minutes']; ?> mins</td>
                    <td>
                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; background: #e2e8f0;">
                            <?php echo isset($quiz['difficulty']) ? htmlspecialchars($quiz['difficulty']) : 'Standard'; ?>
                        </span>
                        <?php if(!empty($quiz['status']) && $quiz['status'] === 'draft'): ?>
                            <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; background: #fef08a; margin-left: 5px;">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td>-<?php echo isset($quiz['negative_marking']) ? $quiz['negative_marking'] : 0; ?> pts</td>
                    <td><?php 
                        if (!empty($quiz['schedule_start']) || !empty($quiz['schedule_end'])) {
                            echo '<span style="font-size: 0.8rem; color: var(--primary-color);">Scheduled</span><br>';
                        }
                        echo htmlspecialchars($quiz['created_at']); 
                    ?></td>
                    <td>
                        <a href="manage_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-primary" style="padding: 6px 10px; font-size: 0.85rem;">Questions</a>
                        
                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure? This deletes the quiz and all questions.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 6px 10px; font-size: 0.85rem;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($quizzes)): ?>
                <tr><td colspan="6" class="text-center">No quizzes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

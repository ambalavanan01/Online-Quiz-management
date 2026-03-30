<?php
// teacher_dashboard.php
require_once 'includes/auth.php';
requireTeacher();

global $db;
$quizzes = $db->findWhere('quizzes', 'created_by', $_SESSION['user_id']);
$all_results = $db->selectAll('results');
$my_quiz_ids = array_map(fn($q) => $q['id'], $quizzes);

// Find results only for quizzes created by this teacher
$my_results = array_filter($all_results, function($r) use ($my_quiz_ids) {
    return in_array($r['quiz_id'], $my_quiz_ids);
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_publish_results') {
    $q_id = $_POST['quiz_id'];
    $quiz = $db->findById('quizzes', $q_id);
    if ($quiz && (isAdmin() || $quiz['created_by'] === $_SESSION['user_id'])) {
        $current = !empty($quiz['hide_results']);
        $db->update('quizzes', $q_id, ['hide_results' => !$current]);
    }
    header("Location: teacher_dashboard.php");
    exit;
}

require_once 'includes/header.php';
?>
<div class="card">
    <h2 class="card-title">Teacher Dashboard</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>! You can manage your quizzes here.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <div class="card" style="text-align: center;">
        <h3>My Quizzes</h3>
        <p style="font-size: 2rem; color: var(--primary-color); font-weight: bold;"><?php echo count($quizzes); ?></p>
    </div>
    <div class="card" style="text-align: center;">
        <h3>Student Attempts on My Quizzes</h3>
        <p style="font-size: 2rem; color: var(--success-color); font-weight: bold;"><?php echo count($my_results); ?></p>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Quick Actions</h3>
    <a href="manage_quizzes.php" class="btn btn-primary">Create / Edit Quizzes</a>
    <a href="view_results.php" class="btn btn-secondary" style="background:var(--border-color); color:var(--text-primary);">View Student Analytics</a>
</div>

<div class="card">
    <h3 class="card-title">Results Visibility Management</h3>
    <p style="color:var(--text-secondary); margin-bottom:15px;">Control whether students can see their scores and detailed feedback for each quiz.</p>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Quiz Title</th>
                    <th>Students Attempted</th>
                    <th>Result Visibility</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $q): ?>
                <tr>
                    <td><?php echo htmlspecialchars($q['title']); ?></td>
                    <td>
                        <?php 
                            $attempts = count(array_filter($my_results, fn($r) => $r['quiz_id'] === $q['id']));
                            echo $attempts;
                        ?>
                    </td>
                    <td>
                        <?php if (empty($q['hide_results'])): ?>
                            <span style="color:var(--success-color); font-weight:bold;">Published to Students</span>
                        <?php else: ?>
                            <span style="color:var(--text-secondary); font-style:italic;">Hidden from Students</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="toggle_publish_results">
                            <input type="hidden" name="quiz_id" value="<?php echo $q['id']; ?>">
                            <?php if (empty($q['hide_results'])): ?>
                                <button type="submit" class="btn" style="background:#fee2e2; color:var(--danger-color); padding: 4px 8px; font-size: 0.8rem;">Hide Results</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-success" style="background:#bbf7d0; color:var(--success-color); padding: 4px 8px; font-size: 0.8rem;">Publish Results</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($quizzes)): ?>
                <tr><td colspan="4" class="text-center">You haven't created any quizzes yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

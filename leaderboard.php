<?php
// leaderboard.php
require_once 'includes/auth.php';
requireLogin();

global $db;
$results = $db->selectAll('results');
$users = $db->selectAll('users');

$userMap = [];
foreach ($users as $u) {
    if ($u['role'] === 'student') {
        $userMap[$u['id']] = $u['username'];
    }
}

// Group by user
$leaderboard = [];
foreach ($results as $r) {
    $uid = $r['user_id'];
    if (!isset($userMap[$uid])) continue;
    
    if (!isset($leaderboard[$uid])) {
        $leaderboard[$uid] = ['username' => $userMap[$uid], 'total_score' => 0, 'quizzes_taken' => 0];
    }
    $leaderboard[$uid]['total_score'] += $r['score'];
    $leaderboard[$uid]['quizzes_taken'] += 1;
}

usort($leaderboard, function($a, $b) {
    return $b['total_score'] <=> $a['total_score'];
});

require_once 'includes/header.php';
?>

<div class="card" style="text-align: center;">
    <h2 class="card-title">Global Leaderboard 🏆</h2>
    <p>Rankings based on total score accumulation across all quizzes.</p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="text-align:center; width:80px;">Rank</th>
                    <th>Student Name</th>
                    <th>Total Score</th>
                    <th>Quizzes Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $index => $l): ?>
                <tr>
                    <td style="text-align:center; font-weight:bold; font-size: 1.2rem; color: var(--primary-color);">
                        #<?php echo $index + 1; ?>
                    </td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($l['username']); ?></td>
                    <td><span style="padding:4px 8px; background:var(--bg-color); border-radius:12px; font-weight:bold;"><?php echo $l['total_score']; ?> pts</span></td>
                    <td><?php echo $l['quizzes_taken']; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($leaderboard)): ?>
                <tr><td colspan="4" class="text-center">No scores recorded yet. Time to study!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

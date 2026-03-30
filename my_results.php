<?php
// my_results.php
require_once 'includes/auth.php';
requireLogin();

global $db;
$results = $db->findWhere('results', 'user_id', $_SESSION['user_id']);
$quizzes = $db->selectAll('quizzes');
$quizMap = [];
foreach ($quizzes as $q) {
    $q['is_hidden'] = !empty($q['hide_results']) ? true : false;
    $quizMap[$q['id']] = $q;
}

$total_score_pct = 0;
$highest_score_pct = 0;
$chartLabels = [];
$chartData = [];
$visible_count = 0;

foreach (array_reverse($results) as $r) {
    $q = $quizMap[$r['quiz_id']] ?? null;
    if ($q && empty($q['is_hidden']) && $r['total'] > 0) {
        $pct = round(($r['score'] / $r['total']) * 100, 1);
        $total_score_pct += $pct;
        if ($pct > $highest_score_pct) $highest_score_pct = $pct;
        
        $chartLabels[] = $q['title'];
        $chartData[] = $pct;
        $visible_count++;
    }
}

$avg_score_pct = $visible_count > 0 ? round($total_score_pct / $visible_count, 1) : 0;

require_once 'includes/header.php';
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card-title">Performance Analytics & History</h2>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <div class="card" style="text-align: center;">
        <h3>Quizzes Attempted</h3>
        <p style="font-size: 2rem; color: var(--primary-color); font-weight: bold;"><?php echo count($results); ?></p>
    </div>
    <div class="card" style="text-align: center;">
        <h3>Average Score</h3>
        <p style="font-size: 2rem; color: #f59e0b; font-weight: bold;"><?php echo $avg_score_pct; ?>%</p>
    </div>
    <div class="card" style="text-align: center;">
        <h3>Highest Score</h3>
        <p style="font-size: 2rem; color: var(--success-color); font-weight: bold;"><?php echo $highest_score_pct; ?>%</p>
    </div>
</div>

<?php if (count($results) > 0): ?>
<div class="card" style="max-width: 800px; margin: 0 auto 24px auto;">
    <h3 class="card-title text-center">Progress Chart</h3>
    <canvas id="progressChart"></canvas>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('progressChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chartLabels); ?>,
                    datasets: [{
                        label: 'Score Percentage (%)',
                        data: <?php echo json_encode($chartData); ?>,
                        backgroundColor: 'rgba(37, 99, 235, 0.2)',
                        borderColor: '#2563eb',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        });
    </script>
</div>
<?php endif; ?>

<div class="card">
    <h3 class="card-title">My Result History</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Quiz Name</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Date Taken</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($results) as $r): ?>
                    <?php 
                        $pct = $r['total'] > 0 ? round(($r['score']/$r['total'])*100, 1) : 0; 
                        $qinfo = $quizMap[$r['quiz_id']] ?? ['title'=>'Unknown', 'is_hidden'=>false];
                    ?>
                <tr>
                    <td><?php echo htmlspecialchars($qinfo['title']); ?></td>
                    <?php if (empty($qinfo['is_hidden'])): ?>
                        <td>
                            <strong><?php echo $r['score']; ?> / <?php echo $r['total']; ?></strong>
                        </td>
                        <td>
                            <?php echo $pct . '%'; ?>
                        </td>
                        <td>
                            <?php if ($pct >= 50): ?>
                                <span style="display:inline-block; padding:4px 8px; background:#bbf7d0; color:var(--success-color); border-radius:12px; font-weight:bold; font-size:0.8rem;">Passed</span>
                            <?php else: ?>
                                <span style="display:inline-block; padding:4px 8px; background:#fecaca; color:var(--danger-color); border-radius:12px; font-weight:bold; font-size:0.8rem;">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo isset($r['submitted_at']) ? htmlspecialchars($r['submitted_at']) : 'N/A'; ?></td>
                        <td>
                            <a href="view_attempt.php?result_id=<?php echo $r['id']; ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.8rem;">View Detail</a>
                        </td>
                    <?php else: ?>
                        <td colspan="3" style="color:var(--text-secondary); text-align:center; font-style:italic;">Results Hidden</td>
                        <td><?php echo isset($r['submitted_at']) ? htmlspecialchars($r['submitted_at']) : 'N/A'; ?></td>
                        <td>-</td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($results)): ?>
                <tr><td colspan="6" class="text-center">No quizzes attempted yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

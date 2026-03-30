<?php
// export_results.php
require_once 'includes/auth.php';
requireTeacher();

global $db;
$all_results = $db->selectAll('results');

if (isAdmin()) {
    $quizzes = $db->selectAll('quizzes');
    $results_to_export = $all_results;
} else {
    $quizzes = $db->findWhere('quizzes', 'created_by', $_SESSION['user_id']);
    $my_quiz_ids = array_map(fn($q) => $q['id'], $quizzes);
    $results_to_export = array_filter($all_results, function($r) use ($my_quiz_ids) {
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

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="quiz_results_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Attempt ID', 'Student Username', 'Quiz Name', 'Score', 'Total Questions', 'Percentage', 'Submitted At']);

foreach ($results_to_export as $r) {
    $student_name = $userMap[$r['user_id']] ?? 'Unknown User';
    $quiz_title = $quizMap[$r['quiz_id']] ?? 'Unknown Quiz';
    $pct = $r['total'] > 0 ? round(($r['score']/$r['total'])*100, 1) . '%' : '0%';
    
    fputcsv($output, [
        $r['id'],
        $student_name,
        $quiz_title,
        $r['score'],
        $r['total'],
        $pct,
        $r['submitted_at']
    ]);
}
fclose($output);
exit;
?>

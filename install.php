<?php
require_once 'includes/db.php';
$users = $db->selectAll('users');
if (count($users) === 0) {
    $db->insert('users', [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin'
    ]);
    echo "<h1>Installation Complete</h1>";
    echo "<p>Admin user created!</p>";
    echo "<p>Username: <strong>admin</strong></p>";
    echo "<p>Password: <strong>admin123</strong></p>";
    echo "<a href='login.php'>Go to Login</a>";
} else {
    echo "<h1>Already Installed</h1>";
    echo "<a href='login.php'>Go to Login</a>";
}
?>

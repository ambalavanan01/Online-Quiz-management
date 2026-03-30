<?php
// register.php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $db;
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role']; // 'student' or 'teacher'

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif ($role !== 'student' && $role !== 'teacher') {
        $error = "Invalid role selected.";
    } else {
        $existing = $db->findWhere('users', 'username', $username);
        if (!empty($existing)) {
            $error = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_data = [
                'username' => $username,
                'password' => $hashed_password,
                'role' => $role
            ];
            
            if ($role === 'teacher') {
                $user_data['status'] = 'pending';
            }
            
            $db->insert('users', $user_data);
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>
<div class="auth-container card">
    <h2 class="card-title text-center">Registration</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">I am a:</label>
            <div style="display: flex; gap: 20px;">
                <label style="cursor: pointer;"><input type="radio" name="role" value="student" checked> Student</label>
                <label style="cursor: pointer;"><input type="radio" name="role" value="teacher"> Teacher</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
    </form>
    <p class="text-center" style="margin-top: 16px;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>
<?php require_once 'includes/footer.php'; ?>

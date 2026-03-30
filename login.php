<?php
// login.php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$role_selected = $_GET['role'] ?? null;
validRoleCheck($role_selected);

function validRoleCheck(&$role) {
    if ($role !== null && !in_array($role, ['admin', 'teacher', 'student'])) {
        $role = null;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role_selected) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $loginResult = login($username, $password, $role_selected);
    if ($loginResult === 'pending') {
        $error = "Your Teacher account is pending Admin approval. Please try again later.";
    } elseif ($loginResult === 'wrong_role') {
        $error = "This account does not have " . ucfirst($role_selected) . " privileges.";
    } elseif ($loginResult === true) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<?php if (!$role_selected): ?>
    <!-- ROLE SELECTION SCREEN -->
    <div style="text-align: center; margin-top: 40px; margin-bottom: 20px;">
        <h1 style="font-weight: 700; color: var(--text-primary);">Who are you?</h1>
        <p style="color: var(--text-secondary); font-size: 1.1rem;">Please select your role to proceed to login.</p>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; margin-top: 40px;">
        
        <!-- Admin Card (Blue) -->
        <a href="?role=admin" style="text-decoration:none; display:flex; align-items:center; justify-content:space-between; padding: 30px 40px; width:340px; background:#fff; border-top: 5px solid #1a73e8; border-radius:6px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)'">
            <!-- Admin Icon (Shield/Person) -->
            <div style="color: #1a73e8;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <path d="M12 8v4"></path>
                    <path d="M12 16h.01"></path>
                </svg>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end;">
                <span style="color:#1a73e8; font-size:1.4rem; font-weight:600; margin-bottom:12px;">Admin</span>
                <div style="background:#1a73e8; color:white; padding:8px 12px; border-radius:6px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                </div>
            </div>
        </a>

        <!-- Teacher Card (Yellow/Gold) -->
        <a href="?role=teacher" style="text-decoration:none; display:flex; align-items:center; justify-content:space-between; padding: 30px 40px; width:340px; background:#fff; border-top: 5px solid #d9a022; border-radius:6px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)'">
            <!-- Teacher Icon (tie/group) -->
            <div style="color: #d9a022;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end;">
                <span style="color:#d9a022; font-size:1.4rem; font-weight:600; margin-bottom:12px;">Teacher</span>
                <div style="background:#d9a022; color:white; padding:8px 12px; border-radius:6px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                </div>
            </div>
        </a>

        <!-- Student Card (Green) -->
        <a href="?role=student" style="text-decoration:none; display:flex; align-items:center; justify-content:space-between; padding: 30px 40px; width:340px; background:#fff; border-top: 5px solid #047857; border-radius:6px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.08)'">
            <!-- Student Icon (Grad cap) -->
            <div style="color: #047857;">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <div style="display:flex; flex-direction:column; align-items:flex-end;">
                <span style="color:#047857; font-size:1.4rem; font-weight:600; margin-bottom:12px;">Student</span>
                <div style="background:#047857; color:white; padding:8px 12px; border-radius:6px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                </div>
            </div>
        </a>
    </div>

<?php else: ?>
    <!-- LOGIN FORM FOR SPECIFIC ROLE -->
    <div style="margin-top: 20px;">
        <a href="login.php" style="color: var(--text-secondary); text-decoration: none; font-weight: 500;">
            &larr; Back to Role Selection
        </a>
    </div>

    <div class="auth-container card" style="margin-top: 30px;">
        <h2 class="card-title text-center"><?php echo ucfirst($role_selected); ?> Login</h2>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration successful. Please login.</div>
        <?php endif; ?>
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
            
            <?php
            // Button color mapped to role
            $btnColor = "var(--primary-color)";
            if ($role_selected === 'teacher') $btnColor = "#d9a022";
            if ($role_selected === 'student') $btnColor = "#047857";
            ?>
            
            <button type="submit" class="btn" style="width: 100%; background-color: <?php echo $btnColor; ?>; color: white;">Verify & Login</button>
        </form>
        <p class="text-center" style="margin-top: 16px;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

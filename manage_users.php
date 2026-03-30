<?php
// manage_users.php
require_once 'includes/auth.php';
requireAdmin();

global $db;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve' && isset($_POST['user_id'])) {
        $db->update('users', $_POST['user_id'], ['status' => 'approved']);
        header("Location: manage_users.php");
        exit;
    }
}

$users = $db->selectAll('users');
require_once 'includes/header.php';
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card-title">Manage Users</h2>
        <a href="admin_dashboard.php" class="btn btn-secondary" style="background:#e2e8f0; color:#1e293b;">Back to Dashboard</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php if ($u['role'] === 'admin') continue; ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td>
                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; background: #e2e8f0;">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'teacher'): ?>
                            <?php if (isset($u['status']) && $u['status'] === 'pending'): ?>
                                <span style="color: var(--danger-color); font-weight: bold;">Pending Approval</span>
                            <?php else: ?>
                                <span style="color: var(--success-color); font-weight: bold;">Approved</span>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'teacher' && isset($u['status']) && $u['status'] === 'pending'): ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.85rem;">Approve</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

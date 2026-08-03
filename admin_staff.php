<?php
require_once 'admin_auth_check.php';
require_once 'db.php';

$admin = $_SESSION['admin_username'];
$success = $error = '';

// ── POST HANDLERS ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff') {
        $uname = trim($_POST['username'] ?? '');
        $pw    = $_POST['password'] ?? '';
        $role  = in_array($_POST['role'] ?? '', ['admin','receptionist']) ? $_POST['role'] : 'receptionist';

        if (strlen($uname) < 3) { $_SESSION['staff_error'] = 'Username must be at least 3 characters.'; }
        elseif (strlen($pw) < 6) { $_SESSION['staff_error'] = 'Password must be at least 6 characters.'; }
        else {
            $chk = $conn->query("SELECT id FROM admins WHERE username='" . $conn->real_escape_string($uname) . "'");
            if ($chk->num_rows > 0) { $_SESSION['staff_error'] = 'Username already exists.'; }
            else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admins (username, password, role) VALUES (?,?,?)");
                $stmt->bind_param("sss", $uname, $hash, $role);
                $stmt->execute(); $stmt->close();
                log_activity($conn, $admin, 'Staff Created', "Added $role account: $uname");
                $_SESSION['staff_success'] = "Staff account \"$uname\" ($role) created.";
            }
        }
    }

    if ($action === 'delete_staff') {
        $del_id = (int)$_POST['staff_id'];
        $row = $conn->query("SELECT username, role FROM admins WHERE id=$del_id")->fetch_assoc();
        if ($row && $row['username'] === $admin) {
            $_SESSION['staff_error'] = 'You cannot delete your own account.';
        } elseif ($row) {
            // Prevent deleting last admin
            $adminCount = (int)$conn->query("SELECT COUNT(*) AS c FROM admins WHERE role='admin'")->fetch_assoc()['c'];
            if ($row['role'] === 'admin' && $adminCount <= 1) {
                $_SESSION['staff_error'] = 'Cannot delete the last admin account.';
            } else {
                $conn->query("DELETE FROM admins WHERE id=$del_id");
                log_activity($conn, $admin, 'Staff Deleted', "Removed account: {$row['username']}");
                $_SESSION['staff_success'] = "Staff account \"{$row['username']}\" removed.";
            }
        }
    }

    if ($action === 'change_role') {
        $target_id = (int)$_POST['staff_id'];
        $new_role  = in_array($_POST['new_role'] ?? '', ['admin','receptionist']) ? $_POST['new_role'] : 'receptionist';
        $row = $conn->query("SELECT username, role FROM admins WHERE id=$target_id")->fetch_assoc();
        if ($row) {
            if ($row['username'] === $admin && $new_role !== 'admin') {
                $_SESSION['staff_error'] = 'You cannot remove your own admin role.';
            } else {
                $adminCount = (int)$conn->query("SELECT COUNT(*) AS c FROM admins WHERE role='admin'")->fetch_assoc()['c'];
                if ($row['role'] === 'admin' && $new_role === 'receptionist' && $adminCount <= 1) {
                    $_SESSION['staff_error'] = 'Cannot demote the last admin account.';
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET role=? WHERE id=?");
                    $stmt->bind_param("si", $new_role, $target_id);
                    $stmt->execute(); $stmt->close();
                    log_activity($conn, $admin, 'Role Changed', "{$row['username']} changed to $new_role");
                    $_SESSION['staff_success'] = "{$row['username']}'s role updated to $new_role.";
                }
            }
        }
    }

    if ($action === 'reset_password') {
        $target_id = (int)$_POST['staff_id'];
        $new_pw    = $_POST['new_password'] ?? '';
        $row = $conn->query("SELECT username FROM admins WHERE id=$target_id")->fetch_assoc();
        if ($row && strlen($new_pw) >= 6) {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $target_id);
            $stmt->execute(); $stmt->close();
            log_activity($conn, $admin, 'Password Reset', "Reset password for: {$row['username']}");
            $_SESSION['staff_success'] = "Password reset for \"{$row['username']}\".";
        } else {
            $_SESSION['staff_error'] = 'Password must be at least 6 characters.';
        }
    }

    header('Location: admin_staff.php');
    exit;
}

if (isset($_SESSION['staff_success'])) {
    $success = $_SESSION['staff_success'];
    unset($_SESSION['staff_success']);
}
if (isset($_SESSION['staff_error'])) {
    $error = $_SESSION['staff_error'];
    unset($_SESSION['staff_error']);
}

$staff_list = $conn->query("SELECT id, username, role, created_at FROM admins ORDER BY role ASC, created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management — Santa Fe Beach Club</title>
    <link rel="stylesheet" href="admin.css?v=2">
    <link rel="stylesheet" href="dashboard.css?v=2">
</head>
<body>
    <?php $active_page = 'staff'; include '_sidebar.php'; ?>

    <main class="main-content">
        <?php
        $page_title = 'Staff Management';
        $page_subtitle = 'Manage receptionist and admin accounts for the dashboard.';
        include '_page_header.php';
        ?>

        <?php if ($success): ?><div class="alert alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:24px;align-items:start;">

            <!-- Staff Table -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Staff Accounts</h3>
                    <button class="btn-primary" onclick="document.getElementById('addModal').classList.add('open')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Staff
                    </button>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Account</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($s = $staff_list->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="user-avatar" style="width:30px;height:30px;font-size:12px;"><?php echo strtoupper(substr($s['username'],0,1)); ?></div>
                                <span style="font-weight:600;"><?php echo htmlspecialchars($s['username']); ?></span>
                                <?php if ($s['username'] === $admin): ?><span class="badge badge-admin" style="font-size:9px;">You</span><?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="staff_id" value="<?php echo $s['id']; ?>">
                                <select name="new_role" onchange="this.form.submit()" style="padding:4px 8px;border-radius:6px;border:1px solid var(--border);font-family:Outfit,sans-serif;font-size:12px;">
                                    <option value="admin" <?php echo $s['role']==='admin'?'selected':''; ?>>Admin</option>
                                    <option value="receptionist" <?php echo $s['role']==='receptionist'?'selected':''; ?>>Receptionist</option>
                                </select>
                            </form>
                        </td>
                        <td style="color:var(--text-muted);font-size:13px;"><?php echo date('M j, Y', strtotime($s['created_at'])); ?></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-secondary" style="padding:5px 10px;font-size:12px;" onclick="openReset(<?php echo $s['id']; ?>,'<?php echo htmlspecialchars($s['username']); ?>')">Reset PW</button>
                                <form method="POST" onsubmit="return confirm('Remove <?php echo htmlspecialchars($s['username']); ?>?')">
                                    <input type="hidden" name="action" value="delete_staff">
                                    <input type="hidden" name="staff_id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" class="btn-danger" <?php echo ($s['username']===$admin)?'disabled':''; ?>>Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Permissions Reference -->
            <div class="admin-card">
                <div class="admin-card-header"><h3>Permission Reference</h3></div>
                <table class="admin-table">
                    <thead><tr><th>Feature</th><th style="text-align:center;">Admin</th><th style="text-align:center;">Reception</th></tr></thead>
                    <tbody>
                    <?php
                    $perms = [
                        'Dashboard'            => ['admin'=>true, 'rec'=>true],
                        'Reservations'         => ['admin'=>true, 'rec'=>true],
                        'Check-in/out'         => ['admin'=>true, 'rec'=>true],
                        'Payments'             => ['admin'=>true, 'rec'=>true],
                        'Accommodations'       => ['admin'=>true, 'rec'=>'View Only'],
                        'Staff Management'     => ['admin'=>true, 'rec'=>false],
                        'Reports'              => ['admin'=>true, 'rec'=>'Limited'],
                        'Promotions'           => ['admin'=>true, 'rec'=>false],
                        'Activity Logs'        => ['admin'=>true, 'rec'=>false],
                        'Settings'             => ['admin'=>true, 'rec'=>true],
                    ];
                    foreach ($perms as $feat => $p):
                        $chk = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
                        $ex  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
                    ?>
                    <tr>
                        <td style="font-size:13px;"><?php echo $feat; ?></td>
                        <td style="text-align:center;"><?php echo $p['admin'] ? $chk : $ex; ?></td>
                        <td style="text-align:center;">
                            <?php if ($p['rec'] === true): echo $chk;
                            elseif ($p['rec'] === false): echo $ex;
                            else: echo '<span style="font-size:11px;color:var(--text-muted);">'.$p['rec'].'</span>'; endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </main>

<!-- Add Staff Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">×</button>
        <h3>Add Staff Account</h3>
        <p class="modal-sub">Create a new login for a team member.</p>
        <form method="POST">
            <input type="hidden" name="action" value="add_staff">
            <div class="admin-form-group"><label>Username</label><input type="text" name="username" required minlength="3" placeholder="e.g. frontdesk2"></div>
            <div class="admin-form-group"><label>Password</label><input type="password" name="password" required minlength="6" placeholder="Min 6 characters"></div>
            <div class="admin-form-group">
                <label>Role</label>
                <select name="role">
                    <option value="receptionist">Receptionist</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Create Account</button>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('resetModal').classList.remove('open')">×</button>
        <h3>Reset Password</h3>
        <p class="modal-sub" id="resetModalSub">Set a new password for this account.</p>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="staff_id" id="resetStaffId">
            <div class="admin-form-group"><label>New Password</label><input type="password" name="new_password" required minlength="6" placeholder="Min 6 characters"></div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save New Password</button>
        </form>
    </div>
</div>

<script>
function openReset(id, name) {
    document.getElementById('resetStaffId').value = id;
    document.getElementById('resetModalSub').textContent = 'Set a new password for "' + name + '".';
    document.getElementById('resetModal').classList.add('open');
}
</script>
<script src="sidebar-toggle.js"></script>
</body>
</html>

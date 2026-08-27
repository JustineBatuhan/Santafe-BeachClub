<?php
require_once __DIR__ . '/../backend/helpers/admin_auth_check.php';
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/../backend/helpers/rbac_helper.php';
require_once __DIR__ . '/../backend/helpers/security_logger.php';
require_once __DIR__ . '/../backend/helpers/password_helper.php';

$admin = $_SESSION['admin_username'];
$success = $error = '';

// ── POST HANDLERS ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_staff') {
        $uname = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pw    = $_POST['password'] ?? '';
        $role  = in_array($_POST['role'] ?? '', ['admin','receptionist']) ? $_POST['role'] : 'receptionist';

        if (strlen($uname) < 3) { 
            $_SESSION['staff_error'] = 'Username must be at least 3 characters.'; 
        } elseif (!str_ends_with($uname, '@beachclub.com')) {
            $_SESSION['staff_error'] = 'Username must end with @beachclub.com.';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['staff_error'] = 'Please provide a valid personal/MFA email address.';
        } elseif (($pwError = pw_validate($pw)) !== null) { 
            $_SESSION['staff_error'] = $pwError; 
        } else {
            $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->bind_param("s", $uname);
            $stmt->execute();
            $chk = $stmt->get_result();
            $stmt->close();

            if ($chk->num_rows > 0) { 
                $_SESSION['staff_error'] = 'Username already exists.'; 
            } else {
                $hash = pw_hash($pw);
                $stmt = $conn->prepare("INSERT INTO admins (username, email, password, role) VALUES (?,?,?,?)");
                $stmt->bind_param("ssss", $uname, $email, $hash, $role);
                $stmt->execute(); 
                $stmt->close();
                log_activity($conn, $admin, 'Staff Created', "Added $role account: $uname with OTP email: $email");
                SecurityLogger::log($conn, 'STAFF_CREATED', "Added {$role} account: {$uname}", SecurityLogger::LEVEL_INFO, $admin);
                $_SESSION['staff_success'] = "Staff account \"$uname\" ($role) created.";
            }
        }
    }

    if ($action === 'edit_email') {
        $target_id  = (int)($_POST['staff_id'] ?? 0);
        $new_email  = trim($_POST['email'] ?? '');

        if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['staff_error'] = 'Please provide a valid email address for OTP delivery.';
        } else {
            $stmt = $conn->prepare("UPDATE admins SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $new_email, $target_id);
            $stmt->execute();
            $stmt->close();
            log_activity($conn, $admin, 'Staff OTP Email Updated', "Updated MFA email for staff ID: $target_id");
            $_SESSION['staff_success'] = "OTP Delivery Email updated successfully.";
        }
    }

    if ($action === 'delete_staff') {
        $del_id = (int)($_POST['staff_id'] ?? 0);
        $stmt = $conn->prepare("SELECT username, role FROM admins WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && $row['username'] === $admin) {
            $_SESSION['staff_error'] = 'You cannot delete your own account.';
        } elseif ($row) {
            // Prevent deleting last admin
            $adminCount = (int)$conn->query("SELECT COUNT(*) AS c FROM admins WHERE role='admin'")->fetch_assoc()['c'];
            if ($row['role'] === 'admin' && $adminCount <= 1) {
                $_SESSION['staff_error'] = 'Cannot delete the last admin account.';
            } else {
                $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->bind_param("i", $del_id);
                $stmt->execute();
                $stmt->close();
                log_activity($conn, $admin, 'Staff Deleted', "Removed account: {$row['username']}");
                SecurityLogger::log($conn, 'STAFF_DELETED', "Removed staff account: {$row['username']}", SecurityLogger::LEVEL_WARNING, $admin);
                $_SESSION['staff_success'] = "Staff account \"{$row['username']}\" removed.";
            }
        }
    }

    if ($action === 'change_role') {
        $target_id = (int)($_POST['staff_id'] ?? 0);
        $new_role  = in_array($_POST['new_role'] ?? '', ['admin','receptionist']) ? $_POST['new_role'] : 'receptionist';
        
        $stmt = $conn->prepare("SELECT username, role FROM admins WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

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
                    $stmt->execute(); 
                    $stmt->close();
                    log_activity($conn, $admin, 'Role Changed', "{$row['username']} changed to $new_role");
                    SecurityLogger::log($conn, 'ROLE_CHANGED', "{$row['username']} changed to {$new_role}", SecurityLogger::LEVEL_INFO, $admin);
                    $_SESSION['staff_success'] = "{$row['username']}'s role updated to $new_role.";
                }
            }
        }
    }

    if ($action === 'reset_password') {
        $target_id = (int)($_POST['staff_id'] ?? 0);
        $new_pw    = $_POST['new_password'] ?? '';
        
        $stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && ($pwError = pw_validate($new_pw)) === null) {
            $hash = pw_hash($new_pw);
            $stmt = $conn->prepare("UPDATE admins SET password=? WHERE id=?");
            $stmt->bind_param("si", $hash, $target_id);
            $stmt->execute(); 
            $stmt->close();
            log_activity($conn, $admin, 'Password Reset', "Reset password for: {$row['username']}");
            SecurityLogger::log($conn, 'PASSWORD_RESET', "Reset password for staff: {$row['username']}", SecurityLogger::LEVEL_INFO, $admin);
            $_SESSION['staff_success'] = "Password reset for \"{$row['username']}\"."; 
        } elseif ($row && $pwError !== null) {
            $_SESSION['staff_error'] = $pwError;
        } else {
            $_SESSION['staff_error'] = 'Password must be at least 8 characters and meet complexity requirements.';
        }
    }

    header('Location: admin_staff');
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

$staff_list = $conn->query("SELECT id, username, email, role, created_at FROM admins ORDER BY role ASC, created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management — Santa Fe Beach Club</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=3">
</head>
<body>
    <?php $active_page = 'staff'; include __DIR__ . '/partials/_sidebar.php'; ?>

    <main class="main-content">
        <?php
        $page_title = 'Staff Management';
        $page_subtitle = 'Manage receptionist and admin accounts, roles, and MFA verification emails.';
        include __DIR__ . '/partials/_page_header.php';
        ?>

        <?php if ($success): ?><div class="alert alert-success"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:1.8fr 1fr;gap:24px;align-items:start;">

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
                    <thead><tr><th>Account</th><th>OTP Delivery Email</th><th>Role</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($s = $staff_list->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="user-avatar" style="width:30px;height:30px;font-size:12px;"><?php echo strtoupper(substr($s['username'],0,1)); ?></div>
                                <div>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($s['username']); ?></div>
                                    <?php if ($s['username'] === $admin): ?><span class="badge badge-admin" style="font-size:9px;">You</span><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <?php if (!empty($s['email'])): ?>
                                    <span style="font-size:13px;color:#1E293B;font-weight:500;"><?php echo htmlspecialchars($s['email']); ?></span>
                                <?php else: ?>
                                    <span style="font-size:12px;color:#DC2626;background:#FEE2E2;padding:2px 6px;border-radius:4px;">No personal email</span>
                                <?php endif; ?>
                                <button type="button" style="background:none;border:none;cursor:pointer;padding:2px;color:#7C533C;" title="Change OTP Delivery Email" onclick="openEditEmail(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['username']); ?>', '<?php echo htmlspecialchars($s['email'] ?? ''); ?>')">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                </button>
                            </div>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="staff_id" value="<?php echo $s['id']; ?>">
                                <select name="new_role" onchange="this.form.submit()" style="padding:4px 8px;border-radius:6px;border:1px solid var(--border);font-family:Outfit,sans-serif;font-size:12px;">
                                    <option value="admin" <?php echo $s['role']==='admin'?'selected':''; ?>>Admin</option>
                                    <option value="receptionist" <?php echo $s['role']==='receptionist'?'selected':''; ?>>Receptionist</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button class="btn-secondary" style="padding:5px 10px;font-size:12px;" onclick="openReset(<?php echo $s['id']; ?>,'<?php echo htmlspecialchars($s['username']); ?>')">Reset PW</button>
                                <form method="POST" onsubmit="return false;" data-confirm-title="Remove Staff Account" data-confirm-msg="Remove <?php echo htmlspecialchars($s['username']); ?>? This cannot be undone." data-confirm-icon="👤" data-confirm-icon-bg="#FEE2E2">
                                    <?php echo csrf_field(); ?>
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
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add_staff">
            <div class="admin-form-group"><label>System Username (Login ID)</label><input type="email" name="username" required pattern=".+@beachclub\.com$" title="Must end with @beachclub.com" placeholder="name@beachclub.com"></div>
            <div class="admin-form-group"><label>Personal Email (Receives Login OTPs)</label><input type="email" name="email" required placeholder="personal@gmail.com"></div>
            <div class="admin-form-group"><label>Password</label><input type="password" name="password" required minlength="8" placeholder="Min 8 chars: upper, lower, number, symbol" title="Must be 8+ characters with uppercase, lowercase, number, and special character"></div>
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

<!-- Edit OTP Email Modal -->
<div class="modal-overlay" id="editEmailModal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('editEmailModal').classList.remove('open')">×</button>
        <h3>Edit OTP Delivery Email</h3>
        <p class="modal-sub" id="editEmailModalSub">Update the email address where login OTPs are sent.</p>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit_email">
            <input type="hidden" name="staff_id" id="editEmailStaffId">
            <div class="admin-form-group">
                <label>Personal / Delivery Email (Gmail, etc.)</label>
                <input type="email" name="email" id="editEmailInput" required placeholder="name@gmail.com">
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save Email</button>
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
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="staff_id" id="resetStaffId">
            <div class="admin-form-group"><label>New Password</label><input type="password" name="new_password" required minlength="8" placeholder="Min 8 chars: upper, lower, number, symbol" title="Must be 8+ characters with uppercase, lowercase, number, and special character"></div>
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

function openEditEmail(id, username, email) {
    document.getElementById('editEmailStaffId').value = id;
    document.getElementById('editEmailInput').value = email;
    document.getElementById('editEmailModalSub').textContent = 'Update OTP delivery email for "' + username + '".';
    document.getElementById('editEmailModal').classList.add('open');
}
</script>
<script src="assets/js/sidebar-toggle.js"></script>
</body>
</html>

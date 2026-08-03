<?php
require_once 'auth_check.php';
require_once 'db.php';
require_once 'business_time_helper.php';

$current_admin = $_SESSION['admin_username'];
$success = '';
$error = '';

// ── POST HANDLERS ──────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Change own password ---
    if ($action === 'change_password') {
        $current_pw  = $_POST['current_password'] ?? '';
        $new_pw      = $_POST['new_password'] ?? '';
        $confirm_pw  = $_POST['confirm_password'] ?? '';

        $row = $conn->query("SELECT password FROM admins WHERE username = '" . $conn->real_escape_string($current_admin) . "'")->fetch_assoc();
        if (!password_verify($current_pw, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pw) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_pw !== $confirm_pw) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $hash, $current_admin);
            $stmt->execute();
            $stmt->close();
            $success = 'Password updated successfully.';
        }
    }

    // --- Change own username ---
    if ($action === 'change_username') {
        $new_username = trim($_POST['new_username'] ?? '');
        $pw_confirm   = $_POST['password_for_username'] ?? '';

        $row = $conn->query("SELECT id, password FROM admins WHERE username = '" . $conn->real_escape_string($current_admin) . "'")->fetch_assoc();
        if (!password_verify($pw_confirm, $row['password'])) {
            $error = 'Password confirmation is incorrect.';
        } elseif (strlen($new_username) < 3) {
            $error = 'Username must be at least 3 characters.';
        } else {
            $check = $conn->query("SELECT id FROM admins WHERE username = '" . $conn->real_escape_string($new_username) . "' AND id != " . $row['id']);
            if ($check->num_rows > 0) {
                $error = 'That username is already taken.';
            } else {
                $stmt = $conn->prepare("UPDATE admins SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $new_username, $row['id']);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_username'] = $new_username;
                $current_admin = $new_username;
                $success = 'Username updated successfully.';
            }
        }
    }

    // --- Add new admin ---
    if ($action === 'add_admin') {
        $new_user = trim($_POST['admin_username'] ?? '');
        $new_pass = $_POST['admin_password'] ?? '';

        if (strlen($new_user) < 3) {
            $error = 'Username must be at least 3 characters.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $check = $conn->query("SELECT id FROM admins WHERE username = '" . $conn->real_escape_string($new_user) . "'");
            if ($check->num_rows > 0) {
                $error = 'An account with that username already exists.';
            } else {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $new_user, $hash);
                $stmt->execute();
                $stmt->close();
                $success = "Admin account \"$new_user\" created successfully.";
            }
        }
    }

    // --- Delete admin ---
    if ($action === 'delete_admin') {
        $del_id = (int)($_POST['admin_id'] ?? 0);
        $del_user_row = $conn->query("SELECT username FROM admins WHERE id = $del_id")->fetch_assoc();
        if ($del_user_row && $del_user_row['username'] === $current_admin) {
            $error = 'You cannot delete your own account.';
        } elseif ($del_id > 0) {
            $conn->query("DELETE FROM admins WHERE id = $del_id");
            $success = 'Admin account removed.';
        }
    }

    // --- Save property settings ---
    if ($action === 'save_property') {
        $fields = ['property_name', 'property_address', 'property_phone', 'property_email', 'checkin_time', 'checkout_time', 'property_timezone', 'currency'];
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($fields as $key) {
            $val = $key === 'property_timezone'
                ? sf_normalize_timezone_identifier($_POST[$key] ?? null)
                : trim($_POST[$key] ?? '');
            $stmt->bind_param("ss", $key, $val);
            $stmt->execute();
        }
        $stmt->close();
        date_default_timezone_set(sf_normalize_timezone_identifier($_POST['property_timezone'] ?? null));
        $success = 'Property settings saved successfully.';
    }
}

// ── FETCH DATA ─────────────────────────────────────────────────────────────────
$admins = $conn->query("SELECT id, username, created_at FROM admins ORDER BY created_at ASC");

$settings_raw = $conn->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $settings_raw->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$current_property_timezone = sf_normalize_timezone_identifier($settings['property_timezone'] ?? null);
$property_timezone_options = sf_get_supported_property_timezones();

$active_tab = $_GET['tab'] ?? 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Santa Fe Beach Club</title>
    <link rel="stylesheet" href="dashboard.css?v=2">
    <style>
        /* ── Settings Page Layout ── */
        .settings-wrapper {
            max-width: 860px;
        }

        .page-header {
            margin-bottom: 28px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1F2937;
        }

        .page-header p {
            font-size: 14px;
            color: var(--color-text-muted);
            margin-top: 4px;
        }

        /* ── Tab navigation ── */
        .tab-nav {
            display: flex;
            gap: 4px;
            margin-bottom: 28px;
            border-bottom: 1px solid #E5E7EB;
        }

        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: none;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover { color: var(--color-text-main); }

        .tab-btn.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
            font-weight: 600;
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Settings Cards ── */
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .settings-card h3 {
            font-size: 15px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 4px;
        }

        .settings-card .card-desc {
            font-size: 13px;
            color: var(--color-text-muted);
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 1px solid #F3F4F6;
        }

        /* ── Form fields ── */
        .settings-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .settings-form .form-row.single { grid-template-columns: 1fr; }

        .settings-form label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .settings-form input,
        .settings-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            color: #374151;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .settings-form input:focus,
        .settings-form select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(124,83,60,0.08);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-save {
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-save:hover { background: var(--color-primary-hover); }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #6EE7B7; }
        .alert-error   { background: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5; }

        /* ── Admin users table ── */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            font-size: 11px;
            font-weight: 700;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 0 0 12px 0;
            border-bottom: 1px solid #F3F4F6;
            text-align: left;
        }

        .admin-table td {
            padding: 14px 0;
            border-bottom: 1px solid #F3F4F6;
            font-size: 14px;
            color: #374151;
            vertical-align: middle;
        }

        .admin-table tr:last-child td { border-bottom: none; }

        .badge-you {
            display: inline-block;
            background: var(--color-sidebar-active);
            color: var(--color-primary);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
            text-transform: uppercase;
        }

        .btn-delete {
            background: none;
            border: 1px solid #FCA5A5;
            color: #DC2626;
            border-radius: 6px;
            padding: 5px 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-delete:hover {
            background: #FEF2F2;
        }

        .btn-delete:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        /* ── Avatar initial circle ── */
        .admin-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--color-sidebar-active);
            color: var(--color-primary);
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .admin-row-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ── Divider ── */
        .section-divider {
            border: none;
            border-top: 1px solid #F3F4F6;
            margin: 24px 0;
        }

        .subsection-title {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Property icon grid ── */
        .property-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--color-sidebar-active);
            color: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }
    </style>
</head>
<body>

    <?php $active_page = 'settings'; include '_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="settings-wrapper">

            <div class="page-header">
                <h1>Settings</h1>
                <p>Manage your account, team access, and property configuration.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Tab Nav -->
            <div class="tab-nav">
                <button class="tab-btn <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" onclick="switchTab('profile')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    My Profile
                </button>
                <button class="tab-btn <?php echo $active_tab === 'users' ? 'active' : ''; ?>" onclick="switchTab('users')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    User Management
                </button>
                <button class="tab-btn <?php echo $active_tab === 'property' ? 'active' : ''; ?>" onclick="switchTab('property')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Property
                </button>
            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- TAB: My Profile                             -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="tab-panel <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" id="tab-profile">

                <!-- Change Username -->
                <div class="settings-card">
                    <h3>Username</h3>
                    <p class="card-desc">Update the username you use to sign in.</p>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="change_username">
                        <div class="form-row">
                            <div>
                                <label>Current Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($current_admin); ?>" disabled style="background:#F9FAFB; color:#888;">
                            </div>
                            <div>
                                <label>New Username</label>
                                <input type="text" name="new_username" required minlength="3" placeholder="Enter new username">
                            </div>
                        </div>
                        <div class="form-row single">
                            <div>
                                <label>Confirm with Password</label>
                                <input type="password" name="password_for_username" required placeholder="Your current password">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-save">Update Username</button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="settings-card">
                    <h3>Password</h3>
                    <p class="card-desc">Choose a strong password. Must be at least 6 characters long.</p>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-row single">
                            <div>
                                <label>Current Password</label>
                                <input type="password" name="current_password" required placeholder="Enter current password">
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label>New Password</label>
                                <input type="password" name="new_password" required minlength="6" placeholder="New password">
                            </div>
                            <div>
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required minlength="6" placeholder="Repeat new password">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-save">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- TAB: User Management                        -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="tab-panel <?php echo $active_tab === 'users' ? 'active' : ''; ?>" id="tab-users">

                <!-- Admin Accounts List -->
                <div class="settings-card">
                    <h3>Admin Accounts</h3>
                    <p class="card-desc">All reception staff with dashboard access. You cannot remove your own account.</p>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Created</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($admin = $admins->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="admin-row-info">
                                        <div class="admin-avatar"><?php echo strtoupper(substr($admin['username'], 0, 1)); ?></div>
                                        <div>
                                            <?php echo htmlspecialchars($admin['username']); ?>
                                            <?php if ($admin['username'] === $current_admin): ?>
                                                <span class="badge-you">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: var(--color-text-muted); font-size:13px;">
                                    <?php echo date('M j, Y', strtotime($admin['created_at'])); ?>
                                </td>
                                <td style="text-align:right;">
                                    <form method="POST" onsubmit="return confirmDelete('<?php echo htmlspecialchars($admin['username']); ?>')">
                                        <input type="hidden" name="action" value="delete_admin">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="btn-delete"
                                            <?php echo ($admin['username'] === $current_admin) ? 'disabled title="Cannot delete your own account"' : ''; ?>>
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Add New Admin -->
                <div class="settings-card">
                    <h3>Add New Admin</h3>
                    <p class="card-desc">Create a new reception staff account. Share credentials securely.</p>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="add_admin">
                        <div class="form-row">
                            <div>
                                <label>Username</label>
                                <input type="text" name="admin_username" required minlength="3" placeholder="e.g. frontdesk2">
                            </div>
                            <div>
                                <label>Password</label>
                                <input type="password" name="admin_password" required minlength="6" placeholder="Min. 6 characters">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:middle;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                Create Account
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════ -->
            <!-- TAB: Property                               -->
            <!-- ═══════════════════════════════════════════ -->
            <div class="tab-panel <?php echo $active_tab === 'property' ? 'active' : ''; ?>" id="tab-property">

                <div class="settings-card">
                    <h3>Property Information</h3>
                    <p class="card-desc">These details appear on receipts, emails, and throughout the dashboard.</p>

                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="save_property">

                        <p class="subsection-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            Venue Details
                        </p>

                        <div class="form-row single">
                            <div>
                                <label>Property Name</label>
                                <input type="text" name="property_name" value="<?php echo htmlspecialchars($settings['property_name'] ?? ''); ?>" placeholder="e.g. Santa Fe Beach Club">
                            </div>
                        </div>
                        <div class="form-row single">
                            <div>
                                <label>Address</label>
                                <input type="text" name="property_address" value="<?php echo htmlspecialchars($settings['property_address'] ?? ''); ?>" placeholder="Full property address">
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label>Phone</label>
                                <input type="text" name="property_phone" value="<?php echo htmlspecialchars($settings['property_phone'] ?? ''); ?>" placeholder="+63 32 000 0000">
                            </div>
                            <div>
                                <label>Email</label>
                                <input type="email" name="property_email" value="<?php echo htmlspecialchars($settings['property_email'] ?? ''); ?>" placeholder="info@yourproperty.com">
                            </div>
                        </div>

                        <hr class="section-divider">

                        <p class="subsection-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            Default Times, Timezone &amp; Currency
                        </p>

                        <div class="form-row">
                            <div>
                                <label>Default Check-in Time</label>
                                <input type="time" name="checkin_time" value="<?php echo htmlspecialchars($settings['checkin_time'] ?? '14:00'); ?>">
                            </div>
                            <div>
                                <label>Default Check-out Time</label>
                                <input type="time" name="checkout_time" value="<?php echo htmlspecialchars($settings['checkout_time'] ?? '12:00'); ?>">
                            </div>
                        </div>
                        <div class="form-row single">
                            <div>
                                <label>Business Timezone</label>
                                <select name="property_timezone">
                                    <?php foreach ($property_timezone_options as $timezone_value => $timezone_label): ?>
                                    <option value="<?php echo htmlspecialchars($timezone_value); ?>" <?php echo ($current_property_timezone === $timezone_value) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($timezone_label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="display:block; margin-top:6px; color:#6B7280; font-size:12px;">Check-out due notifications use this timezone.</small>
                            </div>
                        </div>
                        <div class="form-row single">
                            <div>
                                <label>Currency</label>
                                <select name="currency">
                                    <?php
                                    $currencies = ['PHP' => 'PHP – Philippine Peso', 'USD' => 'USD – US Dollar', 'EUR' => 'EUR – Euro', 'SGD' => 'SGD – Singapore Dollar'];
                                    $current_currency = $settings['currency'] ?? 'PHP';
                                    foreach ($currencies as $code => $label):
                                    ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($current_currency === $code) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">Save Property Settings</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            document.querySelector('[onclick="switchTab(\'' + tab + '\')"]').classList.add('active');
            history.replaceState(null, '', '?tab=' + tab);
        }

        function confirmDelete(username) {
            return confirm('Remove admin account "' + username + '"? This cannot be undone.');
        }
    </script>

<script src="sidebar-toggle.js"></script>
</body>
</html>

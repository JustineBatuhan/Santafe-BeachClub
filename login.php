<?php
session_start();
require 'db.php';

// Already logged in — redirect to correct dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $dest = ($_SESSION['admin_role'] ?? 'receptionist') === 'admin' ? 'admin_dashboard.php' : 'dashboard.php';
    header("Location: $dest");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, password, role FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_logged_in']  = true;
                $_SESSION['admin_username']   = $username;
                $_SESSION['admin_role']       = $row['role'];

                log_activity($conn, $username, 'Login', 'Logged in successfully');

                $dest = $row['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php';
                header("Location: $dest");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Santa Fe Beach Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f5ede6 0%, #ede0d4 100%);
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
        }
        .login-wrapper { display: flex; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.12); max-width: 820px; width: 100%; }
        .login-brand {
            background: linear-gradient(160deg, #7C533C 0%, #4e3226 100%);
            padding: 50px 40px;
            display: flex; flex-direction: column; justify-content: center;
            width: 320px; flex-shrink: 0; color: white;
        }
        .brand-logo { font-size: 32px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.5px; }
        .brand-sub  { font-size: 13px; opacity: 0.7; margin-bottom: 40px; }
        .brand-tagline { font-size: 22px; font-weight: 600; line-height: 1.4; margin-bottom: 16px; }
        .brand-body { font-size: 13px; opacity: 0.65; line-height: 1.7; }
        .login-form-panel { background: white; padding: 50px 44px; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .form-title { font-size: 22px; font-weight: 700; color: #1E293B; margin-bottom: 6px; }
        .form-subtitle { font-size: 13px; color: #94A3B8; margin-bottom: 32px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; color: #374151; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input {
            width: 100%; padding: 11px 14px; border: 1.5px solid #E5E7EB;
            border-radius: 8px; font-family: 'Outfit', sans-serif; font-size: 14px;
            color: #1E293B; outline: none; transition: border-color 0.2s;
        }
        .form-group input:focus { border-color: #7C533C; box-shadow: 0 0 0 3px rgba(124,83,60,0.1); }
        .btn-login {
            width: 100%; background: #7C533C; color: white; border: none;
            padding: 13px; border-radius: 8px; font-family: 'Outfit', sans-serif;
            font-size: 15px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 8px;
        }
        .btn-login:hover { background: #5C3D2B; }
        .error { color: #DC2626; background: #FEF2F2; border: 1px solid #FCA5A5; padding: 11px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-brand">
            <div class="brand-logo">SF</div>
            <div class="brand-sub">Beach Club Management</div>
            <p class="brand-tagline">Welcome back to Santa Fe.</p>
            <p class="brand-body">Sign in to access your dashboard and manage reservations, staff, and more.</p>
        </div>
        <div class="login-form-panel">
            <p class="form-title">Sign In</p>
            <p class="form-subtitle">Enter your credentials to continue</p>
            <?php if ($error): ?>
                <div class="error">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>

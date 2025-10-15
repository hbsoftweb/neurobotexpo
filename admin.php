<?php
// admin.php
declare(strict_types=1);

// Secure session cookie (before session_start)
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// No-cache (prevents back-button showing stale pages)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/api/config.php';

// Helper: redirect relative to this folder (handles subfolders like /NEUROBOTEXPO/)
function go(string $to): void
{
    $base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header('Location: ' . $base . '/' . ltrim($to, '/'));
    exit;
}

// --- Handle logout FIRST (before any "already logged in" checks) ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    // Send user to this folder's admin.php
    go('admin.php');
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// If already logged in, go to dashboard
if (!empty($_SESSION['admin_email'])) {
    go('dashboard.php');
}

// Simple rate-limit (lock for 2 minutes after 5 bad tries)
$LOCK_AFTER = 5;
$LOCK_WINDOW_SECONDS = 120;

$locked_until = (int) ($_SESSION['locked_until'] ?? 0);
$now = time();
$is_locked = ($locked_until && $locked_until > $now);

$error = '';

// Handle POST (login)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $error = 'Something went wrong. Please try again.';
    } else {
        $validEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        $attempts = (int) ($_SESSION['login_attempts'] ?? 0);

        if (!$validEmail || $email !== ADMIN_EMAIL) {
            $error = 'Invalid credentials.';
            $_SESSION['login_attempts'] = $attempts + 1;
        } else {
            if (!password_verify($password, ADMIN_PASSWORD_HASH)) {
                $error = 'Invalid credentials.';
                $_SESSION['login_attempts'] = $attempts + 1;
            } else {
                // success
                $_SESSION['login_attempts'] = 0;
                $_SESSION['locked_until'] = 0;

                session_regenerate_id(true);
                $_SESSION['admin_email'] = ADMIN_EMAIL;
                $_SESSION['logged_in_at'] = time();

                go('dashboard.php');
            }
        }

        // Lock after too many attempts
        if (($_SESSION['login_attempts'] ?? 0) >= $LOCK_AFTER) {
            $_SESSION['locked_until'] = time() + $LOCK_WINDOW_SECONDS;
        }
    }
}

// If locked, show message
if ($is_locked) {
    $error = 'Too many attempts. Please wait a moment and try again.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/admin.css">
</head>

<body>
    <div class="main">
        <div class="login-form-holder">
            <div>
                <h4 class="heading-login">Login</h4>
                <p class="text-login">Enter your credentials to log in to your account</p>

                <?php if (!empty($_GET['r'])): ?>
                    <div class="toast" style="margin:8px 0;color:#b00;">Please log in to continue.</div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="toast" style="margin:8px 0;color:#b00;">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="admin.php" autocomplete="off" novalidate>
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="input-login-wrapper">
                        <label class="label-login">Email</label>
                        <input placeholder="Enter email" required class="input-login" type="email" name="email">
                    </div>
                    <div class="input-login-wrapper">
                        <label class="label-login">Password</label>
                        <input placeholder="Enter password" required class="input-login" type="password"
                            name="password">
                    </div>
                    <div>
                        <button type="submit" class="btn-login" <?php if ($is_locked)
                            echo 'disabled'; ?>>Login</button>
                    </div>
                </form>

                <?php if ($is_locked): ?>
                    <p style="margin-top:10px;font-size:12px;opacity:.8;">Login is temporarily disabled due to multiple
                        failed attempts.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
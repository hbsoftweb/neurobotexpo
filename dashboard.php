<?php
// dashboard.php
declare(strict_types=1);

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

// No-cache (prevents cached dashboard after logout)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Helper: redirect relative to this folder
function go(string $to): void
{
  $base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  header('Location: ' . $base . '/' . ltrim($to, '/'));
  exit;
}

// Guard: must be logged in
if (empty($_SESSION['admin_email'])) {
  go('admin.php?r=1');
}

// Idle timeout (30 minutes)
$IDLE_LIMIT = 1800;
if (!empty($_SESSION['logged_in_at']) && (time() - $_SESSION['logged_in_at'] > $IDLE_LIMIT)) {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
  go('admin.php?r=1');
} else {
  $_SESSION['logged_in_at'] = time(); // refresh activity
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="css/admin.css">
</head>

<body>
  <div class="main">
    <div class="login-form-holder">
      <div>
        <h4 class="heading-login">Dashboard</h4>
        <p class="text-login">Welcome, <?= htmlspecialchars($_SESSION['admin_email'], ENT_QUOTES, 'UTF-8'); ?>.</p>

        <div style="margin-top:12px">
          <!-- Your dashboard content / links go here -->
          <a href="admin.php?action=logout" class="btn-login"
            style="display:inline-block;text-decoration:none;">Logout</a>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
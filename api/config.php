<?php
declare(strict_types=1);

/**
 * Idempotent config loader.
 * Prevents re-execution and function/constant redefinitions if this file is included twice.
 */
if (defined('APP_CONFIG_LOADED')) {
  return;
}
define('APP_CONFIG_LOADED', true);

// ---- Minimal helper: env() (guarded) ----
if (!function_exists('env')) {
  function env(string $k, ?string $d = null): string {
    $v = getenv($k);
    return ($v === false || $v === null) ? (string)($d ?? '') : (string)$v;
  }
}

// ---- Load .env (once) ----
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
  $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (!str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    // Do not overwrite existing process env
    if (getenv($key) === false) {
      putenv("$key=$value");
    }
  }
}

// ---- App / DB / Mail constants (guarded) ----
if (!defined('APP_ENV'))            define('APP_ENV', env('APP_ENV', 'production'));
if (!defined('APP_DEBUG'))          define('APP_DEBUG', env('APP_DEBUG', '0') === '1');
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

if (!defined('DB_HOST'))            define('DB_HOST', env('DB_HOST', '127.0.0.1'));
if (!defined('DB_NAME'))            define('DB_NAME', env('DB_NAME', 'neurobot_expo'));
if (!defined('DB_USER'))            define('DB_USER', env('DB_USER', 'root'));
if (!defined('DB_PASS'))            define('DB_PASS', env('DB_PASS', ''));

if (!defined('API_KEY'))            define('API_KEY', env('API_KEY', 'change-me'));

if (!defined('STORAGE_DIR'))        define('STORAGE_DIR', __DIR__ . '/../storage');
if (!defined('SELFIES_DIR'))        define('SELFIES_DIR', STORAGE_DIR . '/selfies');

if (!defined('CORS_ALLOW_ORIGIN'))  define('CORS_ALLOW_ORIGIN', env('CORS_ALLOW_ORIGIN', '*'));

if (!defined('SMTP_HOST'))          define('SMTP_HOST', env('SMTP_HOST', ''));
if (!defined('SMTP_USER'))          define('SMTP_USER', env('SMTP_USER', ''));
if (!defined('SMTP_PASS'))          define('SMTP_PASS', env('SMTP_PASS', ''));
if (!defined('SMTP_PORT'))          define('SMTP_PORT', (int) env('SMTP_PORT', '465'));
if (!defined('SMTP_SECURE'))        define('SMTP_SECURE', env('SMTP_SECURE', 'smtps'));

if (!defined('MAIL_FROM'))          define('MAIL_FROM', env('MAIL_FROM', SMTP_USER));
if (!defined('MAIL_FROM_NAME'))     define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Neurobot Expo'));
if (!defined('MAIL_ADMIN'))         define('MAIL_ADMIN', env('MAIL_ADMIN', SMTP_USER));

if (!defined('ADMIN_EMAIL'))        define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'info@acrifabgroup.com'));
if (!defined('ADMIN_PASSWORD_HASH'))define('ADMIN_PASSWORD_HASH', env('ADMIN_PASSWORD_HASH', '$2y$10$c3cGYvbAV4vYF2RDSAksWuDauqs9F5ayMzDs.Syo7Fo.5CEjs.WdC'));

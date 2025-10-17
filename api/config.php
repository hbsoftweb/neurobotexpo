<?php
declare(strict_types=1);

// Load .env manually if getenv() doesn't see variables (e.g., Hostinger shared plans)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
  $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '='))
      continue;
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    putenv("$key=$value");
  }
}

function env(string $k, ?string $d = null): string
{
  $v = getenv($k);
  return ($v === false || $v === null) ? (string) ($d ?? '') : (string) $v;
}

define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', '0') === '1');
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Kolkata'));

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'neurobot_expo'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

define('API_KEY', env('API_KEY', 'change-me'));

define('STORAGE_DIR', __DIR__ . '/../storage');
define('SELFIES_DIR', STORAGE_DIR . '/selfies');

define('CORS_ALLOW_ORIGIN', env('CORS_ALLOW_ORIGIN', '*'));

define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_PORT', (int) env('SMTP_PORT', '465'));
define('SMTP_SECURE', env('SMTP_SECURE', 'smtps')); // smtps or starttls

define('MAIL_FROM', env('MAIL_FROM', SMTP_USER));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Neurobot Expo'));
define('MAIL_ADMIN', env('MAIL_ADMIN', SMTP_USER));

define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'admin@123.com'));
define('ADMIN_PASSWORD_HASH', env('ADMIN_PASSWORD_HASH', '$2y$10$c3cGYvbAV4vYF2RDSAksWuDauqs9F5ayMzDs.Syo7Fo.5CEjs.WdC'));

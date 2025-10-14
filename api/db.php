<?php
declare(strict_types=1);

// ---- EDIT THESE IF NEEDED ----
$DB_HOST = 'localhost';
$DB_NAME = 'neurobot_expo';   // <-- use your actual DB name (with underscore)
$DB_USER = 'root';
$DB_PASS = '';                // default XAMPP: empty

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

// Create a single PDO instance and keep it available as $pdo
global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
}

// Also expose a helper function for files that call db()
function db(): PDO {
  global $pdo;
  return $pdo;
}

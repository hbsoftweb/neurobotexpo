<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
  } catch (Throwable $e) {
    // Let the caller handle this cleanly (submit.php will turn it into JSON)
    throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
  }
}

function db(): PDO { global $pdo; return $pdo; }

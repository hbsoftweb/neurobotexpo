<?php
declare(strict_types=1);

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
}
function db(): PDO { global $pdo; return $pdo; }

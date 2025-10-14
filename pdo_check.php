<?php
error_reporting(E_ALL);
ini_set('display_errors','1');

try {
  $dsn = 'mysql:host=' . 'localhost' . ';dbname=' . 'neurobot_expo' . ';charset=utf8mb4';
  $pdo = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);
  echo "OK: Connected to MySQL and database.";
} catch (Throwable $e) {
  echo "ERROR: " . $e->getMessage();
}

<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/mail.php';   // this defines sendMail()

$to = 'jaymodihbsoftweb@gmail.com'; // where to receive the test
$ok = sendMail($to, 'Jay (Test)', 'SMTP sanity check', '<p>SMTP works ✅</p>');

var_dump($ok);

<?php
// submit.php
declare(strict_types=1);

// --- show PHP/DB errors during development ---
ini_set('display_errors', '1');
error_reporting(E_ALL);

// ---------- Basic CORS (relax during dev; tighten for prod) ----------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------- Helpers ----------
function json_response(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_dir(string $path): void {
    if (!is_dir($path)) @mkdir($path, 0775, true);
}

function data_url_to_png(string $dataUrl): string {
    if (!str_starts_with($dataUrl, 'data:image/')) {
        throw new RuntimeException('Invalid selfie data URL.');
    }
    $parts = explode(',', $dataUrl, 2);
    if (count($parts) !== 2) {
        throw new RuntimeException('Malformed data URL.');
    }
    $raw = base64_decode($parts[1], true);
    if ($raw === false) {
        throw new RuntimeException('Base64 decode failed.');
    }
    return $raw;
}

function digits_only(string $s): string {
    return preg_replace('/\D+/', '', $s) ?? '';
}

function ip_to_bin(?string $ip): ?string {
    $b = @inet_pton((string)$ip);
    return $b === false ? null : $b;
}


// ---------- Session & CSRF ----------
session_start();
$sessionCsrf = $_SESSION['csrf_token'] ?? null;

// ---------- Read JSON/form ----------
$ctype = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input') ?: '';
$body = [];

if (stripos($ctype, 'application/json') !== false && $raw !== '') {
    $body = json_decode($raw, true);
    if (!is_array($body)) json_response(400, ['ok' => false, 'error' => 'Invalid JSON body.']);
} else {
    $body = $_POST;
    if (isset($body['selfie_data']) && !isset($body['selfie']['data_url'])) {
        $body['selfie'] = ['mime' => 'image/png', 'data_url' => $body['selfie_data']];
    }
}

// ---------- Normalise payload ----------
$payload = [
    'meta' => $body['meta'] ?? [
        'source' => $body['source'] ?? 'exhibition-form',
        'event_id' => $body['event_id'] ?? '',
        'submitted_at' => date('c'),
    ],
    'visitor' => $body['visitor'] ?? [
        'name' => trim($body['name'] ?? ''),
        'company_name' => trim($body['company_name'] ?? ''),
        'contact_number' => trim($body['contact_number'] ?? ''),
        'email' => trim($body['email'] ?? ''),
        'designation' => $body['designation'] ?? '',
        'designation_other' => trim($body['designation_other'] ?? ''),
        'industries' => $body['industries'] ?? ($body['industry'] ?? []),
        'industry_other' => trim($body['industry_other'] ?? ''),
        'applications' => $body['applications'] ?? ($body['application'] ?? []),
        'special_mention' => trim($body['special_mention'] ?? ''),
    ],
    'selfie' => $body['selfie'] ?? [
        'mime' => 'image/png',
        'data_url' => $body['selfie_data'] ?? '',
    ],
    'csrf_token' => $body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''),
];

// ---------- Validate ----------
$errors = [];
$v = $payload['visitor'];

$v['name'] = trim($v['name']);
$v['company_name'] = trim($v['company_name']);
$v['contact_number'] = digits_only($v['contact_number']);
$v['email'] = trim($v['email']);
$v['designation'] = trim($v['designation']);
$v['designation_other'] = trim($v['designation_other']);
$v['special_mention'] = trim($v['special_mention']);
$industries = is_array($v['industries']) ? $v['industries'] : [];
$applications = is_array($v['applications']) ? $v['applications'] : [];

if ($v['name'] === '') $errors['name'] = 'Name is required.';
if ($v['company_name'] === '') $errors['company_name'] = 'Company name is required.';
if ($v['contact_number'] === '' || strlen($v['contact_number']) < 7 || strlen($v['contact_number']) > 15)
    $errors['contact_number'] = 'Provide a valid phone (7–15 digits).';
if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Provide a valid email.';
if ($v['designation'] === '') $errors['designation'] = 'Designation is required.';
if (strcasecmp($v['designation'], 'Other') === 0 && $v['designation_other'] === '') 
    $errors['designation_other'] = 'Please specify other designation.';
if (count($industries) < 1) $errors['industries'] = 'Select at least one industry.';
if (in_array('Other', $industries, true) && ($v['industry_other'] ?? '') === '') 
    $errors['industry_other'] = 'Please specify other industry.';
if (count($applications) < 1) $errors['applications'] = 'Select at least one application.';
$selfieDataUrl = $payload['selfie']['data_url'] ?? '';
if ($selfieDataUrl === '') $errors['selfie'] = 'Selfie is required.';

if (!empty($errors)) json_response(422, ['ok' => false, 'errors' => $errors]);

// ---------- Save selfie ----------
$storageDir = __DIR__ . '/storage';
$imagesDir = $storageDir . '/selfies';
ensure_dir($imagesDir);

$eventId = preg_replace('/[^A-Za-z0-9_-]+/', '', (string)($payload['meta']['event_id'] ?? 'EXPO'));
$uid = bin2hex(random_bytes(3));
$stamp = date('Ymd-His');
$id = sprintf('%s-%s-%s', $eventId ?: 'EXPO', $stamp, $uid);

try {
    $pngBytes = data_url_to_png($selfieDataUrl);
} catch (Throwable $e) {
    json_response(400, ['ok' => false, 'error' => 'Invalid selfie image: ' . $e->getMessage()]);
}

$selfiePath = $imagesDir . '/' . $id . '.png';
if (@file_put_contents($selfiePath, $pngBytes) === false)
    json_response(500, ['ok' => false, 'error' => 'Failed to save selfie image.']);

// ---------- Save JSON file ----------
$dataFile = $storageDir . '/submissions.jsonl';
$record = [
    'id' => $id,
    'received_at' => date('c'),
    'meta' => [
        'source' => (string)($payload['meta']['source'] ?? 'exhibition-form'),
        'event_id' => $eventId,
        'submitted_at' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ],
    'visitor' => $v,
    'assets' => [
        'selfie' => [
            'path' => 'storage/selfies/' . basename($selfiePath),
            'mime' => 'image/png',
            'bytes' => strlen($pngBytes),
        ]
    ]
];
file_put_contents($dataFile, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);

// ---------- Insert into MySQL ----------
require_once __DIR__ . '/api/db.php'; // adjust path if db.php elsewhere

// --- replace the INSERT and bindings in submit.php with:
$stmt = $pdo->prepare("
  INSERT INTO submissions (
    submission_id, event_id, source, submitted_at, name, company_name, contact_number,
    email, designation, designation_other, industries, industry_other, applications,
    special_mention, selfie_path, ip, ua
  ) VALUES (
    :submission_id, :event_id, :source, :submitted_at, :name, :company_name, :contact_number,
    :email, :designation, :designation_other, :industries, :industry_other, :applications,
    :special_mention, :selfie_path, :ip, :ua
  )
");

$stmt->execute([
    ':submission_id'   => $id,                      // <-- was ':id'
    ':event_id'        => $eventId,
    ':source'          => $record['meta']['source'],
    ':submitted_at'    => date('Y-m-d H:i:s'),
    ':name'            => $v['name'],
    ':company_name'    => $v['company_name'],
    ':contact_number'  => $v['contact_number'],
    ':email'           => $v['email'],
    ':designation'     => $v['designation'],
    ':designation_other'=> $v['designation_other'],
    ':industries'      => json_encode($industries, JSON_UNESCAPED_UNICODE),
    ':industry_other'  => $v['industry_other'] ?? '',
    ':applications'    => json_encode($applications, JSON_UNESCAPED_UNICODE),
    ':special_mention' => $v['special_mention'],
    ':selfie_path'     => 'storage/selfies/' . basename($selfiePath),
    ':ip'              => ip_to_bin($_SERVER['REMOTE_ADDR'] ?? null),
    ':ua'              => $_SERVER['HTTP_USER_AGENT'] ?? '',
]);

// ---------- Emails ----------
require_once __DIR__ . '/mail.php';
try {
    // Reuse the $record you wrote to JSONL (it already contains everything)
    sendSubmissionEmails($record);
} catch (Throwable $e) {
    error_log('sendSubmissionEmails failed: ' . $e->getMessage());
}

// ---------- Success ----------
json_response(200, [
    'ok' => true,
    'id' => $id,
    'message' => 'Submission received.',
    'selfie_path' => 'storage/selfies/' . basename($selfiePath),
    'received_at' => $record['received_at'],
]);
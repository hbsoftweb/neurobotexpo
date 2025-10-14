<?php
// submit.php
declare(strict_types=1);

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
function json_response(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_dir(string $path): void
{
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

function data_url_to_png(string $dataUrl): string
{
    // Expects "data:image/png;base64,AAAA..."
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

function digits_only(string $s): string
{
    return preg_replace('/\D+/', '', $s) ?? '';
}

// ---------- Session & CSRF ----------
session_start();
$sessionCsrf = $_SESSION['csrf_token'] ?? null;

// ---------- Read body (JSON preferred; form as fallback) ----------
$ctype = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input') ?: '';

$body = [];
if (stripos($ctype, 'application/json') !== false && $raw !== '') {
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        json_response(400, ['ok' => false, 'error' => 'Invalid JSON body.']);
    }
} else {
    // Fallback for form posts
    $body = $_POST;
    if (isset($body['selfie_data']) && !isset($body['selfie']['data_url'])) {
        $body['selfie'] = ['mime' => 'image/png', 'data_url' => $body['selfie_data']];
    }
}

// ---------- Normalise expected structure ----------
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
        // Accept both JSON ("industries") and form ("industry[]")
        'industries' => $body['industries'] ?? ($body['industry'] ?? []),
        'industry_other' => trim($body['industry_other'] ?? ''),
        // Accept both JSON ("applications") and form ("application[]")
        'applications' => $body['applications'] ?? ($body['application'] ?? []),
        'special_mention' => trim($body['special_mention'] ?? ''),
    ],
    'selfie' => $body['selfie'] ?? [
        'mime' => 'image/png',
        'data_url' => $body['selfie_data'] ?? '',
    ],
    'csrf_token' => $body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''),
];

// If submitted as classic form, PHP uses 'industry' and 'application' keys (from name="industry[]" etc.)
if (isset($_POST['industry']) && is_array($_POST['industry'])) {
    $payload['visitor']['industries'] = array_values(array_unique(array_map('strval', $_POST['industry'])));
}
if (isset($_POST['application']) && is_array($_POST['application'])) {
    $payload['visitor']['applications'] = array_values(array_unique(array_map('strval', $_POST['application'])));
}

// ---------- CSRF check (relaxed to allow Postman testing) ----------
$clientCsrf = (string) ($payload['csrf_token'] ?? '');
if ($sessionCsrf) {
    if ($clientCsrf === '' || !hash_equals($sessionCsrf, $clientCsrf)) {
        json_response(403, ['ok' => false, 'error' => 'CSRF validation failed.']);
    }
}

// ---------- Validate required fields ----------
$errors = [];

$visitor = $payload['visitor'];
$visitor['name'] = trim((string) $visitor['name']);
$visitor['company_name'] = trim((string) $visitor['company_name']);
$visitor['contact_number'] = trim((string) $visitor['contact_number']);
$visitor['email'] = trim((string) $visitor['email']);
$visitor['designation'] = (string) $visitor['designation'];
$visitor['designation_other'] = trim((string) $visitor['designation_other']);
$visitor['special_mention'] = trim((string) $visitor['special_mention']);

$industries = is_array($visitor['industries']) ? $visitor['industries'] : [];
$applications = is_array($visitor['applications']) ? $visitor['applications'] : [];

if ($visitor['name'] === '')             $errors['name'] = 'Name is required.';
if ($visitor['company_name'] === '')     $errors['company_name'] = 'Company name is required.';

$digitsPhone = digits_only($visitor['contact_number']);
if ($digitsPhone === '' || strlen($digitsPhone) < 7 || strlen($digitsPhone) > 15) {
    $errors['contact_number'] = 'Provide a valid phone (7–15 digits).';
} else {
    $visitor['contact_number'] = $digitsPhone;
}

if (!filter_var($visitor['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Provide a valid email.';
}

if ($visitor['designation'] === '') {
    $errors['designation'] = 'Designation is required.';
}
if (strcasecmp($visitor['designation'], 'Other') === 0 && $visitor['designation_other'] === '') {
    $errors['designation_other'] = 'Please specify other designation.';
}

$industries = array_values(array_unique(array_map('strval', $industries)));
if (count($industries) < 1) {
    $errors['industries'] = 'Select at least one industry.';
}
if (in_array('Other', $industries, true) && ($visitor['industry_other'] ?? '') === '') {
    $errors['industry_other'] = 'Please specify other industry.';
}

$applications = array_values(array_unique(array_map('strval', $applications)));
if (count($applications) < 1) {
    $errors['applications'] = 'Select at least one application.';
}

$selfie = $payload['selfie'];
$selfieDataUrl = is_array($selfie) ? ($selfie['data_url'] ?? '') : '';
if ($selfieDataUrl === '') {
    $errors['selfie'] = 'Selfie is required.';
}

if (!empty($errors)) {
    json_response(422, ['ok' => false, 'errors' => $errors]);
}

// ---------- Persist selfie ----------
$storageDir = __DIR__ . '/storage';
$imagesDir  = $storageDir . '/selfies';
$dataFile   = $storageDir . '/submissions.jsonl';

ensure_dir($imagesDir);

// Generate an ID: EVT-YYYYMMDD-HHMMSS-xxxx
$eventId = preg_replace('/[^A-Za-z0-9_-]+/', '', (string) ($payload['meta']['event_id'] ?? 'EXPO'));
$uid     = bin2hex(random_bytes(3));
$stamp   = date('Ymd-His');
$id      = sprintf('%s-%s-%s', $eventId ?: 'EXPO', $stamp, $uid);

// Decode and save selfie
try {
    $pngBytes = data_url_to_png($selfieDataUrl);
} catch (Throwable $e) {
    json_response(400, ['ok' => false, 'error' => 'Invalid selfie image: ' . $e->getMessage()]);
}

$selfiePath = $imagesDir . '/' . $id . '.png';
if (@file_put_contents($selfiePath, $pngBytes) === false) {
    json_response(500, ['ok' => false, 'error' => 'Failed to save selfie image.']);
}

// ---------- Persist record (JSON Lines) ----------
ensure_dir($storageDir);
$record = [
    'id' => $id,
    'received_at' => date('c'),
    'meta' => [
        'source' => (string) ($payload['meta']['source'] ?? 'exhibition-form'),
        'event_id' => $eventId,
        'submitted_at' => (string) ($payload['meta']['submitted_at'] ?? date('c')),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ],
    'visitor' => [
        'name' => $visitor['name'],
        'company_name' => $visitor['company_name'],
        'contact_number' => $visitor['contact_number'],
        'email' => $visitor['email'],
        'designation' => $visitor['designation'],
        'designation_other' => $visitor['designation_other'],
        'industries' => $industries,
        'industry_other' => (string) ($visitor['industry_other'] ?? ''),
        'applications' => $applications,
        'special_mention' => $visitor['special_mention'],
    ],
    'assets' => [
        'selfie' => [
            'path' => 'storage/selfies/' . basename($selfiePath),
            'mime' => 'image/png',
            'bytes' => strlen($pngBytes),
        ]
    ]
];

$line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
if (@file_put_contents($dataFile, $line, FILE_APPEND | LOCK_EX) === false) {
    json_response(500, ['ok' => false, 'error' => 'Failed to write submission record.']);
}

// ---------- Success ----------
json_response(200, [
    'ok' => true,
    'id' => $id,
    'message' => 'Submission received.',
    'selfie_path' => $record['assets']['selfie']['path'],
    'received_at' => $record['received_at'],
]);

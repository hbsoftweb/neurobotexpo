<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------- CORS / JSON ----------
header('Access-Control-Allow-Origin: ' . CORS_ALLOW_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// ---------- Helpers ----------
function json_response(int $status, array $data): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function ensure_dir(string $p): void {
  if (!is_dir($p)) @mkdir($p, 0775, true);
}
function ip_to_bin(?string $ip): ?string {
  $b = @inet_pton((string)$ip);
  return $b === false ? null : $b;
}
function require_api_key(): void {
  $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
  if (!$key || !hash_equals(API_KEY, $key)) {
    json_response(401, ['ok' => false, 'error' => 'Unauthorized']);
  }
}
function read_json(): array {
  $raw = file_get_contents('php://input') ?: '';
  $data = json_decode($raw, true);
  if (!is_array($data)) json_response(400, ['ok' => false, 'error' => 'Invalid JSON body']);
  return $data;
}
function data_url_png_bytes(string $dataUrl): string {
  if (stripos($dataUrl, 'data:image/') !== 0) throw new RuntimeException('Invalid selfie data URL');
  $parts = explode(',', $dataUrl, 2);
  if (count($parts) !== 2) throw new RuntimeException('Malformed data URL');
  $raw = base64_decode($parts[1], true);
  if ($raw === false) throw new RuntimeException('Base64 decode failed');
  return $raw;
}
function digits_only(string $s): string {
  return preg_replace('/\D+/', '', $s) ?? '';
}
/** Create a stable exhibition code (e.g., ENGIEXPO-2025) from a friendly name */
function slugify(string $s): string {
  $s = trim($s);
  // allow letters/numbers/space/_/-
  $s = preg_replace('/[^\p{L}\p{N}\-_\s]/u', '', $s) ?? '';
  // collapse whitespace to single hyphen
  $s = preg_replace('/\s+/', '-', $s) ?? '';
  $s = strtoupper($s);
  $s = substr($s, 0, 80);
  return $s !== '' ? $s : ('EXPO-' . date('Y'));
}

// ---------- Routing ----------
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. '/api'
$rel    = '/' . ltrim(substr($path, strlen($base)), '/'); // path relative to /api

// =====================================================
// ===============  EXHIBITIONS ROUTES  ================
// =====================================================

// POST /api/exhibitions  → create exhibition (stall-side)
if ($method === 'POST' && preg_match('#^/exhibitions/?$#', $rel)) {
  $in   = read_json();
  $name = trim((string)($in['name'] ?? ''));
  $code = trim((string)($in['code'] ?? ''));

  if ($name === '') {
    json_response(422, ['ok' => false, 'error' => 'Name is required']);
  }
  if ($code === '') {
    $code = slugify($name);
  }

  $pdo = db();

  // block duplicates by code
  $chk = $pdo->prepare("SELECT id FROM exhibitions WHERE code=:c LIMIT 1");
  $chk->execute([':c' => $code]);
  if ($chk->fetch()) {
    json_response(409, ['ok' => false, 'error' => 'Exhibition already exists', 'code' => $code]);
  }

  $ins = $pdo->prepare("INSERT INTO exhibitions (name, code, is_active) VALUES (:n, :c, 1)");
  $ins->execute([':n' => $name, ':c' => $code]);
  $id  = (int)$pdo->lastInsertId();

  json_response(201, ['ok' => true, 'id' => $id, 'name' => $name, 'code' => $code]);
}

// GET /api/exhibitions  → list exhibitions (optional filters)
// NOTE: This endpoint is intentionally left open (no API key) so the stall can show the picker.
if ($method === 'GET' && preg_match('#^/exhibitions/?$#', $rel)) {
  $pdo    = db();
  $active = isset($_GET['active']) ? (int)$_GET['active'] : null;  // 1 or 0
  $q      = trim((string)($_GET['q'] ?? ''));
  $limit  = max(1, min(100, (int)($_GET['limit'] ?? 50)));

  $sql = "SELECT id,name,code,is_active,created_at FROM exhibitions";
  $where = [];
  $params = [];

  if ($active !== null) { $where[] = "is_active = :a"; $params[':a'] = $active; }
  if ($q !== '')        { $where[] = "(name LIKE :q OR code LIKE :q)"; $params[':q'] = "%$q%"; }

  if ($where) $sql .= " WHERE " . implode(' AND ', $where);
  $sql .= " ORDER BY id DESC LIMIT :lim";

  $st = $pdo->prepare($sql);
  foreach ($params as $k => $v) $st->bindValue($k, $v);
  $st->bindValue(':lim', $limit, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll();

  json_response(200, ['ok' => true, 'items' => $rows]);
}

// GET /api/exhibitions/{code} → fetch a single exhibition by code (open)
if ($method === 'GET' && preg_match('#^/exhibitions/([A-Za-z0-9\-_]+)$#', $rel, $m)) {
  $code = $m[1];
  $st = db()->prepare("SELECT id,name,code,is_active,created_at FROM exhibitions WHERE code=:c LIMIT 1");
  $st->execute([':c' => $code]);
  $row = $st->fetch();
  if (!$row) json_response(404, ['ok' => false, 'error' => 'Not found']);
  json_response(200, ['ok' => true, 'item' => $row]);
}

// =====================================================
// ===============  SUBMISSIONS ROUTES  ================
// =====================================================

// POST /api/submissions  → create submission (public)
if ($method === 'POST' && $rel === '/submissions') {
  $in = read_json();
  $m = $in['meta'] ?? [];
  $v = $in['visitor'] ?? [];
  $s = $in['selfie'] ?? [];
  $err = [];

  $name  = trim((string)($v['name'] ?? ''));
  $comp  = trim((string)($v['company_name'] ?? ''));
  $phone = trim((string)($v['contact_number'] ?? ''));
  $email = trim((string)($v['email'] ?? ''));
  $des   = (string)($v['designation'] ?? '');
  $des_o = trim((string)($v['designation_other'] ?? ''));
  $inds  = $v['industries'] ?? [];
  $ind_o = trim((string)($v['industry_other'] ?? ''));
  $apps  = $v['applications'] ?? [];
  $spec  = trim((string)($v['special_mention'] ?? ''));

  $event  = trim((string)(($m['event_id'] ?? '') ?: ''));
  $source = trim((string)($m['source'] ?? 'exhibition-form'));

  if ($name === '')                           $err['name'] = 'Name is required';
  if ($comp === '')                           $err['company_name'] = 'Company name is required';
  $digits = digits_only($phone);
  if ($digits === '' || strlen($digits) < 7 || strlen($digits) > 15)
                                              $err['contact_number'] = 'Provide a valid phone (7–15 digits)';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                                              $err['email'] = 'Provide a valid email';
  if ($des === '')                            $err['designation'] = 'Designation is required';
  if (strcasecmp($des, 'Other') === 0 && $des_o === '')
                                              $err['designation_other'] = 'Please specify other designation';
  if (!is_array($inds) || count($inds) < 1)   $err['industries'] = 'Select at least one industry';
  if (is_array($inds) && in_array('Other', $inds, true) && $ind_o === '')
                                              $err['industry_other'] = 'Please specify other industry';
  if (!is_array($apps) || count($apps) < 1)   $err['applications'] = 'Select at least one application';

  $dataUrl = (string)($s['data_url'] ?? '');
  if ($dataUrl === '')                        $err['selfie'] = 'Selfie is required';

  if (!empty($err)) json_response(422, ['ok' => false, 'errors' => $err]);

  ensure_dir(SELFIES_DIR);
  $stamp = date('Ymd-His');
  $rand  = bin2hex(random_bytes(3));
  $slug  = ($event !== '' ? preg_replace('/[^A-Za-z0-9_-]+/', '', $event) : 'EXPO') . "-$stamp-$rand";

  try {
    $bytes = data_url_png_bytes($dataUrl);
  } catch (Throwable $e) {
    json_response(400, ['ok' => false, 'error' => 'Invalid selfie image: ' . $e->getMessage()]);
  }

  $relPath = 'storage/selfies/' . $slug . '.png';
  $absPath = SELFIES_DIR . '/' . $slug . '.png';
  if (@file_put_contents($absPath, $bytes) === false) {
    json_response(500, ['ok' => false, 'error' => 'Failed to save selfie image']);
  }

  $pdo = db();
  $sql = "INSERT INTO submissions
          (event_id, source, submitted_at, name, company_name, contact_number, email,
           designation, designation_other, industries, industry_other, applications,
           special_mention, selfie_path, ip, ua)
          VALUES (:event_id,:source,:submitted_at,:name,:company,:phone,:email,
                  :des,:des_o,:inds,:ind_o,:apps,:spec,:selfie,:ip,:ua)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':event_id'     => $event ?: null,
    ':source'       => $source,
    ':submitted_at' => date('Y-m-d H:i:s'),
    ':name'         => $name,
    ':company'      => $comp,
    ':phone'        => $digits,
    ':email'        => $email,
    ':des'          => $des,
    ':des_o'        => $des_o ?: null,
    ':inds'         => json_encode(array_values(array_unique(array_map('strval', $inds))), JSON_UNESCAPED_UNICODE),
    ':ind_o'        => $ind_o ?: null,
    ':apps'         => json_encode(array_values(array_unique(array_map('strval', $apps))), JSON_UNESCAPED_UNICODE),
    ':spec'         => $spec,
    ':selfie'       => $relPath,
    ':ip'           => ip_to_bin($_SERVER['REMOTE_ADDR'] ?? null),
    ':ua'           => $_SERVER['HTTP_USER_AGENT'] ?? null,
  ]);
  $id = (int)db()->lastInsertId();

  json_response(201, ['ok' => true, 'id' => $id, 'message' => 'Submission created', 'selfie_path' => $relPath]);
}

// GET /api/submissions  → list (protected by API key)
// supports optional filters: ?event_id=CODE&from=YYYY-MM-DD&to=YYYY-MM-DD
if ($method === 'GET' && preg_match('#^/submissions/?$#', $rel)) {
  require_api_key();

  $page = max(1, (int)($_GET['page'] ?? 1));
  $size = max(1, min(100, (int)($_GET['page_size'] ?? 20)));
  $off  = ($page - 1) * $size;

  $eventId = trim((string)($_GET['event_id'] ?? ''));
  $from = trim((string)($_GET['from'] ?? '')); // YYYY-MM-DD
  $to   = trim((string)($_GET['to'] ?? ''));   // YYYY-MM-DD

  $where = [];
  $params = [];

  if ($eventId !== '') {
    $where[] = 'event_id = :eid';
    $params[':eid'] = $eventId;
  }
  if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = 'submitted_at >= :from_dt';
    $params[':from_dt'] = $from . ' 00:00:00';
  }
  if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = 'submitted_at <= :to_dt';
    $params[':to_dt'] = $to . ' 23:59:59';
  }

  $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

  $pdo = db();

  // total count
  $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM submissions$whereSql");
  foreach ($params as $k => $v) $stmtTotal->bindValue($k, $v);
  $stmtTotal->execute();
  $total = (int)$stmtTotal->fetchColumn();

  // page data
  $sql = "SELECT id,event_id,source,submitted_at,name,company_name,contact_number,email,
                 designation,designation_other,industries,industry_other,applications,
                 special_mention,selfie_path,created_at
          FROM submissions
          $whereSql
          ORDER BY id DESC
          LIMIT :lim OFFSET :off";
  $st = $pdo->prepare($sql);
  foreach ($params as $k => $v) $st->bindValue($k, $v);
  $st->bindValue(':lim', $size, PDO::PARAM_INT);
  $st->bindValue(':off', $off, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll();

  foreach ($rows as &$r) {
    $r['industries']   = json_decode($r['industries']   ?? '[]', true) ?: [];
    $r['applications'] = json_decode($r['applications'] ?? '[]', true) ?: [];
  }

  json_response(200, ['ok' => true, 'page' => $page, 'page_size' => $size, 'total' => $total, 'items' => $rows]);
}

// GET /api/submissions/{id}  → show (protected)
if ($method === 'GET' && preg_match('#^/submissions/(\d+)$#', $rel, $m)) {
  require_api_key();

  $id = (int)$m[1];
  $stmt = db()->prepare("SELECT id,event_id,source,submitted_at,name,company_name,contact_number,email,
                                designation,designation_other,industries,industry_other,applications,
                                special_mention,selfie_path,created_at
                         FROM submissions WHERE id=:id");
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch();
  if (!$row) json_response(404, ['ok' => false, 'error' => 'Not found']);

  $row['industries']   = json_decode($row['industries']   ?? '[]', true) ?: [];
  $row['applications'] = json_decode($row['applications'] ?? '[]', true) ?: [];

  json_response(200, ['ok' => true, 'item' => $row]);
}

// Fallback 404
json_response(404, ['ok' => false, 'error' => 'Route not found']);

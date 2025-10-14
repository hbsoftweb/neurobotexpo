<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ---------- CORS / JSON ----------
header('Access-Control-Allow-Origin: ' . CORS_ALLOW_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ---------- Helpers ----------
function json_response(int $status, array $data): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function ensure_dir(string $p): void { if (!is_dir($p)) @mkdir($p, 0775, true); }
function ip_to_bin(?string $ip): ?string { $b=@inet_pton((string)$ip); return $b===false?null:$b; }
function require_api_key(): void {
  $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
  if (!$key || !hash_equals(API_KEY, $key)) json_response(401, ['ok'=>false,'error'=>'Unauthorized']);
}
function read_json(): array {
  $raw = file_get_contents('php://input') ?: '';
  $data = json_decode($raw, true);
  if (!is_array($data)) json_response(400, ['ok'=>false,'error'=>'Invalid JSON body']);
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
function digits_only(string $s): string { return preg_replace('/\D+/', '', $s) ?? ''; }

// ---------- Routing ----------
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. '/api'
$rel    = '/' . ltrim(substr($path, strlen($base)), '/'); // path relative to /api

if ($method === 'POST' && $rel === '/submissions') {
  // CREATE
  $in  = read_json();
  $m   = $in['meta'] ?? [];
  $v   = $in['visitor'] ?? [];
  $s   = $in['selfie'] ?? [];
  $err = [];

  $name  = trim($v['name'] ?? '');
  $comp  = trim($v['company_name'] ?? '');
  $phone = trim($v['contact_number'] ?? '');
  $email = trim($v['email'] ?? '');
  $des   = (string)($v['designation'] ?? '');
  $des_o = trim($v['designation_other'] ?? '');
  $inds  = $v['industries'] ?? [];
  $ind_o = trim($v['industry_other'] ?? '');
  $apps  = $v['applications'] ?? [];
  $spec  = trim($v['special_mention'] ?? '');

  $event = trim(($m['event_id'] ?? '') ?: '');
  $source= trim(($m['source'] ?? 'exhibition-form'));

  if ($name==='')  $err['name']='Name is required';
  if ($comp==='')  $err['company_name']='Company name is required';
  $digits = digits_only($phone);
  if ($digits==='' || strlen($digits)<7 || strlen($digits)>15) $err['contact_number']='Provide a valid phone (7–15 digits)';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email']='Provide a valid email';
  if ($des==='') $err['designation']='Designation is required';
  if (strcasecmp($des,'Other')===0 && $des_o==='') $err['designation_other']='Please specify other designation';
  if (!is_array($inds) || count($inds)<1) $err['industries']='Select at least one industry';
  if (is_array($inds) && in_array('Other',$inds,true) && $ind_o==='') $err['industry_other']='Please specify other industry';
  if (!is_array($apps) || count($apps)<1) $err['applications']='Select at least one application';
  $dataUrl = (string)($s['data_url'] ?? '');
  if ($dataUrl==='') $err['selfie']='Selfie is required';

  if (!empty($err)) json_response(422, ['ok'=>false,'errors'=>$err]);

  ensure_dir(SELFIES_DIR);
  $stamp = date('Ymd-His');
  $rand  = bin2hex(random_bytes(3));
  $slug  = ($event!=='' ? preg_replace('/[^A-Za-z0-9_-]+/','',$event) : 'EXPO') . "-$stamp-$rand";

  try { $bytes = data_url_png_bytes($dataUrl); }
  catch (Throwable $e) { json_response(400, ['ok'=>false,'error'=>'Invalid selfie image: '.$e->getMessage()]); }

  $relPath = 'storage/selfies/' . $slug . '.png';
  $absPath = SELFIES_DIR . '/' . $slug . '.png';
  if (@file_put_contents($absPath, $bytes) === false) {
    json_response(500, ['ok'=>false,'error'=>'Failed to save selfie image']);
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
    ':event_id' => $event ?: null,
    ':source'   => $source,
    ':submitted_at' => date('Y-m-d H:i:s'),
    ':name' => $name,
    ':company' => $comp,
    ':phone' => $digits,
    ':email' => $email,
    ':des' => $des,
    ':des_o' => $des_o ?: null,
    ':inds' => json_encode(array_values(array_unique(array_map('strval',$inds))), JSON_UNESCAPED_UNICODE),
    ':ind_o' => $ind_o ?: null,
    ':apps' => json_encode(array_values(array_unique(array_map('strval',$apps))), JSON_UNESCAPED_UNICODE),
    ':spec' => $spec,
    ':selfie' => $relPath,
    ':ip' => ip_to_bin($_SERVER['REMOTE_ADDR'] ?? null),
    ':ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
  ]);
  $id = (int)db()->lastInsertId();

  json_response(201, ['ok'=>true,'id'=>$id,'message'=>'Submission created','selfie_path'=>$relPath]);
}

if ($method === 'GET' && preg_match('#^/submissions/?$#', $rel)) {
  // LIST (protected)
  $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
  if (!$key || !hash_equals(API_KEY, $key)) json_response(401, ['ok'=>false,'error'=>'Unauthorized']);

  $page = max(1,(int)($_GET['page'] ?? 1));
  $size = max(1,min(100,(int)($_GET['page_size'] ?? 20)));
  $off  = ($page-1)*$size;

  $pdo = db();
  $total = (int)$pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();

  $stmt = $pdo->prepare("SELECT id,event_id,source,submitted_at,name,company_name,contact_number,email,
                                designation,designation_other,industries,industry_other,applications,
                                special_mention,selfie_path,created_at
                         FROM submissions ORDER BY id DESC LIMIT :lim OFFSET :off");
  $stmt->bindValue(':lim', $size, PDO::PARAM_INT);
  $stmt->bindValue(':off', $off, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();

  foreach ($rows as &$r) {
    $r['industries']  = json_decode($r['industries'] ?? '[]', true) ?: [];
    $r['applications']= json_decode($r['applications'] ?? '[]', true) ?: [];
  }

  json_response(200, ['ok'=>true,'page'=>$page,'page_size'=>$size,'total'=>$total,'items'=>$rows]);
}

if ($method === 'GET' && preg_match('#^/submissions/(\d+)$#', $rel, $m)) {
  // SHOW (protected)
  $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
  if (!$key || !hash_equals(API_KEY, $key)) json_response(401, ['ok'=>false,'error'=>'Unauthorized']);

  $id = (int)$m[1];
  $stmt = db()->prepare("SELECT id,event_id,source,submitted_at,name,company_name,contact_number,email,
                                designation,designation_other,industries,industry_other,applications,
                                special_mention,selfie_path,created_at
                         FROM submissions WHERE id=:id");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch();
  if (!$row) json_response(404, ['ok'=>false,'error'=>'Not found']);
  $row['industries']   = json_decode($row['industries'] ?? '[]', true) ?: [];
  $row['applications'] = json_decode($row['applications'] ?? '[]', true) ?: [];
  json_response(200, ['ok'=>true,'item'=>$row]);
}

json_response(404, ['ok'=>false,'error'=>'Route not found']);

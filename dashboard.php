<?php
// dashboard.php — bound to /api/submissions?page=&page_size=
// Protected dashboard that fetches API data, renders table + accordion, supports CSV export & logout
declare(strict_types=1);

// ---- Session & cache guards ----
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/api/config.php';

// ---- Helpers ----
function go(string $to): void
{
    $base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header('Location: ' . $base . '/' . ltrim($to, '/'));
    exit;
}
function h(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
function base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return $scheme . '://' . $host . $base;
}

// ---- Guard: must be logged in ----
if (empty($_SESSION['admin_email'])) {
    go('admin.php?r=1');
}

// ---- Idle timeout (30 min) ----
$IDLE_LIMIT = 1800;
if (!empty($_SESSION['logged_in_at']) && (time() - $_SESSION['logged_in_at'] > $IDLE_LIMIT)) {
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    $_SESSION = [];
    session_destroy();
    go('admin.php?r=1');
} else {
    $_SESSION['logged_in_at'] = time();
}

// ---------- API fetch (primary) ----------
function api_fetch_submissions(int $page, int $page_size, array &$meta): array
{
    $url = base_url() . "/api/submissions?page={$page}&page_size={$page_size}";
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if (defined('API_KEY') && API_KEY)
        $headers[] = 'X-API-Key: ' . API_KEY;

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $meta['last_url'] = $url;
    $meta['http'] = $code;
    $meta['err'] = $err ?: null;

    if ($err || $code < 200 || $code >= 300 || !$resp)
        return [];
    $json = json_decode($resp, true);
    if (!is_array($json) || empty($json['ok']))
        return [];

    $meta['page'] = (int) ($json['page'] ?? $page);
    $meta['page_size'] = (int) ($json['page_size'] ?? $page_size);
    $meta['total'] = (int) ($json['total'] ?? 0);

    return $json['items'] ?? [];
}

// Pull ALL pages for CSV
function api_fetch_all(array &$meta): array
{
    $page = 1;
    $page_size = 100;
    $all = [];
    do {
        $items = api_fetch_submissions($page, $page_size, $meta);
        if (empty($items))
            break;
        $all = array_merge($all, $items);
        $got = count($items);
        $total = $meta['total'] ?? 0;
        $page++;
        if ($got < $page_size)
            break;
        if ($total && count($all) >= $total)
            break;
    } while (true);
    return $all;
}

// Heuristic → category badges from `applications`
function badges_from_applications($apps): array
{
    $labels = [];
    if (is_string($apps) && $apps !== '')
        $apps = [$apps];
    if (!is_array($apps))
        $apps = [];
    $txt = strtolower(implode(' | ', $apps));

    if (strpos($txt, 'microscope') !== false)
        $labels[] = ['Microscope', 'category-microscope'];
    if (strpos($txt, 'zebra') !== false || strpos($txt, 'vision') !== false || strpos($txt, 'profiler') !== false || strpos($txt, 'z-track') !== false)
        $labels[] = ['Vision', 'category-vision'];
    if (strpos($txt, 'printer') !== false)
        $labels[] = ['Printer', 'category-printer'];

    if (empty($labels) && !empty($apps)) {
        foreach (array_slice($apps, 0, 2) as $a)
            $labels[] = [trim((string) $a), 'category'];
    }
    return $labels;
}

// Selfie URL with fallback
function selfie_src(?string $p): string
{
    $p = (string) $p;
    if ($p !== '') {
        if (preg_match('#^https?://#', $p))
            return $p;
        return ltrim($p, '/');
    }
    return 'assets/images/scalaton.webp';
}

// Industries renderer
function industries_to_string($v): string
{
    if (is_array($v))
        return implode(', ', $v);
    if (is_string($v) && $v !== '')
        return $v;
    return '—';
}

// ---------- Get data for UI ----------
$meta = [];
$ui_page = max(1, (int) ($_GET['page'] ?? 1));
$ui_page_size = max(1, min(100, (int) ($_GET['page_size'] ?? 10)));
$items = api_fetch_submissions($ui_page, $ui_page_size, $meta);

// ---------- CSV export ----------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $all = api_fetch_all($meta);
    $filename = 'neurobot-expo-export-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Event', 'Source', 'Submitted At', 'Name', 'Company', 'Contact', 'Email', 'Designation', 'Industries', 'Applications', 'Special Mention', 'Selfie', 'Created At']);
    foreach ($all as $r) {
        fputcsv($out, [
            $r['id'] ?? '',
            $r['event_id'] ?? '',
            $r['source'] ?? '',
            $r['submitted_at'] ?? '',
            $r['name'] ?? '',
            $r['company_name'] ?? '',
            $r['contact_number'] ?? '',
            $r['email'] ?? '',
            $r['designation'] ?? '',
            industries_to_string($r['industries'] ?? ''),
            industries_to_string($r['applications'] ?? ''),
            $r['special_mention'] ?? '',
            $r['selfie_path'] ?? '',
            $r['created_at'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .muted {
            opacity: .7;
        }

        .click {
            cursor: pointer;
        }

        /* ensure accordion text shows on dark bg */
        .inquiry-data__accordion {
            background: #000;
        }

        /* (optional) if you want the inner content always visible when expanded, keep height auto after transition end */
    </style>
</head>

<body>
    <div>
        <!-- Header -->
        <div class="header-wrapper">
            <div class="header-logo-wrapper" style="display: flex; justify-content: stretch;">
                <img alt="Header Logo" loading="lazy" width="942" height="150" decoding="async"
                    src="assets/images/Neurobot-Logo.svg" style="color: transparent;">
            </div>

            <div class="input-container">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512"
                    class="search-icon" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z">
                    </path>
                </svg>
                <input id="searchInput" class="input-search" placeholder="Search..." type="text" value="">
            </div>

            <div id="exportBtn" class="export-icon-wrapper click" title="Download CSV">
                <img alt="Download icon" loading="lazy" width="512" height="512" decoding="async" class="export-icon"
                    src="assets/images/download.svg" style="color: transparent;">
            </div>

            <div class="button-wrapper-logout">
                <form method="get" action="admin.php" style="height:100%;">
                    <input type="hidden" name="action" value="logout">
                    <button class="btn-logout" style="height: 100%">Log Out</button>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="inquiry-data">
            <table class="inquiry-data__table" id="dataTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Designation</th>
                        <th>Industry</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="muted" style="padding:16px;">No entries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $r):
                            $name = $r['name'] ?? '—';
                            $company = $r['company_name'] ?? '—';
                            $phone = $r['contact_number'] ?? '—';
                            $desig = $r['designation'] ?? '—';
                            $inds = industries_to_string($r['industries'] ?? []);
                            $apps = $r['applications'] ?? [];
                            $badges = badges_from_applications($apps);
                            $selfie = selfie_src($r['selfie_path'] ?? '');
                            $notes = $r['special_mention'] ?? '—';
                            $date = $r['created_at'] ?? ($r['submitted_at'] ?? '');
                            if ($date) {
                                $ts = strtotime($date);
                                if ($ts)
                                    $date = date('d M Y - h:i A', $ts);
                            }
                            ?>
                            <!-- Row -->
                            <tr class="inquiry-data__row click" data-role="toggle">
                                <td><img alt="User Selfie" loading="lazy" width="201" height="201" decoding="async"
                                        class="inquiry-data__selfie" src="<?= h($selfie) ?>" style="color: transparent;"></td>
                                <td><?= h($name) ?></td>
                                <td><?= h($company) ?></td>
                                <td class="width240"><?= h($phone) ?></td>
                                <td><?= h($desig) ?></td>
                                <td class="width300"><?= h($inds) ?></td>
                                <td>
                                    <?php if (empty($badges)): ?>—
                                    <?php else:
                                        foreach ($badges as [$label, $cls]): ?>
                                            <span class="category <?= h($cls) ?>"><?= h($label) ?></span>
                                        <?php endforeach; endif; ?>
                                </td>
                            </tr>

                            <!-- Expanded block -->
                            <tr class="inquiry-data__accordion">
                                <td colspan="7">
                                    <div class="inquiry-data__accordion-content">
                                        <div class="wrapper-left-expand">
                                            <div><img alt="User Selfie" class="inquiry-data__selfie-big"
                                                    src="<?= h($selfie) ?>">
                                            </div>
                                            <p class="profile-info"><?= h($name) ?></p>
                                            <p class="profile-info"><?= h($company) ?></p>
                                            <p class="profile-info"><?= h($desig) ?></p>
                                        </div>
                                        <div class="wrapper-right-expand" style="display:flex;flex-direction:column;gap:6px;">
                                            <p><b class="label-left-expand">Contact
                                                    Number:</b>&nbsp;&nbsp;&nbsp;<?= h($phone) ?>
                                            </p>
                                            <p><b class="label-left-expand">Industry:</b>&nbsp;&nbsp;&nbsp;<?= h($inds) ?></p>
                                            <p><b
                                                    class="label-left-expand">Applications:</b>&nbsp;&nbsp;&nbsp;<?= h(industries_to_string($apps)) ?>
                                            </p>
                                            <p><b class="label-left-expand">Special
                                                    Mention:</b>&nbsp;&nbsp;&nbsp;<?= h($notes) ?>
                                            </p>
                                            <p><b
                                                    class="label-left-expand">Submitted:</b>&nbsp;&nbsp;&nbsp;<?= h($r['submitted_at'] ?? '—') ?>
                                            </p>
                                            <p><b class="label-left-expand">Created:</b>&nbsp;&nbsp;&nbsp;<?= h($date ?: '—') ?>
                                            </p>
                                            <p><b class="label-left-expand">Event
                                                    ID:</b>&nbsp;&nbsp;&nbsp;<?= h($r['event_id'] ?? '—') ?></p>
                                            <p><b
                                                    class="label-left-expand">Email:</b>&nbsp;&nbsp;&nbsp;<?= h($r['email'] ?? '—') ?>
                                            </p>
                                            <p><b
                                                    class="label-left-expand">Source:</b>&nbsp;&nbsp;&nbsp;<?= h($r['source'] ?? '—') ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pager -->
            <?php
            $total = (int) ($meta['total'] ?? 0);
            $page = (int) ($meta['page'] ?? $ui_page);
            $page_size = (int) ($meta['page_size'] ?? $ui_page_size);
            $pages = $page_size ? (int) ceil(($total ?: 0) / $page_size) : 1;
            if ($pages > 1):
                $makeLink = function ($p) use ($page_size) {
                    $url = new \stdClass();
                    $url->q = $_GET ?? [];
                    $url->q['page'] = $p;
                    $url->q['page_size'] = $page_size;
                    return '?' . http_build_query($url->q);
                };
                ?>
                <div class="pager">
                    <?php if ($page > 1): ?>
                        <a href="<?= h($makeLink($page - 1)) ?>">‹ Prev</a>
                    <?php else: ?><span class="muted">‹ Prev</span><?php endif; ?>
                    <span>Page <span class="active"><?= h((string) $page) ?></span> of <?= h((string) $pages) ?></span>
                    <?php if ($page < $pages): ?>
                        <a href="<?= h($makeLink($page + 1)) ?>">Next ›</a>
                    <?php else: ?><span class="muted">Next ›</span><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle the accordion by expanding/collapsing the INNER content div
        document.addEventListener('click', function (e) {
            const row = e.target.closest('tr[data-role="toggle"]');
            if (!row) return;

            const acc = row.nextElementSibling;
            if (!acc || !acc.classList.contains('inquiry-data__accordion')) return;

            const content = acc.querySelector('.inquiry-data__accordion-content');
            if (!content) return;

            // Optional: close any other open accordion (one-at-a-time behaviour)
            document.querySelectorAll('tr.inquiry-data__accordion.expanded').forEach(openAcc => {
                if (openAcc !== acc) {
                    const c = openAcc.querySelector('.inquiry-data__accordion-content');
                    if (c) c.style.maxHeight = '0px';
                    openAcc.classList.remove('expanded');
                }
            });

            if (acc.classList.contains('expanded')) {
                // collapse
                content.style.maxHeight = '0px';
                acc.classList.remove('expanded');
            } else {
                // expand to natural height
                acc.classList.add('expanded');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });

        // Ensure all accordions start collapsed (in case of SSR styles or browser restore)
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.inquiry-data__accordion .inquiry-data__accordion-content')
                .forEach(c => { c.style.maxHeight = '0px'; });
        });

        // Search (client-side filter on current page); collapse any open accordion for hidden rows
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const q = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('tr.inquiry-data__row');
                rows.forEach(r => {
                    const text = r.innerText.toLowerCase();
                    const match = text.includes(q);
                    r.style.display = match ? '' : 'none';
                    const acc = r.nextElementSibling;
                    if (acc && acc.classList.contains('inquiry-data__accordion')) {
                        if (!match && acc.classList.contains('expanded')) {
                            // collapse visual state
                            const content = acc.querySelector('.inquiry-data__accordion-content');
                            if (content) acc.style.height = '0px';
                            acc.classList.remove('expanded');
                        }
                        acc.style.display = match ? '' : 'none';
                    }
                });
            });
        }

        // Export CSV (all pages)
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('export', 'csv');
                window.location.href = url.toString();
            });
        }
    </script>
</body>

</html>
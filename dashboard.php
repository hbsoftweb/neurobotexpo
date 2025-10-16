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

// ----- Category helpers -----
function app_is_microscope(string $t): bool {
    return (bool) preg_match('/microscope|microscopy|stereo|sterio|auto\s*focus\s*microscope|3d\s*microscope|touch\s*screen\s*microscope/i', $t);
}
function app_is_vision(string $t): bool {
    return (bool) preg_match('/\bzebra\b|vision|profiler|z[-\s]?track|camera|line\s*scan|fs\s*-\s*70|vs\s*-\s*40|bfs|pge|flir/i', $t);
}
function app_is_printer(string $t): bool {
    return (bool) preg_match('/printer|printing|\br10\b|\br20\b|\br60\b|\b1200e\b|\bb1040h\b/i', $t);
}

// Heuristic → ONE chip per category (Microscope / Vision / Printer). Fallback to product names if no category.
function badges_from_applications($apps): array
{
    if (!is_array($apps)) $apps = (array)$apps;

    $found = ['Microscope'=>false, 'Vision'=>false, 'Printer'=>false];
    foreach ($apps as $a) {
        $t = (string)$a;
        $lc = strtolower($t);
        if (app_is_microscope($lc)) $found['Microscope'] = true;
        if (app_is_vision($lc))     $found['Vision'] = true;
        if (app_is_printer($lc))    $found['Printer'] = true;
    }

    $labels = [];
    if ($found['Microscope']) $labels[] = ['Microscope', 'category-microscope'];
    if ($found['Vision'])     $labels[] = ['Vision', 'category-vision'];
    if ($found['Printer'])    $labels[] = ['Printer', 'category-printer'];

    if (empty($labels) && !empty($apps)) {
        foreach (array_slice($apps, 0, 2) as $a) $labels[] = [trim((string)$a), 'category'];
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
    if (is_array($v)) return implode(', ', $v);
    if (is_string($v) && $v !== '') return $v;
    return '—';
}

// ---------- Get data for UI ----------
$meta = [];
$ui_page = max(1, (int) ($_GET['page'] ?? 1));
$ui_page_size = max(1, min(100, (int) ($_GET['page_size'] ?? 10)));
$items = api_fetch_submissions($ui_page, $ui_page_size, $meta);

// Build filter chip sources (unique lists) from current page data
$chip_industries = [];
$chip_printers = [];
$chip_visions = [];
$chip_microscopes = [];

foreach ($items as $r) {
    // industries
    if (!empty($r['industries']) && is_array($r['industries'])) {
        foreach ($r['industries'] as $ind) {
            $ind = trim((string)$ind);
            if ($ind !== '') $chip_industries[$ind] = true;
        }
    }
    // applications by category
    $apps = $r['applications'] ?? [];
    if (!is_array($apps)) $apps = (array)$apps;
    foreach ($apps as $a) {
        $name = trim((string)$a);
        if ($name === '') continue;
        $lc = strtolower($name);
        if (app_is_printer($lc))    $chip_printers[$name] = true;
        if (app_is_vision($lc))     $chip_visions[$name] = true;
        if (app_is_microscope($lc)) $chip_microscopes[$name] = true;
    }
}
ksort($chip_industries, SORT_NATURAL|SORT_FLAG_CASE);
ksort($chip_printers, SORT_NATURAL|SORT_FLAG_CASE);
ksort($chip_visions, SORT_NATURAL|SORT_FLAG_CASE);
ksort($chip_microscopes, SORT_NATURAL|SORT_FLAG_CASE);

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
        .muted { opacity: .7; }
        .click { cursor: pointer; }
        .inquiry-data__accordion { background: #000; }
        .chip { list-style:none; display:inline-block; margin:6px 8px; padding:7px 18px; border:1px solid #fff; border-radius:20px; }
        .chip.selected { background:#05d9ff; color:#000; border-color:#000; }
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
                    <path d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z"></path>
                </svg>
                <input id="searchInput" class="input-search" placeholder="Search..." type="text" value="">
            </div>

            <!-- Filter button with tiny badge -->
            <div id="filterBtn" class="export-icon-wrapper click icon-with-badge" title="Open Filters">
                <span id="filterBadge" class="badge" aria-hidden="true" style="display:none;"></span>
                <img alt="Filter icon" loading="lazy" width="512" height="512" decoding="async" class="export-icon"
                    src="assets/images/filter.png" style="color: transparent;">
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

        <!-- Filters Modal -->
        <div id="filterBackdrop" class="filters-backdrop" aria-hidden="true"></div>
        <div id="filterModal" class="filters-modal" role="dialog" aria-modal="true" aria-labelledby="filtersTitle" aria-hidden="true">
            <div class="filters-dialog">
                <div class="filters-header">
                    <h2 id="filtersTitle" class="filters-heading">Filters</h2>
                    <button id="filterClose" class="filters-close" aria-label="Close filters">×</button>
                </div>

                <div class="filters-body" id="filterPanel">
                    <div class="filters-title">Industry</div>
                    <div class="industry-filter-wrapper">
                        <ul>
                            <?php foreach (array_keys($chip_industries) as $ind): ?>
                                <li class="chip" data-type="industry" data-value="<?= h(strtolower($ind)) ?>"><?= h($ind) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="filters-title" style="margin-top:10px;border-top:1px solid #fff;padding-top:20px;">Printer</div>
                    <ul class="application-filter-wrapper">
                        <?php foreach (array_keys($chip_printers) as $n): ?>
                            <li class="chip" data-type="printer" data-value="<?= h(strtolower($n)) ?>"><?= h($n) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="filters-title">Vision</div>
                    <ul class="application-filter-wrapper vision-filter">
                        <?php foreach (array_keys($chip_visions) as $n): ?>
                            <li class="chip" data-type="vision" data-value="<?= h(strtolower($n)) ?>"><?= h($n) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="filters-title">Microscope</div>
                    <ul class="application-filter-wrapper">
                        <?php foreach (array_keys($chip_microscopes) as $n): ?>
                            <li class="chip" data-type="microscope" data-value="<?= h(strtolower($n)) ?>"><?= h($n) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="filters-footer">
                    <button id="filtersClear" class="btn-filters-secondary" type="button">Clear</button>
                    <button id="filtersApply" class="btn-filters-primary" type="button">Apply</button>
                </div>
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
                            $indsArr = $r['industries'] ?? [];
                            $inds = industries_to_string($indsArr);
                            $apps = $r['applications'] ?? [];
                            if (!is_array($apps)) $apps = (array)$apps;
                            $badges = badges_from_applications($apps);
                            $selfie = selfie_src($r['selfie_path'] ?? '');
                            $notes = $r['special_mention'] ?? '—';
                            $date = $r['created_at'] ?? ($r['submitted_at'] ?? '');
                            if ($date) {
                                $ts = strtotime($date);
                                if ($ts) $date = date('d M Y - h:i A', $ts);
                            }
                            // Build searchable text + data attributes
                            $categories_text = implode(' ', array_map(fn($b)=>strtolower($b[0]??''), $badges));
                            $apps_text = strtolower(implode(' | ', $apps));
                            $inds_text = strtolower(implode(' | ', (array)$indsArr));
                            $search_text = strtolower(trim($name.' '.$company.' '.$phone.' '.$desig.' '.$inds.' '.$categories_text));
                            ?>
                            <!-- Row -->
                            <tr class="inquiry-data__row click"
                                data-role="toggle"
                                data-text="<?= h($search_text) ?>"
                                data-industries="<?= h($inds_text) ?>"
                                data-apps="<?= h($apps_text) ?>">
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
                                            <div><img alt="User Selfie" class="inquiry-data__selfie-big" src="<?= h($selfie) ?>"></div>
                                            <p class="profile-info"><?= h($name) ?></p>
                                            <p class="profile-info"><?= h($company) ?></p>
                                            <p class="profile-info"><?= h($desig) ?></p>
                                        </div>
                                        <div class="wrapper-right-expand" style="display:flex;flex-direction:column;gap:6px;">
                                            <p><b class="label-left-expand">Contact Number:</b>&nbsp;&nbsp;&nbsp;<?= h($phone) ?></p>
                                            <p><b class="label-left-expand">Industry:</b>&nbsp;&nbsp;&nbsp;<?= h($inds) ?></p>
                                            <p><b class="label-left-expand">Applications:</b>&nbsp;&nbsp;&nbsp;<?= h(industries_to_string($apps)) ?></p>
                                            <p><b class="label-left-expand">Special Mention:</b>&nbsp;&nbsp;&nbsp;<?= h($notes) ?></p>
                                            <p><b class="label-left-expand">Submitted:</b>&nbsp;&nbsp;&nbsp;<?= h($r['submitted_at'] ?? '—') ?></p>
                                            <p><b class="label-left-expand">Created:</b>&nbsp;&nbsp;&nbsp;<?= h($date ?: '—') ?></p>
                                            <p><b class="label-left-expand">Event ID:</b>&nbsp;&nbsp;&nbsp;<?= h($r['event_id'] ?? '—') ?></p>
                                            <p><b class="label-left-expand">Email:</b>&nbsp;&nbsp;&nbsp;<?= h($r['email'] ?? '—') ?></p>
                                            <p><b class="label-left-expand">Source:</b>&nbsp;&nbsp;&nbsp;<?= h($r['source'] ?? '—') ?></p>
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
        // ----- Accordion (inner content max-height) -----
        document.addEventListener('click', function (e) {
            const row = e.target.closest('tr[data-role="toggle"]');
            if (!row) return;

            const acc = row.nextElementSibling;
            if (!acc || !acc.classList.contains('inquiry-data__accordion')) return;

            const content = acc.querySelector('.inquiry-data__accordion-content');
            if (!content) return;

            document.querySelectorAll('tr.inquiry-data__accordion.expanded').forEach(openAcc => {
                if (openAcc !== acc) {
                    const c = openAcc.querySelector('.inquiry-data__accordion-content');
                    if (c) c.style.maxHeight = '0px';
                    openAcc.classList.remove('expanded');
                }
            });

            if (acc.classList.contains('expanded')) {
                content.style.maxHeight = '0px';
                acc.classList.remove('expanded');
            } else {
                acc.classList.add('expanded');
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.inquiry-data__accordion .inquiry-data__accordion-content')
                .forEach(c => { c.style.maxHeight = '0px'; });
        });

        // ----- Search + Union Filters -----
        const searchInput = document.getElementById('searchInput');
        const selectedTokens = new Set(); // stores lowercased values of selected chips across ALL groups

        function filterRows() {
            const q = (searchInput.value || '').toLowerCase().trim();
            const rows = document.querySelectorAll('tr.inquiry-data__row');

            rows.forEach(r => {
                const text = r.dataset.text || '';                 // name, company, contact, designation, industry, category
                const inds = r.dataset.industries || '';           // industries tokens
                const apps = r.dataset.apps || '';                 // applications tokens

                const textMatch = q === '' ? true : text.includes(q);

                // Union chip filtering
                let chipsMatch = true;
                if (selectedTokens.size > 0) {
                    chipsMatch = false;
                    for (const t of selectedTokens) {
                        if (inds.includes(t) || apps.includes(t)) { chipsMatch = true; break; }
                    }
                }

                const show = textMatch && chipsMatch;
                r.style.display = show ? '' : 'none';

                // also hide matched accordion row
                const acc = r.nextElementSibling;
                if (acc && acc.classList.contains('inquiry-data__accordion')) {
                    if (!show && acc.classList.contains('expanded')) {
                        const content = acc.querySelector('.inquiry-data__accordion-content');
                        if (content) content.style.maxHeight = '0px';
                        acc.classList.remove('expanded');
                    }
                    acc.style.display = show ? '' : 'none';
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterRows);
        }

        // ----- Modal wiring -----
        const filterBtn = document.getElementById('filterBtn');
        const filterModal = document.getElementById('filterModal');
        const filterBackdrop = document.getElementById('filterBackdrop');
        const filterClose = document.getElementById('filterClose');
        const filtersApply = document.getElementById('filtersApply');
        const filtersClear = document.getElementById('filtersClear');
        const filterBadge = document.getElementById('filterBadge');

        function openFilters() {
            filterModal.setAttribute('aria-hidden', 'false');
            filterBackdrop.setAttribute('aria-hidden', 'false');
            document.body.classList.add('no-scroll');
        }
        function closeFilters() {
            filterModal.setAttribute('aria-hidden', 'true');
            filterBackdrop.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');
        }

        filterBtn.addEventListener('click', openFilters);
        filterClose.addEventListener('click', closeFilters);
        filterBackdrop.addEventListener('click', closeFilters);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeFilters();
        });

        // ----- Chip selection (inside modal) -----
        function updateChipsUI(el) {
            el.classList.toggle('selected');
            const val = el.getAttribute('data-value');
            if (!val) return;
            if (el.classList.contains('selected')) selectedTokens.add(val);
            else selectedTokens.delete(val);
        }

        // Attach chip handlers
        document.querySelectorAll('#filterPanel .chip').forEach(chip => {
            chip.addEventListener('click', () => {
                updateChipsUI(chip);
            });
        });

        // Badge updater (APPLIED count)
        function updateBadge() {
            const n = selectedTokens.size;
            if (!filterBadge) return;
            if (n > 0) {
                filterBadge.textContent = String(n);
                filterBadge.style.display = 'inline-block';
                filterBadge.setAttribute('aria-hidden', 'false');
            } else {
                filterBadge.textContent = '';
                filterBadge.style.display = 'none';
                filterBadge.setAttribute('aria-hidden', 'true');
            }
        }

        // Apply & Clear buttons
        filtersApply.addEventListener('click', () => {
            filterRows();     // actually filter the table
            updateBadge();    // show how many filters are APPLIED
            closeFilters();
        });
        filtersClear.addEventListener('click', () => {
            selectedTokens.clear();
            document.querySelectorAll('#filterPanel .chip.selected').forEach(c => c.classList.remove('selected'));
            filterRows();
            updateBadge();    // clears badge to 0/hidden
        });

        // ----- Export CSV (all pages) -----
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('export', 'csv');
                window.location.href = url.toString();
            });
        }

        // Initial badge state
        updateBadge();
    </script>
</body>
</html>

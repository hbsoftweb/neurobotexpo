<?php
// index.php — Exhibition picker for stall staff
declare(strict_types=1);
$base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$api = $base . '/api';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Select Exhibition</title>
    <link rel="stylesheet" href="./css/index.css">
    <!-- Minimal -->
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    <!-- Optional extras -->
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#0f172a">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <style>
        .card {
            max-width: 720px;
            margin: 60px auto;
            background: #0c0c0c;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
            padding: 30px;
        }

        .row {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }

        .muted {
            opacity: .8;
            font-size: .9rem;
        }

        .stepper-input {
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            flex: 1;
            font-size: 1rem;
            height: auto;
            background-color: #fff;
            color: #0c0c0c;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        /* Make dropdown options cyan with white text */
        select.stepper-input option {
            background-color: #00ffff;
            /* cyan */
            color: #000000;
            /* black text inside dropdown for better contrast */
        }

        /* Optional: hover/focus state for dropdown */
        .stepper-input:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 5px rgba(0, 255, 255, .5);
        }
    </style>

</head>

<body>
    <div class="card">
        <img src="assets/images/Neurobot-Logo.svg" alt="Neurobot" width="200" style="display:block;margin:0 auto 16px">
        <h2 style="text-align:center;margin-bottom:20px;">Select Exhibition</h2>

        <div class="row">
            <select id="exhibitionSelect" class="stepper-input">
                <option value="">— Select exhibition —</option>
            </select>
            <button id="goBtn" class="cross-cut-button">Continue →</button>
        </div>

        <details style="margin-top:16px; cursor: pointer;">
            <summary>Create new exhibition</summary>
            <div class="row" style="margin-top:10px;">
                <input id="newExhName" class="stepper-input" placeholder="Exhibition name (e.g., ABC Expo 2025)">
                <button id="createBtn" class="cross-cut-button">Create</button>
            </div>
            <small class="muted">A code will be auto-generated (e.g., ABC-EXPO-2025)</small>
        </details>
    </div>
    <!-- <div style="display: flex; justify-content: center;">
        <a href="dashboard.php">
            <button id="createBtn" class="cross-cut-button">Login as ADMIN?</button>
        </a>
    </div> -->

    <script>
        const API_BASE = '<?= htmlspecialchars($api, ENT_QUOTES) ?>';

        // --- Helper: store/retrieve exhibition ---
        function saveCurrentExhibition(exh) {
            sessionStorage.setItem('currentExhibition', JSON.stringify(exh));      // persists for tab
            localStorage.setItem('lastExhibition', JSON.stringify({ ...exh, savedAt: Date.now() })); // reuse later
        }
        function getLastExhibition() {
            try {
                const v = JSON.parse(localStorage.getItem('lastExhibition') || 'null');
                if (v && Date.now() - (v.savedAt || 0) < 14 * 24 * 60 * 60 * 1000) return v;
            } catch { }
            return null;
        }

        // --- Load exhibitions from API ---
        async function loadExhibitions() {
            const sel = document.getElementById('exhibitionSelect');
            sel.innerHTML = '<option value="">— Select exhibition —</option>';
            try {
                const res = await fetch(API_BASE + '/exhibitions?active=1&limit=100');
                const data = await res.json();
                (data.items || []).forEach(row => {
                    const opt = document.createElement('option');
                    opt.value = row.code;
                    opt.textContent = `${row.name} (${row.code})`;
                    sel.appendChild(opt);
                });
                // Preselect last used
                const last = getLastExhibition();
                if (last) {
                    const match = Array.from(sel.options).find(o => o.value === last.code);
                    if (match) sel.value = last.code;
                }
            } catch {
                alert('Could not load exhibitions.');
            }
        }

        // --- Create new exhibition ---
        async function createExhibition() {
            const name = (document.getElementById('newExhName').value || '').trim();
            if (!name) return alert('Please enter a name');
            const res = await fetch(API_BASE + '/exhibitions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.ok) return alert(data.error || 'Failed to create exhibition');
            await loadExhibitions();
            document.getElementById('exhibitionSelect').value = data.code;
        }

        // --- Continue to form ---
        function goToForm() {
            const sel = document.getElementById('exhibitionSelect');
            const code = (sel.value || '').trim();
            if (!code) return alert('Please select an exhibition');
            const name = sel.options[sel.selectedIndex].textContent;
            saveCurrentExhibition({ code, name });
            location.href = 'form.php?e=' + encodeURIComponent(code);
        }

        document.getElementById('createBtn').addEventListener('click', createExhibition);
        document.getElementById('goBtn').addEventListener('click', goToForm);
        loadExhibitions();
    </script>
</body>

</html>
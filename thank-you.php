<?php
// thank-you.php — loops back to form for the same exhibition
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Thank You</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="./css/index.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; color:#fff; }
    .card { width:min(720px, 92vw); background:#000; border:1px solid #1f2937; border-radius:16px; padding:28px; text-align:center; }
    .muted{opacity:.8}
    .logo{display:block;margin:0 auto 12px}
    .actions{display:flex; gap:10px; justify-content:center; margin-top:18px}
    .cross-cut-button{cursor:pointer; width: 100%;}
  </style>
</head>
<body>
  <div class="card">
    <img class="logo" src="assets/images/Neurobot-Logo.svg" alt="Neurobot" width="220">
    <h2>Thank you! 🎉</h2>
    <p class="muted">Your submission has been received.</p>

    <div class="actions">
      <button id="againBtn" class="cross-cut-button">Fill Another Form</button>
      <a href="index.php" class="cross-cut-button" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center">Change Exhibition</a>
    </div>

    <p class="muted" style="margin-top:12px;font-size:.95rem">Tip: keep this tab open to continue for the same exhibition.</p>
  </div>

  <script>
    // Prefer sessionStorage (current tab) for the in-progress exhibition.
    // Fallback to localStorage's lastExhibition if needed.
    function getCurrentExhibition() {
      try {
        const s = sessionStorage.getItem('currentExhibition');
        if (s) return JSON.parse(s);
      } catch {}
      try {
        const l = localStorage.getItem('lastExhibition');
        if (l) return JSON.parse(l);
      } catch {}
      return null;
    }

    document.getElementById('againBtn').addEventListener('click', () => {
      const ex = getCurrentExhibition();
      const code = ex && ex.code ? ex.code : new URLSearchParams(location.search).get('e');
      if (code) {
        // loop back straight into the form for the same exhibition
        window.location.href = 'form.php?e=' + encodeURIComponent(code);
      } else {
        // no remembered exhibition — fall back to picker
        window.location.href = 'index.php';
      }
    });
  </script>
</body>
</html>

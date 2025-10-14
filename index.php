<?php
// index.php
session_start();
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Exhibition Form</title>
  <link rel="stylesheet" href="./css/index.css">

  <!-- Minimal toast styles (safe to keep even with your CSS) -->
  <style>
    .toast {
      position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%);
      padding: 12px 16px; border-radius: 10px; font: 14px/1.4 system-ui, Arial;
      color: #fff; background: #333; box-shadow: 0 8px 30px rgba(0,0,0,.4);
      z-index: 9999; opacity: 0; pointer-events: none; transition: opacity .25s ease;
    }
    .toast--show { opacity: 1; pointer-events: auto; }
    .toast--error { background:#c0392b; }
    .toast--success { background:#2e7d32; }
    .is-hidden { display:none !important; }
    .invalid-hint { color:#ffb4b4; font-size:12px; margin-top:6px; display:none; }
    .invalid .invalid-hint { display:block; }
  </style>
</head>
<body>
  <div class="main">
    <div class="logo-wrapper">
      <img src="assets/images/Neurobot-Logo.svg" alt="Neurobot Logo" loading="lazy" width="500" height="150" decoding="async"/>
    </div>

    <div class="container">
      <div class="form-holder">
        <!-- JSON submit to submit.php -->
        <form id="exhibition-form" action="submit.php" method="post" data-endpoint="submit.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
          <input type="hidden" name="source" value="exhibition-form">
          <input type="hidden" name="event_id" value="ENGIEXPO-2025">
          <input type="hidden" name="selfie_data" id="selfie_data" value="">

          <div>
            <label class="label-input" for="name">Name*</label>
            <input class="stepper-input" id="name" name="name" type="text" placeholder="Enter Your Name" autocomplete="name" required>
            <div class="invalid-hint">Please enter your name.</div>
          </div>

          <div>
            <label class="label-input" for="company_name">Company name*</label>
            <input class="stepper-input" id="company_name" name="company_name" type="text" placeholder="Enter Your Company Name" autocomplete="organization" required>
            <div class="invalid-hint">Please enter your company name.</div>
          </div>

          <div>
            <label class="label-input" for="contact_number">Contact Number*</label>
            <input class="stepper-input" id="contact_number" name="contact_number" type="tel" inputmode="numeric" placeholder="Enter Your Phone number" pattern="^[0-9]{7,15}$" aria-describedby="phoneHelp" required>
            <small id="phoneHelp" style="display:block;opacity:.8">Digits only, 7–15 characters.</small>
            <div class="invalid-hint">Enter a valid phone (digits only, 7–15).</div>
          </div>

          <div>
            <label class="label-input" for="email">Email*</label>
            <input class="stepper-input" id="email" name="email" type="email" placeholder="Enter Your Email" autocomplete="email" required>
            <div class="invalid-hint">Please enter a valid email address.</div>
          </div>

          <!-- Designation (single choice) -->
          <div>
            <label class="label-input" for="designation">Designation*</label>
            <div class="checkbox-group" id="designation-group" role="radiogroup" aria-labelledby="designation">
              <div class="radio-inputs">
                <label>
                  <input class="radio-input" type="radio" value="Founder/CEO" name="designation" required>
                  <span class="radio-tile"><span class="radio-icon">
                    <img alt="Founder/CEO" loading="lazy" width="97" height="97" decoding="async" class="radio-icon-img" src="assets/images/Founder-CEO.svg"></span>
                    <span class="radio-label">Founder/CEO</span>
                  </span>
                </label>
              </div>
              <div class="radio-inputs">
                <label>
                  <input class="radio-input" type="radio" value="Purchase Head" name="designation">
                  <span class="radio-tile"><span class="radio-icon">
                    <img alt="Purchase Head" loading="lazy" width="97" height="97" decoding="async" class="radio-icon-img" src="assets/images/Purchase-Head.svg"></span>
                    <span class="radio-label">Purchase Head</span>
                  </span>
                </label>
              </div>
              <div class="radio-inputs">
                <label>
                  <input class="radio-input" type="radio" value="Decision Maker" name="designation">
                  <span class="radio-tile"><span class="radio-icon">
                    <img alt="Decision Maker" loading="lazy" width="96" height="96" decoding="async" class="radio-icon-img" src="assets/images/Decision-Maker.svg"></span>
                    <span class="radio-label">Decision Maker</span>
                  </span>
                </label>
              </div>
              <div class="radio-inputs">
                <label>
                  <input class="radio-input" type="radio" value="Production" name="designation">
                  <span class="radio-tile"><span class="radio-icon">
                    <img alt="Production" loading="lazy" width="96" height="96" decoding="async" class="radio-icon-img" src="assets/images/Production.svg"></span>
                    <span class="radio-label">Production</span>
                  </span>
                </label>
              </div>
              <div class="radio-inputs">
                <label>
                  <input class="radio-input" type="radio" value="Other" name="designation">
                  <span class="radio-tile"><span class="radio-icon">
                    <img alt="Other" loading="lazy" width="97" height="97" decoding="async" class="radio-icon-img" src="assets/images/Other.svg"></span>
                    <span class="radio-label">Other</span>
                  </span>
                </label>
              </div>
            </div>
            <div>
              <input class="stepper-input" id="designation_other" name="designation_other" type="text" placeholder="Other Designation" aria-hidden="true" hidden>
            </div>
            <div class="invalid-hint">Please choose your designation (and specify if Other).</div>
          </div>

          <!-- Industry (multi-select) -->
          <div>
            <label class="label-input" for="industry">Industry* (select all that apply)</label>
            <div class="checkbox-group" id="industry-group" role="group" aria-labelledby="industry">
              <div class="radio-inputs">
                <label><input class="radio-input" type="checkbox" value="Pharma" name="industry[]">
                  <span class="radio-tile"><span class="radio-icon"><img alt="Pharma" loading="lazy" width="97" height="97" decoding="async" class="radio-icon-img" src="assets/images/Pharma.svg"></span>
                    <span class="radio-label">Pharma</span></span></label>
              </div>
              <div class="radio-inputs">
                <label><input class="radio-input" type="checkbox" value="Packaging" name="industry[]">
                  <span class="radio-tile"><span class="radio-icon"><img alt="Packaging" loading="lazy" width="97" height="97" decoding="async" class="radio-icon-img" src="assets/images/Packaging.svg"></span>
                    <span class="radio-label">Packaging</span></span></label>
              </div>
              <div class="radio-inputs">
                <label><input class="radio-input" type="checkbox" value="Automotive" name="industry[]">
                  <span class="radio-tile"><span class="radio-icon"><img alt="Automotive" loading="lazy" width="96" height="96" decoding="async" class="radio-icon-img" src="assets/images/Automotive.svg"></span>
                    <span class="radio-label">Automotive</span></span></label>
              </div>
              <div class="radio-inputs">
                <label><input class="radio-input" type="checkbox" value="FMCG" name="industry[]">
                  <span class="radio-tile"><span class="radio-icon"><img alt="FMCG" loading="lazy" width="96" height="96" decoding="async" class="radio-icon-img" src="assets/images/FMCG.svg"></span>
                    <span class="radio-label">FMCG</span></span></label>
              </div>
              <div class="radio-inputs">
                <label><input class="radio-input" type="checkbox" value="Electronics" name="industry[]">
                  <span class="radio-tile"><span class="radio-icon"><img alt="Electronics" loading="lazy" width="97" height="97" decoding="async" class="radio-icon-img" src="assets/images/Electronics.svg"></span>
                    <span class="radio-label">Electronics</span></span></label>
              </div>
              <div class="radio-inputs">
                <label><input class="radio-input" type="checkbox" value="Other" name="industry[]">
                  <span class="radio-tile"><span class="radio-icon"><img alt="Other" loading="lazy" width="97" height="97" decoding="async" class="radio-icon-img" src="assets/images/Other.svg"></span>
                    <span class="radio-label">Other</span></span></label>
              </div>
            </div>
            <div>
              <input class="stepper-input" id="industry_other" name="industry_other" type="text" placeholder="Other Industry" aria-hidden="true" hidden>
            </div>
            <small style="display:block;opacity:.8">Pick one or more industries.</small>
            <div class="invalid-hint">Select at least one industry (and specify if Other).</div>
          </div>

          <!-- Applications -->
          <div>
            <label class="label-input" for="application">Application*</label>
            <div>
              <div class="application-wrapper">
                <div class="img-wpr-category">
                  <img alt="Printer Image" loading="lazy" width="150" height="150" decoding="async" class="img-wrapper-categpryone" src="assets/images/Printer.webp">
                </div>
                <div class="checkbox-group-application" id="application-group-1">
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="R10" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">R10</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="R20" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">R20</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="R60" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">R60</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="1200e" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">1200e</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="B1040H" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">B1040H</span></span></label></div>
                </div>
              </div>

              <div class="application-wrapper">
                <div class="img-wpr-category">
                  <img alt="Camera Image" loading="lazy" width="150" height="150" decoding="async" class="img-wrapper-categpryone" src="assets/images/Vision.webp">
                </div>
                <div class="checkbox-group-application" id="application-group-2">
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Lenia Lite 4K (LINE SCAN)" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Lenia Lite 4K <br>(LINE SCAN)</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Z-Track (3D Profiler)" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Z-Track <br>(3D Profiler)</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Flir BFS-PGE-50S4C-C" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Flir BFS-PGE-50S4C-C</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Zebra VS - 40" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Zebra VS - 40</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Zebra FS - 70" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Zebra FS - 70</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Camera BFS-PGE-16S2M-CS" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Camera BFS-PGE-16S2M-CS</span></span></label></div>
                </div>
              </div>

              <div class="application-wrapper">
                <div class="img-wpr-category">
                  <img alt="Microscope Image" loading="lazy" width="150" height="150" decoding="async" class="img-wrapper-categpryone" src="assets/images/Microscope.webp">
                </div>
                <div class="checkbox-group-application" id="application-group-3">
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="3D Microscope" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">3D Microscope</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="7&quot; Touch Screen Microscope" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">7" Touch Screen Microscope</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="4K 3D Microscope" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">4K 3D Microscope</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Auto Focus Microscope" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Auto Focus Microscope</span></span></label></div>
                  <div class="radio-inputs"><label><input class="radio-input" type="checkbox" value="Sterio Microscope" name="application[]"><span class="radio-tile radio-tile-application"><span class="radio-icon"></span><span class="radio-label radio-label-application">Sterio Microscope</span></span></label></div>
                </div>
              </div>
            </div>
            <small style="display:block;opacity:.8">Select at least one application.</small>
            <div class="invalid-hint">Select at least one application.</div>
          </div>

          <div>
            <label class="label-input" for="special_mention">Special Mention*</label>
            <input class="stepper-input" id="special_mention" name="special_mention" type="text" placeholder="Enter Special Mention" required>
            <div class="invalid-hint">Please enter a special mention.</div>
          </div>

          <!-- Selfie -->
          <div class="cam-div">
            <label class="label-input" for="camera">Capture Your Selfie*</label>
            <div>
              <div class="cam-holder" id="cam-holder">
                <video id="camera" autoplay playsinline style="max-width:100%;border-radius:12px;"></video>
                <canvas id="snapshot" style="max-width:100%;border-radius:12px;display:none;"></canvas>
              </div>
            </div>

            <div class="cross-cut-button selfie-button" id="captureBtn" role="button" tabindex="0">
              Capture Selfie <div class="arrowsup"></div>
            </div>
            <div class="cross-cut-button selfie-button" id="retakeBtn" role="button" tabindex="0" style="display:none;">
              Retake Selfie <div class="arrowsup"></div>
            </div>
            <small id="selfieHelp" style="display:block;opacity:.8">We’ll save a still image (no video stored).</small>

            <div class="submit-inline">
              <button type="submit" class="cross-cut-button">
                SUBMIT <div class="arrows"></div>
              </button>
            </div>
          </div>

          <!-- Sticky action bar -->
          <div class="button-holder">
            <button type="button" class="cross-cut-button" id="prevBtn">
              PREV <div class="arrowsup"></div>
            </button>
            <button type="submit" class="cross-cut-button" id="submitBtn">
              SUBMIT <div class="arrows"></div>
            </button>
          </div>

          <div class="thank-you-message" id="thanks" style="display:none;">
            <span>Thank you for contacting us. Our team will reach out to you shortly.</span>
          </div>

          <!-- Debug (only shown if submit fails) -->
          <div id="debug" class="debug-block is-hidden">
            <strong>Payload preview (demo mode):</strong>
            <pre id="debugPre"></pre>
          </div>
        </form>
      </div>
    </div>

    <div class="progress-holder">
      <div class="progress-count"> 50% Complete </div>
      <div class="progress-bar"><div class="progress-fill"></div></div>
    </div>
  </div>

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <script>
  (function () {
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => Array.from(document.querySelectorAll(sel));

    const form = $('#exhibition-form');
    const endpoint = form.getAttribute('data-endpoint') || 'submit.php';

    // ---- Toast helpers ----
    const toast = $('#toast');
    function showToast(msg, type='success', ms=3000){
      toast.textContent = msg;
      toast.className = 'toast toast--' + (type === 'error' ? 'error' : 'success') + ' toast--show';
      setTimeout(() => toast.classList.remove('toast--show'), ms);
    }

    // ---- Toggle Other fields ----
    function toggleOtherRadio(radioName, otherInputId) {
      const selected = document.querySelector(`input[name="${radioName}"]:checked`);
      const otherField = document.getElementById(otherInputId);
      if (!otherField) return;
      const show = selected && selected.value === 'Other';
      otherField.hidden = !show;
      otherField.setAttribute('aria-hidden', String(!show));
      if (!show) otherField.value = '';
    }
    function toggleOtherCheckbox(groupName, otherInputId) {
      const otherBox = document.querySelector(`input[name="${groupName}"][value="Other"]`);
      const otherField = document.getElementById(otherInputId);
      if (!otherField || !otherBox) return;
      const show = otherBox.checked;
      otherField.hidden = !show;
      otherField.setAttribute('aria-hidden', String(!show));
      if (!show) otherField.value = '';
    }
    $$('#designation-group input[name="designation"]').forEach((el) => {
      el.addEventListener('change', () => toggleOtherRadio('designation', 'designation_other'));
    });
    $$('#industry-group input[name="industry[]"]').forEach((el) => {
      el.addEventListener('change', () => toggleOtherCheckbox('industry[]', 'industry_other'));
    });
    toggleOtherRadio('designation', 'designation_other');
    toggleOtherCheckbox('industry[]', 'industry_other');

    // ---- Applications check ----
    function hasAtLeastOne(selector) { return $$(selector).some((el) => el.checked); }

    // ---- Camera / Selfie ----
    const video = $('#camera');
    const canvas = $('#snapshot');
    const captureBtn = $('#captureBtn');
    const retakeBtn = $('#retakeBtn');
    const selfieData = $('#selfie_data');
    let stream = null;

    async function startCamera() {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
        video.srcObject = stream;
        video.style.display = 'block';
        canvas.style.display = 'none';
        retakeBtn.style.display = 'none';
        selfieData.value = '';
      } catch (err) {
        console.error('Camera error:', err);
        $('#selfieHelp').textContent = 'Unable to access camera. Please allow permission or try another device.';
      }
    }
    // Safe init whether script loads before/after DOM ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', startCamera);
    } else {
      startCamera();
    }

    function captureSelfie() {
      if (!video.videoWidth || !video.videoHeight) return;
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      selfieData.value = canvas.toDataURL('image/png');
      video.style.display = 'none';
      canvas.style.display = 'block';
      retakeBtn.style.display = 'inline-flex';
    }
    function retakeSelfie() {
      if (!stream) return;
      video.style.display = 'block';
      canvas.style.display = 'none';
      retakeBtn.style.display = 'none';
      selfieData.value = '';
    }
    captureBtn.addEventListener('click', captureSelfie);
    captureBtn.addEventListener('keyup', (e) => { if (e.key === 'Enter' || e.key === ' ') captureSelfie(); });
    retakeBtn.addEventListener('click', retakeSelfie);
    retakeBtn.addEventListener('keyup', (e) => { if (e.key === 'Enter' || e.key === ' ') retakeSelfie(); });

    // ---- Build payload ----
    function buildPayload() {
      const industries = $$('#industry-group input[name="industry[]"]:checked').map(i => i.value);
      const applications = $$('input[name="application[]"]:checked').map(a => a.value);

      return {
        meta: {
          source: (document.querySelector('input[name="source"]')?.value || 'exhibition-form'),
          event_id: (document.querySelector('input[name="event_id"]')?.value || ''),
          submitted_at: new Date().toISOString()
        },
        visitor: {
          name: $('#name')?.value.trim() || '',
          company_name: $('#company_name')?.value.trim() || '',
          contact_number: $('#contact_number')?.value.trim() || '',
          email: $('#email')?.value.trim() || '',
          designation: (document.querySelector('input[name="designation"]:checked')?.value || ''),
          designation_other: $('#designation_other')?.value.trim() || '',
          industries,
          industry_other: $('#industry_other')?.value.trim() || '',
          applications,
          special_mention: $('#special_mention')?.value.trim() || ''
        },
        selfie: { mime: 'image/png', data_url: $('#selfie_data')?.value || '' },
        csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
      };
    }

    // ---- Validation + UI hints ----
    function setValidity(el, ok) {
      if (!el) return;
      el.setCustomValidity('');
      el.closest('div')?.classList.toggle('invalid', !ok);
    }

    function validateForm() {
      let valid = true;

      const requiredIds = ['name','company_name','contact_number','email','special_mention'];
      requiredIds.forEach(id => {
        const el = document.getElementById(id);
        const ok = !!(el && el.value.trim());
        if (!ok) valid = false;
        setValidity(el, ok);
      });

      const phone = $('#contact_number');
      if (phone && phone.value && !/^[0-9]{7,15}$/.test(phone.value)) {
        phone.setCustomValidity('Digits only, 7–15 characters.');
        phone.closest('div')?.classList.add('invalid');
        valid = false;
      }

      const email = $('#email');
      if (email && !email.checkValidity()) {
        email.closest('div')?.classList.add('invalid');
        valid = false;
      }

      // Designation (+ Other)
      const desSel = document.querySelector('input[name="designation"]:checked');
      if (!desSel) { valid = false; }
      if (desSel && desSel.value === 'Other') {
        const other = $('#designation_other');
        if (!other.value.trim()) { valid = false; other.closest('div')?.classList.add('invalid'); }
      }

      // Industries (+ Other)
      const industryChecks = $$('input[name="industry[]"]');
      if (!industryChecks.some(c => c.checked)) { valid = false; $('#industry-group')?.classList.add('invalid'); }
      const indOther = document.querySelector('input[name="industry[]"][value="Other"]');
      if (indOther && indOther.checked) {
        const other = $('#industry_other');
        if (!other.value.trim()) { valid = false; other.closest('div')?.classList.add('invalid'); }
      }

      // Applications
      if (!hasAtLeastOne('input[name="application[]"]')) {
        valid = false; $('#application-group-1')?.classList.add('invalid');
      }

      // Selfie
      if (!$('#selfie_data').value) {
        valid = false;
        showToast('Please capture your selfie before submitting.', 'error');
      }

      if (!valid) form.reportValidity();
      return valid;
    }

    function disableForm() {
      $$('#exhibition-form input, #exhibition-form button').forEach(el => { el.disabled = true; });
    }
    function enableForm() {
      $$('#exhibition-form input, #exhibition-form button').forEach(el => { el.disabled = false; });
    }
    function showThanks() {
      $('#thanks').style.display = 'block';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ---- Submit ----
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // clear previous invalid UI
      $$('.invalid').forEach(n => n.classList.remove('invalid'));

      if (!validateForm()) return;

      const payload = buildPayload();

      try {
        const res = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': payload.csrf_token
          },
          body: JSON.stringify(payload)
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.ok) {
          // collect a readable message
          let msg = data.error || 'Please check the form and try again.';
          if (data.errors && typeof data.errors === 'object') {
            const firstKey = Object.keys(data.errors)[0];
            if (firstKey) msg = data.errors[firstKey];
          }
          showToast(msg, 'error');
          enableForm();
          return;
        }

        // Success UI
        disableForm();
        showThanks();
        $('#debug').classList.add('is-hidden');
        showToast('Thank you! Your response has been submitted.', 'success', 3500);
      } catch (err) {
        console.warn('Submit failed, showing payload for debugging:', err);
        $('#debugPre').textContent = JSON.stringify(payload, null, 2);
        $('#debug').classList.remove('is-hidden');
        showToast('Server unreachable. Showing payload preview.', 'error', 4000);
      }
    });

    // Prevent accidental Enter submits
    form.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && e.target.tagName.toLowerCase() !== 'textarea') e.preventDefault();
    });

    // Prev button (scroll up)
    $('#prevBtn')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  })();
  </script>
</body>
</html>

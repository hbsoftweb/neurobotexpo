<?php
// form.php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Exhibition Form</title>
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

    <!-- Toast (tiny, self-contained) -->
    <style>
        .toast {
            position: fixed;
            left: 50%;
            bottom: 24px;
            transform: translateX(-50%);
            padding: 12px 16px;
            border-radius: 10px;
            font: 14px/1.4 system-ui, Arial;
            color: #fff;
            background: #333;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .4);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s
        }

        .toast--show {
            opacity: 1;
            pointer-events: auto
        }

        .toast--error {
            background: #c0392b
        }

        .toast--success {
            background: #2e7d32
        }

        .invalid-hint {
            color: #ffb4b4;
            font-size: 12px;
            margin-top: 10px;
            display: none
        }

        .invalid .invalid-hint {
            display: block
        }

        .is-loading {
            opacity: .8;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="main">
        <div class="logo-wrapper">
            <img src="assets/images/Neurobot-Logo.svg" alt="Neurobot Logo" loading="lazy" width="500" height="150"
                decoding="async" />
        </div>

        <div class="container">
            <div class="form-holder">
                <!-- JSON submit to submit.php -->
                <form id="exhibition-form" action="submit.php" data-endpoint="submit.php" method="post" novalidate>
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="source" value="exhibition-form">
                    <!-- CHANGED: event_id now empty + has id for JS to fill -->
                    <input type="hidden" name="event_id" id="event_id" value="">
                    <input type="hidden" name="selfie_data" id="selfie_data" value="">

                    <!-- STEP 1 -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="name">Name*</label>
                        <input class="stepper-input" id="name" name="name" type="text" placeholder="Enter Your Name"
                            autocomplete="name" required>
                        <div class="invalid-hint">Please enter your name.</div>
                    </div>

                    <!-- STEP 2 -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="company_name">Company Name*</label>
                        <input class="stepper-input" id="company_name" name="company_name" type="text"
                            placeholder="Enter Your Company Name" autocomplete="organization" required>
                        <div class="invalid-hint">Please enter your company name.</div>
                    </div>

                    <!-- STEP 3 -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="contact_number">Contact Number*</label>
                        <input class="stepper-input" id="contact_number" name="contact_number" type="tel"
                            inputmode="numeric" placeholder="Enter Your Phone number" pattern="^[0-9]{7,15}$"
                            aria-describedby="phoneHelp" required>
                        <small id="phoneHelp" style="display:block;opacity:.8;margin-top:10px">Digits only, 7–15
                            characters.</small>
                        <div class="invalid-hint">Enter a valid phone (digits only, 7–15).</div>
                    </div>

                    <!-- STEP 4 -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="email">Email*</label>
                        <input class="stepper-input" id="email" name="email" type="email" placeholder="Enter Your Email"
                            autocomplete="email" required>
                        <div class="invalid-hint">Please enter a valid email address.</div>
                    </div>

                    <!-- STEP 5: Designation -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="designation">Designation*</label>
                        <div class="checkbox-group" id="designation-group" role="radiogroup"
                            aria-labelledby="designation">
                            <label class="radio-inputs">
                                <input class="radio-input" type="radio" value="Founder/CEO" name="designation" required>
                                <span class="radio-tile"><span class="radio-icon"><img alt="Founder/CEO" loading="lazy"
                                            width="97" height="97" decoding="async" class="radio-icon-img"
                                            src="assets/images/Founder-CEO.svg"></span><span
                                        class="radio-label">Founder/CEO</span></span>
                            </label>
                            <label class="radio-inputs">
                                <input class="radio-input" type="radio" value="Purchase Head" name="designation">
                                <span class="radio-tile"><span class="radio-icon"><img alt="Purchase Head"
                                            loading="lazy" width="97" height="97" decoding="async"
                                            class="radio-icon-img" src="assets/images/Purchase-Head.svg"></span><span
                                        class="radio-label">Purchase Head</span></span>
                            </label>
                            <label class="radio-inputs">
                                <input class="radio-input" type="radio" value="Decision Maker" name="designation">
                                <span class="radio-tile"><span class="radio-icon"><img alt="Decision Maker"
                                            loading="lazy" width="96" height="96" decoding="async"
                                            class="radio-icon-img" src="assets/images/Decision-Maker.svg"></span><span
                                        class="radio-label">Decision
                                        Maker</span></span>
                            </label>
                            <label class="radio-inputs">
                                <input class="radio-input" type="radio" value="Production" name="designation">
                                <span class="radio-tile"><span class="radio-icon"><img alt="Production" loading="lazy"
                                            width="96" height="96" decoding="async" class="radio-icon-img"
                                            src="assets/images/Production.svg"></span><span
                                        class="radio-label">Production</span></span>
                            </label>
                            <label class="radio-inputs">
                                <input class="radio-input" type="radio" value="Other" name="designation">
                                <span class="radio-tile"><span class="radio-icon"><img alt="Other" loading="lazy"
                                            width="97" height="97" decoding="async" class="radio-icon-img"
                                            src="assets/images/Other.svg"></span><span
                                        class="radio-label">Other</span></span>
                            </label>
                        </div>
                        <div>
                            <input class="stepper-input" id="designation_other" name="designation_other" type="text"
                                placeholder="Other Designation" aria-hidden="true" hidden>
                        </div>
                        <div class="invalid-hint">Please choose your designation (and specify if Other).</div>
                    </div>

                    <!-- STEP 6: Industry -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="industry">Industry* (select all that apply)</label>
                        <div class="checkbox-group" id="industry-group" role="group" aria-labelledby="industry">
                            <label class="radio-inputs"><input class="radio-input" type="checkbox" value="Pharma"
                                    name="industry[]"><span class="radio-tile"><span class="radio-icon"><img
                                            alt="Pharma" loading="lazy" width="97" height="97" decoding="async"
                                            class="radio-icon-img" src="assets/images/Pharma.svg"></span><span
                                        class="radio-label">Pharma</span></span></label>
                            <label class="radio-inputs"><input class="radio-input" type="checkbox" value="Packaging"
                                    name="industry[]"><span class="radio-tile"><span class="radio-icon"><img
                                            alt="Packaging" loading="lazy" width="97" height="97" decoding="async"
                                            class="radio-icon-img" src="assets/images/Packaging.svg"></span><span
                                        class="radio-label">Packaging</span></span></label>
                            <label class="radio-inputs"><input class="radio-input" type="checkbox" value="Automotive"
                                    name="industry[]"><span class="radio-tile"><span class="radio-icon"><img
                                            alt="Automotive" loading="lazy" width="96" height="96" decoding="async"
                                            class="radio-icon-img" src="assets/images/Automotive.svg"></span><span
                                        class="radio-label">Automotive</span></span></label>
                            <label class="radio-inputs"><input class="radio-input" type="checkbox" value="FMCG"
                                    name="industry[]"><span class="radio-tile"><span class="radio-icon"><img alt="FMCG"
                                            loading="lazy" width="96" height="96" decoding="async"
                                            class="radio-icon-img" src="assets/images/FMCG.svg"></span><span
                                        class="radio-label">FMCG</span></span></label>
                            <label class="radio-inputs"><input class="radio-input" type="checkbox" value="Electronics"
                                    name="industry[]"><span class="radio-tile"><span class="radio-icon"><img
                                            alt="Electronics" loading="lazy" width="97" height="97" decoding="async"
                                            class="radio-icon-img" src="assets/images/Electronics.svg"></span><span
                                        class="radio-label">Electronics</span></span></label>
                            <label class="radio-inputs"><input class="radio-input" type="checkbox" value="Other"
                                    name="industry[]"><span class="radio-tile"><span class="radio-icon"><img alt="Other"
                                            loading="lazy" width="97" height="97" decoding="async"
                                            class="radio-icon-img" src="assets/images/Other.svg"></span><span
                                        class="radio-label">Other</span></span></label>
                        </div>
                        <div>
                            <input class="stepper-input" id="industry_other" name="industry_other" type="text"
                                placeholder="Other Industry" aria-hidden="true" hidden>
                        </div>
                        <small style="display:block;opacity:.8">Pick one or more industries.</small>
                        <div class="invalid-hint">Select at least one industry (and specify if Other).</div>
                    </div>

                    <!-- STEP 7: Applications -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="application">Application*</label>

                        <div class="application-wrapper">
                            <div class="img-wpr-category">
                                <img alt="Printer Image" loading="lazy" width="150" height="150" decoding="async"
                                    class="img-wrapper-categpryone" src="assets/images/Printer.webp">
                            </div>
                            <div class="checkbox-group-application" id="application-group-1">
                                <label class="radio-inputs"><input class="radio-input" type="checkbox" value="R10"
                                        name="application[]"><span class="radio-tile radio-tile-application"><span
                                            class="radio-icon"></span><span
                                            class="radio-label radio-label-application">R10</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox" value="R20"
                                        name="application[]"><span class="radio-tile radio-tile-application"><span
                                            class="radio-icon"></span><span
                                            class="radio-label radio-label-application">R20</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox" value="R60"
                                        name="application[]"><span class="radio-tile radio-tile-application"><span
                                            class="radio-icon"></span><span
                                            class="radio-label radio-label-application">R60</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox" value="1200e"
                                        name="application[]"><span class="radio-tile radio-tile-application"><span
                                            class="radio-icon"></span><span
                                            class="radio-label radio-label-application">1200e</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox" value="B1040H"
                                        name="application[]"><span class="radio-tile radio-tile-application"><span
                                            class="radio-icon"></span><span
                                            class="radio-label radio-label-application">B1040H</span></span></label>
                            </div>
                        </div>

                        <div class="application-wrapper">
                            <div class="img-wpr-category">
                                <img alt="Camera Image" loading="lazy" width="150" height="150" decoding="async"
                                    class="img-wrapper-categpryone" src="assets/images/Vision.webp">
                            </div>
                            <div class="checkbox-group-application" id="application-group-2">
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Lenia Lite 4K (LINE SCAN)" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Lenia Lite 4K
                                            <br>(LINE SCAN)</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Z-Track (3D Profiler)" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Z-Track <br>(3D
                                            Profiler)</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Flir BFS-PGE-50S4C-C" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Flir
                                            BFS-PGE-50S4C-C</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Zebra VS - 40" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Zebra VS -
                                            40</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Zebra FS - 70" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Zebra FS -
                                            70</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Camera BFS-PGE-16S2M-CS" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Camera
                                            BFS-PGE-16S2M-CS</span></span></label>
                            </div>
                        </div>

                        <div class="application-wrapper">
                            <div class="img-wpr-category">
                                <img alt="Microscope Image" loading="lazy" width="150" height="150" decoding="async"
                                    class="img-wrapper-categpryone" src="assets/images/Microscope.webp">
                            </div>
                            <div class="checkbox-group-application" id="application-group-3">
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="3D Microscope" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">3D
                                            Microscope</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="7&quot; Touch Screen Microscope" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">7" Touch Screen
                                            Microscope</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="4K 3D Microscope" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">4K 3D
                                            Microscope</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Auto Focus Microscope" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Auto Focus
                                            Microscope</span></span></label>
                                <label class="radio-inputs"><input class="radio-input" type="checkbox"
                                        value="Sterio Microscope" name="application[]"><span
                                        class="radio-tile radio-tile-application"><span class="radio-icon"></span><span
                                            class="radio-label radio-label-application">Sterio
                                            Microscope</span></span></label>
                            </div>
                        </div>
                        <small style="display:block;opacity:.8">Select at least one application.</small>
                        <div class="invalid-hint">Select at least one application.</div>
                    </div>

                    <!-- STEP 8 -->
                    <div class="form-step" data-step>
                        <label class="label-input" for="special_mention">Special Mention*</label>
                        <input class="stepper-input" id="special_mention" name="special_mention" type="text"
                            placeholder="Enter Special Mention" required>
                        <div class="invalid-hint">Please enter a special mention.</div>
                    </div>

                    <!-- STEP 9: Selfie -->
                    <div class="form-step cam-div" data-step>
                        <label class="label-input" for="camera">Capture Your Selfie*</label>
                        <div class="cam-holder" id="cam-holder">
                            <video id="camera" autoplay playsinline style="max-width:100%;border-radius:12px;"></video>
                            <canvas id="snapshot" style="max-width:100%;border-radius:12px;display:none;"></canvas>
                        </div>

                        <div class="cross-cut-button selfie-button" id="captureBtn" role="button" tabindex="0">
                            Capture Selfie <div class="arrowsup"></div>
                        </div>
                        <div class="cross-cut-button selfie-button" id="retakeBtn" role="button" tabindex="0"
                            style="display:none;">
                            Retake Selfie <div class="arrowsup"></div>
                        </div>
                        <small id="selfieHelp" style="display:block;opacity:.8">We’ll save a still image (no video
                            stored).</small>
                    </div>

                    <!-- Wizard controls (sticky) -->
                    <div class="button-holder" id="wizardButtons">
                        <button type="button" class="cross-cut-button" id="prevBtn">PREV <div class="arrowsup"></div>
                        </button>
                        <button type="button" class="cross-cut-button" id="nextBtn">NEXT <div class="arrows"></div>
                        </button>
                    </div>

                    <!-- Thanks -->
                    <div class="thank-you-message" id="thanks" style="display:none;">
                        <span>Thank you for contacting us. Our team will reach out to you shortly.</span>
                    </div>

                    <!-- Debug -->
                    <div id="debug" class="debug-block is-hidden">
                        <strong>Payload preview (demo mode):</strong>
                        <pre id="debugPre"></pre>
                    </div>
                </form>
            </div>
        </div>

        <!-- Progress -->
        <div class="progress-holder">
            <div class="progress-count" id="progressText">0% Completed</div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width:0%"></div>
            </div>
        </div>
    </div>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <script>
        // ===== Exhibition loop bootstrap (tab-scoped) =====
        (function () {
            // Use ?e=CODE to (re)hydrate exhibition in this tab
            const params = new URLSearchParams(location.search);
            const queryCode = params.get('e');

            function getCur() {
                try { return JSON.parse(sessionStorage.getItem('currentExhibition') || 'null'); }
                catch { return null; }
            }

            if (queryCode) {
                const cur = getCur();
                if (!cur || cur.code !== queryCode) {
                    sessionStorage.setItem('currentExhibition', JSON.stringify({ code: queryCode, name: queryCode }));
                }
            }

            // Require an active exhibition
            const cur = getCur();
            if (!cur || !cur.code) {
                location.href = 'index.php';
                return;
            }

            // Inject event_id for submit
            const eidField = document.getElementById('event_id');
            if (eidField) eidField.value = cur.code;

            // Sticky mini-banner to confirm current exhibition
            const banner = document.createElement('div');
            banner.style.cssText = 'position:sticky;top:0;z-index:999;background:#0b3558;color:#fff;padding:8px 12px;font:14px system-ui;';
            banner.innerHTML = `<strong>Exhibition:</strong> ${cur.name || cur.code}
                <a href="index.php" style="color:#aef;margin-left:12px;text-decoration:underline;">Switch</a>`;
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => document.body.prepend(banner));
            } else {
                document.body.prepend(banner);
            }
        })();

        (function () {
            const $ = (s) => document.querySelector(s);
            const $$ = (s) => Array.from(document.querySelectorAll(s));

            const form = $('#exhibition-form');
            const steps = $$('.form-step[data-step]');
            const prevBtn = $('#prevBtn');
            const nextBtn = $('#nextBtn');
            const progressFill = $('#progressFill');
            const progressText = $('#progressText');
            const endpoint = form.getAttribute('data-endpoint') || 'submit.php';

            // Toast
            const toast = $('#toast');
            const showToast = (msg, type = 'success', ms = 3000) => {
                toast.textContent = msg;
                toast.className = 'toast toast--' + (type === 'error' ? 'error' : 'success') + ' toast--show';
                setTimeout(() => toast.classList.remove('toast--show'), ms);
            };

            // Switch the submit button into/out of a loading state
            function setSubmitting(isOn) {
                if (isOn) {
                    nextBtn.dataset.originalText = nextBtn.textContent;
                    nextBtn.textContent = 'Please wait…';
                    nextBtn.disabled = true;
                    prevBtn.disabled = true;
                    nextBtn.classList.add('is-loading');
                } else {
                    nextBtn.textContent = nextBtn.dataset.originalText || 'SUBMIT';
                    nextBtn.disabled = false;
                    prevBtn.disabled = false;
                    nextBtn.classList.remove('is-loading');
                }
            }

            // Show only one step
            let current = 0;
            function renderStep() {
                steps.forEach((el, i) => el.style.display = i === current ? 'block' : 'none');

                // Buttons
                prevBtn.style.visibility = current === 0 ? 'hidden' : 'visible';

                const atLast = current === steps.length - 1;
                nextBtn.textContent = atLast ? 'SUBMIT' : 'NEXT';
                nextBtn.classList.toggle('is-submit', atLast);

                // Progress
                const pct = Math.round(((current + 1) / steps.length) * 100);
                progressFill.style.width = pct + '%';
                progressText.textContent = pct + '% Completed';

                // Scroll to top of form on step change
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            // ---- Toggle “Other” helpers (FIXED selectors) ----
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
            $$('#designation-group input[name="designation"]').forEach(el => {
                el.addEventListener('change', () => toggleOtherRadio('designation', 'designation_other'));
            });
            $$('#industry-group input[name="industry[]"]').forEach(el => {
                el.addEventListener('change', () => toggleOtherCheckbox('industry[]', 'industry_other'));
            });
            toggleOtherRadio('designation', 'designation_other');
            toggleOtherCheckbox('industry[]', 'industry_other');

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
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startCamera); else startCamera();

            function captureSelfie() {
                if (!video.videoWidth || !video.videoHeight) return;
                canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d'); ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                selfieData.value = canvas.toDataURL('image/png');
                video.style.display = 'none'; canvas.style.display = 'block'; retakeBtn.style.display = 'inline-flex';
            }
            function retakeSelfie() {
                if (!stream) return;
                video.style.display = 'block'; canvas.style.display = 'none'; retakeBtn.style.display = 'none'; selfieData.value = '';
            }
            captureBtn.addEventListener('click', captureSelfie);
            captureBtn.addEventListener('keyup', e => { if (e.key === 'Enter' || e.key === ' ') captureSelfie(); });
            retakeBtn.addEventListener('click', retakeSelfie);
            retakeBtn.addEventListener('keyup', e => { if (e.key === 'Enter' || e.key === ' ') retakeSelfie(); });

            // ---- Utilities ----
            const hasChecked = (sel) => $$(sel).some(el => el.checked);

            function setValidity(el, ok) {
                if (!el) return;
                el.setCustomValidity('');
                el.closest('div')?.classList.toggle('invalid', !ok);
            }

            // Validate just the currently visible step
            function validateCurrentStep() {
                const stepEl = steps[current];
                let valid = true;

                // clear previous marks within this step
                stepEl.querySelectorAll('.invalid').forEach(n => n.classList.remove('invalid'));

                // Name
                if (stepEl.contains($('#name'))) {
                    const el = $('#name'); const ok = !!el.value.trim(); if (!ok) valid = false; setValidity(el, ok);
                }
                // Company
                if (stepEl.contains($('#company_name'))) {
                    const el = $('#company_name'); const ok = !!el.value.trim(); if (!ok) valid = false; setValidity(el, ok);
                }
                // Phone
                if (stepEl.contains($('#contact_number'))) {
                    const el = $('#contact_number');
                    const ok = /^[0-9]{7,15}$/.test(el.value.trim());
                    if (!ok) { el.setCustomValidity('Digits only, 7–15 characters.'); valid = false; }
                    setValidity(el, ok);
                }
                // Email
                if (stepEl.contains($('#email'))) {
                    const el = $('#email'); const ok = el.checkValidity(); if (!ok) valid = false; setValidity(el, ok);
                }
                // Designation (+ other)
                if (stepEl.contains($('#designation-group'))) {
                    const desSel = document.querySelector('input[name="designation"]:checked');
                    if (!desSel) { valid = false; $('#designation-group').classList.add('invalid'); }
                    if (desSel && desSel.value === 'Other') {
                        const other = $('#designation_other'); if (!other.value.trim()) { valid = false; other.closest('div')?.classList.add('invalid'); }
                    }
                }
                // Industry (+ other)
                if (stepEl.contains($('#industry-group'))) {
                    if (!hasChecked('input[name="industry[]"]')) { valid = false; $('#industry-group').classList.add('invalid'); }
                    const indOther = document.querySelector('input[name="industry[]"][value="Other"]');
                    if (indOther && indOther.checked) {
                        const other = $('#industry_other'); if (!other.value.trim()) { valid = false; other.closest('div')?.classList.add('invalid'); }
                    }
                }
                // Applications
                if (stepEl.querySelector('#application-group-1')) {
                    if (!hasChecked('input[name="application[]"]')) { valid = false; $('#application-group-1')?.classList.add('invalid'); }
                }
                // Special mention
                if (stepEl.contains($('#special_mention'))) {
                    const el = $('#special_mention'); const ok = !!el.value.trim(); if (!ok) valid = false; setValidity(el, ok);
                }
                // Selfie
                if (stepEl.contains($('#cam-holder'))) {
                    if (!$('#selfie_data').value) { valid = false; showToast('Please capture your selfie before continuing.', 'error'); }
                }

                if (!valid) form.reportValidity();
                return valid;
            }

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

            function disableForm() { $$('#exhibition-form input, #exhibition-form button').forEach(el => el.disabled = true); }
            function enableForm() { $$('#exhibition-form input, #exhibition-form button').forEach(el => el.disabled = false); }
            function showThanks() { $('#thanks').style.display = 'block'; window.scrollTo({ top: 0, behavior: 'smooth' }); }

            // NEXT / SUBMIT
            nextBtn.addEventListener('click', async () => {
                const atLast = current === steps.length - 1;
                if (!atLast) {
                    if (!validateCurrentStep()) return;
                    current++; renderStep(); return;
                }

                // Final submit
                // validate entire form quickly
                for (let i = 0; i < steps.length; i++) { current = i; if (!validateCurrentStep()) { renderStep(); return; } }
                current = steps.length - 1; renderStep();

                const payload = buildPayload();
                try {
                    setSubmitting(true);
                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': payload.csrf_token },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                        let msg = data.error || 'Please check the form and try again.';
                        if (data.errors && typeof data.errors === 'object') {
                            const firstKey = Object.keys(data.errors)[0]; if (firstKey) msg = data.errors[firstKey];
                        }
                        showToast(msg, 'error');
                        setSubmitting(false);
                        return;

                    }
                    // ✅ Success → redirect to thank-you page, preserving exhibition code
                    const THANKS_URL = 'thank-you.php';
                    const qs = data.id ? `?id=${encodeURIComponent(data.id)}` : '';
                    let eParam = '';
                    try {
                        const cur = JSON.parse(sessionStorage.getItem('currentExhibition') || 'null');
                        if (cur && cur.code) eParam = (qs ? '&' : '?') + 'e=' + encodeURIComponent(cur.code);
                    } catch { }
                    window.location.assign(THANKS_URL + qs + eParam);

                } catch (err) {
                    console.warn('Submit failed, showing payload for debugging:', err);
                    $('#debugPre').textContent = JSON.stringify(payload, null, 2);
                    $('#debug').classList.remove('is-hidden');
                    showToast('Server unreachable. Showing payload preview.', 'error', 4000);
                    setSubmitting(false);
                }
            });

            // PREV
            prevBtn.addEventListener('click', () => {
                if (current > 0) { current--; renderStep(); }
            });

            // Prevent accidental Enter submits
            form.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && e.target.tagName.toLowerCase() !== 'textarea') e.preventDefault();
            });

            // Initial render
            renderStep();
        })();
    </script>
</body>

</html>
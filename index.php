<?php
// Minimal CSRF setup (prep for your API)
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="./css/index.css">
</head>

<body>
    <div class="main">
        <div class="logo-wrapper">
            <img src="assets/images/Neurobot-Logo.svg" alt="Neurobot Logo" loading="lazy" width="500" height="150"
                decoding="async" data-nimg="1">
        </div>

        <div class="container">
            <div class="form-holder">
                <form id="exhibition-form" action="submit.php" method="post" enctype="application/x-www-form-urlencoded"
                    novalidate>
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <input type="hidden" name="source" value="exhibition-form">
                    <input type="hidden" name="event_id" value="ENGIEXPO-2025">
                    <input type="hidden" name="selfie_data" id="selfie_data" value="">

                    <div>
                        <label class="label-input" for="name">Name*</label>
                        <input class="stepper-input" id="name" name="name" type="text" placeholder="Enter Your Name"
                            autocomplete="name" required>
                    </div>

                    <div>
                        <label class="label-input" for="company_name">Company name*</label>
                        <input class="stepper-input" id="company_name" name="company_name" type="text"
                            placeholder="Enter Your Company Name" autocomplete="organization" required>
                    </div>

                    <div>
                        <label class="label-input" for="contact_number">Contact Number*</label>
                        <input class="stepper-input" id="contact_number" name="contact_number" type="tel"
                            inputmode="numeric" placeholder="Enter Your Phone number" pattern="^[0-9]{7,15}$"
                            aria-describedby="phoneHelp" required>
                        <small id="phoneHelp" style="display:block;opacity:.8">Digits only, 7–15 characters.</small>
                    </div>

                    <div>
                        <label class="label-input" for="email">Email*</label>
                        <input class="stepper-input" id="email" name="email" type="email" placeholder="Enter Your Email"
                            autocomplete="email" required>
                    </div>

                    <!-- Designation (single choice) -->
                    <div>
                        <label class="label-input" for="designation">Designation*</label>
                        <div class="checkbox-group" id="designation-group" role="radiogroup"
                            aria-labelledby="designation">
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="radio" value="Founder/CEO" name="designation"
                                        required>
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Founder/CEO" loading="lazy" width="97" height="97"
                                                decoding="async" data-nimg="1" class="radio-icon-img"
                                                src="assets/images/Founder-CEO.svg" style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Founder/CEO</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="radio" value="Purchase Head" name="designation">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Purchase Head" loading="lazy" width="97" height="97"
                                                decoding="async" data-nimg="1" class="radio-icon-img"
                                                src="assets/images/Purchase-Head.svg" style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Purchase Head</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="radio" value="Decision Maker" name="designation">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Decision Maker" loading="lazy" width="96" height="96"
                                                decoding="async" data-nimg="1" class="radio-icon-img"
                                                src="assets/images/Decision-Maker.svg" style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Decision Maker</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="radio" value="Production" name="designation">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Production" loading="lazy" width="96" height="96" decoding="async"
                                                data-nimg="1" class="radio-icon-img" src="assets/images/Production.svg"
                                                style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Production</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="radio" value="Other" name="designation">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Other" loading="lazy" width="97" height="97" decoding="async"
                                                data-nimg="1" class="radio-icon-img" src="assets/images/Other.svg"
                                                style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Other</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <input class="stepper-input" id="designation_other" name="designation_other" type="text"
                                placeholder="Other Designation" aria-hidden="true" hidden>
                        </div>
                    </div>

                    <!-- Industry (MULTI-SELECT) -->
                    <div>
                        <label class="label-input" for="industry">Industry* (select all that apply)</label>
                        <div class="checkbox-group" id="industry-group" role="group" aria-labelledby="industry">
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="checkbox" value="Pharma" name="industry[]">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Pharma" loading="lazy" width="97" height="97" decoding="async"
                                                data-nimg="1" class="radio-icon-img" src="assets/images/Pharma.svg"
                                                style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Pharma</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="checkbox" value="Packaging" name="industry[]">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Packaging" loading="lazy" width="97" height="97" decoding="async"
                                                data-nimg="1" class="radio-icon-img" src="assets/images/Packaging.svg"
                                                style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Packaging</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="checkbox" value="Automotive" name="industry[]">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Automotive" loading="lazy" width="96" height="96" decoding="async"
                                                data-nimg="1" class="radio-icon-img" src="assets/images/Automotive.svg"
                                                style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Automotive</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="checkbox" value="FMCG" name="industry[]">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="FMCG" loading="lazy" width="96" height="96" decoding="async"
                                                data-nimg="1" class="radio-icon-img" src="assets/images/FMCG.svg"
                                                style="color: transparent;">
                                        </span>
                                        <span class="radio-label">FMCG</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="checkbox" value="Electronics" name="industry[]">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Electronics" loading="lazy" width="97" height="97"
                                                decoding="async" data-nimg="1" class="radio-icon-img"
                                                src="assets/images/Electronics.svg" style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Electronics</span>
                                    </span>
                                </label>
                            </div>
                            <div class="radio-inputs">
                                <label>
                                    <input class="radio-input" type="checkbox" value="Other" name="industry[]">
                                    <span class="radio-tile">
                                        <span class="radio-icon">
                                            <img alt="Other" loading="lazy" width="97" height="97" decoding="async"
                                                data-nimg="1" class="radio-icon-img" src="assets/images/Other.svg"
                                                style="color: transparent;">
                                        </span>
                                        <span class="radio-label">Other</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <input class="stepper-input" id="industry_other" name="industry_other" type="text"
                                placeholder="Other Industry" aria-hidden="true" hidden>
                        </div>
                        <small style="display:block;opacity:.8">Pick one or more industries.</small>
                    </div>

                    <!-- Application (checkbox group) -->
                    <div>
                        <label class="label-input" for="application">Application*</label>
                        <div>
                            <div class="application-wrapper">
                                <div class="img-wpr-category">
                                    <img alt="Printer Image" loading="lazy" width="150" height="150" decoding="async"
                                        data-nimg="1" class="img-wrapper-categpryone" src="assets/images/Printer.webp"
                                        style="color: transparent;">
                                </div>
                                <div class="checkbox-group-application" id="application-group-1">
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="R10" name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">R10</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="R20" name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">R20</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="R60" name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">R60</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="1200e"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">1200e</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="B1040H"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">B1040H</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="application-wrapper">
                                <div class="img-wpr-category">
                                    <img alt="Camera Image" loading="lazy" width="150" height="150" decoding="async"
                                        data-nimg="1" class="img-wrapper-categpryone" src="assets/images/Vision.webp"
                                        style="color: transparent;">
                                </div>
                                <div class="checkbox-group-application" id="application-group-2">
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Lenia Lite 4K (LINE SCAN)"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Lenia Lite 4K
                                                    <br>(LINE SCAN)</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Z-Track (3D Profiler)"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Z-Track <br>(3D
                                                    Profiler)</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Flir BFS-PGE-50S4C-C"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Flir
                                                    BFS-PGE-50S4C-C</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Zebra VS - 40"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Zebra VS - 40</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Zebra FS - 70"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Zebra FS - 70</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Camera BFS-PGE-16S2M-CS"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Camera
                                                    BFS-PGE-16S2M-CS</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="application-wrapper">
                                <div class="img-wpr-category">
                                    <img alt="Microscope Image" loading="lazy" width="150" height="150" decoding="async"
                                        data-nimg="1" class="img-wrapper-categpryone"
                                        src="assets/images/Microscope.webp" style="color: transparent;">
                                </div>
                                <div class="checkbox-group-application" id="application-group-3">
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="3D Microscope"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">3D Microscope</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox"
                                                value="7&quot; Touch Screen Microscope" name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">7" Touch Screen
                                                    Microscope</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="4K 3D Microscope"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">4K 3D
                                                    Microscope</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Auto Focus Microscope"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Auto Focus
                                                    Microscope</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="radio-inputs">
                                        <label>
                                            <input class="radio-input" type="checkbox" value="Sterio Microscope"
                                                name="application[]">
                                            <span class="radio-tile radio-tile-application">
                                                <span class="radio-icon"></span>
                                                <span class="radio-label radio-label-application">Sterio
                                                    Microscope</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small style="display:block;opacity:.8">Select at least one application.</small>
                    </div>

                    <div>
                        <label class="label-input" for="special_mention">Special Mention*</label>
                        <input class="stepper-input" id="special_mention" name="special_mention" type="text"
                            placeholder="Enter Special Mention" required>
                    </div>

                    <!-- Selfie -->
                    <div class="cam-div">
                        <label class="label-input" for="camera">Capture Your Selfie*</label>
                        <div>
                            <div class="cam-holder" id="cam-holder">
                                <video id="camera" autoplay playsinline
                                    style="max-width:100%;border-radius:12px;"></video>
                                <canvas id="snapshot" style="max-width:100%;border-radius:12px;display:none;"></canvas>
                            </div>
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

                    <div class="button-holder">
                        <div class="cross-cut-button" id="prevBtn" role="button" tabindex="0">Prev <div
                                class="arrowsup"></div>
                        </div>
                        <button type="submit" class="cross-cut-button" id="submitBtn">
                            Next <div class="arrows"></div>
                        </button>
                    </div>

                    <div class="thank-you-message" id="thanks" style="display:none;">
                        <span>Thank you for visiting our stall, our sales team will get in touch with you soon.</span>
                    </div>
                </form>
            </div>
        </div>

        <div class="progress-holder">
            <div class="progress-count"> 50% Complete </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const $ = (sel) => document.querySelector(sel);
            const $$ = (sel) => Array.from(document.querySelectorAll(sel));

            const form = $('#exhibition-form');

            // ----- Toggle 'Other' for radio (Designation) -----
            function toggleOtherRadio(radioName, otherInputId) {
                const selected = $(`input[name="${radioName}"]:checked`);
                const otherField = $(`#${otherInputId}`);
                if (!otherField) return;
                const show = selected && selected.value === 'Other';
                otherField.hidden = !show;
                otherField.setAttribute('aria-hidden', String(!show));
                if (!show) otherField.value = '';
            }

            // ----- Toggle 'Other' for checkbox group (Industry[]) -----
            function toggleOtherCheckbox(groupName, otherInputId) {
                const otherChecked = $(`input[name="${groupName}"][value="Other"]`)?.checked;
                const otherField = $(`#${otherInputId}`);
                if (!otherField) return;
                const show = !!otherChecked;
                otherField.hidden = !show;
                otherField.setAttribute('aria-hidden', String(!show));
                if (!show) otherField.value = '';
            }

            // Bind Designation radios
            $$('#designation-group input[name="designation"]').forEach((el) => {
                el.addEventListener('change', () => toggleOtherRadio('designation', 'designation_other'));
            });

            // Bind Industry checkboxes
            $$('#industry-group input[name="industry[]"]').forEach((el) => {
                el.addEventListener('change', () => toggleOtherCheckbox('industry[]', 'industry_other'));
            });

            // Initialise
            toggleOtherRadio('designation', 'designation_other');
            toggleOtherCheckbox('industry[]', 'industry_other');

            // Application (at least one)
            function hasAtLeastOneApplication() {
                return $$('input[name="application[]"]').some((c) => c.checked);
            }

            // ----- Camera / Selfie -----
            const video = $('#camera');
            const canvas = $('#snapshot');
            const captureBtn = $('#captureBtn');
            const retakeBtn = $('#retakeBtn');
            const selfieData = $('#selfie_data');

            let stream = null;
            let selfieCaptured = false;

            async function startCamera() {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                    video.srcObject = stream;
                    video.style.display = 'block';
                    canvas.style.display = 'none';
                    selfieCaptured = false;
                    retakeBtn.style.display = 'none';
                } catch (err) {
                    console.error('Camera error:', err);
                    $('#selfieHelp').textContent = 'Unable to access camera. Please allow permission or try another device.';
                }
            }
            function captureSelfie() {
                if (!video.videoWidth || !video.videoHeight) return;
                const w = video.videoWidth, h = video.videoHeight;
                canvas.width = w; canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, w, h);
                const dataUrl = canvas.toDataURL('image/png');
                selfieData.value = dataUrl;
                video.style.display = 'none';
                canvas.style.display = 'block';
                retakeBtn.style.display = 'inline-flex';
                selfieCaptured = true;
            }
            function retakeSelfie() {
                if (!stream) return;
                video.style.display = 'block';
                canvas.style.display = 'none';
                retakeBtn.style.display = 'none';
                selfieCaptured = false;
                selfieData.value = '';
            }

            document.addEventListener('DOMContentLoaded', startCamera);
            captureBtn.addEventListener('click', captureSelfie);
            captureBtn.addEventListener('keyup', (e) => { if (e.key === 'Enter' || e.key === ' ') captureSelfie(); });
            retakeBtn.addEventListener('click', retakeSelfie);
            retakeBtn.addEventListener('keyup', (e) => { if (e.key === 'Enter' || e.key === ' ') retakeSelfie(); });

            // ----- Validation -----
            form.addEventListener('submit', (e) => {
                let valid = true;

                // Required text fields
                ['name', 'company_name', 'contact_number', 'email', 'special_mention'].forEach((id) => {
                    const el = document.getElementById(id);
                    if (!el || !el.value.trim()) valid = false;
                });

                // Phone pattern
                const phone = $('#contact_number');
                if (phone && phone.value && !/^[0-9]{7,15}$/.test(phone.value)) {
                    valid = false;
                    phone.setCustomValidity('Please enter a valid phone number (digits only, 7–15).');
                } else if (phone) {
                    phone.setCustomValidity('');
                }

                // Designation (single)
                const des = $$('input[name="designation"]');
                if (!des.some(r => r.checked)) valid = false;
                const desSel = $('input[name="designation"]:checked');
                if (desSel && desSel.value === 'Other') {
                    const other = $('#designation_other');
                    if (!other.value.trim()) valid = false;
                }

                // Industry (MULTI)
                const industries = $$('input[name="industry[]"]');
                if (!industries.some(c => c.checked)) {
                    valid = false;
                    alert('Please select at least one Industry.');
                }
                const indOther = $(`input[name="industry[]"][value="Other"]`);
                if (indOther && indOther.checked) {
                    const other = $('#industry_other');
                    if (!other.value.trim()) {
                        valid = false;
                        alert('Please specify the Other Industry.');
                    }
                }

                // Applications
                if (!hasAtLeastOneApplication()) {
                    valid = false;
                    alert('Please select at least one Application.');
                }

                // Selfie
                if (!selfieCaptured || !selfieData.value) {
                    valid = false;
                    alert('Please capture your selfie before submitting.');
                }

                if (!valid) {
                    e.preventDefault();
                    const firstError = document.querySelector(':invalid') || document.querySelector('[aria-invalid="true"]');
                    if (firstError && firstError.scrollIntoView) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Prevent accidental Enter submits
            form.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && e.target.tagName.toLowerCase() !== 'textarea') {
                    e.preventDefault();
                }
            });

            // Prev button (placeholder)
            $('#prevBtn')?.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>
</body>

</html>
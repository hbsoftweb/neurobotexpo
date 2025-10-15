<?php
require __DIR__ . '/api/config.php';        // SMTP_* and MAIL_* constants
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Low-level sender
 */
function sendMail(string $toEmail, string $toName, string $subject, string $html, array $attachments = []): bool {
  $mail = new PHPMailer(true);
  try {
    // $mail->SMTPDebug = 2; // <- enable while testing
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = (SMTP_PORT == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Gmail: From must match the authenticated account
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName ?: $toEmail);
    $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

    foreach ($attachments as $path) {
      if (is_file($path)) $mail->addAttachment($path);
    }

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;
    $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log('PHPMailer error: ' . $mail->ErrorInfo);
    return false;
  }
}

/** Map exact form labels to filenames inside /assets/files */
function appToPdfMap(): array {
  return [
    // Printer
    'R10'    => 'R10.pdf',
    'R20'    => 'R20.pdf',
    'R60'    => 'R60.pdf',
    '1200e'  => '1200e.pdf',
    'B1040H' => 'B1040H.pdf',

    // Vision
    'Lenia Lite 4K (LINE SCAN)' => 'Lenia-Lite-4K-(LINE SCAN).pdf',
    'Z-Track (3D Profiler)'     => 'Z-Track-(3D Profiler).pdf',
    'Flir BFS-PGE-50S4C-C'      => 'Flir-BFS-PGE-5054C-C.pdf', // filename in folder
    'Zebra VS - 40'             => 'Zebra-VS-40.pdf',
    'Zebra FS - 70'             => 'Zebra-FS-70.pdf',
    'Camera BFS-PGE-16S2M-CS'   => 'Camera-BFS-PGE-16S2M-CS.pdf',

    // Microscope
    '3D Microscope'               => '3D-Microscope.pdf',
    '7" Touch Screen Microscope'  => '7inch-Touch-Screen-Microscope.pdf',
    '4K 3D Microscope'            => '4K-3D-Microscope.pdf',
    'Auto Focus Microscope'       => 'Auto-Focus-Microscope.pdf',
    'Sterio Microscope'           => 'Sterio-Microscope.pdf',
  ];
}

/** Turn selected application labels into absolute file paths (dedup, skip missing) */
function mapApplicationsToPdfs(array $apps): array {
  $map  = appToPdfMap();
  $base = __DIR__ . '/assets/files/';
  $out  = [];
  foreach ($apps as $label) {
    $label = trim((string)$label);
    if ($label === '' || !isset($map[$label])) continue;
    $abs = $base . $map[$label];
    if (is_file($abs)) $out[$abs] = true;
  }
  return array_keys($out);
}

/**
 * High-level helper called from submit.php
 * Expects the $record you already build in submit.php
 */
function sendSubmissionEmails(array $record): void {
  $v = $record['visitor'];
  $name  = (string)($v['name'] ?? '');
  $email = (string)($v['email'] ?? '');
  $industries   = implode(', ', (array)($v['industries'] ?? []));
  $applications = implode(', ', (array)($v['applications'] ?? []));
  $selfieRel = $record['assets']['selfie']['path'] ?? '';
  $selfieAbs = __DIR__ . '/' . ltrim($selfieRel, '/');

  // --- Admin notification (unchanged; selfie optional)
  $adminHtml = '
    <h2>New Exhibition Submission</h2>
    <p><strong>ID:</strong> '.htmlspecialchars($record['id']).'</p>
    <p><strong>Name:</strong> '.htmlspecialchars($name).'</p>
    <p><strong>Company:</strong> '.htmlspecialchars($v['company_name'] ?? '').'</p>
    <p><strong>Phone:</strong> '.htmlspecialchars($v['contact_number'] ?? '').'</p>
    <p><strong>Email:</strong> '.htmlspecialchars($email).'</p>
    <p><strong>Designation:</strong> '.htmlspecialchars($v['designation'] ?? '').'</p>
    <p><strong>Industries:</strong> '.htmlspecialchars($industries).'</p>
    <p><strong>Applications:</strong> '.htmlspecialchars($applications).'</p>
    <p><strong>Special mention:</strong><br>'.nl2br(htmlspecialchars($v['special_mention'] ?? '')).'</p>
    <p><em>Submitted at:</em> '.htmlspecialchars($record['received_at'] ?? date('c')).'</p>
  ';
  $adminAttachments = (is_file($selfieAbs) ? [$selfieAbs] : []);
  sendMail(MAIL_ADMIN, 'Admin', 'New submission: '.$name, $adminHtml, $adminAttachments);

  // --- Visitor auto-reply (attach PDFs for selected applications)
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $userHtml = '
      <p>Hi '.htmlspecialchars($name).',</p>
      <p>Thanks for visiting our stall and submitting your details. We&rsquo;ve attached product sheets for your selected applications.</p>
      <p><strong>Your summary</strong></p>
      <ul>
        <li>Company: '.htmlspecialchars($v['company_name'] ?? '').'</li>
        <li>Phone: '.htmlspecialchars($v['contact_number'] ?? '').'</li>
        <li>Designation: '.htmlspecialchars($v['designation'] ?? '').'</li>
        <li>Industries: '.htmlspecialchars($industries).'</li>
        <li>Applications: '.htmlspecialchars($applications).'</li>
      </ul>
      <p>Regards,<br>'.htmlspecialchars(MAIL_FROM_NAME).'</p>
    ';

    $userAttachments = mapApplicationsToPdfs((array)($v['applications'] ?? []));
    sendMail($email, $name, 'Thanks — product sheets attached', $userHtml, $userAttachments);
  }
}

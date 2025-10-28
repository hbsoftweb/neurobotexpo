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
    'Flir BFS-PGE-50S4C-C'      => 'Flir-BFS-PGE-5054C-C.pdf',
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

  // --------------- Admin notification (styled like reference) ---------------
  $adminTitle = 'New Exhibition Submission';
  $adminHtml = '<!doctype html>
<html>
<head><meta charset="UTF-8"><title>'.htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8').'</title></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:left;">'.htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8').'</h2>

    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>ID:</strong> '.htmlspecialchars($record['id']).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Name:</strong> '.htmlspecialchars($name).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Company:</strong> '.htmlspecialchars($v['company_name'] ?? '').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Phone:</strong> '.htmlspecialchars($v['contact_number'] ?? '').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Email:</strong> '.htmlspecialchars($email).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Designation:</strong> '.htmlspecialchars($v['designation'] ?? '').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Industries:</strong> '.htmlspecialchars($industries).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Applications:</strong> '.htmlspecialchars($applications).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Special mention:</strong><br>'.nl2br(htmlspecialchars($v['special_mention'] ?? '')).'</p>
    <p style="margin:12px 0 0 0;font-size:14px;line-height:1.6;color:#555;"><em>Submitted at:</em> '.htmlspecialchars($record['received_at'] ?? date('c')).'</p>
  </div>
</body>
</html>';

  $adminAttachments = (is_file($selfieAbs) ? [$selfieAbs] : []);
  sendMail(MAIL_ADMIN, 'Admin', 'New submission: '.$name, $adminHtml, $adminAttachments);

  // --------------- Visitor auto-reply (styled like reference) ---------------
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $userTitle = 'Thanks for Visiting Our Stall';
    $userHtml = '<!doctype html>
<html>
<head><meta charset="UTF-8"><title>'.htmlspecialchars($userTitle, ENT_QUOTES, 'UTF-8').'</title></head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
  <div style="width:100%;max-width:500px;margin:0 auto;border-top:5px solid #9D7458;padding:20px;">
    <h2 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;text-align:left;">'.htmlspecialchars($userTitle, ENT_QUOTES, 'UTF-8').'</h2>

    <p style="margin:0 0 12px 0;font-size:16px;line-height:1.6;">Hi '.htmlspecialchars($name).',</p>
    <p style="margin:0 0 12px 0;font-size:16px;line-height:1.6;">Thanks for visiting our stall and submitting your details. We’ve attached product sheets for your selected applications.</p>

    <p style="margin:16px 0 8px 0;font-size:16px;line-height:1.6;"><strong>Your summary</strong></p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Company:</strong> '.htmlspecialchars($v['company_name'] ?? '').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Phone:</strong> '.htmlspecialchars($v['contact_number'] ?? '').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Designation:</strong> '.htmlspecialchars($v['designation'] ?? '').'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Industries:</strong> '.htmlspecialchars($industries).'</p>
    <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;"><strong>Applications:</strong> '.htmlspecialchars($applications).'</p>

    <p style="margin:16px 0 0 0;font-size:16px;line-height:1.6;">Regards,<br>'.htmlspecialchars(MAIL_FROM_NAME).'</p>
  </div>
</body>
</html>';

    $userAttachments = mapApplicationsToPdfs((array)($v['applications'] ?? []));
    sendMail($email, $name, 'Thanks — product sheets attached', $userHtml, $userAttachments);
  }
}

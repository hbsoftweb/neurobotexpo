<?php
require __DIR__ . '/api/config.php';        // has SMTP_* and MAIL_* constants
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
    // $mail->SMTPDebug = 2; // uncomment while debugging
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

/**
 * High-level helper for your submission flow.
 * Expects the $record you already build in submit.php (same keys).
 */
function sendSubmissionEmails(array $record): void {
  $v = $record['visitor'];
  $name  = (string)($v['name'] ?? '');
  $email = (string)($v['email'] ?? '');
  $industries   = implode(', ', (array)($v['industries'] ?? []));
  $applications = implode(', ', (array)($v['applications'] ?? []));
  $selfieRel = $record['assets']['selfie']['path'] ?? '';
  $selfieAbs = __DIR__ . '/' . ltrim($selfieRel, '/');

  // --- Admin notification
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

  // --- Visitor auto-reply
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $userHtml = '
      <p>Hi '.htmlspecialchars($name).',</p>
      <p>Thanks for visiting our stall and submitting your details. Our team will reach out shortly.</p>
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
    sendMail($email, $name, 'Thanks for your submission — Neurobot Expo', $userHtml);
  }
}

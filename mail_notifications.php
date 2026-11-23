<?php

function send_notification_email($recipient, $subject, $message) {
  $config_file = __DIR__ . '/smtp_config.php';
  $autoload_file = __DIR__ . '/vendor/autoload.php';

  if (!file_exists($config_file) || !file_exists($autoload_file)) {
    error_log('Notification email not sent: SMTP configuration or Composer dependencies are missing.');
    return false;
  }

  $config = require $config_file;
  $recipient = strtolower(trim((string)$recipient));
  //Only allow sending emails to the specific recipient to avoid sending email to arbitrary addresses
  //Remove this check if you want to allow sending emails to any address
  if ($recipient !== 'databasecoursework@hotmail.com') {
    error_log('Notification email blocked by recipient allowlist.');
    return false;
  }
  
  require_once $autoload_file;
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->Port = (int)$config['port'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
    $mail->Encoding = 'base64';
    $mail->isHTML(false);

    $mail->setFrom($config['from'], 'Database Auction');
    $mail->addAddress($recipient);
    $mail->Subject = $subject;
    $mail->Body = $message;

    $mail->send();
    return true;
  } catch (PHPMailer\PHPMailer\Exception $e) {
    error_log('Notification email failed: ' . $e->getMessage());
    return false;
  } catch (Throwable $e) {
    error_log('Notification email failed: ' . $e->getMessage());
    return false;
  }
}

function send_user_notification_email($conn, $user_id, $subject, $message) {
  $stmt = $conn->prepare('SELECT email FROM users WHERE userID = ?');
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();

  if (!$user) {
    return false;
  }
  return send_notification_email($user['email'], $subject, $message);
}

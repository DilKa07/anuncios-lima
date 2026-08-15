<?php

/**
 * Helper simple para enviar correos. Usa mail() por defecto.
 * Para integración SMTP real se recomienda usar PHPMailer o SwiftMailer.
 */

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

function mail_log($line)
{
    file_put_contents(__DIR__ . '/../../storage/logs/mail.log', '[' . date('c') . '] ' . $line . "\n", FILE_APPEND);
}

function mail_send_smtp($to, $subject, $htmlBody, $plainBody, $mailCfg)
{
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        mail_log('SMTP requested but PHPMailer is not installed.');
        return false;
    }

    $host = trim((string)($mailCfg['smtp_host'] ?? ''));
    $port = (int)($mailCfg['smtp_port'] ?? 587);
    $username = trim((string)($mailCfg['smtp_user'] ?? ''));
    $password = trim((string)($mailCfg['smtp_pass'] ?? ''));
    $secure = strtolower(trim((string)($mailCfg['smtp_secure'] ?? 'tls')));

    if ($host === '' || $username === '' || $password === '') {
        mail_log('SMTP config incompleta: revisa smtp_host/smtp_user/smtp_pass en config/app.php (para Brevo usa smtp-relay.brevo.com + SMTP Key)');
        return false;
    }

    $fromEmail = $mailCfg['from_email'] ?? ($mailCfg['from'] ?? $username);
    $fromName = $mailCfg['from_name'] ?? 'Website';

    try {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->SMTPAuth = true;
        $mailer->AuthType = 'LOGIN';
        $mailer->Username = $username;
        $mailer->Password = $password;
        $mailer->CharSet = 'UTF-8';

        if ($secure === 'ssl') {
            $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($to);
        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $plainBody;

        $mailer->send();
        mail_log('SMTP OK to=' . $to . ' subject=' . $subject);
        return true;
    } catch (Throwable $e) {
        mail_log('SMTP FAILED to=' . $to . ' subject=' . $subject . ' error=' . $e->getMessage());
        return false;
    }
}

function mail_send_brevo_api($to, $subject, $htmlBody, $plainBody, $mailCfg)
{
    $apiKey = trim((string)($mailCfg['brevo_api_key'] ?? ''));
    if ($apiKey === '') {
        mail_log('Brevo API config incompleta: falta brevo_api_key en config/app.php');
        return false;
    }

    $fromEmail = trim((string)($mailCfg['from_email'] ?? ($mailCfg['from'] ?? '')));
    if ($fromEmail === '') {
        mail_log('Brevo API config incompleta: falta from_email en config/app.php');
        return false;
    }

    $fromName = trim((string)($mailCfg['from_name'] ?? 'Website'));

    $payload = [
        'sender' => [
            'email' => $fromEmail,
            'name' => $fromName,
        ],
        'to' => [
            ['email' => (string)$to],
        ],
        'subject' => (string)$subject,
        'htmlContent' => (string)$htmlBody,
        'textContent' => (string)$plainBody,
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        mail_log('Brevo API FAILED: no se pudo serializar payload JSON');
        return false;
    }

    $url = 'https://api.brevo.com/v3/smtp/email';
    $headers = [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            mail_log('Brevo API FAILED to=' . $to . ' subject=' . $subject . ' error=' . $curlError);
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            mail_log('Brevo API OK to=' . $to . ' subject=' . $subject . ' from=' . $fromEmail . ' code=' . $httpCode);
            return true;
        }

        mail_log('Brevo API FAILED to=' . $to . ' subject=' . $subject . ' from=' . $fromEmail . ' code=' . $httpCode . ' response=' . $response);
        return false;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $jsonPayload,
            'ignore_errors' => true,
            'timeout' => 30,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', (string)$statusLine, $matches);
    $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;

    if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
        mail_log('Brevo API OK to=' . $to . ' subject=' . $subject . ' from=' . $fromEmail . ' code=' . $httpCode);
        return true;
    }

    mail_log('Brevo API FAILED to=' . $to . ' subject=' . $subject . ' from=' . $fromEmail . ' code=' . $httpCode . ' response=' . (string)$response);
    return false;
}

function mail_send($to, $subject, $htmlBody, $plainBody = null, $options = [])
{
    $cfg = require __DIR__ . '/../../config/app.php';
    $mailCfg = $cfg['mail'] ?? [];

    $from = $mailCfg['from'] ?? 'no-reply@localhost';
    $fromName = $mailCfg['from_name'] ?? 'Website';

    $plain = $plainBody ?: strip_tags($htmlBody);

    $driver = strtolower((string)($mailCfg['driver'] ?? 'mail'));
    if ($driver === 'brevo_api') {
        return mail_send_brevo_api($to, $subject, $htmlBody, $plain, $mailCfg);
    }

    if ($driver === 'smtp') {
        $smtpSent = mail_send_smtp($to, $subject, $htmlBody, $plain, $mailCfg);
        return $smtpSent;
    }

    $boundary = md5((string)microtime(true));
    $headers = "From: {$fromName} <{$from}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $plain . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $htmlBody . "\r\n\r\n";
    $body .= "--{$boundary}--\r\n";

    // si está deshabilitado, solo registrar en logs
    if (empty($mailCfg['enabled'])) {
        mail_log('mail_send SKIPPED (enabled=false) to=' . $to . ' subject=' . $subject);
        return true;
    }

    // fallback: usar mail()
    $sent = @mail($to, $subject, $body, $headers);
    if (!$sent) {
        $lastError = error_get_last();
        $log = 'mail_send FAILED to=' . $to . ' subject=' . $subject . ' from=' . $from;
        if (!empty($lastError['message'])) {
            $log .= ' error=' . $lastError['message'];
        }
        mail_log($log);
    } else {
        mail_log('mail() OK to=' . $to . ' subject=' . $subject);
    }
    return $sent;
}

function mail_send_template($to, $subject, $templatePath, $vars = [])
{
    // cargar plantilla PHP que imprima HTML
    if (!file_exists($templatePath)) return false;
    extract($vars);
    ob_start();
    include $templatePath;
    $html = ob_get_clean();
    return mail_send($to, $subject, $html);
}

?>

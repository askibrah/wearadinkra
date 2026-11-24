<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/**
 * Send an SMS through the Intek gateway.
 * The payload includes common field aliases used by Intek-style APIs;
 * adjust SMS_API_URL/keys in config.php if your dashboard specifies different names.
 */
function wa_send_sms(string $phone, string $message): array
{
    $to = preg_replace('/[^0-9+]/', '', (string)$phone);
    if ($to === '' || str_starts_with($to, '+000')) {
        return ['ok' => false, 'error' => 'Invalid phone number'];
    }
    if (str_starts_with($to, '0') && strlen(preg_replace('/\D/', '', $to)) === 10) {
        $to = '+233' . substr(preg_replace('/\D/', '', $to), 1);
    }

    if (!defined('SMS_API_KEY') || SMS_API_KEY === '' || !defined('SMS_API_URL') || SMS_API_URL === '') {
        wa_log('api/storage/sms.log', date('c') . " !! SMS SKIPPED (missing config) -> {$to} | {$message}" . PHP_EOL);
        return ['ok' => false, 'error' => 'SMS is not configured'];
    }

    $payload = [
        'api_key'   => SMS_API_KEY,
        'app_key'   => SMS_API_KEY,
        'key'       => SMS_API_KEY,
        'sender'    => SMS_SENDER,
        'sender_id' => SMS_SENDER,
        'from'      => SMS_SENDER,
        'to'        => $to,
        'recipient' => $to,
        'number'    => $to,
        'msisdn'    => [$to],
        'message'   => $message,
        'content'   => $message,
        'msg'       => $message,
        'text'      => $message,
    ];

    $ch = curl_init(SMS_API_URL);
    if (defined('SMS_CA_BUNDLE') && SMS_CA_BUNDLE !== '') {
        curl_setopt($ch, CURLOPT_CAINFO, SMS_CA_BUNDLE);
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . SMS_API_KEY,
            'Api-Key: ' . SMS_API_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    wa_log('api/storage/sms.log', date('c') . " -> {$to} | HTTP {$code} | " . ($response ?: $error) . PHP_EOL);

    if ($error !== '') return ['ok' => false, 'error' => $error];
    if ($code >= 400) return ['ok' => false, 'error' => 'SMS gateway HTTP ' . $code, 'response' => $response];
    return ['ok' => true, 'response' => $response, 'http_code' => $code];
}

function wa_log(string $relative, string $line): void
{
    $path = __DIR__ . '/../' . $relative;
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

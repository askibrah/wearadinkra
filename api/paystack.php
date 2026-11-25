<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/**
 * Paystack hosted payment. Secret key stays on the server; only the
 * authorization URL is returned to the browser.
 */
function wa_paystack_request(string $url, array $data = [], string $method = 'POST')
{
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
            'Content-Type: application/json',
        ],
    ];
    /* Some shared hosts ship without a CA bundle; set PAYSTACK_CA_BUNDLE in config.php to a cacert.pem path. */
    if (defined('PAYSTACK_CA_BUNDLE') && PAYSTACK_CA_BUNDLE !== '') {
        $opts[CURLOPT_CAINFO] = PAYSTACK_CA_BUNDLE;
    }
    if ($method === 'POST') { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = json_encode($data); }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode((string)$body, true)];
}

$action = $_GET['action'] ?? (json_decode(file_get_contents('php://input'), true)['action'] ?? '');
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

if ($action === 'initialize') {
    $email = trim((string)($input['email'] ?? ''));
    $amount = (float)($input['amount'] ?? 0);
    $reference = trim((string)($input['reference'] ?? ''));
    $name = trim((string)($input['name'] ?? 'Customer'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $amount <= 0 || $reference === '') {
        wa_json(['ok' => false, 'message' => 'Invalid payment details.'], 422);
    }
    $callback = APP_URL . 'paystack-callback.html';
    // Paystack shows this address in its popup. Keep the brand there instead of the shopper's email.
    $displayEmail = defined('PAYSTACK_DISPLAY_EMAIL') && filter_var(PAYSTACK_DISPLAY_EMAIL, FILTER_VALIDATE_EMAIL)
        ? (string)PAYSTACK_DISPLAY_EMAIL
        : $email;
    [$code, $res] = wa_paystack_request('https://api.paystack.co/transaction/initialize', [
        'email' => $displayEmail,
        'amount' => round($amount * 100),
        'reference' => $reference,
        'currency' => 'GHS',
        'callback_url' => $callback,
        'metadata' => ['custom_fields' => [['display_name' => 'Name', 'variable_name' => 'name', 'value' => $name]]],
    ]);
    if ($code >= 200 && $code < 300 && !empty($res['status']) && !empty($res['data']['authorization_url'])) {
        wa_json(['ok' => true, 'authorization_url' => $res['data']['authorization_url'], 'reference' => $res['data']['reference'] ?? $reference]);
    }
    wa_json(['ok' => false, 'message' => 'Could not start Paystack payment. ' . ($res['message'] ?? '')], 502);
}

if ($action === 'verify') {
    $reference = trim((string)($_GET['reference'] ?? ''));
    if ($reference === '') wa_json(['ok' => false, 'message' => 'Missing reference.'], 422);
    [$code, $res] = wa_paystack_request('https://api.paystack.co/transaction/verify/' . urlencode($reference), [], 'GET');
    $status = $res['data']['status'] ?? '';
    wa_json(['ok' => $code >= 200 && $code < 300 && $status === 'success', 'status' => $status, 'reference' => $reference]);
}

wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);

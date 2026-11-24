<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

$input = wa_input();
if (!wa_valid_csrf($input)) {
    wa_json(['ok' => false, 'message' => 'Invalid security token. Please reload.'], 403);
}

$action = (string)($input['action'] ?? '');

/* Every action in this file sends email on the site's behalf, so it must be
   admin-only. The CSRF check above is not enough on its own: any visitor can
   request a token from api/auth.php?action=csrf. */
if (empty($_SESSION['admin_id'])) {
    wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
}

if ($action === 'promotion_notify') {
    $type = trim((string)($input['type'] ?? 'Promotion'));
    $productName = trim((string)($input['productName'] ?? 'All products'));
    $discount = max(1, min(90, (int)($input['discount'] ?? 0)));
    $start = (string)($input['start'] ?? '');
    $end = (string)($input['end'] ?? '');
    if ($type === '' || $discount <= 0 || $end === '') {
        wa_json(['ok' => false, 'message' => 'Promotion details are incomplete.'], 422);
    }

    $stmt = $pdo->query('SELECT name, email FROM users WHERE email <> "" ORDER BY id ASC');
    $users = $stmt->fetchAll();

    $sent = 0;
    $failed = 0;
    foreach ($users as $u) {
        $email = strtolower(trim((string)$u['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
        $ok = wa_email_promotion($email, (string)$u['name'], $type, $discount, $start, $end, $productName);
        if ($ok) $sent++; else $failed++;
    }

    wa_json([
        'ok' => true,
        'message' => 'Promotion announced to ' . $sent . ' customer(s).',
        'total' => count($users),
        'sent' => $sent,
        'failed' => $failed,
    ]);
}

if ($action === 'test_email') {
    $to = trim((string)($input['to'] ?? ''));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        wa_json(['ok' => false, 'message' => 'Enter a valid email address.'], 422);
    }
    $kind = (string)($input['kind'] ?? 'otp');
    if (!in_array($kind, ['otp', 'activation', 'promotion'], true)) {
        wa_json(['ok' => false, 'message' => 'Unknown email type.'], 422);
    }

    $from = wa_mail_from();
    $loggedOnly = defined('MAIL_LOG_ONLY') && MAIL_LOG_ONLY;

    if ($kind === 'activation') {
        $ok = wa_email_activation($to, 'Test Customer', bin2hex(random_bytes(16)), '123456');
    } elseif ($kind === 'promotion') {
        $ok = wa_email_promotion($to, 'Test Customer', 'Flash Sale', 20, date('Y-m-d\TH:i'), date('Y-m-d\TH:i', time() + 86400), 'All products');
    } else {
        $ok = wa_email_otp($to, 'Test Customer', '123456', 10);
    }

    if ($ok && $loggedOnly) {
        wa_json([
            'ok' => true,
            'logged_only' => true,
            'from' => $from,
            'message' => 'Written to api/storage/mail.log only — MAIL_LOG_ONLY is enabled, so nothing was actually sent.',
        ]);
    }
    if ($ok) {
        wa_json([
            'ok' => true,
            'logged_only' => false,
            'from' => $from,
            'message' => "Test email sent to {$to} from {$from}. If it does not arrive, check the spam folder, then SPF/DKIM under cPanel → Email Deliverability.",
        ]);
    }
    wa_json([
        'ok' => false,
        'logged_only' => false,
        'from' => $from,
        'message' => "Sending failed from {$from}. Open api/storage/mail.log on the server for the reason.",
    ], 502);
}

wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);

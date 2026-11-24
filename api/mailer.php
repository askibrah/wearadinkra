<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sms.php'; // wa_log()

/**
 * Send an HTML email.
 * Uses authenticated SMTP when SMTP_HOST is configured in config.php,
 * otherwise falls back to PHP mail(). Every message is logged locally so
 * codes can be verified on a local server without a mail client.
 */
/**
 * Resolve the sender address.
 *
 * Mail sent from a domain the site does not own fails SPF/DKIM and is dropped
 * or spam-filtered. If MAIL_FROM names a different domain than APP_URL (a very
 * easy copy-paste slip between "wearadinkra" and "wearadinkrah"), fall back to
 * no-reply@<site host> and record why in the mail log.
 */
function wa_mail_from(): string
{
    $from = defined('MAIL_FROM') ? trim((string) MAIL_FROM) : '';

    $host = strtolower((string) parse_url(APP_URL, PHP_URL_HOST));
    $host = (string) preg_replace('/^www\./', '', $host);

    // Local development: leave whatever is configured alone.
    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
        return $from;
    }

    $at = strrpos($from, '@');
    $fromHost = $at === false ? '' : strtolower(substr($from, $at + 1));

    if ($from === '' || $fromHost !== $host) {
        $fallback = 'no-reply@' . $host;
        wa_log('api/storage/mail.log', date('c') . " !! MAIL_FROM '{$from}' does not match the site domain; using '{$fallback}'" . PHP_EOL);
        return $fallback;
    }

    return $from;
}

function wa_send_email(string $to, string $subject, string $html): bool
{
    $from = wa_mail_from();
    $fromName = MAIL_FROM_NAME;

    wa_log('api/storage/mail.log', date('c') . " -> {$to} | {$subject} | " . strip_tags($html) . PHP_EOL);

    if (defined('MAIL_LOG_ONLY') && MAIL_LOG_ONLY) {
        return true;
    }

    if (defined('SMTP_HOST') && SMTP_HOST !== '') {
        $ok = wa_smtp_send($to, $subject, $html, $from, $fromName);
        if (!$ok) {
            wa_log('api/storage/mail.log', date('c') . " !! SMTP MAIL FAILED -> {$to} | {$subject}" . PHP_EOL);
        }
        return $ok;
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . $fromName . ' <' . $from . ">\r\n";
    $headers .= 'Reply-To: ' . $fromName . ' <' . $from . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $params = '-f ' . $from;
    $sent = @mail($to, $subject, $html, $headers, $params);
    if (!$sent) {
        wa_log('api/storage/mail.log', date('c') . " !! MAIL() FAILED -> {$to} | {$subject}" . PHP_EOL);
    }
    return $sent;
}

/**
 * Minimal authenticated SMTP sender (no external dependencies).
 * Works with most hosts on ports 587 (tls) / 465 (ssl) / 25 (none).
 */
function wa_smtp_send(string $to, string $subject, string $html, string $from, string $fromName): bool
{
    $host = SMTP_HOST;
    $port = (int)SMTP_PORT;
    $secure = strtolower(defined('SMTP_SECURE') ? SMTP_SECURE : 'tls');
    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    // cPanel's mail hostname needs SNI/peer_name for the TLS upgrade to verify.
    $context = stream_context_create([
        'ssl' => [
            'peer_name' => $host,
            'SNI_enabled' => true,
        ],
    ]);
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        wa_log('api/storage/mail.log', date('c') . " !! SMTP CONNECT FAILED: {$errstr}" . PHP_EOL);
        return false;
    }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 4096)) !== false) {
            $data .= $line;
            if (preg_match('/^\d{3}[\s-]/', $line)) break;
        }
        return $data;
    };
    $send = static function (string $command) use ($fp, $read): string {
        fwrite($fp, $command . "\r\n");
        return $read();
    };

    $hello = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $hello = preg_replace('/[^a-zA-Z0-9.\-]/', '', $hello) ?: 'localhost';

    $read(); // server greeting
    $send('EHLO ' . $hello);

    if ($secure === 'tls') {
        $start = $send('STARTTLS');
        if (!str_starts_with($start, '220')) {
            fclose($fp);
            wa_log('api/storage/mail.log', date('c') . " !! SMTP STARTTLS REJECTED: " . trim($start) . PHP_EOL);
            return false;
        }
        // STREAM_CRYPTO_METHOD_TLS_CLIENT negotiates old TLS on some PHP builds,
        // which modern cPanel mail servers refuse. Prefer TLS 1.3, then 1.2.
        $methods = [];
        foreach (['STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT', 'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT', 'STREAM_CRYPTO_METHOD_TLS_CLIENT'] as $name) {
            if (defined($name)) $methods[] = constant($name);
        }
        $crypto = false;
        foreach (array_unique($methods) as $method) {
            $crypto = @stream_socket_enable_crypto($fp, true, $method);
            if ($crypto) break;
        }
        if (!$crypto) {
            $err = error_get_last();
            fclose($fp);
            wa_log('api/storage/mail.log', date('c') . " !! SMTP STARTTLS FAILED: " . ($err['message'] ?? 'unknown TLS error') . PHP_EOL);
            return false;
        }
        $send('EHLO ' . $hello);
    }

    if (defined('SMTP_USER') && SMTP_USER !== '') {
        $send('AUTH LOGIN');
        $send(base64_encode(SMTP_USER));
        $auth = $send(base64_encode(defined('SMTP_PASS') ? SMTP_PASS : ''));
        if (!str_starts_with($auth, '235')) {
            fclose($fp);
            wa_log('api/storage/mail.log', date('c') . " !! SMTP AUTH FAILED" . PHP_EOL);
            return false;
        }
    }

    $send('MAIL FROM: <' . $from . '>');
    $rcpt = $send('RCPT TO: <' . $to . '>');
    if (!str_starts_with($rcpt, '250') && !str_starts_with($rcpt, '251')) {
        fclose($fp);
        return false;
    }
    $send('DATA');

    $message = 'From: ' . $fromName . ' <' . $from . ">\r\n"
        . 'To: ' . $to . "\r\n"
        . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "X-Mailer: PHP/" . phpversion() . "\r\n"
        . "\r\n"
        . str_replace("\n.", "\n..", str_replace("\r\n", "\n", $html));

    fwrite($fp, $message . "\r\n.\r\n");
    $result = $read();
    $send('QUIT');
    fclose($fp);

    return str_starts_with(trim($result), '250');
}

/** Kente colour strip used across all branded emails. */
function wa_email_kente(): string
{
    $colours = ['#C62828', '#F5C542', '#2E7D32', '#111111'];
    $html = '<div style="height:6px;font-size:0;line-height:0;">';
    foreach ($colours as $c) {
        $html .= '<span style="display:inline-block;width:25%;height:6px;background:' . $c . ';"></span>';
    }
    return $html . '</div>';
}

/** Shared branded HTML wrapper — logo, Kente strips, sea-blue accents. */
function wa_email_layout(string $title, string $preheader, string $bodyHtml): string
{
    $logo = rtrim(APP_URL, '/') . '/assets/wearadinkra-logo.png';
    $home = rtrim(APP_URL, '/');
    $safeTitle = htmlspecialchars($title);
    $safePre = htmlspecialchars($preheader);
    $kente = wa_email_kente();

    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1.0">'
        . '<title>' . $safeTitle . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f4f1ec;color:#0B132B;font-family:Avant Garde,AvantGarde,ITC Avant Garde Gothic,Century Gothic,Arial,sans-serif;">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $safePre . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ec;padding:32px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e8e1d7;">'
        . '<tr><td>' . $kente . '</td></tr>'
        . '<tr><td style="background:#ffffff;padding:30px 24px;text-align:center;">'
        . '<img src="' . htmlspecialchars($logo) . '" alt="Wear Adinkra" width="76" height="76" style="display:block;margin:0 auto 12px;width:76px;height:76px;border-radius:50%;background:#ffffff;padding:6px;">'
        . '<div style="font-size:28px;letter-spacing:1px;color:#0B132B;">wearadinkra</div>'
        . '<div style="display:inline-block;margin-top:10px;padding:4px 12px;background:#0B132B;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#ffffff;">Our Heritage Lives On&hellip;</div>'
        . '</td></tr>'
        . '<tr><td>' . $kente . '</td></tr>'
        . '<tr><td style="padding:36px 32px 28px;">' . $bodyHtml . '</td></tr>'
        . '<tr><td>' . $kente . '</td></tr>'
        . '<tr><td style="padding:22px 32px;background:#fbfaf8;border-top:1px solid #ece6dd;text-align:center;">'
        . '<div style="font-size:14px;color:#1C5D99;margin-bottom:6px;">Our heritage lives on</div>'
        . '<div style="font-size:12px;color:#6b7280;line-height:1.6;">Wear Adinkra &bull; Accra, Ghana<br>'
        . '<a href="' . htmlspecialchars($home) . '" style="color:#1C5D99;text-decoration:none;">' . htmlspecialchars($home) . '</a></div>'
        . '<div style="font-size:10px;letter-spacing:1px;text-transform:uppercase;color:#9ca3af;margin-top:12px;">Powered by WearAdinkra</div>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Verification code email (password reset / OTP). */
function wa_email_otp_html(string $name, string $otp, int $minutes = 10): string
{
    $subject = 'Your Wear Adinkra verification code';
    $body = <<<HTML
<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">Account Security</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.2;color:#0B132B;font-weight:normal;">Your verification code</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">Hi {$name}, use the code below to continue your Wear Adinkra experience. This is the same code we send to your phone by SMS.</p>
<div style="margin:0 0 24px;padding:24px;text-align:center;background:#f8fbff;border:1px solid #dbeafe;border-top:4px solid #1C5D99;">
  <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;margin-bottom:10px;">One-Time Code</div>
  <div style="font-size:38px;letter-spacing:10px;font-weight:bold;color:#0B132B;">{$otp}</div>
</div>
<p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">This code expires in {$minutes} minutes. If you did not request it, you can safely ignore this email.</p>
HTML;
    return wa_email_layout($subject, 'Your Wear Adinkra verification code is ' . $otp, $body);
}

function wa_email_otp(string $to, string $name, string $otp, int $minutes = 10): bool
{
    return wa_send_email($to, 'Your Wear Adinkra verification code', wa_email_otp_html($name, $otp, $minutes));
}

/** Activation email — includes the same OTP sent by SMS plus the activation link. */
function wa_email_activation_html(string $name, string $token, string $otp = ''): string
{
    $link = rtrim(APP_URL, '/') . '/activate.html?token=' . urlencode($token);
    $subject = 'Activate your Wear Adinkra account';
    $otpBlock = '';
    if ($otp !== '') {
        $otpBlock = <<<HTML
<div style="margin:0 0 26px;padding:22px;text-align:center;background:#f8fbff;border:1px solid #dbeafe;border-top:4px solid #1C5D99;">
  <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;margin-bottom:10px;">Verification Code</div>
  <div style="font-size:34px;letter-spacing:8px;font-weight:bold;color:#0B132B;">{$otp}</div>
  <div style="font-size:12px;color:#6b7280;margin-top:8px;">Same code sent to your phone &middot; expires in 10 minutes</div>
</div>
HTML;
    }
    $body = <<<HTML
<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">Welcome to the Collection</p>
<h1 style="margin:0 0 16px;font-size:32px;line-height:1.2;color:#0B132B;font-weight:normal;">Welcome, {$name}</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#374151;">Your Wear Adinkra account is almost ready. Use the code below (the same one sent by SMS) or activate directly to step into a world where Ghanaian heritage meets modern fashion.</p>
{$otpBlock}
<p style="margin:0 0 24px;text-align:center;">
  <a href="{$link}" style="display:inline-block;background:#0B132B;color:#ffffff;text-decoration:none;padding:15px 30px;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">Activate Account</a>
</p>
<p style="margin:0;font-size:12px;line-height:1.7;color:#6b7280;">If the button does not work, copy this link:<br><a href="{$link}" style="color:#1C5D99;word-break:break-all;">{$link}</a></p>
HTML;
    return wa_email_layout($subject, 'Activate your Wear Adinkra account and get your code', $body);
}

function wa_email_activation(string $to, string $name, string $token, string $otp = ''): bool
{
    return wa_send_email($to, 'Activate your Wear Adinkra account', wa_email_activation_html($name, $token, $otp));
}

/** Order confirmation email. */
function wa_email_order(string $to, string $name, string $orderId, float $total, string $method, string $items): bool
{
    $subject = 'Your Wear Adinkra order ' . $orderId;
    $body = '<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">Order Confirmed</p>'
        . '<h1 style="margin:0 0 16px;font-size:30px;line-height:1.2;color:#0B132B;font-weight:normal;">Thank you, ' . htmlspecialchars($name) . '</h1>'
        . '<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">Your order has been received. We are preparing it with care.</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 24px;font-size:14px;">'
        . '<tr><td style="padding:12px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Order number</td><td style="padding:12px 0;border-bottom:1px solid #ece6dd;text-align:right;font-weight:bold;color:#0B132B;">' . htmlspecialchars($orderId) . '</td></tr>'
        . '<tr><td style="padding:12px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Items</td><td style="padding:12px 0;border-bottom:1px solid #ece6dd;text-align:right;color:#0B132B;">' . htmlspecialchars($items) . '</td></tr>'
        . '<tr><td style="padding:12px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Payment</td><td style="padding:12px 0;border-bottom:1px solid #ece6dd;text-align:right;color:#0B132B;">' . htmlspecialchars(strtoupper($method)) . '</td></tr>'
        . '<tr><td style="padding:12px 0;color:#6b7280;">Total</td><td style="padding:12px 0;text-align:right;font-weight:bold;color:#1C5D99;font-size:16px;">GH&#8373; ' . number_format($total, 2) . '</td></tr>'
        . '</table>'
        . '<p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">We will notify you when your order ships. Thank you for wearing your culture.</p>';
    return wa_send_email($to, $subject, wa_email_layout($subject, 'Your Wear Adinkra order ' . $orderId . ' was received', $body));
}

/** Admin password reset email — same branded layout as customer emails. */
function wa_email_admin_reset(string $to, string $name, string $link): bool
{
    $subject = 'Reset your Wear Adinkra admin password';
    $body = <<<HTML
<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">Admin Security</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.2;color:#0B132B;font-weight:normal;">Password reset requested</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">Hi {$name}, use the button below to choose a new password for your Wear Adinkra admin account.</p>
<p style="margin:0 0 24px;text-align:center;">
  <a href="{$link}" style="display:inline-block;background:#0B132B;color:#ffffff;text-decoration:none;padding:15px 30px;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">Reset password</a>
</p>
<p style="margin:0;font-size:12px;line-height:1.7;color:#6b7280;">This link expires in 15 minutes. If you did not request it, you can safely ignore this email.</p>
HTML;
    return wa_send_email($to, $subject, wa_email_layout($subject, 'Reset your admin password', $body));
}

/** Order status change notification to the customer. */
function wa_email_order_status(string $to, string $name, string $orderId, string $status): bool
{
    $labels = [
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'in_transit' => 'In transit',
        'delivered' => 'Delivered',
    ];
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    $messages = [
        'shipped' => 'Your parcel has left our hands and is on its way.',
        'in_transit' => 'Your order is moving through the delivery network.',
        'delivered' => 'Your order has arrived. Thank you for wearing your culture.',
    ];
    $note = $messages[$status] ?? 'We are preparing your order with care and will keep you updated.';
    $subject = 'Order ' . $orderId . ' is ' . $label;
    $body = <<<HTML
<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">Order Update</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.2;color:#0B132B;font-weight:normal;">Hi {$name}, your order has moved</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">{$note}</p>
<div style="margin:0 0 26px;padding:24px;text-align:center;background:#0B132B;border-top:4px solid #1C5D99;">
  <div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#8CB9E8;margin-bottom:8px;">Order {$orderId}</div>
  <div style="font-size:30px;color:#F5C542;letter-spacing:1px;">{$label}</div>
</div>
<p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">You can follow the progress any time with the tracking tool on our website. If you have questions, reply to this email or message us on WhatsApp.</p>
HTML;
    return wa_send_email($to, $subject, wa_email_layout($subject, 'Order ' . $orderId . ' is now ' . $label, $body));
}

/** Admin new-order alert email. */
function wa_email_admin_order(string $to, string $orderId, string $customer, string $email, string $phone, float $total, string $method, string $items): bool
{
    $subject = 'New Wear Adinkra order ' . $orderId;
    $body = '<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">New Order Alert</p>'
        . '<h1 style="margin:0 0 16px;font-size:30px;line-height:1.2;color:#0B132B;font-weight:normal;">New order received</h1>'
        . '<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">A customer has placed an order on Wear Adinkra.</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 24px;font-size:14px;">'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Order</td><td style="padding:10px 0;border-bottom:1px solid #ece6dd;text-align:right;font-weight:bold;">' . htmlspecialchars($orderId) . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Customer</td><td style="padding:10px 0;border-bottom:1px solid #ece6dd;text-align:right;">' . htmlspecialchars($customer) . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Email</td><td style="padding:10px 0;border-bottom:1px solid #ece6dd;text-align:right;">' . htmlspecialchars($email) . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Phone</td><td style="padding:10px 0;border-bottom:1px solid #ece6dd;text-align:right;">' . htmlspecialchars($phone) . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Payment</td><td style="padding:10px 0;border-bottom:1px solid #ece6dd;text-align:right;">' . htmlspecialchars(strtoupper($method)) . '</td></tr>'
        . '<tr><td style="padding:10px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Items</td><td style="padding:10px 0;border-bottom:1px solid #ece6dd;text-align:right;">' . htmlspecialchars($items) . '</td></tr>'
        . '<tr><td style="padding:10px 0;color:#6b7280;">Total</td><td style="padding:10px 0;text-align:right;font-weight:bold;color:#1C5D99;">GH&#8373; ' . number_format($total, 2) . '</td></tr>'
        . '</table>';
    return wa_send_email($to, $subject, wa_email_layout($subject, 'New order ' . $orderId, $body));
}

/** Bulk promotion announcement email sent to every customer. */
function wa_email_promotion_html(string $name, string $type, int $discount, string $start, string $end, string $productName = 'All products'): string
{
    $fmtDate = static function (string $value): string {
        if ($value === '') return 'Now';
        $ts = strtotime($value);
        return $ts ? date('d M Y, H:i', $ts) : htmlspecialchars($value);
    };
    $startText = $fmtDate($start);
    $endText = $fmtDate($end);
    $subject = $type . ' is here — ' . $discount . '% off at Wear Adinkra';
    $shopUrl = rtrim(APP_URL, '/') . '/index.html#shop';
    $body = <<<HTML
<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">Special Promotion</p>
<h1 style="margin:0 0 16px;font-size:32px;line-height:1.2;color:#0B132B;font-weight:normal;">{$type} starts now</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#374151;">Hi {$name}, something special just dropped at Wear Adinkra. Wear your culture for less while this promotion lasts.</p>
<div style="margin:0 0 26px;padding:24px;text-align:center;background:#0B132B;border-top:4px solid #1C5D99;">
  <div style="font-size:11px;letter-spacing:3px;text-transform:uppercase;color:#8CB9E8;margin-bottom:6px;">Promotion type</div>
  <div style="font-size:26px;color:#ffffff;margin-bottom:14px;">{$type}</div>
  <div style="font-size:46px;line-height:1;color:#F5C542;font-weight:bold;">{$discount}% OFF</div>
  <div style="font-size:12px;color:#ffffffcc;margin-top:10px;">{$productName}</div>
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 26px;font-size:14px;">
  <tr><td style="padding:12px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Starts</td><td style="padding:12px 0;border-bottom:1px solid #ece6dd;text-align:right;font-weight:bold;color:#0B132B;">{$startText}</td></tr>
  <tr><td style="padding:12px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Ends</td><td style="padding:12px 0;border-bottom:1px solid #ece6dd;text-align:right;font-weight:bold;color:#0B132B;">{$endText}</td></tr>
  <tr><td style="padding:12px 0;border-bottom:1px solid #ece6dd;color:#6b7280;">Type</td><td style="padding:12px 0;border-bottom:1px solid #ece6dd;text-align:right;color:#0B132B;">{$type}</td></tr>
  <tr><td style="padding:12px 0;color:#6b7280;">Applies to</td><td style="padding:12px 0;text-align:right;color:#0B132B;">{$productName}</td></tr>
</table>
<p style="margin:0 0 24px;text-align:center;">
  <a href="{$shopUrl}" style="display:inline-block;background:#1C5D99;color:#ffffff;text-decoration:none;padding:15px 30px;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:bold;">Shop the promotion</a>
</p>
<p style="margin:0;font-size:12px;line-height:1.7;color:#6b7280;">Prices are already updated on the store. No code needed &mdash; simply add your favourites to the cart.</p>
HTML;
    return wa_email_layout($subject, $type . ' — ' . $discount . '% off from ' . $startText, $body);
}

function wa_email_promotion(string $to, string $name, string $type, int $discount, string $start, string $end, string $productName = 'All products'): bool
{
    $subject = $type . ' is here — ' . $discount . '% off at Wear Adinkra';
    return wa_send_email($to, $subject, wa_email_promotion_html($name, $type, $discount, $start, $end, $productName));
}

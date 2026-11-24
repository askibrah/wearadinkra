<?php
declare(strict_types=1);

/* ---------------------------------------------------------------
 * Wear Adinkra — secure auth configuration
 *
 * TEMPLATE FILE. Copy this file to `config.php` in the same folder
 * and fill in your own values:
 *
 *     cp api/config.example.php api/config.php
 *
 * `api/config.php` is git-ignored and must never be committed.
 * --------------------------------------------------------------- */

define('APP_URL', 'http://localhost/wearadinkra/');
define('APP_NAME', 'Wear Adinkra');

/* MySQL database — adjust these to match your hosting environment */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'wearadinkra');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/* OTP + activation lifetimes */
define('OTP_TTL', 10 * 60);          // 10 minutes
define('RESET_OTP_TTL', 15 * 60);    // 15 minutes for password recovery
define('ACTIVATION_TTL', 24 * 60 * 60); // 24 hours

/* Brute-force protection */
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_SECONDS', 5 * 60);   // 5 minutes

/* ---------------------------------------------------------------
 * SMS provider (Intek)
 * --------------------------------------------------------------- */
define('SMS_API_KEY', '');
define('SMS_API_URL', 'https://www.inteksms.top/api/v1/messages/send');
define('SMS_SENDER', 'BGI OTP');
/* Optional: absolute path to cacert.pem if the server cannot verify the SMS gateway SSL certificate.
   Leave empty on most live hosts (PHP already has a CA bundle configured). */
define('SMS_CA_BUNDLE', '');

/* Admin order notifications (fallback if no active admin phone/email found in DB) */
define('ADMIN_NOTIFY_EMAIL', 'info@wearadinkrah.com');
define('ADMIN_NOTIFY_PHONE', '');

/* ---------------------------------------------------------------
 * Email — replace with your SMTP/mail setup on the live server.
 * PHP mail() is used; mail.log captures everything locally so you
 * can still complete testing before SMTP is configured.
 * --------------------------------------------------------------- */
define('MAIL_FROM', 'no-reply@wearadinkrah.com');
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_LOG', __DIR__ . '/storage/mail.log');
define('DEV_MODE', false); // Production-safe: never expose OTPs/activation links to the browser. Set true temporarily for local testing.
define('MAIL_LOG_ONLY', false); // Local testing: write every email to api/storage/mail.log and report success without sending. Keep false on the live server.

/* ---------------------------------------------------------------
 * SMTP (recommended on a live server — more reliable than mail()).
 * Leave SMTP_HOST empty to use PHP mail() instead.
 * For Gmail: host=smtp.gmail.com port=587 secure=tls user=your@gmail.com
 * pass must be an App Password (not your normal account password).
 * --------------------------------------------------------------- */
define('SMTP_HOST', '');        // e.g. smtp.gmail.com
define('SMTP_PORT', 587);       // 587 (tls) or 465 (ssl)
define('SMTP_USER', '');        // SMTP username / email
define('SMTP_PASS', '');        // SMTP password / app password
define('SMTP_SECURE', 'tls');   // 'tls', 'ssl' or 'none'

/* Paystack (test keys — replace with live keys in production) */
define('PAYSTACK_PUBLIC_KEY', '');
define('PAYSTACK_SECRET_KEY', '');
define('PAYPAL_FEE_RATE', 0.01); // 1%: 50 GHS = 0.50 GHS, 100 GHS = 1 GHS
/* Optional: absolute path to cacert.pem if this host cannot verify api.paystack.co over TLS. */
define('PAYSTACK_CA_BUNDLE', '');

/* ---------------------------------------------------------------
 * Social login — paste your app IDs/secret here later.
 * The endpoints are already wired and will return a clear error
 * until these are filled in.
 * --------------------------------------------------------------- */
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('FACEBOOK_CLIENT_ID', '');
define('FACEBOOK_CLIENT_SECRET', '');

/* Secure session settings */
if (session_status() === PHP_SESSION_NONE) {
    session_name('wa_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('Africa/Accra');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

function wa_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function wa_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) return $data;
    return $_POST;
}

function wa_valid_csrf(array $data): bool
{
    return isset($data['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$data['csrf_token']);
}

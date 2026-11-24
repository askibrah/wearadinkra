<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/rate_limit.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

/* ---------------- helpers ---------------- */
function wa_find_user_by_identifier(PDO $pdo, string $identifier)
{
    $identifier = strtolower(trim($identifier));
    if (str_contains($identifier, '@')) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$identifier]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([wa_normalise_phone($identifier)]);
    }
    return $stmt->fetch();
}

function wa_user_public(array $u): array
{
    return [
        'id' => (int)$u['id'],
        'name' => $u['name'],
        'email' => $u['email'],
        'status' => $u['status'],
    ];
}

function wa_social_credentials(PDO $pdo): array
{
    $cfg = [
        'googleId' => defined('GOOGLE_CLIENT_ID') ? (string) GOOGLE_CLIENT_ID : '',
        'googleSecret' => defined('GOOGLE_CLIENT_SECRET') ? (string) GOOGLE_CLIENT_SECRET : '',
        'fbId' => defined('FACEBOOK_CLIENT_ID') ? (string) FACEBOOK_CLIENT_ID : '',
        'fbSecret' => defined('FACEBOOK_CLIENT_SECRET') ? (string) FACEBOOK_CLIENT_SECRET : '',
    ];
    try {
        $row = $pdo->query('SELECT settings FROM seo_settings ORDER BY id DESC LIMIT 1')->fetchColumn();
        $saved = $row ? json_decode((string)$row, true) : null;
        if (is_array($saved)) {
            foreach (['googleId', 'googleSecret', 'fbId', 'fbSecret'] as $key) {
                if (isset($saved[$key]) && $saved[$key] !== '') $cfg[$key] = (string)$saved[$key];
            }
        }
    } catch (PDOException $e) { /* fall back to config.php constants */ }
    return $cfg;
}

function wa_generate_otp(PDO $pdo, int $userId, string $channel, ?int $ttl = null): string
{
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $ttl = $ttl ?? OTP_TTL;
    $stmt = $pdo->prepare('UPDATE otp_codes SET used_at = ? WHERE user_id = ? AND channel = ? AND used_at IS NULL');
    $stmt->execute([wa_now(), $userId, $channel]);
    $stmt = $pdo->prepare('INSERT INTO otp_codes (user_id, channel, code_hash, expires_at, created_at) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $channel, password_hash($otp, PASSWORD_DEFAULT), time() + $ttl, wa_now()]);
    return $otp;
}

function wa_verify_otp_row(PDO $pdo, int $userId, string $otp, string $channel): bool
{
    $stmt = $pdo->prepare('SELECT * FROM otp_codes WHERE user_id = ? AND channel = ? AND used_at IS NULL AND expires_at >= ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId, $channel, time()]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($otp, $row['code_hash'])) return false;
    $stmt = $pdo->prepare('UPDATE otp_codes SET used_at = ? WHERE id = ?');
    $stmt->execute([wa_now(), (int)$row['id']]);
    return true;
}

function wa_issue_activation(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('UPDATE activation_tokens SET used_at = ? WHERE user_id = ? AND used_at IS NULL');
    $stmt->execute([wa_now(), $userId]);
    $stmt = $pdo->prepare('INSERT INTO activation_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, hash('sha256', $token), time() + ACTIVATION_TTL, wa_now()]);
    return $token;
}

/**
 * Activate a user through either verification method (OTP or activation link).
 * Consumes the other method at the same time, so one account can only be
 * activated once and the unused path stops working immediately.
 */
function wa_activate_user(PDO $pdo, int $userId): void
{
    $now = wa_now();
    $stmt = $pdo->prepare('UPDATE users SET status = "active", activated_at = ?, email_verified_at = COALESCE(email_verified_at, ?), phone_verified_at = COALESCE(phone_verified_at, ?) WHERE id = ?');
    $stmt->execute([$now, $now, $now, $userId]);
    $stmt = $pdo->prepare('UPDATE activation_tokens SET used_at = ? WHERE user_id = ? AND used_at IS NULL');
    $stmt->execute([$now, $userId]);
    $stmt = $pdo->prepare('UPDATE otp_codes SET used_at = ? WHERE user_id = ? AND used_at IS NULL');
    $stmt->execute([$now, $userId]);
    wa_clear_attempts($pdo, 'user:' . $userId);
}

function wa_send_otps(PDO $pdo, array $user, string $otp, string $activationToken): void
{
    wa_email_activation($user['email'], $user['name'], $activationToken, $otp);
    if (!str_starts_with($user['phone'], '+000')) {
        wa_send_sms($user['phone'], 'Wear Adinkra: Your verification code is ' . $otp . '. Expires in 10 minutes.');
    }
}

function wa_normalise_phone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
        $digits = '233' . ltrim($digits, '0'); // Ghana local 0XXXXXXXXX -> 233XXXXXXXXX
    }
    return '+' . ltrim($digits, '+');
}

function wa_session_login(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

/* ---------------- Public GET endpoints ---------------- */
if ($action === 'csrf') {
    wa_json(['ok' => true, 'csrf_token' => $_SESSION['csrf_token']]);
}
if ($action === 'reviews_list' && $method === 'GET') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if ($productId <= 0) {
        $stmt = $pdo->query('SELECT author, rating, comment, images, created_at FROM reviews ORDER BY id DESC LIMIT 200');
        wa_json(['ok' => true, 'reviews' => $stmt->fetchAll()]);
    }
    $stmt = $pdo->prepare('SELECT author, rating, comment, images, created_at FROM reviews WHERE product_id = ? ORDER BY id DESC LIMIT 20');
    $stmt->execute([$productId]);
    wa_json(['ok' => true, 'reviews' => $stmt->fetchAll()]);
}

/* ---------------- POST endpoints ---------------- */
if ($method !== 'POST') wa_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token. Please reload.'], 403);

switch ($action) {

    /* ---------- REGISTER ---------- */
    case 'register':
        $name = trim((string)($input['name'] ?? ''));
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $phoneRaw = trim((string)($input['phone'] ?? ''));
        $password = (string)($input['password'] ?? '');
        $phone = $phoneRaw !== '' ? wa_normalise_phone($phoneRaw) : '+000' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

        if (mb_strlen($name) < 2) wa_json(['ok' => false, 'message' => 'Enter your full name.'], 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) wa_json(['ok' => false, 'message' => 'Enter a valid email address.'], 422);
        if ($phoneRaw !== '' && !preg_match('/^\+[0-9]{9,16}$/', $phone)) wa_json(['ok' => false, 'message' => 'Enter a valid phone number.'], 422);
        if (strlen($password) < 8) wa_json(['ok' => false, 'message' => 'Password must be at least 8 characters.'], 422);

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1');
        $stmt->execute([$email, $phone]);
        if ($stmt->fetch()) wa_json(['ok' => false, 'message' => 'An account with that email or phone already exists.'], 409);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, status, created_at) VALUES (?, ?, ?, ?, "pending", ?)');
        $stmt->execute([$name, $email, $phone, $hash, wa_now()]);
        $userId = (int)$pdo->lastInsertId();

        $otp = wa_generate_otp($pdo, $userId, 'account');
        $token = wa_issue_activation($pdo, $userId);
        wa_send_otps($pdo, ['email' => $email, 'phone' => $phone, 'name' => $name], $otp, $token);

        $response = [
            'ok' => true,
            'message' => 'Account created. We sent a verification code and an activation link to your email.',
            'user_id' => $userId,
            'email' => $email,
        ];
        wa_json($response);

    /* ---------- VERIFY OTP ---------- */
    case 'verify_otp':
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $otp = preg_replace('/\D/', '', (string)($input['otp'] ?? ''));
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) wa_json(['ok' => false, 'message' => 'Account not found.'], 404);
        if ($user['status'] === 'active') wa_json(['ok' => true, 'activated' => true, 'message' => 'Account is already active. You can log in.']);

        if (!wa_verify_otp_row($pdo, (int)$user['id'], $otp, 'account')) {
            wa_json(['ok' => false, 'message' => 'Invalid or expired code.'], 422);
        }
        wa_activate_user($pdo, (int)$user['id']);

        wa_json(['ok' => true, 'activated' => true, 'message' => 'Account activated. You can now log in.']);

    /* ---------- ACTIVATE ---------- */
    case 'activate':
        $token = (string)($input['token'] ?? '');
        if ($token === '') wa_json(['ok' => false, 'message' => 'Missing activation token.'], 422);
        $stmt = $pdo->prepare('SELECT t.*, u.status AS user_status FROM activation_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = ? ORDER BY t.id DESC LIMIT 1');
        $stmt->execute([hash('sha256', $token)]);
        $row = $stmt->fetch();
        if (!$row) wa_json(['ok' => false, 'message' => 'This activation link is invalid or has expired.'], 410);
        if ($row['user_status'] === 'active') wa_json(['ok' => true, 'activated' => true, 'message' => 'Account is already active. You can log in.']);
        if (!empty($row['used_at']) || (int)$row['expires_at'] < time()) wa_json(['ok' => false, 'message' => 'This activation link is invalid or has expired.'], 410);

        wa_activate_user($pdo, (int)$row['user_id']);
        wa_json(['ok' => true, 'activated' => true, 'message' => 'Account activated. You can now log in.']);

    /* ---------- VERIFICATION STATUS (countdowns) ---------- */
    case 'verification_status':
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) wa_json(['ok' => false, 'message' => 'Account not found.'], 404);
        if ($user['status'] === 'active') wa_json(['ok' => true, 'active' => true, 'otp_seconds' => 0, 'activation_seconds' => 0]);

        $stmt = $pdo->prepare('SELECT expires_at FROM otp_codes WHERE user_id = ? AND used_at IS NULL AND expires_at >= ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([(int)$user['id'], time()]);
        $otpExpiry = $stmt->fetchColumn();
        $stmt = $pdo->prepare('SELECT expires_at FROM activation_tokens WHERE user_id = ? AND used_at IS NULL AND expires_at >= ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([(int)$user['id'], time()]);
        $linkExpiry = $stmt->fetchColumn();
        wa_json([
            'ok' => true,
            'active' => false,
            'otp_seconds' => $otpExpiry ? max(0, (int)$otpExpiry - time()) : 0,
            'activation_seconds' => $linkExpiry ? max(0, (int)$linkExpiry - time()) : 0,
        ]);

    /* ---------- LOGIN ---------- */
    case 'login':
        $loginId = strtolower(trim((string)($input['login'] ?? $input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $identifier = $loginId . '|' . $ip;

        $status = wa_rate_status($pdo, $identifier);
        if ($status['locked']) {
            wa_json([
                'ok' => false,
                'locked' => true,
                'retry_after' => $status['retry_after'],
                'message' => 'Too many failed attempts. Try again in ' . ceil($status['retry_after'] / 60) . ' minutes.',
            ], 429);
        }

        $user = wa_find_user_by_identifier($pdo, $loginId);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            wa_record_attempt($pdo, $identifier, false);
            $left = wa_rate_status($pdo, $identifier)['remaining'];
            wa_json([
                'ok' => false,
                'message' => 'Incorrect email/phone or password.',
                'remaining' => $left,
            ], 401);
        }
        if ($user['status'] !== 'active') {
            wa_json(['ok' => false, 'message' => 'Account not activated. Check your email for the activation link.', 'inactive' => true], 403);
        }

        wa_clear_attempts($pdo, $identifier);
        wa_session_login((int)$user['id']);
        wa_json(['ok' => true, 'message' => 'Welcome back!', 'user' => wa_user_public($user)]);

    /* ---------- RESEND OTP ---------- */
    case 'resend_otp':
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) wa_json(['ok' => false, 'message' => 'Account not found.'], 404);
        if ($user['status'] === 'active') wa_json(['ok' => true, 'message' => 'Account already active.']);
        $otp = wa_generate_otp($pdo, (int)$user['id'], 'account');
        $token = wa_issue_activation($pdo, (int)$user['id']);
        wa_send_otps($pdo, $user, $otp, $token);
        $response = ['ok' => true, 'message' => 'A new code has been sent to your email.'];
        wa_json($response);

    /* ---------- SOCIAL (Google / Facebook) ---------- */
    case 'social':
        $provider = strtolower((string)($input['provider'] ?? ''));
        $token = (string)($input['token'] ?? '');
        if (!in_array($provider, ['google', 'facebook'], true)) wa_json(['ok' => false, 'message' => 'Unsupported provider.'], 422);

        $creds = wa_social_credentials($pdo);
        $clientId = $provider === 'google' ? $creds['googleId'] : $creds['fbId'];
        if ($clientId === '') {
            wa_json(['ok' => false, 'message' => ucfirst($provider) . ' login is not configured yet. Add the app ID in api/config.php.', 'not_configured' => true], 501);
        }
        if ($token === '') wa_json(['ok' => false, 'message' => 'Missing social token.'], 422);

        $profile = null;
        if ($provider === 'google') {
            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
            $json = @file_get_contents($url);
            $data = $json ? json_decode($json, true) : null;
            if (!is_array($data) || ($data['aud'] ?? '') !== $clientId || empty($data['email'])) {
                wa_json(['ok' => false, 'message' => 'Google token could not be verified.'], 401);
            }
            $profile = ['id' => $data['sub'] ?? '', 'email' => strtolower($data['email']), 'name' => $data['name'] ?? 'Adinkra Customer'];
        } else {
            $url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . urlencode($token);
            $json = @file_get_contents($url);
            $data = $json ? json_decode($json, true) : null;
            if (!is_array($data) || empty($data['id']) || empty($data['email'])) {
                wa_json(['ok' => false, 'message' => 'Facebook token could not be verified.'], 401);
            }
            $profile = ['id' => $data['id'], 'email' => strtolower($data['email']), 'name' => $data['name'] ?? 'Adinkra Customer'];
        }

        $field = $provider . '_id';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE $field = ? OR email = ? LIMIT 1");
        $stmt->execute([$profile['id'], $profile['email']]);
        $user = $stmt->fetch();
        if (!$user) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, status, $field, created_at, activated_at) VALUES (?, ?, ?, ?, 'active', ?, ?, ?)");
            $stmt->execute([
                $profile['name'],
                $profile['email'],
                '+000' . str_pad((string)random_int(0, 999999999), 9, '0', STR_PAD_LEFT), // unique placeholder for social accounts
                password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                $profile['id'],
                wa_now(),
                wa_now(),
            ]);
            $user = ['id' => $pdo->lastInsertId(), 'name' => $profile['name'], 'email' => $profile['email'], 'status' => 'active'];
        }
        wa_session_login((int)$user['id']);
        wa_json(['ok' => true, 'message' => 'Signed in with ' . ucfirst($provider), 'user' => wa_user_public($user)]);

    /* ---------- PASSWORD RECOVERY ---------- */
    case 'forgot_request':
        $identifier = strtolower(trim((string)($input['identifier'] ?? '')));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateKey = 'reset:' . $identifier . '|' . $ip;
        $status = wa_rate_status($pdo, $rateKey);
        if ($status['locked']) {
            wa_json(['ok' => false, 'locked' => true, 'retry_after' => $status['retry_after'], 'message' => 'Too many recovery requests. Try again later.'], 429);
        }
        if ($identifier === '') wa_json(['ok' => false, 'message' => 'Enter your email or phone number.'], 422);

        if (str_contains($identifier, '@')) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$identifier]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
            $stmt->execute([wa_normalise_phone($identifier)]);
        }
        $user = $stmt->fetch();

        if ($user) {
            $otp = wa_generate_otp($pdo, (int)$user['id'], 'reset', RESET_OTP_TTL);
            wa_email_otp($user['email'], $user['name'], $otp, 15);
            if (!str_starts_with($user['phone'], '+000')) {
                wa_send_sms($user['phone'], 'Wear Adinkra: Your password reset code is ' . $otp . '. Expires in 15 minutes.');
            }
        }
        wa_record_attempt($pdo, $rateKey, false);

        $response = ['ok' => true, 'message' => 'If an account matches, a reset code has been sent to your email and phone.'];
        wa_json($response);

    case 'forgot_verify':
        $identifier = strtolower(trim((string)($input['identifier'] ?? '')));
        $otp = preg_replace('/\D/', '', (string)($input['otp'] ?? ''));
        if (str_contains($identifier, '@')) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$identifier]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
            $stmt->execute([wa_normalise_phone($identifier)]);
        }
        $user = $stmt->fetch();
        if (!$user || !wa_verify_otp_row($pdo, (int)$user['id'], $otp, 'reset')) {
            wa_record_attempt($pdo, 'reset:' . $identifier . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), false);
            wa_json(['ok' => false, 'message' => 'Invalid or expired reset code.'], 422);
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL');
        $stmt->execute([wa_now(), (int)$user['id']]);
        $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([(int)$user['id'], hash('sha256', $token), time() + RESET_OTP_TTL, wa_now()]);
        wa_clear_attempts($pdo, 'reset:' . $identifier . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        wa_json(['ok' => true, 'message' => 'Code verified. Choose a new password.', 'reset_token' => $token]);

    case 'forgot_reset':
        $token = (string)($input['reset_token'] ?? '');
        $password = (string)($input['password'] ?? '');
        if (strlen($password) < 8) wa_json(['ok' => false, 'message' => 'Password must be at least 8 characters.'], 422);
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at >= ? LIMIT 1');
        $stmt->execute([hash('sha256', $token), time()]);
        $row = $stmt->fetch();
        if (!$row) wa_json(['ok' => false, 'message' => 'Reset link expired or already used.'], 410);

        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['user_id']]);
        $stmt = $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?');
        $stmt->execute([wa_now(), (int)$row['id']]);
        wa_json(['ok' => true, 'message' => 'Password reset successfully. You can now log in.']);

    /* ---------- REVIEWS ---------- */
    case 'review_submit':
        $productId = (int)($input['product_id'] ?? 0);
        $orderId = substr(trim((string)($input['order_id'] ?? '')), 0, 40);
        $author = trim((string)($input['author'] ?? 'Customer'));
        $rating = (int)($input['rating'] ?? 5);
        $comment = trim((string)($input['comment'] ?? ''));
        $images = $input['images'] ?? [];
        if ($productId <= 0 || $rating < 1 || $rating > 5 || $comment === '') wa_json(['ok' => false, 'message' => 'Invalid review.'], 422);
        if (mb_strlen($comment) > 1200) wa_json(['ok' => false, 'message' => 'Review is too long.'], 422);
        if (!is_array($images)) $images = [];
        $images = array_slice($images, 0, 5);
        foreach ($images as $img) {
            if (!is_string($img) || !str_starts_with($img, 'data:image/') || strlen($img) > 1200000) {
                wa_json(['ok' => false, 'message' => 'Review images must be small JPEG/PNG files.'], 422);
            }
        }
        $stmt = $pdo->prepare('INSERT INTO reviews (product_id, order_id, author, rating, comment, images, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$productId, $orderId, mb_substr($author, 0, 150), $rating, $comment, json_encode(array_values($images)), wa_now()]);
        wa_json(['ok' => true, 'message' => 'Review submitted.']);

    case 'reviews_list':
        $productId = (int)($_GET['product_id'] ?? 0);
        if ($productId <= 0) wa_json(['ok' => true, 'reviews' => []]);
        $stmt = $pdo->prepare('SELECT author, rating, comment, images, created_at FROM reviews WHERE product_id = ? ORDER BY id DESC LIMIT 20');
        $stmt->execute([$productId]);
        wa_json(['ok' => true, 'reviews' => $stmt->fetchAll()]);

    /* ---------- ORDER NOTIFICATION ---------- */
    case 'order_notify':
        $orderId = strtoupper(trim((string)($input['order_id'] ?? '')));
        $name = trim((string)($input['name'] ?? 'Valued Customer'));
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $phone = trim((string)($input['phone'] ?? ''));
        $total = (float)($input['total'] ?? 0);
        $method = trim((string)($input['method'] ?? 'paystack'));
        $items = trim((string)($input['items'] ?? ''));
        if ($orderId === '' || $total <= 0) wa_json(['ok' => false, 'message' => 'Invalid order data.'], 422);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) wa_email_order($email, $name, $orderId, $total, $method, $items);
        if ($phone !== '') {
            wa_send_sms(wa_normalise_phone($phone), 'Wear Adinkra: order ' . $orderId . ' confirmed. Total GH₵ ' . number_format($total, 2) . '. Thank you!');
        }
        wa_json(['ok' => true, 'message' => 'Order notification sent.']);

    /* ---------- LOGOUT / ME ---------- */
    case 'logout':
        // Clear only the authenticated state. Keep the session (and its CSRF token)
        // alive so other open tabs do not suddenly get "Invalid security token".
        unset($_SESSION['user_id'], $_SESSION['login_time']);
        wa_json(['ok' => true, 'message' => 'Signed out.']);

    case 'me':
        if (empty($_SESSION['user_id'])) wa_json(['ok' => true, 'user' => null]);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
        wa_json(['ok' => true, 'user' => $user ? wa_user_public($user) : null]);

    default:
        wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);
}

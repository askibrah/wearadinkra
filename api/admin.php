<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/rate_limit.php';

/* Admin table lives in the same MySQL database as the store. */
$pdo->exec('CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT "admin",
    status VARCHAR(20) NOT NULL DEFAULT "active",
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);
try { $pdo->exec('ALTER TABLE admins ADD COLUMN phone VARCHAR(40) NULL'); } catch (Exception $e) { /* column exists */ }

/* Seed demo admin accounts on first run. */
$seed = [
    ['Super Admin', 'admin@wearadinkra.com', 'super_admin'],
    ['Manager', 'manager@wearadinkra.com', 'manager'],
];
foreach ($seed as $s) {
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([$s[1]]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO admins (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$s[0], $s[1], password_hash('Admin123!', PASSWORD_DEFAULT), $s[2]]);
    }
}

function wa_admin_public(array $a): array
{
    return ['id' => (int)$a['id'], 'name' => $a['name'], 'email' => $a['email'], 'phone' => $a['phone'] ?? '', 'role' => $a['role']];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($action === 'csrf') wa_json(['ok' => true, 'csrf_token' => $_SESSION['csrf_token']]);

if ($action === 'me') {
    if (empty($_SESSION['admin_id'])) wa_json(['ok' => true, 'user' => null]);
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['admin_id']]);
    $a = $stmt->fetch();
    wa_json(['ok' => true, 'user' => $a ? wa_admin_public($a) : null]);
}

if ($method !== 'POST') wa_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token. Please reload.'], 403);

switch ($action) {
    case 'update_profile':
        if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $name = trim((string)($input['name'] ?? ''));
        $phone = trim((string)($input['phone'] ?? ''));
        $password = (string)($input['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') wa_json(['ok' => false, 'message' => 'Valid name and email are required.'], 422);
        $stmt = $pdo->prepare('UPDATE admins SET name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([mb_substr($name,0,150), $email, mb_substr($phone,0,40), (int)$_SESSION['admin_id']]);
        if ($password !== '') {
            if (strlen($password) < 8) wa_json(['ok' => false, 'message' => 'Password must be at least 8 characters.'], 422);
            $stmt = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), (int)$_SESSION['admin_id']]);
        }
        wa_json(['ok' => true, 'message' => 'Admin profile updated.']);

    case 'login':
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $identifier = 'admin:' . $email . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        $status = wa_rate_status($pdo, $identifier);
        if ($status['locked']) {
            wa_json(['ok' => false, 'locked' => true, 'retry_after' => $status['retry_after'],
                'message' => 'Too many failed attempts. Try again in ' . ceil($status['retry_after'] / 60) . ' minutes.'], 429);
        }

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            wa_record_attempt($pdo, $identifier, false);
            wa_json(['ok' => false, 'message' => 'Incorrect email or password.',
                'remaining' => wa_rate_status($pdo, $identifier)['remaining']], 401);
        }
        if ($admin['status'] !== 'active') wa_json(['ok' => false, 'message' => 'This admin account is inactive.'], 403);

        wa_clear_attempts($pdo, $identifier);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_login_time'] = time();
        wa_json(['ok' => true, 'message' => 'Welcome back!', 'user' => wa_admin_public($admin)]);

    case 'forgot':
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin) {
            $token = bin2hex(random_bytes(24));
            $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([(int)$admin['id'], hash('sha256', $token), time() + 900]);
            $link = APP_URL . 'admin-reset.html?token=' . urlencode($token);
            wa_email_admin_reset($email, (string)$admin['name'], $link);
        }
        wa_json(['ok' => true, 'message' => 'If that email is registered, a reset link has been sent.']);

    case 'reset':
        $token = (string)($input['token'] ?? '');
        $password = (string)($input['password'] ?? '');
        if (strlen($password) < 8) wa_json(['ok' => false, 'message' => 'Password must be at least 8 characters.'], 422);
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at >= ? LIMIT 1');
        $stmt->execute([hash('sha256', $token), time()]);
        $row = $stmt->fetch();
        if (!$row) wa_json(['ok' => false, 'message' => 'Reset link expired or already used.'], 410);
        $stmt = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), (int)$row['user_id']]);
        $stmt = $pdo->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?');
        $stmt->execute([wa_now(), (int)$row['id']]);
        wa_json(['ok' => true, 'message' => 'Password updated. Redirecting…']);

    case 'logout':
        unset($_SESSION['admin_id'], $_SESSION['admin_login_time']);
        wa_json(['ok' => true, 'message' => 'Signed out.']);

    default:
        wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);
}

<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS site_visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    source VARCHAR(150) NULL,
    page VARCHAR(255) NULL,
    device VARCHAR(30) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_visits_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($action === 'list' || $method === 'GET') {
    if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
    $rows = $pdo->query('SELECT * FROM site_visits ORDER BY id DESC LIMIT 200')->fetchAll();
    wa_json(['ok' => true, 'visits' => $rows]);
}

if ($action !== 'track') wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$ip = trim(explode(',', (string)$ip)[0]);
$country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';
$city = '';
// Optional free geo lookup (best effort, 2s timeout)
if (!$country && filter_var($ip, FILTER_VALIDATE_IP) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $geo = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=country,city', false, $ctx);
    $g = $geo ? json_decode($geo, true) : null;
    if (is_array($g)) { $country = $g['country'] ?? ''; $city = $g['city'] ?? ''; }
}
$source = trim((string)($input['source'] ?? ''));
if ($source === '') {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $ref ? parse_url($ref, PHP_URL_HOST) : '';
    $source = $host && $host !== ($_SERVER['HTTP_HOST'] ?? '') ? $host : 'Direct';
}
$page = mb_substr(trim((string)($input['page'] ?? '')), 0, 255);
$device = preg_match('/Mobile|Android|iPhone/i', $_SERVER['HTTP_USER_AGENT'] ?? '') ? 'Mobile' : 'Desktop';

$stmt = $pdo->prepare('INSERT INTO site_visits (ip, country, city, source, page, device, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([mb_substr($ip, 0, 45), mb_substr((string)$country, 0, 100), mb_substr((string)$city, 0, 100), mb_substr($source, 0, 150), $page, $device]);
wa_json(['ok' => true]);

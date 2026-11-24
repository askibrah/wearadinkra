<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS store_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(40) NOT NULL UNIQUE,
    customer VARCHAR(190) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NULL,
    total DECIMAL(10,2) NOT NULL,
    method VARCHAR(40) NULL,
    items TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT "processing",
    guest TINYINT(1) NOT NULL DEFAULT 1,
    is_new TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);
try { $pdo->exec('ALTER TABLE store_orders ADD COLUMN guest TINYINT(1) NOT NULL DEFAULT 1'); } catch (Exception $e) { /* column exists */ }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($action === 'status' && $method === 'GET') {
    $orderId = trim((string)($_GET['order_id'] ?? ''));
    $stmt = $pdo->prepare('SELECT order_id, status, is_new, created_at FROM store_orders WHERE order_id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    wa_json(['ok' => true, 'status' => $row ? $row['status'] : null, 'order' => $row ?: null]);
}

if ($action === 'list' || $method === 'GET') {
    if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
    $rows = $pdo->query('SELECT * FROM store_orders ORDER BY id DESC LIMIT 30')->fetchAll();
    wa_json(['ok' => true, 'orders' => $rows]);
}

if ($action === 'add' && !wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token.'], 403);
if ($action === 'status' && $method === 'POST') {
    if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
    if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token.'], 403);
    $orderId = trim((string)($input['order_id'] ?? ''));
    $status = trim((string)($input['status'] ?? 'processing'));
    if (!in_array($status, ['processing', 'shipped', 'in_transit', 'delivered'], true)) $status = 'processing';

    // Remember the previous row so we can notify the customer only on a real change.
    $beforeStmt = $pdo->prepare('SELECT status, customer, email FROM store_orders WHERE order_id = ? LIMIT 1');
    $beforeStmt->execute([$orderId]);
    $before = $beforeStmt->fetch();

    $stmt = $pdo->prepare('UPDATE store_orders SET status = ? WHERE order_id = ?');
    $stmt->execute([$status, $orderId]);
    $affected = $stmt->rowCount();
    wa_log('api/storage/orders.log', date('c') . " status_update admin=" . ($_SESSION['admin_id'] ?? '0') . " order={$orderId} status={$status} affected={$affected}" . PHP_EOL);

    if ($before && $before['status'] !== $status) {
        $customerEmail = strtolower(trim((string)$before['email']));
        if (filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            wa_email_order_status($customerEmail, (string)($before['customer'] ?: 'Valued Customer'), $orderId, $status);
        }
    }
    if ($affected === 0) {
        $check = $pdo->prepare('SELECT status FROM store_orders WHERE order_id = ? LIMIT 1');
        $check->execute([$orderId]);
        $row = $check->fetch();
        if (!$row) {
            $ins = $pdo->prepare('INSERT INTO store_orders (order_id, customer, email, phone, total, method, items, status, guest, is_new, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0, NOW())');
            $ins->execute([$orderId, 'Customer', '', '', 0, '', '[]', $status]);
        }
    }
    $check2 = $pdo->prepare('SELECT status FROM store_orders WHERE order_id = ? LIMIT 1');
    $check2->execute([$orderId]);
    $saved = $check2->fetch();
    wa_json(['ok' => true, 'message' => 'Order status updated.', 'status' => $saved ? $saved['status'] : $status, 'order_id' => $orderId, 'affected' => $affected]);
}
if ($action !== 'add' && $action !== 'seen' && $action !== 'delete') wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);
if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token.'], 403);

if ($action === 'delete') {
    $orderId = trim((string)($input['order_id'] ?? ''));
    if ($orderId === '') wa_json(['ok' => false, 'message' => 'Invalid order.'], 422);
    $stmt = $pdo->prepare('DELETE FROM store_orders WHERE order_id = ?');
    $stmt->execute([$orderId]);
    wa_log('api/storage/orders.log', date('c') . " order_deleted admin=" . ($_SESSION['admin_id'] ?? '0') . " order={$orderId}" . PHP_EOL);
    wa_json(['ok' => true, 'message' => 'Order deleted.']);
}

if ($action === 'seen') {
    if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
    $pdo->exec('UPDATE store_orders SET is_new = 0');
    wa_json(['ok' => true]);
}

$orderId = trim((string)($input['order_id'] ?? ''));
$total = (float)($input['total'] ?? 0);
if ($orderId === '' || $total <= 0) wa_json(['ok' => false, 'message' => 'Invalid order.'], 422);
$items = is_array($input['items'] ?? null) ? json_encode($input['items']) : (string)($input['items'] ?? '[]');
$stmt = $pdo->prepare('INSERT INTO store_orders (order_id, customer, email, phone, total, method, items, guest, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE order_id = order_id');
$customer = mb_substr(trim((string)($input['customer'] ?? 'Customer')), 0, 190);
$email = mb_substr(trim((string)($input['email'] ?? '')), 0, 190);
$phone = mb_substr(trim((string)($input['phone'] ?? '')), 0, 40);
$methodName = mb_substr(trim((string)($input['method'] ?? '')), 0, 40);
$guest = !empty($input['guest']) ? 1 : 0;
$stmt->execute([$orderId, $customer, $email, $phone, $total, $methodName, $items, $guest]);

// Notify admin(s) by email and SMS
try {
    $itemList = $items;
    $decodedItems = json_decode($items, true);
    if (is_array($decodedItems)) {
        $parts = [];
        foreach ($decodedItems as $it) {
            if (is_string($it)) { $parts[] = $it; continue; }
            if (is_array($it)) {
                $label = trim((string)($it['name'] ?? $it['title'] ?? $it['product'] ?? 'Item'));
                $qty = isset($it['qty']) ? (int)$it['qty'] : (isset($it['quantity']) ? (int)$it['quantity'] : 1);
                $extras = [];
                foreach (['symbol' => 'Symbol', 'size' => 'Size', 'color' => 'Color', 'kidAge' => 'Age', 'kidHeight' => 'Height'] as $key => $labelText) {
                    $value = trim((string)($it[$key] ?? ''));
                    if ($value !== '') $extras[] = $labelText . ': ' . $value;
                }
                $parts[] = $label . ($qty > 1 ? ' x' . $qty : '') . ($extras ? ' (' . implode(', ', $extras) . ')' : '');
            }
        }
        if ($parts) $itemList = implode(', ', $parts);
    }
    $admins = $pdo->query("SELECT email, phone, name FROM admins WHERE status = 'active'")->fetchAll();
    $smsMsg = 'Wear Adinkra: New order ' . $orderId . ' from ' . $customer . '. Total GH₵ ' . number_format($total, 2) . '. ' . strtoupper($methodName) . '.';
    foreach ($admins as $a) {
        $adminEmail = trim((string)($a['email'] ?? ''));
        $adminPhone = trim((string)($a['phone'] ?? ''));
        if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            wa_email_admin_order($adminEmail, $orderId, $customer, $email, $phone, $total, $methodName, $itemList);
        }
        if ($adminPhone !== '') {
            wa_send_sms($adminPhone, $smsMsg);
        }
    }
    if (defined('ADMIN_NOTIFY_EMAIL') && ADMIN_NOTIFY_EMAIL !== '' && filter_var(ADMIN_NOTIFY_EMAIL, FILTER_VALIDATE_EMAIL)) {
        $already = false;
        foreach ($admins as $a) { if (strcasecmp((string)$a['email'], ADMIN_NOTIFY_EMAIL) === 0) { $already = true; break; } }
        if (!$already) wa_email_admin_order(ADMIN_NOTIFY_EMAIL, $orderId, $customer, $email, $phone, $total, $methodName, $itemList);
    }
    // Always alert the domain's admin mailbox too, even if config.php still has an old address.
    $siteHost = strtolower((string)parse_url(APP_URL, PHP_URL_HOST));
    if ($siteHost !== '' && $siteHost !== 'localhost' && $siteHost !== '127.0.0.1') {
        $siteAdmin = 'admin@' . $siteHost;
        if (filter_var($siteAdmin, FILTER_VALIDATE_EMAIL)) {
            $already = false;
            foreach ($admins as $a) { if (strcasecmp((string)$a['email'], $siteAdmin) === 0) { $already = true; break; } }
            if (!$already && (!defined('ADMIN_NOTIFY_EMAIL') || strcasecmp((string)ADMIN_NOTIFY_EMAIL, $siteAdmin) !== 0)) {
                wa_email_admin_order($siteAdmin, $orderId, $customer, $email, $phone, $total, $methodName, $itemList);
            }
        }
    }
    if (defined('ADMIN_NOTIFY_PHONE') && ADMIN_NOTIFY_PHONE !== '') {
        $already = false;
        foreach ($admins as $a) { if (trim((string)$a['phone']) === trim(ADMIN_NOTIFY_PHONE)) { $already = true; break; } }
        if (!$already) wa_send_sms(ADMIN_NOTIFY_PHONE, $smsMsg);
    }
} catch (Throwable $e) {
    wa_log('api/storage/mail.log', date('c') . ' !! ADMIN ORDER NOTIFY FAILED: ' . $e->getMessage() . PHP_EOL);
}

wa_json(['ok' => true]);

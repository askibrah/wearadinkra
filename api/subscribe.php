<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($method === 'GET' || $action === 'list') {
    if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
    $rows = $pdo->query('SELECT id, email, created_at FROM newsletter_subscribers ORDER BY id DESC')->fetchAll();
    wa_json(['ok' => true, 'subscribers' => $rows]);
}

if ($method !== 'POST') wa_json(['ok' => false, 'message' => 'Method not allowed.'], 405);

if ($action === 'subscribe') {
    $email = strtolower(trim((string)($input['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) wa_json(['ok' => false, 'message' => 'Please enter a valid email address.'], 422);
    $check = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) wa_json(['ok' => false, 'already' => true, 'message' => 'You have already joined our newsletter.']);
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())');
    $stmt->execute([$email]);
    wa_json(['ok' => true, 'message' => 'Thank you for subscribing to our newsletter.']);
}

if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token. Please reload.'], 403);

if ($action !== 'send') wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);

$subject = trim((string)($input['subject'] ?? ''));
$message = trim((string)($input['message'] ?? ''));
if ($subject === '' || $message === '') wa_json(['ok' => false, 'message' => 'Subject and message are required.'], 422);

$rows = $pdo->query('SELECT email FROM newsletter_subscribers ORDER BY id ASC')->fetchAll();
$sent = 0;
$failed = 0;
foreach ($rows as $row) {
    $ok = wa_email_newsletter((string)$row['email'], $subject, $message);
    if ($ok) $sent++; else $failed++;
}
wa_json(['ok' => true, 'message' => "Newsletter sent to {$sent} subscriber(s)." . ($failed ? " {$failed} failed." : ''), 'sent' => $sent, 'failed' => $failed, 'total' => count($rows)]);

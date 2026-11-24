<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS adinkra_symbols (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    image VARCHAR(500) NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($method === 'GET' || $action === 'list') {
    $rows = $pdo->query('SELECT id, name, description, image FROM adinkra_symbols ORDER BY id DESC')->fetchAll();
    wa_json(['ok' => true, 'symbols' => $rows]);
}

if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token. Please reload.'], 403);

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) wa_json(['ok' => false, 'message' => 'Invalid symbol.'], 422);
    $stmt = $pdo->prepare('DELETE FROM adinkra_symbols WHERE id = ?');
    $stmt->execute([$id]);
    wa_json(['ok' => true, 'message' => 'Adinkra symbol deleted.']);
}

if ($action !== 'add' && $action !== 'update') wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);

$id = (int)($input['id'] ?? 0);
$name = trim((string)($input['name'] ?? ''));
$description = trim((string)($input['description'] ?? ''));
$image = trim((string)($input['image'] ?? ''));
if ($name === '' || $image === '') wa_json(['ok' => false, 'message' => 'Name and image are required.'], 422);
if ($action === 'update' && $id <= 0) wa_json(['ok' => false, 'message' => 'Invalid symbol.'], 422);

if ($action === 'add') {
    $stmt = $pdo->prepare('INSERT INTO adinkra_symbols (name, description, image, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([mb_substr($name, 0, 150), $description, mb_substr($image, 0, 500)]);
    wa_json(['ok' => true, 'message' => 'Adinkra symbol added.', 'id' => (int)$pdo->lastInsertId()]);
}

$stmt = $pdo->prepare('UPDATE adinkra_symbols SET name = ?, description = ?, image = ?, updated_at = NOW() WHERE id = ?');
$stmt->execute([mb_substr($name, 0, 150), $description, mb_substr($image, 0, 500), $id]);
wa_json(['ok' => true, 'message' => 'Adinkra symbol updated.']);

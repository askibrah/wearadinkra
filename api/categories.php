<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS product_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($method === 'GET' || $action === 'list') {
    $rows = $pdo->query('SELECT id, name FROM product_categories ORDER BY id ASC')->fetchAll();
    wa_json(['ok' => true, 'categories' => $rows]);
}

if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token. Please reload.'], 403);

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) wa_json(['ok' => false, 'message' => 'Invalid category.'], 422);
    $stmt = $pdo->prepare('DELETE FROM product_categories WHERE id = ?');
    $stmt->execute([$id]);
    wa_json(['ok' => true, 'message' => 'Category removed.']);
}

if ($action !== 'add') wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);

$name = trim((string)($input['name'] ?? ''));
if ($name === '') wa_json(['ok' => false, 'message' => 'Enter a category name.'], 422);
if (mb_strlen($name) > 150) $name = mb_substr($name, 0, 150);

$stmt = $pdo->prepare('INSERT INTO product_categories (name, created_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE name = name');
$stmt->execute([$name]);
wa_json(['ok' => true, 'message' => 'Category saved.', 'name' => $name, 'id' => (int)$pdo->lastInsertId()]);

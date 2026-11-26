<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS product_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    sizes TEXT NULL,
    created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);
try { $pdo->exec('ALTER TABLE product_categories ADD COLUMN sizes TEXT NULL'); } catch (PDOException $e) { /* already there */ }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($method === 'GET' || $action === 'list') {
    $rows = $pdo->query('SELECT id, name, sizes FROM product_categories ORDER BY id ASC')->fetchAll();
    foreach ($rows as &$row) {
        $decoded = json_decode((string)$row['sizes'], true);
        $row['sizes'] = is_array($decoded) ? $decoded : ['adult' => [], 'girls' => [], 'boys' => []];
    }
    unset($row);
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

if ($action !== 'add' && $action !== 'update') wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);

$rawSizes = $input['sizes'] ?? [];
if (is_string($rawSizes)) $rawSizes = json_decode($rawSizes, true);
if (!is_array($rawSizes)) $rawSizes = [];
$sizes = [
    'adult' => array_values(array_filter(array_map('strval', (array)($rawSizes['adult'] ?? [])), 'strlen')),
    'girls' => array_values(array_filter(array_map('strval', (array)($rawSizes['girls'] ?? [])), 'strlen')),
    'boys' => array_values(array_filter(array_map('strval', (array)($rawSizes['boys'] ?? [])), 'strlen')),
];
$sizesJson = json_encode($sizes, JSON_UNESCAPED_UNICODE);

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) wa_json(['ok' => false, 'message' => 'Invalid category.'], 422);
    $stmt = $pdo->prepare('UPDATE product_categories SET sizes = ? WHERE id = ?');
    $stmt->execute([$sizesJson, $id]);
    wa_json(['ok' => true, 'message' => 'Category sizes saved.']);
}

$name = trim((string)($input['name'] ?? ''));
if ($name === '') wa_json(['ok' => false, 'message' => 'Enter a category name.'], 422);
if (mb_strlen($name) > 150) $name = mb_substr($name, 0, 150);

$stmt = $pdo->prepare('INSERT INTO product_categories (name, sizes, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE name = name');
$stmt->execute([$name, $sizesJson]);
wa_json(['ok' => true, 'message' => 'Category saved.', 'name' => $name, 'id' => (int)$pdo->lastInsertId()]);

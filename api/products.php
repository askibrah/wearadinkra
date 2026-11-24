<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(60) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image TEXT NOT NULL,
    images TEXT NULL,
    video TEXT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET);

try { $pdo->exec('ALTER TABLE products ADD COLUMN description TEXT NULL'); } catch (Throwable $e) {}

function wa_store_product_image(string $img): string
{
    $img = trim($img);
    if ($img === '') return '';
    if (preg_match('#^https?://#i', $img)) return $img;
    if (preg_match('#^assets/product-images/[A-Za-z0-9._-]+$#', $img)) return $img;
    if (preg_match('#^assets/products/[A-Za-z0-9._/-]+$#', $img)) return $img;
    if (!preg_match('#^data:image/(jpeg|jpg|png|webp|gif);base64,#i', $img, $m)) {
        // Accept already-relative safe paths
        if (preg_match('#^[A-Za-z0-9._/-]+\.(jpg|jpeg|png|webp|gif)$#i', $img)) return $img;
        return '';
    }
    $raw = base64_decode(substr($img, strpos($img, ',') + 1), true);
    if ($raw === false || $raw === '') return '';
    if (strlen($raw) > 8 * 1024 * 1024) return '';
    $src = @imagecreatefromstring($raw);
    if (!$src) return '';
    $w = imagesx($src); $h = imagesy($src);
    $maxW = 1200;
    if ($w > $maxW) {
        $newW = $maxW;
        $newH = (int)round($h * ($maxW / $w));
        $dst = imagecreatetruecolor($newW, $newH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);
        $src = $dst;
    }
    $dir = realpath(__DIR__ . '/../assets/product-images');
    if (!$dir) { $dir = __DIR__ . '/../assets/product-images'; @mkdir($dir, 0775, true); }
    $name = 'p_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.jpg';
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    imagejpeg($src, $path, 82);
    imagedestroy($src);
    return is_file($path) ? ('assets/product-images/' . $name) : '';
}

function wa_normalize_product_images($images): array
{
    if (!is_array($images)) return [];
    $out = [];
    foreach (array_slice($images, 0, 10) as $img) {
        if (!is_string($img)) continue;
        $stored = wa_store_product_image($img);
        if ($stored !== '') $out[] = $stored;
    }
    return array_values(array_unique($out));
}

function wa_seed_products(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM products');
    if ((int)$stmt->fetchColumn() > 0) return;
    $json = @file_get_contents(__DIR__ . '/../products.json');
    $products = $json ? json_decode($json, true) : [];
    if (!is_array($products)) return;
    $stmt = $pdo->prepare('INSERT INTO products (id, name, price, category, stock, image, images, video, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    foreach ($products as $p) {
        $stmt->execute([
            (int)($p['id'] ?? 0),
            (string)($p['name'] ?? 'Product'),
            (float)($p['price'] ?? 0),
            (string)($p['category'] ?? 'Unisex'),
            (int)($p['stock'] ?? 0),
            (string)($p['image'] ?? ''),
            json_encode($p['images'] ?? []),
            (string)($p['video'] ?? ''),
        ]);
    }
}

function wa_product_rows(PDO $pdo): array
{
    wa_seed_products($pdo);
    $rows = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
    $reviewMap = [];
    try {
        $stats = $pdo->query('SELECT product_id, COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM reviews GROUP BY product_id')->fetchAll();
        foreach ($stats as $s) {
            $reviewMap[(int)$s['product_id']] = [
                'count' => (int)$s['cnt'],
                'avg' => round((float)$s['avg_rating'], 1),
            ];
        }
    } catch (Throwable $e) { /* reviews table may be unavailable */ }
    return array_map(function ($r) use ($reviewMap) {
        $imgs = json_decode($r['images'] ?? '[]', true);
        $id = (int)$r['id'];
        $rv = $reviewMap[$id] ?? ['count' => 0, 'avg' => 0];
        return [
            'id' => $id,
            'name' => $r['name'],
            'price' => (float)$r['price'],
            'category' => $r['category'],
            'stock' => (int)$r['stock'],
            'image' => $r['image'],
            'images' => is_array($imgs) ? $imgs : [],
            'video' => $r['video'] ?? '',
            'description' => $r['description'] ?? '',
            'reviews' => $rv['count'],
            'rating' => $rv['avg'],
        ];
    }, $rows);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = wa_input();
$action = isset($input['action']) ? (string)$input['action'] : ($_GET['action'] ?? '');

if ($action === 'list' || $method === 'GET') {
    wa_json(['ok' => true, 'products' => wa_product_rows($pdo)]);
}

if ($action !== 'add' && $action !== 'update') wa_json(['ok' => false, 'message' => 'Unknown action.'], 400);
if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
if (!wa_valid_csrf($input)) wa_json(['ok' => false, 'message' => 'Invalid security token.'], 403);

$name = trim((string)($input['name'] ?? ''));
$price = (float)($input['price'] ?? 0);
if ($action === 'update') {
    if (empty($_SESSION['admin_id'])) wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) wa_json(['ok' => false, 'message' => 'Invalid product.'], 422);
    $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) wa_json(['ok' => false, 'message' => 'Product not found.'], 404);
    $images = wa_normalize_product_images($input['images'] ?? []);
    $image = $images[0] ?? wa_store_product_image((string)($input['image'] ?? ''));
    if ($image === '' && !empty($images)) $image = $images[0];
    $stmt = $pdo->prepare('UPDATE products SET name=?, price=?, category=?, stock=?, image=?, images=?, video=?, description=? WHERE id=?');
    $stmt->execute([mb_substr($name,0,190), $price, mb_substr((string)($input['category'] ?? 'Unisex'),0,60), (int)($input['stock'] ?? 0), $image, json_encode(array_values($images)), (string)($input['video'] ?? ''), (string)($input['description'] ?? ''), $id]);
    wa_json(['ok' => true, 'message' => 'Product updated.', 'products' => wa_product_rows($pdo)]);
}
$category = trim((string)($input['category'] ?? 'Unisex'));
$stock = (int)($input['stock'] ?? 0);
$video = trim((string)($input['video'] ?? ''));
$images = wa_normalize_product_images($input['images'] ?? []);
if ($name === '' || $price <= 0) wa_json(['ok' => false, 'message' => 'Name and price are required.'], 422);
if (count($images) === 0) wa_json(['ok' => false, 'message' => 'Upload at least one image.'], 422);
if ($video !== '' && !preg_match('#(youtube\.com|youtu\.be|tiktok\.com)#i', $video)) {
    wa_json(['ok' => false, 'message' => 'Video must be a YouTube or TikTok link.'], 422);
}

$stmt = $pdo->prepare('INSERT INTO products (name, price, category, stock, image, images, video, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->execute([mb_substr($name, 0, 190), $price, mb_substr($category, 0, 60), $stock, $images[0], json_encode(array_values($images)), $video, (string)($input['description'] ?? '')]);
wa_json(['ok' => true, 'message' => 'Product published.', 'id' => (int)$pdo->lastInsertId(), 'products' => wa_product_rows($pdo)]);

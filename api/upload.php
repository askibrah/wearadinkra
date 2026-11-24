<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    wa_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
if (empty($_SESSION['admin_id'])) {
    wa_json(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
}
$csrf = (string)($_POST['csrf_token'] ?? '');
if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    wa_json(['ok' => false, 'message' => 'Invalid security token.'], 403);
}

$file = $_FILES['image'] ?? null;
if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    wa_json(['ok' => false, 'message' => 'No image uploaded.'], 422);
}
if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
    wa_json(['ok' => false, 'message' => 'Image is too large (max 8MB).'], 422);
}

$tmp = (string)$file['tmp_name'];
$info = @getimagesize($tmp);
if (!$info || empty($info['mime'])) {
    wa_json(['ok' => false, 'message' => 'Invalid image file.'], 422);
}
$mime = (string)$info['mime'];
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
if (!isset($allowed[$mime])) {
    wa_json(['ok' => false, 'message' => 'Only JPG, PNG, WEBP, or GIF images are allowed.'], 422);
}

$src = null;
switch ($mime) {
    case 'image/jpeg': $src = @imagecreatefromjpeg($tmp); break;
    case 'image/png':  $src = @imagecreatefrompng($tmp); break;
    case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : null; break;
    case 'image/gif':  $src = @imagecreatefromgif($tmp); break;
}
if (!$src) {
    wa_json(['ok' => false, 'message' => 'Could not process image.'], 422);
}

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
    $w = $newW; $h = $newH;
}

$dir = realpath(__DIR__ . '/../assets/product-images');
if (!$dir) {
    $dir = __DIR__ . '/../assets/product-images';
    @mkdir($dir, 0775, true);
}
$name = 'p_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.jpg';
$path = $dir . DIRECTORY_SEPARATOR . $name;
imagejpeg($src, $path, 82);
imagedestroy($src);

if (!is_file($path)) {
    wa_json(['ok' => false, 'message' => 'Failed to save image.'], 500);
}

$relative = 'assets/product-images/' . $name;
wa_json(['ok' => true, 'path' => $relative, 'url' => $relative]);

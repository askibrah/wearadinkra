<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/* Public OAuth client IDs only. Secrets stay on the server and are never
   returned here. Values saved in Admin → Settings → Sign-in override the
   constants in config.php, so admins can update IDs without editing PHP. */
$saved = [];
try {
    $row = $pdo->query('SELECT settings FROM seo_settings ORDER BY id DESC LIMIT 1')->fetchColumn();
    if ($row) {
        $decoded = json_decode((string)$row, true);
        if (is_array($decoded)) $saved = $decoded;
    }
} catch (PDOException $e) { /* fall back to config.php constants */ }

wa_json([
    'ok' => true,
    'googleClientId' => (string)($saved['googleId'] ?? (defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '')),
    'facebookAppId' => (string)($saved['fbId'] ?? (defined('FACEBOOK_CLIENT_ID') ? FACEBOOK_CLIENT_ID : '')),
]);

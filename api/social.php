<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/* Public OAuth client IDs only. Secrets stay in config.php and are never
   exposed to the browser — the PHP auth endpoint does the token verification. */
wa_json([
    'ok' => true,
    'googleClientId' => defined('GOOGLE_CLIENT_ID') ? (string) GOOGLE_CLIENT_ID : '',
    'facebookAppId' => defined('FACEBOOK_CLIENT_ID') ? (string) FACEBOOK_CLIENT_ID : '',
]);

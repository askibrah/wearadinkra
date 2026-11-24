<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$cacheFile = __DIR__ . '/storage/rate.json';
$ttl = 3600; // refresh every hour

if (is_file($cacheFile)) {
    $cached = json_decode((string)file_get_contents($cacheFile), true);
    if (is_array($cached) && ($cached['time'] ?? 0) > time() - $ttl && isset($cached['rate'])) {
        wa_json(['ok' => true, 'rate' => (float)$cached['rate'], 'source' => $cached['source'] ?? 'cache']);
    }
}

$rate = null;
$source = '';

// Google Finance (free) — GHS to USD
$url = 'https://www.google.com/finance/quote/GHS-USD';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
    CURLOPT_TIMEOUT => 12,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$html = curl_exec($ch);
curl_close($ch);

if (is_string($html) && preg_match('/class="YMlKec fxKbKc">([0-9]+(?:\.[0-9]+)?)/', $html, $m)) {
    $rate = (float)$m[1];
    $source = 'Google Finance';
}
if ($rate === null && is_string($html) && preg_match('/data-last-price="([0-9]+(?:\.[0-9]+)?)"/', $html, $m)) {
    $rate = (float)$m[1];
    $source = 'Google Finance';
}

// Fallback free API (no key) if Google markup changes
if ($rate === null) {
    $json = @file_get_contents('https://open.er-api.com/v6/latest/GHS');
    $data = $json ? json_decode($json, true) : null;
    if (is_array($data) && isset($data['rates']['USD'])) {
        $rate = (float)$data['rates']['USD'];
        $source = 'ExchangeRate-API';
    }
}

if ($rate === null || $rate <= 0) {
    wa_json(['ok' => false, 'message' => 'Rate unavailable', 'rate' => 0.065], 200);
}

@file_put_contents($cacheFile, json_encode(['rate' => $rate, 'source' => $source, 'time' => time()]), LOCK_EX);
wa_json(['ok' => true, 'rate' => $rate, 'source' => $source]);

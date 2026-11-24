<?php
declare(strict_types=1);
require_once __DIR__ . '/api/mailer.php';
header('Content-Type: text/html; charset=UTF-8');

$kind = (string)($_GET['kind'] ?? 'activation');
$sampleName = 'Kwame';
$sampleOtp = '123456';
$sampleType = 'Flash Sale';
$sampleDiscount = 20;
$sampleStart = date('Y-m-d\TH:i');
$sampleEnd = date('Y-m-d\TH:i', time() + 86400);

if ($kind === 'otp') {
    $subject = 'Your Wear Adinkra verification code';
    $body = '<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;">Account Security</p>'
        . '<h1 style="margin:0 0 16px;font-size:30px;line-height:1.2;color:#0B132B;font-weight:normal;">Your verification code</h1>'
        . '<p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">Hi ' . $sampleName . ', use the code below to continue your Wear Adinkra experience.</p>'
        . '<div style="margin:0 0 24px;padding:24px;text-align:center;background:#f8fbff;border:1px solid #dbeafe;border-top:4px solid #1C5D99;">'
        . '<div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#1C5D99;margin-bottom:10px;">One-Time Code</div>'
        . '<div style="font-size:38px;letter-spacing:10px;font-weight:bold;color:#0B132B;">' . $sampleOtp . '</div></div>'
        . '<p style="margin:0;font-size:13px;line-height:1.7;color:#6b7280;">This code expires in 10 minutes.</p>';
    echo wa_email_layout($subject, 'Your code is ' . $sampleOtp, $body);
} elseif ($kind === 'promotion') {
    echo wa_email_promotion_html($sampleName, $sampleType, $sampleDiscount, $sampleStart, $sampleEnd, 'All products');
} else {
    echo wa_email_activation_html($sampleName, 'sample-token-for-preview', $sampleOtp);
}

<?php
// includes/qrcode.php

function generateQRCode($url, $size = 300) {
    // Simple QR Code using Google Chart API (works offline? No, but very easy)
    // Alternative: Use local library below

    return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($url) . "&choe=UTF-8";
}

// OR use this pure PHP version (recommended - no internet needed)
function generateQRCodeLocal($data, $size = 300) {
    require_once __DIR__ . '/phpqrcode/qrlib.php'; // we'll add this file
    $filename = 'qrcodes/' . md5($data . time()) . '.png';
    $filepath = __DIR__ . '/../uploads/' . $filename;
    
    QRcode::png($data, $filepath, QR_ECLEVEL_L, 10, 2);
    return $filename;
}
?>
<?php
$url = 'https://ajip-api.cloud/api/data/pengumuman-tender';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($response === false) {
    echo "CURL Error\n";
    exit;
}

$data = json_decode($response, true);
if ($data === null) {
    echo "JSON Decode Error\n";
    exit;
}

echo "Total items: " . count($data) . "\n";

$targetKd = '10103894000';
$found = false;
foreach ($data as $item) {
    if ((string)($item['kd_tender'] ?? '') === $targetKd || (string)($item['kd_pkt_dce'] ?? '') === $targetKd) {
        echo "Found target ID: " . json_encode($item, JSON_PRETTY_PRINT) . "\n";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "Target ID $targetKd NOT found in API response.\n";
    // Show first item as sample
    if (count($data) > 0) {
        echo "Sample item: " . json_encode($data[0], JSON_PRETTY_PRINT) . "\n";
    }
}

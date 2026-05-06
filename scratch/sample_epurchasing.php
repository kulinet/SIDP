<?php
$url = "https://ajip-api.cloud/api/data/paket-epurchasing";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$data = json_decode($response, true);
if (is_array($data)) {
    echo "Count: " . count($data) . "\n";
    print_r(array_slice($data, 0, 1));
} else {
    echo "Failed: " . substr($response, 0, 200);
}

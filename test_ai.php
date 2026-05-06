<?php
$apiKey = "sk-PAknjOEGubNCIaULNnJdYHxwV1fCiqELWQ2jzPSBfGqc2Byf";
$url = 'https://api.ai.cc/v1/chat/completions';

$data = [
    'model' => 'gpt-5.4-mini',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello']
    ],
    'max_tokens' => 10
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Curl Error: $error\n";
echo "Response: $response\n";

echo "\n--- Testing Model List ---\n";
$ch = curl_init('https://api.ai.cc/v1/models');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey
]);
$models = curl_exec($ch);
echo "Models Response: $models\n";
curl_close($ch);

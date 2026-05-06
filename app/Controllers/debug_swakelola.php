<?php
require 'vendor/autoload.php';
$client = \Config\Services::curlrequest();
$url = "https://ajip-api.cloud/api/lpse/pencatatan-swakelola";
try {
    $res = $client->get($url, ['timeout' => 30]);
    $data = json_decode($res->getBody(), true);
    echo "Total Rows: " . count($data) . "\n";
    if (count($data) > 0) {
        echo "Sample Row Fields:\n";
        print_r(array_keys($data[0]));
        echo "\nSample Row Data:\n";
        print_r($data[0]);
    } else {
        echo "No data found.";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

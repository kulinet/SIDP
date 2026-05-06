<?php
$client = curl_init();
curl_setopt($client, CURLOPT_RETURNTRANSFER, true);

// Tender Selesai
curl_setopt($client, CURLOPT_URL, 'https://ajip-api.cloud/api/data/tender-selesai');
$res1 = curl_exec($client);
$data1 = json_decode($res1, true);

// Pengumuman Tender
curl_setopt($client, CURLOPT_URL, 'https://ajip-api.cloud/api/data/pengumuman-tender');
$res2 = curl_exec($client);
$data2 = json_decode($res2, true);

echo "Tender Selesai (1 row):\n";
print_r(array_slice($data1 ?: [], 0, 1));
echo "\nPengumuman Tender (1 row):\n";
print_r(array_slice($data2 ?: [], 0, 1));

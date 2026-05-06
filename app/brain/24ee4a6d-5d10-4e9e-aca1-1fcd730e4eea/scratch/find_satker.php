<?php
// Load CodeIgniter
require 'c:/laragon/www/sidp/public/index.php';

$client = \Config\Services::curlrequest();
$url = "https://ajip-api.cloud/api/master/organisasi"; // Assuming this is apiMasterSatker
$res = $client->get($url);
$data = json_decode($res->getBody(), true);

foreach($data as $s) {
    if (strpos($s['nama_satker'], 'Pariwisata') !== false) {
        echo "Nama: " . $s['nama_satker'] . " | KD: " . $s['kd_satker'] . " | KD STR: " . $s['kd_satker_str'] . "\n";
    }
}

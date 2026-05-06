<?php
$token = 'inprc743d77f5b94343e6981757875dfc7f00';
$kode_penyedia = '01JBE5J7EK40W6Y0HAGDHQD6AW';

function callApi($url, $token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['body' => $response, 'info' => $info];
}

echo "Testing Vendor Detail...\n";
$vendor = callApi("https://data.inaproc.id/api/v1/ekatalog/penyedia-detail?kode_penyedia=$kode_penyedia", $token);
print_r($vendor);

echo "\nTesting Product List...\n";
$products = callApi("https://data.inaproc.id/api/v1/ekatalog/list-produk-penyedia?kode_penyedia=$kode_penyedia&limit=10", $token);
print_r($products);

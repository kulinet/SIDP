<?php
$currentYear = 2026; // or 2024? let's use date('Y')
$currentYear = date('Y');

$apiPenyedia = 'https://ajip-api.cloud/api/data/perbandingan-rup-penyedia';
$apiSwakelola = 'https://ajip-api.cloud/api/data/perbandingan-swakelola';
$apiRupAnggaranPenyedia = 'https://ajip-api.cloud/api/data/rup-paket-anggaran';
$apiRupAnggaranSwakelola = 'https://ajip-api.cloud/api/data/rup-paket-swakelola';

function fetchApi($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output, true) ?? [];
}

echo "Fetching data for year $currentYear...\n";

$rawPenyedia = fetchApi($apiPenyedia);
$rawSwakelola = fetchApi($apiSwakelola);

$rupAnggaranPenyedia = fetchApi($apiRupAnggaranPenyedia);
$rupAnggaranSwakelola = fetchApi($apiRupAnggaranSwakelola);

// Calculate Dashboard Total (from perbandingan APIs)
$dashPaguPenyedia = 0;
$dashPaketPenyedia = 0;
foreach ($rawPenyedia as $item) {
    $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? '')));
    if ($yr != $currentYear) continue;
    $dashPaguPenyedia += (float)($item['pagu'] ?? ($item['total_pagu'] ?? ($item['Pagu'] ?? 0)));
    $dashPaketPenyedia++;
}

$dashPaguSwakelola = 0;
$dashPaketSwakelola = 0;
foreach ($rawSwakelola as $item) {
    $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? '')));
    if ($yr != $currentYear) continue;
    $dashPaguSwakelola += (float)($item['pagu'] ?? ($item['total_pagu'] ?? ($item['Pagu'] ?? 0)));
    $dashPaketSwakelola++;
}

echo "Dashboard (Perbandingan API):\n";
echo "Penyedia: Paket = $dashPaketPenyedia, Pagu = " . number_format($dashPaguPenyedia, 0) . "\n";
echo "Swakelola: Paket = $dashPaketSwakelola, Pagu = " . number_format($dashPaguSwakelola, 0) . "\n";
echo "Total Pagu Dashboard: " . number_format($dashPaguPenyedia + $dashPaguSwakelola, 0) . "\n\n";

// Calculate RUP OPD Total (from rup-paket-anggaran APIs)
$allRup = array_merge($rupAnggaranPenyedia, $rupAnggaranSwakelola);
$seenRup = [];
$rupOpdPagu = 0;
$rupOpdPaket = 0;
$rupOpdPaguNoDedup = 0;

foreach ($allRup as $item) {
    if (($item['tahun_anggaran'] ?? '') != $currentYear) continue;
    
    $pagu = (float)($item['pagu'] ?? 0);
    $rupOpdPaguNoDedup += $pagu;

    $kdRup = $item['kd_rup'] ?? null;
    if ($kdRup && isset($seenRup[$kdRup])) continue;
    if ($kdRup) $seenRup[$kdRup] = true;

    $rupOpdPagu += $pagu;
    $rupOpdPaket++;
}

echo "RUP OPD (Anggaran API):\n";
echo "Total Paket (Deduped): $rupOpdPaket\n";
echo "Total Pagu (Deduped): " . number_format($rupOpdPagu, 0) . "\n";
echo "Total Pagu (No Dedup): " . number_format($rupOpdPaguNoDedup, 0) . "\n\n";

// Find discrepancies
// Map Perbandingan to kd_rup
$perbandinganMap = [];
foreach ($rawPenyedia as $item) {
    $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? '')));
    if ($yr != $currentYear) continue;
    $kdRup = $item['kd_rup'] ?? null;
    if ($kdRup) $perbandinganMap[$kdRup] = (float)($item['pagu'] ?? 0);
}
foreach ($rawSwakelola as $item) {
    $yr = $item['tahun_anggaran'] ?? ($item['tahun_anggaran_sirup'] ?? ($item['tahun_anggaran_dana'] ?? ($item['tahun'] ?? '')));
    if ($yr != $currentYear) continue;
    $kdRup = $item['kd_rup'] ?? null;
    if ($kdRup) $perbandinganMap[$kdRup] = (float)($item['pagu'] ?? 0);
}

// Map Anggaran to kd_rup (Summing pagu if multiple rows, vs deduplicating)
$anggaranMapSum = [];
$anggaranMapDedup = [];
foreach ($allRup as $item) {
    if (($item['tahun_anggaran'] ?? '') != $currentYear) continue;
    $kdRup = $item['kd_rup'] ?? null;
    if (!$kdRup) continue;
    $pagu = (float)($item['pagu'] ?? 0);

    if (!isset($anggaranMapSum[$kdRup])) {
        $anggaranMapSum[$kdRup] = 0;
    }
    $anggaranMapSum[$kdRup] += $pagu;

    if (!isset($anggaranMapDedup[$kdRup])) {
        $anggaranMapDedup[$kdRup] = $pagu;
    }
}

$diffCount = 0;
foreach ($perbandinganMap as $kdRup => $paguP) {
    if (!isset($anggaranMapDedup[$kdRup])) {
        // Missing in Anggaran
        // echo "Missing in Anggaran: $kdRup ($paguP)\n";
    } else {
        $paguADedup = $anggaranMapDedup[$kdRup];
        $paguASum = $anggaranMapSum[$kdRup];
        if ($paguP != $paguADedup) {
            $diffCount++;
            if ($diffCount <= 5) {
                echo "Diff for $kdRup: Perbandingan=$paguP | AnggaranDedup=$paguADedup | AnggaranSum=$paguASum\n";
            }
        }
    }
}
echo "Total items with different pagu values: $diffCount\n";

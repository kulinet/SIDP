<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Debug Inaproc API response structure
 * Usage: php spark debug:inaproc 01JJ3EZS1MFRY5CVS4B83NME9J
 */
class DebugInaproc extends BaseCommand
{
    protected $group       = 'Sync';
    protected $name        = 'debug:inaproc';
    protected $description = 'Debug raw Inaproc API response for a single vendor';

    public function run(array $params)
    {
        $kode  = $params[0] ?? '01JJ3EZS1MFRY5CVS4B83NME9J';
        $token = getenv('INAPROC_BEARER_TOKEN') ?: 'inprc73221b186ad14d568b02f22b214c9574';

        CLI::write("Testing vendor: {$kode}", 'yellow');
        CLI::write("Token: " . substr($token, 0, 12) . '...', 'white');

        $client = \Config\Services::curlrequest();

        // Test 1: legacy endpoint
        CLI::write("\n=== Legacy Endpoint ===", 'cyan');
        $url1 = "https://data.inaproc.id/api/legacy/ekatalog/penyedia-detail?kode_penyedia={$kode}";
        CLI::write("URL: {$url1}", 'white');
        try {
            $res = $client->get($url1, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept'        => 'application/json',
                ],
                'timeout' => 15,
            ]);
            $statusCode = $res->getStatusCode();
            $body = $res->getBody();
            CLI::write("HTTP Status: {$statusCode}", $statusCode === 200 ? 'green' : 'red');
            CLI::write("Raw body: " . substr($body, 0, 600), 'white');

            $data = json_decode($body, true);
            CLI::write("JSON keys: " . implode(', ', array_keys($data ?? [])), 'white');
            if (isset($data['data'])) {
                CLI::write("data type: " . (is_array($data['data']) ? 'array, count=' . count($data['data']) : gettype($data['data'])), 'white');
                if (is_array($data['data']) && !empty($data['data'])) {
                    $first = is_numeric(array_keys($data['data'])[0]) ? $data['data'][0] : $data['data'];
                    CLI::write("first item type: " . gettype($first), 'white');
                    CLI::write("nama_penyedia: " . ($first['nama_penyedia'] ?? 'NOT FOUND'), $first['nama_penyedia'] ?? false ? 'green' : 'red');
                }
            }
        } catch (\Exception $e) {
            CLI::write("ERROR: " . $e->getMessage(), 'red');
        }

        // Test 2: Products
        CLI::write("\n=== Product Catalog ===", 'cyan');
        $url2 = "https://data.inaproc.id/api/v1/ekatalog/list-produk-penyedia?kode_penyedia={$kode}&limit=3";
        try {
            $res2 = $client->get($url2, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept'        => 'application/json',
                ],
                'timeout' => 15,
            ]);
            $body2 = $res2->getBody();
            CLI::write("HTTP: " . $res2->getStatusCode(), 'white');
            CLI::write("Raw: " . substr($body2, 0, 400), 'white');
            $data2 = json_decode($body2, true);
            $products = $data2['data'] ?? [];
            CLI::write("Products count: " . count($products), 'green');
            if (!empty($products)) {
                CLI::write("First product: kd_produk=" . ($products[0]['kd_produk'] ?? 'N/A') . ", nama=" . ($products[0]['nama_produk'] ?? 'N/A'), 'white');
            }
        } catch (\Exception $e) {
            CLI::write("ERROR: " . $e->getMessage(), 'red');
        }
    }
}

<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\EpurchasingModel;

/**
 * Sync vendor names and product catalogs from Inaproc API.
 *
 * API Budget: 5000 requests/day
 *   - 1 request per vendor (name)
 *   - 1 request per vendor (product catalog)
 *   - Total per full sync: ~350 requests (175 vendors × 2)
 *
 * Usage:
 *   php spark sync:vendors               # Sync only what's missing
 *   php spark sync:vendors --batch=50    # Max 50 vendors per run
 *   php spark sync:vendors --names-only  # Only fetch vendor names
 *   php spark sync:vendors --prods-only  # Only fetch product catalogs
 *   php spark sync:vendors --force       # Re-fetch everything
 *   php spark sync:vendors --status      # Show cache status (no API calls)
 */
class SyncVendors extends BaseCommand
{
    protected $group       = 'Sync';
    protected $name        = 'sync:vendors';
    protected $description = 'Sync vendor names & product catalogs from Inaproc (budget-aware, 5000 req/day)';

    protected $usage     = 'sync:vendors [options]';
    protected $options   = [
        '--batch'      => 'Max vendors to process per run (default: all missing)',
        '--names-only' => 'Only fetch vendor names (1 request/vendor)',
        '--prods-only' => 'Only fetch product catalogs (1 request/vendor)',
        '--force'      => 'Re-fetch even already-cached data',
        '--yes'        => 'Skip confirmation prompt (non-interactive)',
        '--status'     => 'Show cache status without making any API calls',
    ];

    private string $vendorDir;
    private string $productDir;
    private string $token;
    private int    $apiCount = 0;

    const VENDOR_URL  = 'https://data.inaproc.id/api/legacy/ekatalog/penyedia-detail?kode_penyedia=';
    const PRODUCT_URL = 'https://data.inaproc.id/api/v1/ekatalog/list-produk-penyedia?kode_penyedia=';

    public function run(array $params)
    {
        $this->vendorDir  = WRITEPATH . 'inaproc/vendors/';
        $this->productDir = WRITEPATH . 'inaproc/products/';
        $this->token      = getenv('INAPROC_BEARER_TOKEN') ?: 'inprc73221b186ad14d568b02f22b214c9574';

        foreach ([$this->vendorDir, $this->productDir] as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0777, true);
        }

        $namesOnly = array_key_exists('names-only', $params);
        $prodsOnly = array_key_exists('prods-only', $params);
        $force     = array_key_exists('force', $params);
        $statusOnly= array_key_exists('status', $params);
        $yes       = array_key_exists('yes', $params);
        $batch     = (int)($params['batch'] ?? 0);

        // Collect all vendor codes from DB
        $model = new EpurchasingModel();
        $rows  = $model->select('kode_penyedia')
                       ->where('kode_penyedia IS NOT NULL')
                       ->where('kode_penyedia !=', '')
                       ->findAll();
        $allKodes = array_values(array_unique(array_column($rows, 'kode_penyedia')));
        $total    = count($allKodes);

        CLI::write('================================================', 'cyan');
        CLI::write('  Inaproc Vendor & Product Catalog Sync', 'yellow');
        CLI::write('================================================', 'cyan');
        CLI::write("Total unique vendors in DB: {$total}", 'white');
        CLI::write("Mode: " . ($namesOnly ? 'Names Only' : ($prodsOnly ? 'Products Only' : 'Names + Products')), 'white');
        CLI::write("Force re-fetch: " . ($force ? 'YES' : 'NO'), 'white');
        CLI::write('', '');

        // Build status report
        $nameOk   = 0; $nameMissing  = [];
        $prodOk   = 0; $prodMissing  = [];

        foreach ($allKodes as $kode) {
            // Check vendor name cache
            $vFile  = $this->vendorDir . $kode . '.json';
            $vData  = file_exists($vFile) ? json_decode(file_get_contents($vFile), true) : null;
            $hasName = !empty($vData['nama_penyedia']);
            if ($hasName && !$force) $nameOk++; else $nameMissing[] = $kode;

            // Check product catalog cache
            $pFile  = $this->productDir . $kode . '.json';
            $pData  = file_exists($pFile) ? json_decode(file_get_contents($pFile), true) : null;
            $hasProd = is_array($pData) && !empty($pData);
            if ($hasProd && !$force) $prodOk++; else $prodMissing[] = $kode;
        }

        CLI::write("Vendor names : {$nameOk}/{$total} cached", $nameOk == $total ? 'green' : 'yellow');
        CLI::write("Product catalogs: {$prodOk}/{$total} cached", $prodOk == $total ? 'green' : 'yellow');

        // Estimate API requests needed
        $needNames = $prodsOnly ? 0 : count($nameMissing);
        $needProds = $namesOnly ? 0 : count($prodMissing);
        $totalReq  = $needNames + $needProds;
        CLI::write("API requests needed: {$totalReq} (names:{$needNames} + products:{$needProds})", 'cyan');
        CLI::write("Daily budget remaining estimate: " . (5000 - $totalReq) . " after this run", 'cyan');

        if ($statusOnly) {
            CLI::write('', '');
            CLI::write('Status check complete. No API calls made.', 'green');
            return;
        }

        if ($totalReq === 0) {
            CLI::write('', '');
            CLI::write('All data already cached. Nothing to do.', 'green');
            CLI::write('Use --force to re-fetch everything.', 'cyan');
            return;
        }

        CLI::write('', '');
        if (!$yes) {
            $proceed = CLI::prompt('Proceed with ' . $totalReq . ' API requests?', ['y', 'n']);
            if ($proceed !== 'y') { CLI::write('Aborted.', 'red'); return; }
        } else {
            CLI::write('Auto-confirmed (--yes).', 'cyan');
        }

        // === SYNC VENDOR NAMES ===
        if (!$prodsOnly) {
            CLI::write('', '');
            CLI::write('--- Fetching Vendor Names ---', 'yellow');
            $toFetch = $batch > 0 ? array_slice($nameMissing, 0, $batch) : $nameMissing;
            $this->syncVendorNames($toFetch);
        }

        // === SYNC PRODUCT CATALOGS ===
        if (!$namesOnly) {
            CLI::write('', '');
            CLI::write('--- Fetching Product Catalogs ---', 'yellow');
            $toFetch = $batch > 0 ? array_slice($prodMissing, 0, $batch) : $prodMissing;
            $this->syncProductCatalogs($toFetch);
        }

        // === UPDATE DB with fresh vendor names ===
        CLI::write('', '');
        CLI::write('--- Updating database from cache ---', 'yellow');
        $this->updateDbFromCache($allKodes);

        // Clear app cache
        cache()->deleteMatching('epurchasing_*');
        CLI::write('', '');
        CLI::write('================================================', 'cyan');
        CLI::write("Done! Total API requests used: {$this->apiCount}", 'green');
        CLI::write('App cache cleared.', 'green');
        CLI::write('================================================', 'cyan');
    }

    // ================================================================
    // SYNC: Vendor Names
    // ================================================================
    private function syncVendorNames(array $kodes): void
    {
        $total = count($kodes);
        if ($total === 0) { CLI::write('  Nothing to fetch.', 'white'); return; }

        $client  = \Config\Services::curlrequest();
        $ok = 0; $fail = 0;

        foreach ($kodes as $i => $kode) {
            $num = $i + 1;
            CLI::write("[{$num}/{$total}] {$kode}", 'white');

            $url  = self::VENDOR_URL . $kode;
            $name = null;

            try {
                $res  = $client->get($url, [
                    'headers' => $this->headers(),
                    'timeout' => 12,
                ]);
                $this->apiCount++;
                $body = json_decode($res->getBody(), true);
                // Legacy endpoint returns direct array [{...}], v1 returns {"data": [{...}]}
                $vendor = $body[0] ?? ($body['data'][0] ?? null);
                $name   = is_array($vendor) ? ($vendor['nama_penyedia'] ?? null) : null;
            } catch (\Exception $e) {
                CLI::write("  -> API error: " . $e->getMessage(), 'red');
            }

            $file = $this->vendorDir . $kode . '.json';
            if ($name) {
                file_put_contents($file, json_encode([
                    'nama_penyedia' => $name,
                    '_synced_at'    => date('Y-m-d H:i:s'),
                ]));
                CLI::write("  -> OK: {$name}", 'green');
                $ok++;
            } else {
                // Save not-found marker with 24h cooldown
                file_put_contents($file, json_encode([
                    'nama_penyedia' => null,
                    '_not_found'    => true,
                    '_last_attempt' => date('Y-m-d H:i:s'),
                ]));
                CLI::write("  -> Not found in Inaproc", 'yellow');
                $fail++;
            }

            usleep(250000); // 250ms between requests
        }

        CLI::write("Names: OK={$ok}, Not found={$fail}", 'cyan');
    }

    // ================================================================
    // SYNC: Product Catalogs
    // ================================================================
    private function syncProductCatalogs(array $kodes): void
    {
        $total = count($kodes);
        if ($total === 0) { CLI::write('  Nothing to fetch.', 'white'); return; }

        $client = \Config\Services::curlrequest();
        $ok = 0; $fail = 0;

        foreach ($kodes as $i => $kode) {
            $num = $i + 1;
            CLI::write("[{$num}/{$total}] {$kode}", 'white');

            // Fetch existing cache (may have partial data from before)
            $file    = $this->productDir . $kode . '.json';
            $prodMap = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];

            try {
                $url = self::PRODUCT_URL . $kode . '&limit=1000';
                $res = $client->get($url, [
                    'headers' => $this->headers(),
                    'timeout' => 20,
                ]);
                $this->apiCount++;
                $body = json_decode($res->getBody(), true);
                $products = $body['data'] ?? [];

                if (!empty($products)) {
                    foreach ($products as $p) {
                        $pid  = $p['kd_produk'] ?? null;
                        $pname = $p['nama_produk'] ?? null;
                        if ($pid) $prodMap[$pid] = $pname;
                    }
                    file_put_contents($file, json_encode($prodMap, JSON_UNESCAPED_UNICODE));
                    CLI::write("  -> OK: " . count($products) . " products (total cached: " . count($prodMap) . ")", 'green');
                    $ok++;
                } else {
                    // Vendor exists but has no catalog — save empty array so we don't retry constantly
                    if (empty($prodMap)) {
                        file_put_contents($file, json_encode([]));
                    }
                    CLI::write("  -> No products in catalog", 'yellow');
                    $fail++;
                }
            } catch (\Exception $e) {
                CLI::write("  -> API error: " . $e->getMessage(), 'red');
                $fail++;
            }

            usleep(250000); // 250ms between requests
        }

        CLI::write("Products: OK={$ok}, Empty/Failed={$fail}", 'cyan');
    }

    // ================================================================
    // UPDATE DB: Write cached vendor names into epurchasing table
    // ================================================================
    private function updateDbFromCache(array $allKodes): void
    {
        $db = \Config\Database::connect();
        $updated = 0;

        foreach ($allKodes as $kode) {
            $file = $this->vendorDir . $kode . '.json';
            if (!file_exists($file)) continue;
            $data = json_decode(file_get_contents($file), true);
            $name = $data['nama_penyedia'] ?? null;

            if ($name) {
                $db->table('epurchasing')
                   ->where('kode_penyedia', $kode)
                   ->where('(nama_penyedia IS NULL OR nama_penyedia = "" OR nama_penyedia != ' . $db->escape($name) . ')')
                   ->update(['nama_penyedia' => $name]);
                $updated += $db->affectedRows();
            }
        }

        CLI::write("DB rows updated: {$updated}", 'green');
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'User-Agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept'        => 'application/json',
        ];
    }
}

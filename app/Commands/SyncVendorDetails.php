<?php

namespace App\Commands;

use App\Services\InaprocApiService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncVendorDetails extends BaseCommand
{
    protected $group = 'Sync';
    protected $name = 'sync:vendor-details';
    protected $description = 'Sinkronisasi detail vendor penyedia dari Inaproc ke tabel lokal';

    public function run(array $params)
    {
        $kode = $params[0] ?? null;
        $service = new InaprocApiService();

        if (!empty($kode)) {
            $result = $service->syncVendorDetail($kode, true);
            CLI::write($result['ok'] ? 'Sync OK: ' . $kode : 'Sync failed: ' . ($result['error'] ?? 'unknown'), $result['ok'] ? 'green' : 'red');
            return;
        }

        $syncService = new \App\Services\SyncService();
        CLI::write('Memulai sinkronisasi detail vendor (SIKaP Bot CSV & Inaproc API)...', 'yellow');

        $count = $syncService->syncVendorDetails(date('Y'), function($msg) {
            CLI::write(' -> ' . $msg, 'white');
        });

        CLI::write('Selesai sinkronisasi ' . $count . ' vendor penyedia.', 'green');
    }
}

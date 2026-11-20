<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\RupPenyediaModel;
use App\Models\RupSwakelolaModel;

class SyncRup extends BaseCommand
{
    protected $group       = 'Sync';
    protected $name        = 'sync:rup';
    protected $description = 'Sync RUP data from API to Local Database';

    public function run(array $params)
    {
        $currentYear = date('Y');
        CLI::write("Starting Sync for Year: $currentYear", 'yellow');

        $syncService = new \App\Services\SyncService();

        try {
            CLI::write("Syncing Penyedia...", 'white');
            $countP = $syncService->syncPenyedia($currentYear, function($msg) {
                CLI::write($msg, 'cyan');
            });
            CLI::write("Penyedia: $countP items processed.", 'green');

            CLI::write("Syncing Swakelola...", 'white');
            $countS = $syncService->syncSwakelola($currentYear, function($msg) {
                CLI::write($msg, 'cyan');
            });
            CLI::write("Swakelola: $countS items processed.", 'green');

            CLI::write("Syncing Master Anggaran (Kegiatan)...", 'white');
            $countM = $syncService->syncMasterAnggaran($currentYear, function($msg) {
                CLI::write($msg, 'cyan');
            });
            CLI::write("Master Anggaran: $countM items processed.", 'green');

            CLI::write("Syncing E-Purchasing...", 'white');
            $countE = $syncService->syncEpurchasing($currentYear, function($msg) {
                CLI::write($msg, 'cyan');
            });
            CLI::write("E-Purchasing: $countE items processed.", 'green');

            CLI::write("Syncing LPSE Tenders...", 'white');
            $countT = $syncService->syncTender($currentYear, function($msg) {
                CLI::write($msg, 'cyan');
            });
            CLI::write("LPSE Tenders: $countT items processed.", 'green');

            CLI::write("Syncing LPSE Nontenders...", 'white');
            $countNT = $syncService->syncNontender($currentYear, function($msg) {
                CLI::write($msg, 'cyan');
            });
            CLI::write("LPSE Nontenders: $countNT items processed.", 'green');

            CLI::write("Sync Completed Successfully!", 'green');
        } catch (\Exception $e) {
            CLI::error("Sync Failed: " . $e->getMessage());
        }
    }
}

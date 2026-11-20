<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncEpurchasingCommand extends BaseCommand
{
    protected $group       = 'Sync';
    protected $name        = 'sync:epurchasing';
    protected $description = 'Sync E-Purchasing data from API to Local Database';

    public function run(array $params)
    {
        $year = $params[0] ?? date('Y');
        CLI::write("Starting E-Purchasing Sync for Year: $year", 'yellow');

        $syncService = new \App\Services\SyncService();

        try {
            $count = $syncService->syncEpurchasing($year, function($msg) {
                CLI::write($msg, 'cyan');
            });
            CLI::write("E-Purchasing: $count items processed.", 'green');
        } catch (\Exception $e) {
            CLI::error("Error during sync: " . $e->getMessage());
        }
    }
}


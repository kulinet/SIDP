<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSnapshotsToMonevPeriod extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->fieldExists('contract_snapshot', 'monev_period')) {
            $db->query("ALTER TABLE `monev_period` 
                ADD COLUMN `contract_snapshot` JSON NULL AFTER `jenis_monev`");
        }

        if (!$db->fieldExists('progress_snapshot', 'monev_period')) {
            $db->query("ALTER TABLE `monev_period` 
                ADD COLUMN `progress_snapshot` JSON NULL AFTER `contract_snapshot`");
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->fieldExists('progress_snapshot', 'monev_period')) {
            $db->query("ALTER TABLE `monev_period` DROP COLUMN `progress_snapshot`");
        }

        if ($db->fieldExists('contract_snapshot', 'monev_period')) {
            $db->query("ALTER TABLE `monev_period` DROP COLUMN `contract_snapshot`");
        }
    }
}

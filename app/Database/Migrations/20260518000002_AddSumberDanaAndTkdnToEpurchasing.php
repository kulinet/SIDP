<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSumberDanaAndTkdnToEpurchasing extends Migration
{
    public function up()
    {
        // 1. Add sumber_dana after tahun
        $sumberDanaExists = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'epurchasing'
               AND COLUMN_NAME = 'sumber_dana'"
        )->getRow()->cnt;

        if (!$sumberDanaExists) {
            $this->db->query(
                "ALTER TABLE epurchasing
                 ADD COLUMN sumber_dana VARCHAR(100) NULL DEFAULT NULL AFTER tahun"
            );
        }

        // 2. Add tkdn after total_harga
        $tkdnExists = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'epurchasing'
               AND COLUMN_NAME = 'tkdn'"
        )->getRow()->cnt;

        if (!$tkdnExists) {
            $this->db->query(
                "ALTER TABLE epurchasing
                 ADD COLUMN tkdn VARCHAR(50) NULL DEFAULT NULL AFTER total_harga"
            );
        }
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE epurchasing
            DROP COLUMN IF EXISTS sumber_dana,
            DROP COLUMN IF EXISTS tkdn
        ");
    }
}

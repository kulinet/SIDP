<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNamaPenyediaToEpurchasing extends Migration
{
    public function up()
    {
        // Check if column already exists (MySQL < 8.0 compatible)
        $exists = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'epurchasing'
               AND COLUMN_NAME = 'nama_penyedia'"
        )->getRow()->cnt;

        if (!$exists) {
            $this->db->query(
                "ALTER TABLE epurchasing
                 ADD COLUMN nama_penyedia VARCHAR(500) NULL DEFAULT NULL AFTER kode_penyedia"
            );
        }
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE epurchasing
            DROP COLUMN IF EXISTS nama_penyedia
        ");
    }
}

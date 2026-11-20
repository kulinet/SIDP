<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveTkdnFromEpurchasing extends Migration
{
    public function up()
    {
        $tkdnExists = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'epurchasing'
               AND COLUMN_NAME = 'tkdn'"
        )->getRow()->cnt;

        if ($tkdnExists) {
            $this->db->query(
                "ALTER TABLE epurchasing DROP COLUMN tkdn"
            );
        }
    }

    public function down()
    {
        $this->db->query(
            "ALTER TABLE epurchasing ADD COLUMN tkdn VARCHAR(50) NULL DEFAULT NULL AFTER total_harga"
        );
    }
}

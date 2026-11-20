<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateMonevEvidenceSchema extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Extend entity_type to include MONEV_PERIOD
        $db->query("ALTER TABLE `monev_evidence` 
            MODIFY COLUMN `entity_type` ENUM('MONEV_VISIT', 'MONEV_ASSESSMENT', 'MONEV_FINDING', 'MONEV_FOLLOWUP', 'MONEV_PERIOD') NOT NULL");

        // 2. Extend jenis_eviden to include DOKUMEN_ADMINISTRASI
        $db->query("ALTER TABLE `monev_evidence` 
            MODIFY COLUMN `jenis_eviden` ENUM('FOTO_LAPANGAN', 'DOKUMEN_UJI_LAB', 'BERITA_ACARA', 'SURAT_TEGURAN', 'BUKTI_PERBAIKAN', 'DOKUMEN_ADMINISTRASI', 'LAINNYA') NOT NULL");
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $db->query("ALTER TABLE `monev_evidence` 
            MODIFY COLUMN `jenis_eviden` ENUM('FOTO_LAPANGAN', 'DOKUMEN_UJI_LAB', 'BERITA_ACARA', 'SURAT_TEGURAN', 'BUKTI_PERBAIKAN', 'LAINNYA') NOT NULL");

        $db->query("ALTER TABLE `monev_evidence` 
            MODIFY COLUMN `entity_type` ENUM('MONEV_VISIT', 'MONEV_ASSESSMENT', 'MONEV_FINDING', 'MONEV_FOLLOWUP') NOT NULL");
    }
}

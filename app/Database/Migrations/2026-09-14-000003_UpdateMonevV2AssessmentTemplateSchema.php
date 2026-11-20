<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateMonevV2AssessmentTemplateSchema extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. monev_assessment_templates: adjust versi to VARCHAR(20) and add threshold_config
        $db->query("ALTER TABLE `monev_assessment_templates` 
            MODIFY COLUMN `versi` VARCHAR(20) NOT NULL DEFAULT '1.0'");

        if (!$db->fieldExists('threshold_config', 'monev_assessment_templates')) {
            $db->query("ALTER TABLE `monev_assessment_templates` 
                ADD COLUMN `threshold_config` JSON NULL AFTER `deskripsi`");
        }

        // 2. monev_assessment_indicators: add evidence_requirement
        if (!$db->fieldExists('evidence_requirement', 'monev_assessment_indicators')) {
            $db->query("ALTER TABLE `monev_assessment_indicators` 
                ADD COLUMN `evidence_requirement` TEXT NULL AFTER `panduan_nilai_3`");
        }

        // 3. monev_assessment_item: add evidence_requirement, assessor_id, note
        if (!$db->fieldExists('evidence_requirement', 'monev_assessment_item')) {
            $db->query("ALTER TABLE `monev_assessment_item` 
                ADD COLUMN `evidence_requirement` TEXT NULL AFTER `score`");
        }

        if (!$db->fieldExists('note', 'monev_assessment_item')) {
            $db->query("ALTER TABLE `monev_assessment_item` 
                ADD COLUMN `note` TEXT NULL AFTER `catatan_penilai`");
        }

        if (!$db->fieldExists('assessor_id', 'monev_assessment_item')) {
            $db->query("ALTER TABLE `monev_assessment_item` 
                ADD COLUMN `assessor_id` INT UNSIGNED NULL AFTER `note`,
                ADD INDEX `idx_item_assessor` (`assessor_id`),
                ADD CONSTRAINT `fk_item_assessor` FOREIGN KEY (`assessor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL");
        }

        // 4. monev_assessment: allow NULL for kategori_hasil and modify template_version_snapshot to VARCHAR(20)
        $db->query("ALTER TABLE `monev_assessment` 
            MODIFY COLUMN `template_version_snapshot` VARCHAR(20) NOT NULL DEFAULT '1.0',
            MODIFY COLUMN `kategori_hasil` VARCHAR(50) NULL DEFAULT NULL");
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->fieldExists('assessor_id', 'monev_assessment_item')) {
            $db->query("ALTER TABLE `monev_assessment_item` DROP FOREIGN KEY `fk_item_assessor`");
            $db->query("ALTER TABLE `monev_assessment_item` DROP COLUMN `assessor_id`");
        }
        if ($db->fieldExists('note', 'monev_assessment_item')) {
            $db->query("ALTER TABLE `monev_assessment_item` DROP COLUMN `note`");
        }
        if ($db->fieldExists('evidence_requirement', 'monev_assessment_item')) {
            $db->query("ALTER TABLE `monev_assessment_item` DROP COLUMN `evidence_requirement`");
        }
        if ($db->fieldExists('evidence_requirement', 'monev_assessment_indicators')) {
            $db->query("ALTER TABLE `monev_assessment_indicators` DROP COLUMN `evidence_requirement`");
        }
        if ($db->fieldExists('threshold_config', 'monev_assessment_templates')) {
            $db->query("ALTER TABLE `monev_assessment_templates` DROP COLUMN `threshold_config`");
        }
    }
}

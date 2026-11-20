<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateMonevFindingAndFollowupSchema extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Updating monev_finding
        if (!$db->fieldExists('title', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` ADD COLUMN `title` VARCHAR(255) NULL AFTER `kode_temuan`");
        }

        if (!$db->fieldExists('category', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` ADD COLUMN `category` VARCHAR(100) NULL AFTER `title`");
        }

        if (!$db->fieldExists('severity', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` ADD COLUMN `severity` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'MEDIUM' AFTER `category`");
        }

        if (!$db->fieldExists('responsible_party', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` ADD COLUMN `responsible_party` VARCHAR(150) NULL AFTER `rekomendasi_tindakan`");
        }

        if (!$db->fieldExists('due_date', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` ADD COLUMN `due_date` DATE NULL AFTER `responsible_party`");
        }

        if (!$db->fieldExists('status', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` ADD COLUMN `status` ENUM('OPEN', 'IN_PROGRESS', 'RESOLVED', 'VERIFIED', 'CLOSED') NOT NULL DEFAULT 'OPEN' AFTER `due_date`");
        }

        if (!$db->fieldExists('created_by', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` ADD COLUMN `created_by` INT UNSIGNED NULL AFTER `resolved_at`");
            $db->query("ALTER TABLE `monev_finding` ADD CONSTRAINT `fk_finding_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL");
        }

        // 2. Updating monev_followup
        if (!$db->fieldExists('action', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` ADD COLUMN `action` TEXT NULL AFTER `finding_id`");
        }

        if (!$db->fieldExists('pic', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` ADD COLUMN `pic` VARCHAR(150) NULL AFTER `action`");
        }

        if (!$db->fieldExists('deadline', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` ADD COLUMN `deadline` DATE NULL AFTER `pic`");
        }

        if (!$db->fieldExists('status', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` ADD COLUMN `status` ENUM('OPEN', 'IN_PROGRESS', 'COMPLETED', 'VERIFIED', 'OVERDUE', 'CANCELLED') NOT NULL DEFAULT 'OPEN' AFTER `deadline`");
        }

        if (!$db->fieldExists('verification_notes', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` ADD COLUMN `verification_notes` TEXT NULL AFTER `status`");
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Rollback monev_followup
        if ($db->fieldExists('verification_notes', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` DROP COLUMN `verification_notes`");
        }
        if ($db->fieldExists('status', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` DROP COLUMN `status`");
        }
        if ($db->fieldExists('deadline', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` DROP COLUMN `deadline`");
        }
        if ($db->fieldExists('pic', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` DROP COLUMN `pic`");
        }
        if ($db->fieldExists('action', 'monev_followup')) {
            $db->query("ALTER TABLE `monev_followup` DROP COLUMN `action`");
        }

        // Rollback monev_finding
        if ($db->fieldExists('created_by', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` DROP FOREIGN KEY `fk_finding_creator`");
            $db->query("ALTER TABLE `monev_finding` DROP COLUMN `created_by`");
        }
        if ($db->fieldExists('status', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` DROP COLUMN `status`");
        }
        if ($db->fieldExists('due_date', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` DROP COLUMN `due_date`");
        }
        if ($db->fieldExists('responsible_party', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` DROP COLUMN `responsible_party`");
        }
        if ($db->fieldExists('severity', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` DROP COLUMN `severity`");
        }
        if ($db->fieldExists('category', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` DROP COLUMN `category`");
        }
        if ($db->fieldExists('title', 'monev_finding')) {
            $db->query("ALTER TABLE `monev_finding` DROP COLUMN `title`");
        }
    }
}

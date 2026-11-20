-- ============================================================
-- SQL SCRIPT UNTUK SERVER: MASTER ANGGARAN & HISTORI PERGESERAN
-- Database: sidp_new (atau nama database di server Anda)
-- ============================================================

-- 1. Menambahkan kolom pendukung pada tabel `master_anggaran`
ALTER TABLE `master_anggaran`
  ADD COLUMN `pagu_awal` DECIMAL(20,2) NULL AFTER `pagu_program`,
  ADD COLUMN `is_edited` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pagu_awal`,
  ADD COLUMN `jenis_perubahan` VARCHAR(50) NULL AFTER `is_edited`,
  ADD COLUMN `keterangan_perubahan` TEXT NULL AFTER `jenis_perubahan`;

-- Set nilai default pagu_awal sama dengan pagu_program saat ini untuk data yang sudah ada
UPDATE `master_anggaran` SET `pagu_awal` = `pagu_program` WHERE `pagu_awal` IS NULL;

-- 2. Membuat tabel log histori perubahan / pergeseran anggaran
CREATE TABLE IF NOT EXISTS `master_anggaran_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `master_anggaran_id` INT NOT NULL,
  `kd_satker` VARCHAR(50) NULL,
  `nama_satker` VARCHAR(255) NULL,
  `kd_program_str` VARCHAR(50) NULL,
  `nama_program` VARCHAR(255) NULL,
  `pagu_sebelum` DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  `pagu_sesudah` DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  `selisih` DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  `jenis_perubahan` VARCHAR(50) NOT NULL,
  `komentar` TEXT NULL,
  `user_id` INT NULL,
  `user_nama` VARCHAR(100) NULL,
  `tahun` INT NOT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_mah_satker` (`kd_satker`, `tahun`),
  INDEX `idx_mah_master_id` (`master_anggaran_id`),
  INDEX `idx_mah_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

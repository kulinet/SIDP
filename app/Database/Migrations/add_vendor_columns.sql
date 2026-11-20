-- ==============================================================================
-- Script SQL Penambahan Kolom Identitas Penyedia di Database Server
-- Database Target : sidp_new (atau nama database yang digunakan di server)
-- ==============================================================================

-- 1. Tabel LPSE Tender
ALTER TABLE `lpse_tender`
  ADD COLUMN `kd_penyedia` VARCHAR(100) NULL AFTER `nama_penyedia`,
  ADD COLUMN `npwp_penyedia` VARCHAR(100) NULL AFTER `kd_penyedia`,
  ADD COLUMN `alamat_penyedia` TEXT NULL AFTER `npwp_penyedia`,
  ADD COLUMN `bentuk_usaha` VARCHAR(100) NULL AFTER `alamat_penyedia`;

-- 2. Tabel LPSE Non-Tender
ALTER TABLE `lpse_nontender`
  ADD COLUMN `kd_penyedia` VARCHAR(100) NULL AFTER `nama_penyedia`,
  ADD COLUMN `npwp_penyedia` VARCHAR(100) NULL AFTER `kd_penyedia`,
  ADD COLUMN `alamat_penyedia` TEXT NULL AFTER `npwp_penyedia`,
  ADD COLUMN `bentuk_usaha` VARCHAR(100) NULL AFTER `alamat_penyedia`;

-- 3. Tabel LPSE Pencatatan Non-Tender
ALTER TABLE `lpse_pencatatan_nontender`
  ADD COLUMN `kd_penyedia` VARCHAR(100) NULL AFTER `nama_penyedia`,
  ADD COLUMN `npwp_penyedia` VARCHAR(100) NULL AFTER `kd_penyedia`;

-- 4. Tabel Cache Inaproc Vendor Sync
ALTER TABLE `inaproc_vendor_sync`
  ADD COLUMN `alamat_penyedia` TEXT NULL AFTER `npwp_penyedia`,
  ADD COLUMN `email` VARCHAR(255) NULL AFTER `alamat_penyedia`,
  ADD COLUMN `telepon` VARCHAR(100) NULL AFTER `email`,
  ADD COLUMN `nib` VARCHAR(100) NULL AFTER `telepon`;

-- Optional: Tambahkan index untuk mempercepat query pencarian / filter per penyedia
ALTER TABLE `lpse_tender` ADD INDEX `idx_tender_nama_penyedia` (`nama_penyedia`);
ALTER TABLE `lpse_nontender` ADD INDEX `idx_nontender_nama_penyedia` (`nama_penyedia`);
ALTER TABLE `lpse_pencatatan_nontender` ADD INDEX `idx_pct_nama_penyedia` (`nama_penyedia`);
ALTER TABLE `epurchasing` ADD INDEX `idx_epur_nama_penyedia` (`nama_penyedia`);

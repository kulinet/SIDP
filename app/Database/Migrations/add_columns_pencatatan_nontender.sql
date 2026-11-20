-- SQL Patch: Tambah kolom pada lpse_pencatatan_nontender
ALTER TABLE `lpse_pencatatan_nontender`
  ADD COLUMN `kategori_pengadaan` VARCHAR(100) NULL DEFAULT NULL AFTER `jenis_realisasi`,
  ADD COLUMN `mtd_pemilihan` VARCHAR(100) NULL DEFAULT NULL AFTER `kategori_pengadaan`,
  ADD COLUMN `sumber_dana` VARCHAR(100) NULL DEFAULT NULL AFTER `mtd_pemilihan`,
  ADD COLUMN `tgl_buat_paket` DATETIME NULL DEFAULT NULL AFTER `sumber_dana`,
  ADD COLUMN `tgl_selesai_paket` DATETIME NULL DEFAULT NULL AFTER `tgl_buat_paket`;

-- ALTER TABLE untuk menambahkan kolom BAP, BAST, Progres, dan Pembayaran
-- ke tabel lpse_nontender (kompatibel dengan MySQL 5.7+ & 8.x / MariaDB)

ALTER TABLE `lpse_nontender`
ADD COLUMN `tgl_bap` DATE NULL AFTER `tgl_selesai_pekerjaan`,
ADD COLUMN `no_bap` VARCHAR(100) NULL AFTER `tgl_bap`,
ADD COLUMN `progres_pekerjaan` DECIMAL(5,2) NULL AFTER `no_bap`,
ADD COLUMN `tgl_bast` DATE NULL AFTER `progres_pekerjaan`,
ADD COLUMN `no_bast` VARCHAR(100) NULL AFTER `tgl_bast`,
ADD COLUMN `besar_pembayaran` DECIMAL(15,2) NULL DEFAULT 0.00 AFTER `no_bast`;

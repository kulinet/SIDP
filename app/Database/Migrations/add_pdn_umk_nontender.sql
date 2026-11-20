-- Jalankan query ini di phpMyAdmin / Adminer untuk menambah kolom PDN dan UMK
-- ke tabel lpse_nontender (kompatibel dengan MySQL 8.x)

ALTER TABLE `lpse_nontender`
    ADD COLUMN `nilai_pdn_kontrak` DOUBLE NULL DEFAULT 0 AFTER `nilai_negosiasi`,
    ADD COLUMN `nilai_umk_kontrak` DOUBLE NULL DEFAULT 0 AFTER `nilai_pdn_kontrak`;

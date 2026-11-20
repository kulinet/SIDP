<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonevV2Tables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Table monev_assessment_templates
        $db->query("CREATE TABLE IF NOT EXISTS `monev_assessment_templates` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `kode_template` VARCHAR(50) NOT NULL UNIQUE,
            `nama_template` VARCHAR(255) NOT NULL,
            `versi` INT UNSIGNED NOT NULL DEFAULT 1,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `deskripsi` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 2. Table monev_assessment_aspects
        $db->query("CREATE TABLE IF NOT EXISTS `monev_assessment_aspects` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `template_id` INT UNSIGNED NOT NULL,
            `urutan` INT UNSIGNED NOT NULL DEFAULT 1,
            `kode_aspek` VARCHAR(50) NOT NULL,
            `nama_aspek` VARCHAR(255) NOT NULL,
            `bobot_persen` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `deskripsi` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_aspect_template` (`template_id`),
            CONSTRAINT `fk_aspect_template` FOREIGN KEY (`template_id`) REFERENCES `monev_assessment_templates` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 3. Table monev_assessment_indicators
        $db->query("CREATE TABLE IF NOT EXISTS `monev_assessment_indicators` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `aspect_id` INT UNSIGNED NOT NULL,
            `urutan` INT UNSIGNED NOT NULL DEFAULT 1,
            `kode_indikator` VARCHAR(50) NOT NULL,
            `nama_indikator` VARCHAR(255) NOT NULL,
            `deskripsi_kriteria` TEXT NULL,
            `panduan_nilai_1` TEXT NULL COMMENT 'Kriteria TIDAK_BAIK (Skor 1)',
            `panduan_nilai_2` TEXT NULL COMMENT 'Kriteria BAIK (Skor 2)',
            `panduan_nilai_3` TEXT NULL COMMENT 'Kriteria SANGAT_BAIK (Skor 3)',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_indicator_aspect` (`aspect_id`),
            CONSTRAINT `fk_indicator_aspect` FOREIGN KEY (`aspect_id`) REFERENCES `monev_assessment_aspects` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 4. Table monev_period (proyek_id is INT signed matching monev_proyek.id)
        $db->query("CREATE TABLE IF NOT EXISTS `monev_period` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `proyek_id` INT NOT NULL,
            `nomor_monev` VARCHAR(100) NOT NULL UNIQUE,
            `jenis_monev` ENUM('INITIAL', 'PERIODIC', 'SPECIAL', 'FINAL') NOT NULL DEFAULT 'PERIODIC',
            `periode_ke` INT UNSIGNED NOT NULL DEFAULT 1,
            `tgl_rencana_monev` DATE NOT NULL,
            `tgl_realisasi_monev` DATE NULL,
            `assessor_id` INT UNSIGNED NOT NULL,
            `supervisor_id` INT UNSIGNED NULL,
            `status` ENUM('DRAFT', 'SUBMITTED', 'IN_REVIEW', 'RETURNED', 'APPROVED', 'FINALIZED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
            `skor_kinerja_total` DECIMAL(5,2) NULL,
            `kategori_kinerja` ENUM('SANGAT_BAIK', 'BAIK', 'CUKUP', 'BURUK', 'KRITIS') NULL,
            `ringkasan_hasil` TEXT NULL,
            `kesimpulan_rekomendasi` TEXT NULL,
            `final_document_path` VARCHAR(255) NULL,
            `is_locked` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_period_proyek` (`proyek_id`),
            INDEX `idx_period_status` (`status`),
            INDEX `idx_period_tgl` (`tgl_rencana_monev`),
            INDEX `idx_period_assessor` (`assessor_id`),
            CONSTRAINT `fk_period_proyek` FOREIGN KEY (`proyek_id`) REFERENCES `monev_proyek` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_period_assessor` FOREIGN KEY (`assessor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_period_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 5. Table monev_visit
        $db->query("CREATE TABLE IF NOT EXISTS `monev_visit` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `monev_period_id` INT UNSIGNED NOT NULL,
            `tgl_kunjungan` DATE NOT NULL,
            `waktu_mulai` TIME NULL,
            `waktu_selesai` TIME NULL,
            `lokasi_spesifik` VARCHAR(255) NULL,
            `gps_lat` DECIMAL(10,7) NULL,
            `gps_lng` DECIMAL(10,7) NULL,
            `cuaca` VARCHAR(50) NULL,
            `pihak_hadir_ppk` VARCHAR(255) NULL,
            `pihak_hadir_penyedia` VARCHAR(255) NULL,
            `pihak_hadir_konsultan` VARCHAR(255) NULL,
            `agenda_pemeriksaan` TEXT NULL,
            `catatan_lapangan` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_visit_period` (`monev_period_id`),
            CONSTRAINT `fk_visit_period` FOREIGN KEY (`monev_period_id`) REFERENCES `monev_period` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 6. Table monev_assessment
        $db->query("CREATE TABLE IF NOT EXISTS `monev_assessment` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `monev_period_id` INT UNSIGNED NOT NULL UNIQUE,
            `template_id` INT UNSIGNED NULL,
            `template_version_snapshot` INT UNSIGNED NOT NULL DEFAULT 1,
            `skor_total` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `kategori_hasil` ENUM('SANGAT_BAIK', 'BAIK', 'CUKUP', 'BURUK', 'KRITIS') NOT NULL DEFAULT 'CUKUP',
            `catatan_umum` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_assessment_period` FOREIGN KEY (`monev_period_id`) REFERENCES `monev_period` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_assessment_template` FOREIGN KEY (`template_id`) REFERENCES `monev_assessment_templates` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 7. Table monev_assessment_item (Snapshot Preservation)
        $db->query("CREATE TABLE IF NOT EXISTS `monev_assessment_item` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `assessment_id` INT UNSIGNED NOT NULL,
            `indicator_id` INT UNSIGNED NULL,
            `aspek_kode_snapshot` VARCHAR(50) NOT NULL,
            `aspek_nama_snapshot` VARCHAR(255) NOT NULL,
            `indikator_kode_snapshot` VARCHAR(50) NOT NULL,
            `indikator_nama_snapshot` VARCHAR(255) NOT NULL,
            `bobot_persen_snapshot` DECIMAL(5,2) NOT NULL,
            `rating` ENUM('TIDAK_BAIK', 'BAIK', 'SANGAT_BAIK') NOT NULL,
            `score` TINYINT UNSIGNED NOT NULL COMMENT '1: TIDAK_BAIK, 2: BAIK, 3: SANGAT_BAIK',
            `catatan_penilai` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_item_assessment` (`assessment_id`),
            CONSTRAINT `fk_item_assessment` FOREIGN KEY (`assessment_id`) REFERENCES `monev_assessment` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_item_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `monev_assessment_indicators` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 8. Table monev_finding
        $db->query("CREATE TABLE IF NOT EXISTS `monev_finding` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `monev_period_id` INT UNSIGNED NOT NULL,
            `kode_temuan` VARCHAR(50) NOT NULL,
            `kategori_temuan` ENUM('TEKNIS_KONSTRUKSI', 'KETERLAMBATAN_WAKTU', 'K3_LINGKUNGAN', 'ADMINISTRATIF', 'LAINNYA') NOT NULL,
            `tingkat_keparahan` ENUM('RENDAH', 'SEDANG', 'TINGGI', 'KRITIS') NOT NULL DEFAULT 'SEDANG',
            `deskripsi_temuan` TEXT NOT NULL,
            `lokasi_spesifik` VARCHAR(255) NULL,
            `rekomendasi_tindakan` TEXT NOT NULL,
            `batas_waktu_penyelesaian` DATE NOT NULL,
            `status_temuan` ENUM('OPEN', 'IN_PROGRESS', 'PENDING_VERIFICATION', 'RESOLVED', 'ESCALATED') NOT NULL DEFAULT 'OPEN',
            `resolved_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_finding_period` (`monev_period_id`),
            INDEX `idx_finding_status` (`status_temuan`),
            CONSTRAINT `fk_finding_period` FOREIGN KEY (`monev_period_id`) REFERENCES `monev_period` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 9. Table monev_followup
        $db->query("CREATE TABLE IF NOT EXISTS `monev_followup` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `finding_id` INT UNSIGNED NOT NULL,
            `action_by_user_id` INT UNSIGNED NOT NULL,
            `tgl_tindak_lanjut` DATE NOT NULL,
            `deskripsi_perbaikan` TEXT NOT NULL,
            `catatan_verifikasi_assessor` TEXT NULL,
            `status_verifikasi` ENUM('DIAJUKAN', 'DITERIMA', 'DITOLAK') NOT NULL DEFAULT 'DIAJUKAN',
            `verified_by_user_id` INT UNSIGNED NULL,
            `verified_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_followup_finding` (`finding_id`),
            CONSTRAINT `fk_followup_finding` FOREIGN KEY (`finding_id`) REFERENCES `monev_finding` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_followup_actor` FOREIGN KEY (`action_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_followup_verifier` FOREIGN KEY (`verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 10. Table monev_approval
        $db->query("CREATE TABLE IF NOT EXISTS `monev_approval` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `monev_period_id` INT UNSIGNED NOT NULL,
            `stage` ENUM('SUBMISSION', 'REVIEW', 'APPROVAL', 'FINALIZATION') NOT NULL,
            `action` ENUM('SUBMIT', 'RETURN', 'APPROVE', 'FINALIZE', 'CANCEL') NOT NULL,
            `actor_id` INT UNSIGNED NOT NULL,
            `catatan` TEXT NULL,
            `signature_hash` VARCHAR(64) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_approval_period` (`monev_period_id`),
            CONSTRAINT `fk_approval_period` FOREIGN KEY (`monev_period_id`) REFERENCES `monev_period` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_approval_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 11. Table monev_evidence
        $db->query("CREATE TABLE IF NOT EXISTS `monev_evidence` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `entity_type` ENUM('MONEV_VISIT', 'MONEV_ASSESSMENT', 'MONEV_FINDING', 'MONEV_FOLLOWUP') NOT NULL,
            `entity_id` INT UNSIGNED NOT NULL,
            `jenis_eviden` ENUM('FOTO_LAPANGAN', 'DOKUMEN_UJI_LAB', 'BERITA_ACARA', 'SURAT_TEGURAN', 'BUKTI_PERBAIKAN', 'LAINNYA') NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_name` VARCHAR(255) NOT NULL,
            `file_size` INT UNSIGNED NULL,
            `mime_type` VARCHAR(100) NULL,
            `file_hash` VARCHAR(64) NULL,
            `gps_lat` DECIMAL(10,7) NULL,
            `gps_lng` DECIMAL(10,7) NULL,
            `tgl_pengambilan` DATETIME NULL,
            `keterangan` TEXT NULL,
            `uploaded_by` INT UNSIGNED NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_evidence_target` (`entity_type`, `entity_id`),
            CONSTRAINT `fk_evidence_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // 12. Table monev_audit_log
        $db->query("CREATE TABLE IF NOT EXISTS `monev_audit_log` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `monev_period_id` INT UNSIGNED NULL,
            `action` VARCHAR(100) NOT NULL,
            `actor_id` INT UNSIGNED NOT NULL,
            `actor_role` VARCHAR(50) NOT NULL,
            `old_status` VARCHAR(50) NULL,
            `new_status` VARCHAR(50) NULL,
            `changes_payload` JSON NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` VARCHAR(255) NULL,
            `notes` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_audit_period` (`monev_period_id`),
            INDEX `idx_audit_actor` (`actor_id`),
            CONSTRAINT `fk_audit_period` FOREIGN KEY (`monev_period_id`) REFERENCES `monev_period` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // ─────────────────────────────────────────────────────────────────
        // INITIAL SEEDING: Default Template (6 Aspek, 12 Indikator)
        // ─────────────────────────────────────────────────────────────────
        $existingTmpl = $db->table('monev_assessment_templates')
            ->where('kode_template', 'TMPL-KONSTRUKSI-V1')
            ->get()
            ->getRowArray();

        if (!$existingTmpl) {
            $db->table('monev_assessment_templates')->insert([
                'kode_template' => 'TMPL-KONSTRUKSI-V1',
                'nama_template' => 'Template Standar Evaluasi Pengawasan Konstruksi',
                'versi'         => 1,
                'is_active'     => 1,
                'deskripsi'     => 'Template penilaian standar Monev V2 terdiri dari 6 aspek dan 12 indikator teknis.',
                'created_at'    => date('Y-m-d H:i:s')
            ]);
            $templateId = $db->insertID();

            // 6 Aspek & 12 Indikator
            $aspectsData = [
                [
                    'kode_aspek'   => 'ASP-01',
                    'nama_aspek'   => 'Administrasi',
                    'bobot_persen' => 15.00,
                    'urutan'       => 1,
                    'deskripsi'    => 'Kepatuhan legalitas kontrak, perizinan, dan tata kelola laporan berkala.',
                    'indicators'   => [
                        [
                            'kode_indikator'     => 'ADM-01',
                            'nama_indikator'     => 'Kelengkapan Dokumen Kontrak, SPMK, & Jaminan Pelaksanaan',
                            'urutan'             => 1,
                            'deskripsi_kriteria' => 'Ketersediaan dan keabsahan berkas kontrak, SPMK, jaminan pelaksanaan, dan asuransi konstruksi.',
                            'panduan_nilai_1'    => 'Dokumen kontrak atau jaminan pelaksanaan belum lengkap / belum diserahkan.',
                            'panduan_nilai_2'    => 'Dokumen kontrak lengkap tetapi lampiran teknis/asuransi belum lengkap.',
                            'panduan_nilai_3'    => 'Dokumen kontrak, SPMK, jaminan pelaksanaan, dan asuransi 100% lengkap & sah.'
                        ],
                        [
                            'kode_indikator'     => 'ADM-02',
                            'nama_indikator'     => 'Kepatuhan Pelaporan Berkala & Administrasi Lapangan',
                            'urutan'             => 2,
                            'deskripsi_kriteria' => 'Ketertiban penyusunan dan pengesahan laporan harian, mingguan, bulanan, dan buku tamu proyek.',
                            'panduan_nilai_1'    => 'Laporan berkala tidak dibuat atau menunggak lebih dari 2 minggu.',
                            'panduan_nilai_2'    => 'Laporan berkala dibuat tetapi terlambat penandatanganan oleh pengawas.',
                            'panduan_nilai_3'    => 'Laporan berkala harian/mingguan tersusun tertib, tepat waktu, dan disahkan lengkap.'
                        ]
                    ]
                ],
                [
                    'kode_aspek'   => 'ASP-02',
                    'nama_aspek'   => 'Jadwal dan Waktu',
                    'bobot_persen' => 25.00,
                    'urutan'       => 2,
                    'deskripsi'    => 'Kepatuhan capaian progres fisik terhadap Kurva S dan milestone kontrak.',
                    'indicators'   => [
                        [
                            'kode_indikator'     => 'JAD-01',
                            'nama_indikator'     => 'Deviasi Fisik Kumulatif terhadap Kurva S Baseline',
                            'urutan'             => 1,
                            'deskripsi_kriteria' => 'Besaran selisih antara realisasi fisik tervalidasi dengan rencana target Kurva S.',
                            'panduan_nilai_1'    => 'Deviasi minus <= -10% (Kritis / Terlambat berat / Potensi SCM).',
                            'panduan_nilai_2'    => 'Deviasi minus antara -5% s/d -10% (Perhatian / Deviasi sedang).',
                            'panduan_nilai_3'    => 'Deviasi di atas -5% s/d positif (Sesuai rencana atau lebih cepat).'
                        ],
                        [
                            'kode_indikator'     => 'JAD-02',
                            'nama_indikator'     => 'Kepatuhan Milestone Kritis dan Jalur Kritis (Critical Path)',
                            'urutan'             => 2,
                            'deskripsi_kriteria' => 'Pencapaian tahapan kunci struktur/sub-pekerjaan kritis sesuai jadwal kerja.',
                            'panduan_nilai_1'    => 'Pekerjaan jalur kritis terhenti atau tertunda signifikan tanpa rencana percepatan.',
                            'panduan_nilai_2'    => 'Pekerjaan kritis terlambat minor namun ada kompensasi penambahan sumber daya.',
                            'panduan_nilai_3'    => 'Seluruh milestone kritis terlaksana tepat waktu sesuai urutan network planning.'
                        ]
                    ]
                ],
                [
                    'kode_aspek'   => 'ASP-03',
                    'nama_aspek'   => 'Material',
                    'bobot_persen' => 20.00,
                    'urutan'       => 3,
                    'deskripsi'    => 'Standar mutu bahan, spesifikasi teknis, sertifikasi SNI, dan persetujuan material.',
                    'indicators'   => [
                        [
                            'kode_indikator'     => 'MAT-01',
                            'nama_indikator'     => 'Kesesuaian Spesifikasi Teknis Material vs RKS',
                            'urutan'             => 1,
                            'deskripsi_kriteria' => 'Kualitas fisik dan merek bahan yang digunakan di lapangan dibanding syarat teknis.',
                            'panduan_nilai_1'    => 'Ditemukan material tidak sesuai RKS atau di bawah standar spesifikasi.',
                            'panduan_nilai_2'    => 'Material sesuai RKS namun terdapat toleransi minor pada beberapa item pendukung.',
                            'panduan_nilai_3'    => 'Seluruh material 100% sesuai RKS, berstandar SNI, dan memiliki izin persetujuan pengawas.'
                        ],
                        [
                            'kode_indikator'     => 'MAT-02',
                            'nama_indikator'     => 'Ketersediaan Sertifikat Uji Lab & Approval Material',
                            'urutan'             => 2,
                            'deskripsi_kriteria' => 'Kelengkapan uji laboratorium independen (uji kuat tekan, tarik besi, gradasi, dll).',
                            'panduan_nilai_1'    => 'Tidak ada bukti uji laboratorium untuk material struktur utama.',
                            'panduan_nilai_2'    => 'Hasil uji lab sedang dalam proses / baru sebagian uji terpenuhi.',
                            'panduan_nilai_3'    => 'Sertifikat hasil uji laboratorium lengkap, lulus standar kekuatan, dan terlampir resmi.'
                        ]
                    ]
                ],
                [
                    'kode_aspek'   => 'ASP-04',
                    'nama_aspek'   => 'Tenaga Kerja dan Peralatan',
                    'bobot_persen' => 15.00,
                    'urutan'       => 4,
                    'deskripsi'    => 'Kecukupan personil teknis/tenaga ahli bersertifikat dan kesiapan alat kerja.',
                    'indicators'   => [
                        [
                            'kode_indikator'     => 'TKN-01',
                            'nama_indikator'     => 'Kecukupan dan Kualifikasi Personil Inti / Tenaga Ahli',
                            'urutan'             => 1,
                            'deskripsi_kriteria' => 'Kehadiran Site Manager, Pelaksana, dan Petugas K3 bersertifikat di lokasi proyek.',
                            'panduan_nilai_1'    => 'Personil inti/ahli tidak hadir di lapangan tanpa penanggung jawab pengganti.',
                            'panduan_nilai_2'    => 'Personil hadir paruh waktu atau kualifikasi teknis belum lengkap diverifikasi.',
                            'panduan_nilai_3'    => 'Personil inti lengkap berada di lapangan dan memiliki sertifikat keahlian valid.'
                        ],
                        [
                            'kode_indikator'     => 'TKN-02',
                            'nama_indikator'     => 'Kesiapan, Kelaikan Operasi, dan Kuantitas Peralatan Konstruksi',
                            'urutan'             => 2,
                            'deskripsi_kriteria' => 'Kesesuaian jumlah dan kondisi fisik peralatan utama yang dimobilisasi ke proyek.',
                            'panduan_nilai_1'    => 'Peralatan utama kurang dari kebutuhan atau sering rusak menghambat progres.',
                            'panduan_nilai_2'    => 'Peralatan cukup namun utilisasi jam operasi belum optimal.',
                            'panduan_nilai_3'    => 'Peralatan utama lengkap, dalam kondisi prima, laik operasi, dan produktivitas tinggi.'
                        ]
                    ]
                ],
                [
                    'kode_aspek'   => 'ASP-05',
                    'nama_aspek'   => 'Keselamatan dan Kesehatan Kerja (K3)',
                    'bobot_persen' => 15.00,
                    'urutan'       => 5,
                    'deskripsi'    => 'Penerapan Sistem Manajemen K3 Konstruksi, APD, dan pencegahan kecelakaan.',
                    'indicators'   => [
                        [
                            'kode_indikator'     => 'K3-01',
                            'nama_indikator'     => 'Penerapan RKK & Kepatuhan Penggunaan APD Lengkap',
                            'urutan'             => 1,
                            'deskripsi_kriteria' => 'Kepatuhan seluruh tenaga kerja memakai helm, rompi, sepatu safety, dan sabuk pengaman.',
                            'panduan_nilai_1'    => 'Banyak pekerja tidak memakai APD dasar; tidak ada safety briefing.',
                            'panduan_nilai_2'    => 'Sebagian besar memakai APD tetapi masih ada kelalaian minor saat bekerja di ketinggian.',
                            'panduan_nilai_3'    => '100% pekerja tertib memakai APD lengkap, ada safety briefing rutin, dan nihil kecelakaan.'
                        ],
                        [
                            'kode_indikator'     => 'K3-02',
                            'nama_indikator'     => 'Pengamanan Zona Bahaya, Jalur Evakuasi, & Kotak P3K',
                            'urutan'             => 2,
                            'deskripsi_kriteria' => 'Pemasangan rambu peringatan K3, barikade pengaman galian/ketinggian, dan kesiapan P3K.',
                            'panduan_nilai_1'    => 'Tidak ada rambu peringatan, galian curam terbuka tanpa barikade, kotak P3K kosong.',
                            'panduan_nilai_2'    => 'Rambu terpasang terbatas dan pengamanan area bahaya belum menyeluruh.',
                            'panduan_nilai_3'    => 'Rambu K3 lengkap terbaca jelas, barikade pengaman kokoh, dan perlengkapan P3K siap pakai.'
                        ]
                    ]
                ],
                [
                    'kode_aspek'   => 'ASP-06',
                    'nama_aspek'   => 'Lingkungan',
                    'bobot_persen' => 10.00,
                    'urutan'       => 6,
                    'deskripsi'    => 'Pengelolaan limbah proyek, ceceran material, dan pengendalian gangguan lingkungan.',
                    'indicators'   => [
                        [
                            'kode_indikator'     => 'ENV-01',
                            'nama_indikator'     => 'Pengelolaan Sampah, Limbah Konstruksi, & Drainase Proyek',
                            'urutan'             => 1,
                            'deskripsi_kriteria' => 'Ketertiban pembuangan puing, ceceran adukan, dan kelancaran saluran air sementara.',
                            'panduan_nilai_1'    => 'Puing berserakan mencemari lingkungan atau menyumbat saluran drainase warga.',
                            'panduan_nilai_2'    => 'Limbah dikumpulkan tetapi penumpukan belum diangkut tepat waktu.',
                            'panduan_nilai_3'    => 'Area kerja bersih tertata rapi, limbah dikelola teratur, dan drainase lancar bebas genangan.'
                        ],
                        [
                            'kode_indikator'     => 'ENV-02',
                            'nama_indikator'     => 'Pengendalian Dampak Lingkungan Sekitar (Debu, Bising, & Akses)',
                            'urutan'             => 2,
                            'deskripsi_kriteria' => 'Upaya penyiraman debu, peredam kebisingan jam istirahat, dan kenyamanan masyarakat.',
                            'panduan_nilai_1'    => 'Banyak keluhan masyarakat terkait polusi debu tebal, ceceran tanah di jalan, atau bising malam.',
                            'panduan_nilai_2'    => 'Ada upaya penyiraman dan pembersihan roda kendaraan tetapi belum konsisten.',
                            'panduan_nilai_3'    => 'Pengendalian debu/kebisingan sangat baik, jalan umum bersih, dan komunikasi warga harmonis.'
                        ]
                    ]
                ]
            ];

            foreach ($aspectsData as $asp) {
                $db->table('monev_assessment_aspects')->insert([
                    'template_id'  => $templateId,
                    'urutan'       => $asp['urutan'],
                    'kode_aspek'   => $asp['kode_aspek'],
                    'nama_aspek'   => $asp['nama_aspek'],
                    'bobot_persen' => $asp['bobot_persen'],
                    'deskripsi'    => $asp['deskripsi'],
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
                $aspectId = $db->insertID();

                foreach ($asp['indicators'] as $ind) {
                    $db->table('monev_assessment_indicators')->insert([
                        'aspect_id'          => $aspectId,
                        'urutan'             => $ind['urutan'],
                        'kode_indikator'     => $ind['kode_indikator'],
                        'nama_indikator'     => $ind['nama_indikator'],
                        'deskripsi_kriteria' => $ind['deskripsi_kriteria'],
                        'panduan_nilai_1'    => $ind['panduan_nilai_1'],
                        'panduan_nilai_2'    => $ind['panduan_nilai_2'],
                        'panduan_nilai_3'    => $ind['panduan_nilai_3'],
                        'created_at'         => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");

        $tables = [
            'monev_audit_log',
            'monev_evidence',
            'monev_approval',
            'monev_followup',
            'monev_finding',
            'monev_assessment_item',
            'monev_assessment',
            'monev_visit',
            'monev_period',
            'monev_assessment_indicators',
            'monev_assessment_aspects',
            'monev_assessment_templates'
        ];

        foreach ($tables as $tbl) {
            $db->query("DROP TABLE IF EXISTS `{$tbl}`");
        }

        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }
}

<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\MonevIntegrationService;
use App\Services\MonevWorkflowService;
use App\Models\MonevProyekModel;
use App\Models\MonevJadwalModel;
use App\Models\MonevHarianModel;
use App\Models\MonevDokumenModel;
use App\Models\MonevEvidenceModel;
use App\Models\MonevPeriodModel;
use App\Database\Seeds\MonevAssessmentTemplateSeeder;

class MonevIntegrationTest extends CIUnitTestCase
{
    protected MonevIntegrationService $integrationService;
    protected MonevWorkflowService $workflowService;
    protected MonevProyekModel $proyekModel;
    protected MonevJadwalModel $jadwalModel;
    protected MonevHarianModel $harianModel;
    protected MonevDokumenModel $dokumenModel;
    protected MonevEvidenceModel $evidenceModel;
    protected MonevPeriodModel $periodModel;

    protected int $testProyekId;
    protected int $testJadwalId;
    protected int $ppkUserId = 3;
    protected int $operatorUserId = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationService = new MonevIntegrationService();
        $this->workflowService    = new MonevWorkflowService();
        $this->proyekModel         = new MonevProyekModel();
        $this->jadwalModel         = new MonevJadwalModel();
        $this->harianModel         = new MonevHarianModel();
        $this->dokumenModel        = new MonevDokumenModel();
        $this->evidenceModel       = new MonevEvidenceModel();
        $this->periodModel         = new MonevPeriodModel();

        // Seed master template
        $seeder = new MonevAssessmentTemplateSeeder(config('Database'));
        $seeder->run();

        // Setup dedicated test project
        $this->setupTestProject();
    }

    protected function setupTestProject(): void
    {
        $db = \Config\Database::connect();

        // Create or get PPK user
        $user = $db->table('users')->where('id', $this->ppkUserId)->get()->getRowArray();
        if (!$user) {
            $db->table('users')->insert([
                'id'           => $this->ppkUserId,
                'username'     => 'ppk_test',
                'email'        => 'ppk@lombokbaratkab.go.id',
                'nama_lengkap' => 'Ir. H. Syamsul Bahri, ST., MT.',
                'nomor_hp'     => '081234567890',
                'role'         => 'ppk',
                'password'     => password_hash('password123', PASSWORD_BCRYPT),
            ]);
        }

        // Clean up previous test project if existing with this specific test number
        $existing = $this->proyekModel->where('no_kontrak', '600/TEST-INTEG/CK/2026')->first();
        if ($existing) {
            $this->testProyekId = (int) $existing['id'];
            $this->harianModel->where('proyek_id', $this->testProyekId)->delete();
            $this->jadwalModel->where('proyek_id', $this->testProyekId)->delete();
            $this->dokumenModel->where('proyek_id', $this->testProyekId)->delete();
        } else {
            $this->proyekModel->insert([
                'user_id'            => $this->ppkUserId,
                'kd_rup'             => 'RUP-TEST-998877',
                'nama_proyek'        => 'Pembangunan Jembatan Terintegrasi Monev V2',
                'lokasi'             => 'Kecamatan Gerung, Lombok Barat',
                'no_kontrak'         => '600/TEST-INTEG/CK/2026',
                'nilai_kontrak'      => 2500000000.00,
                'tgl_mulai'          => date('Y-m-d', strtotime('-30 days')),
                'tgl_selesai'        => date('Y-m-d', strtotime('+60 days')),
                'nama_penyedia'      => 'PT. Konstruksi Megah Mandiri',
                'konsultan_pengawas' => 'CV. Reka Desain Konsultan',
                'direksi_teknis'     => 'Dinas PUTR Bidang Bina Marga',
                'pphp'               => 'Tim PPHP Bina Marga',
                'ppk'                => 'Ir. H. Syamsul Bahri, ST., MT.',
                'sumber_dana'        => 'DAK Fisik 2026',
                'status'             => 'Berjalan',
                'file_kontrak'       => 'uploads/kontrak/kontrak_600_test.pdf',
                'dokumen_mc'         => 'uploads/kontrak/mc0_jembatan.pdf',
            ]);
            $this->testProyekId = (int) $this->proyekModel->getInsertID();
        }

        // Insert Jadwal Kurva S (bobot 100%, 13 minggu)
        // Target kumulatif: M1: 5%, M2: 12%, M3: 20%, M4: 30%, M5: 45%, M6: 60%, M7: 75%, M8: 85%, M9: 92%, M10: 96%, M11: 98%, M12: 100%, M13: 100%
        $targets = [5.0, 12.0, 20.0, 30.0, 45.0, 60.0, 75.0, 85.0, 92.0, 96.0, 98.0, 100.0, 100.0];
        $this->jadwalModel->insert([
            'proyek_id'       => $this->testProyekId,
            'item_pekerjaan'  => 'Pekerjaan Struktur Utama Jembatan',
            'bobot'           => 100.00,
            'target_mingguan' => json_encode($targets),
        ]);
        $this->testJadwalId = (int) $this->jadwalModel->getInsertID();

        // Insert Documents into monev_dokumen
        $this->dokumenModel->insert([
            'proyek_id'    => $this->testProyekId,
            'nama_dokumen' => 'Time Schedule & Kurva S Disetujui',
            'kategori'     => 'Jadwal',
            'file_path'    => 'uploads/dokumen/time_schedule_approved.pdf',
        ]);
        $this->dokumenModel->insert([
            'proyek_id'    => $this->testProyekId,
            'nama_dokumen' => 'Shop Drawing Abutment 1 & 2',
            'kategori'     => 'Gambar Teknis',
            'file_path'    => 'uploads/dokumen/shop_drawing_abutment.dwg',
        ]);
    }

    /**
     * 1. Verifikasi integrasi data kontrak:
     * TIDAK ada duplicate input untuk nama_proyek, RUP, PPK, penyedia, nilai_kontrak, SPMK, dsb.
     */
    public function testContractDataIntegrationWithoutDuplicateInput(): void
    {
        $context = $this->integrationService->getProjectContext($this->testProyekId);

        $this->assertIsArray($context);
        $this->assertArrayHasKey('contract_data', $context);
        
        $contract = $context['contract_data'];
        $this->assertSame((int) $this->testProyekId, (int) $contract['proyek_id']);
        $this->assertSame('Pembangunan Jembatan Terintegrasi Monev V2', $contract['nama_proyek']);
        $this->assertSame('600/TEST-INTEG/CK/2026', $contract['no_kontrak']);
        $this->assertEquals(2500000000.00, $contract['nilai_kontrak']);
        $this->assertSame('PT. Konstruksi Megah Mandiri', $contract['penyedia']);
        $this->assertSame('CV. Reka Desain Konsultan', $contract['konsultan_pengawas']);
        $this->assertSame('Dinas PUTR Bidang Bina Marga', $contract['direksi_teknis']);
        $this->assertSame('Tim PPHP Bina Marga', $contract['pphp']);
        $this->assertSame('RUP-TEST-998877', $contract['kd_rup']);

        // PPK structure verified from users table
        $this->assertIsArray($contract['ppk']);
        $this->assertSame($this->ppkUserId, (int) $contract['ppk']['user_id']);
        $this->assertNotEmpty($contract['ppk']['nama_lengkap']);
        $this->assertNotEmpty($contract['ppk']['email']);
    }

    /**
     * 2. RULE: Hanya data monev_harian yang verified (is_verified = 1) yang boleh menjadi official progress.
     * Log yang is_verified = 0 DILARANG dihitung sebagai official progress.
     */
    public function testOnlyVerifiedDailyLogsCountTowardsOfficialProgress(): void
    {
        $asOfDate = date('Y-m-d');

        // Log 1: VERIFIED, tanggal 10 hari lalu, progres 25%
        $this->harianModel->insert([
            'proyek_id'      => $this->testProyekId,
            'item_id'        => $this->testJadwalId,
            'tanggal'        => date('Y-m-d', strtotime('-10 days')),
            'volume_selesai' => 25.0,
            'satuan'         => '%',
            'lokasi'         => 'Abutment 1',
            'progres_persen' => 25.00,
            'is_verified'    => 1,
            'verified_at'    => date('Y-m-d H:i:s', strtotime('-9 days')),
            'foto_awal'      => 'uploads/harian/foto_awal_v1.jpg',
            'foto_proses'    => 'uploads/harian/foto_proses_v1.jpg',
            'foto_hasil'     => 'uploads/harian/foto_hasil_v1.jpg',
            'keterangan'     => 'Pengecoran abutment tahap 1 verified oleh konsultan',
        ]);

        // Log 2: BELUM DIVERIFIKASI (is_verified = 0), tanggal kemarin, klaim penyedia 65%
        $this->harianModel->insert([
            'proyek_id'      => $this->testProyekId,
            'item_id'        => $this->testJadwalId,
            'tanggal'        => date('Y-m-d', strtotime('-1 days')),
            'volume_selesai' => 65.0,
            'satuan'         => '%',
            'lokasi'         => 'Abutment 1',
            'progres_persen' => 65.00,
            'is_verified'    => 0, // Unverified!
            'foto_awal'      => 'uploads/harian/foto_awal_unverified.jpg',
            'keterangan'     => 'Klaim sepihak penyedia belum disetujui pengawas',
        ]);

        $context = $this->integrationService->getProjectContext($this->testProyekId, $asOfDate);
        $progress = $context['progress_metrics'];

        // Official progress MUST be 25%, NOT 65%
        $this->assertEquals(25.00, $progress['progress_aktual_verified'], 'Official progress must reflect ONLY verified logs!');
        $this->assertSame(1, $progress['verified_logs_count']);
        $this->assertSame(1, $progress['unverified_logs_count']);

        // S-curve actual must also strictly be 25%
        $this->assertEquals(25.00, $progress['scurve']['current_actual']);

        // Now verify Log 2 and check that official progress updates to 65%
        $db = \Config\Database::connect();
        $db->table('monev_harian')->where('progres_persen', 65.00)->update([
            'is_verified' => 1,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        $updatedContext = $this->integrationService->getProjectContext($this->testProyekId, $asOfDate);
        $this->assertEquals(65.00, $updatedContext['progress_metrics']['progress_aktual_verified']);
        $this->assertSame(2, $updatedContext['progress_metrics']['verified_logs_count']);
        $this->assertSame(0, $updatedContext['progress_metrics']['unverified_logs_count']);
    }

    /**
     * 3. Verifikasi kalkulasi timeline metrics:
     * umur kontrak (total_days, elapsed_days) dan sisa waktu kontrak (remaining_days, is_overdue).
     */
    public function testTimelineMetricsCalculation(): void
    {
        $context = $this->integrationService->getProjectContext($this->testProyekId);
        $timeline = $context['timeline_metrics'];

        $this->assertArrayHasKey('total_days', $timeline);
        $this->assertArrayHasKey('elapsed_days', $timeline);
        $this->assertArrayHasKey('remaining_days', $timeline);
        $this->assertArrayHasKey('is_overdue', $timeline);
        $this->assertArrayHasKey('time_pct', $timeline);

        // Durasi 91 hari (-30 s/d +60)
        $this->assertGreaterThanOrEqual(90, $timeline['total_days']);
        $this->assertGreaterThanOrEqual(29, $timeline['elapsed_days']);
        $this->assertGreaterThan(0, $timeline['remaining_days']);
        $this->assertFalse($timeline['is_overdue']);
        $this->assertGreaterThan(0, $timeline['time_pct']);
        $this->assertLessThanOrEqual(100, $timeline['time_pct']);
    }

    /**
     * 4. Verifikasi deviasi dan penentuan status proyek.
     */
    public function testDeviationAndProjectStatus(): void
    {
        $context = $this->integrationService->getProjectContext($this->testProyekId);
        $progress = $context['progress_metrics'];

        $this->assertArrayHasKey('progress_rencana', $progress);
        $this->assertArrayHasKey('progress_aktual_verified', $progress);
        $this->assertArrayHasKey('deviasi', $progress);
        $this->assertArrayHasKey('status_proyek', $progress);

        $expectedDeviasi = round($progress['progress_aktual_verified'] - $progress['progress_rencana'], 2);
        $this->assertEquals($expectedDeviasi, $progress['deviasi']);
        $this->assertNotEmpty($progress['status_proyek']);
    }

    /**
     * 5. Contextual & System Insight untuk Indikator 3 & Indikator 4:
     * - Jangan otomatis menentukan rating resmi (is_rating_automatic = false)
     * - Operator tetap yang memilih rating
     * - Menampilkan system insight / recommendation & disclaimer
     */
    public function testSystemInsightsForIndicators3And4(): void
    {
        $context = $this->integrationService->getProjectContext($this->testProyekId);
        $insights = $context['system_insights'];

        $this->assertArrayHasKey('indicator_3_time_schedule', $insights);
        $this->assertArrayHasKey('indicator_4_kurva_s', $insights);

        // Indikator 3: Jangka Waktu Kontrak
        $ind3 = $insights['indicator_3_time_schedule'];
        $this->assertSame('IND-03', $ind3['indicator_code']);
        $this->assertNotEmpty($ind3['system_insight']);
        $this->assertContains($ind3['suggested_rating'], ['TIDAK_BAIK', 'BAIK', 'SANGAT_BAIK']);
        $this->assertContains($ind3['suggested_score'], [1, 2, 3]);
        $this->assertFalse($ind3['is_rating_automatic'], 'Rating resmi DILARANG otomatis ditentukan oleh sistem!');
        $this->assertStringContainsString('Assessor/Operator', $ind3['disclaimer']);

        // Indikator 4: Kurva S
        $ind4 = $insights['indicator_4_kurva_s'];
        $this->assertSame('IND-04', $ind4['indicator_code']);
        $this->assertNotEmpty($ind4['system_insight']);
        $this->assertContains($ind4['suggested_rating'], ['TIDAK_BAIK', 'BAIK', 'SANGAT_BAIK']);
        $this->assertContains($ind4['suggested_score'], [1, 2, 3]);
        $this->assertFalse($ind4['is_rating_automatic'], 'Rating resmi DILARANG otomatis ditentukan oleh sistem!');
        $this->assertStringContainsString('Assessor/Operator', $ind4['disclaimer']);
    }

    /**
     * 6. Integrasi dokumen:
     * - Reference documents bersumber dari monev_dokumen dan file kontrak/mc di monev_proyek
     * - Progress evidence bersumber dari foto monev_harian yang verified
     * - Monev evidence (temuan/kunjungan lapangan Monev V2) dapat dibedakan secara tegas
     */
    public function testDocumentAndEvidenceSeparation(): void
    {
        // Masukkan daily log verified dengan foto untuk progress evidence
        $this->harianModel->insert([
            'proyek_id'      => $this->testProyekId,
            'item_id'        => $this->testJadwalId,
            'tanggal'        => date('Y-m-d', strtotime('-5 days')),
            'volume_selesai' => 30.0,
            'satuan'         => '%',
            'lokasi'         => 'Jembatan Segmen 1',
            'progres_persen' => 30.00,
            'is_verified'    => 1,
            'foto_awal'      => 'uploads/harian/foto_awal_jembatan.jpg',
            'foto_proses'    => 'uploads/harian/foto_proses_jembatan.jpg',
            'foto_hasil'     => 'uploads/harian/foto_hasil_jembatan.jpg',
            'keterangan'     => 'Foto progress fisik pengecoran',
        ]);

        $context = $this->integrationService->getProjectContext($this->testProyekId);

        $refDocs = $context['reference_documents'];
        $this->assertNotEmpty($refDocs);

        // Periksa keberadaan dokumen kontrak utama & time schedule
        $sources = array_column($refDocs, 'source');
        $this->assertContains('monev_proyek', $sources);
        $this->assertContains('monev_dokumen', $sources);

        // Periksa progress evidence dari foto harian verified
        $progEvidences = $context['progress_evidences'];
        $this->assertNotEmpty($progEvidences);
        foreach ($progEvidences as $ev) {
            $this->assertSame('monev_harian', $ev['source']);
            $this->assertSame(1, $ev['is_verified']);
            $this->assertNotEmpty($ev['file_path']);
        }

        // Monev V2 Evidence disimpan terpisah di tabel monev_evidence
        $this->evidenceModel->insert([
            'entity_type'     => 'MONEV_VISIT',
            'entity_id'       => 999,
            'jenis_eviden'    => 'FOTO_LAPANGAN',
            'file_path'       => 'uploads/monev_v2/visit_photo_999.jpg',
            'file_name'       => 'visit_photo_999.jpg',
            'file_size'       => 102400,
            'mime_type'       => 'image/jpeg',
            'tgl_pengambilan' => date('Y-m-d'),
            'keterangan'      => 'Dokumentasi kunjungan fisik Monev V2 Tim Ahli',
            'uploaded_by'     => $this->operatorUserId,
        ]);
        $evidenceId = $this->evidenceModel->getInsertID();

        $monevEv = $this->evidenceModel->find($evidenceId);
        $this->assertNotNull($monevEv);
        $this->assertSame('MONEV_VISIT', $monevEv['entity_type']);
        $this->assertSame('uploads/monev_v2/visit_photo_999.jpg', $monevEv['file_path']);

        // Bersihkan data test evidence
        $this->evidenceModel->delete($evidenceId);
    }

    /**
     * 7. Workflow startMonev meng-capture snapshot kontrak & progress terintegrasi.
     */
    public function testStartMonevCapturesIntegratedSnapshots(): void
    {
        $res = $this->workflowService->startMonev($this->testProyekId, $this->operatorUserId, 'operator', [
            'jenis_monev' => 'PERIODIC',
        ]);

        $this->assertIsArray($res);
        $periodId = $res['period_id'];

        $period = $this->periodModel->getFullPeriodDetails($periodId);
        $this->assertNotNull($period);

        // Snapshot kontrak terisi lengkap dari data existing
        $this->assertIsArray($period['contract_snapshot']);
        $this->assertSame('Pembangunan Jembatan Terintegrasi Monev V2', $period['contract_snapshot']['nama_proyek']);
        $this->assertSame('PT. Konstruksi Megah Mandiri', $period['contract_snapshot']['penyedia']);
        $this->assertSame('600/TEST-INTEG/CK/2026', $period['contract_snapshot']['no_kontrak']);

        // Snapshot progress terisi dari progress verified
        $this->assertIsArray($period['progress_snapshot']);
        $this->assertArrayHasKey('progress_aktual_verified', $period['progress_snapshot']);
        $this->assertArrayHasKey('progress_rencana', $period['progress_snapshot']);
        $this->assertArrayHasKey('deviasi', $period['progress_snapshot']);
        $this->assertArrayHasKey('status_proyek', $period['progress_snapshot']);

        // Contextual insights tersimpan di progress snapshot
        $this->assertArrayHasKey('system_insights', $period['progress_snapshot']);
        $this->assertFalse($period['progress_snapshot']['system_insights']['indicator_3_time_schedule']['is_rating_automatic']);
        $this->assertFalse($period['progress_snapshot']['system_insights']['indicator_4_kurva_s']['is_rating_automatic']);
    }
}

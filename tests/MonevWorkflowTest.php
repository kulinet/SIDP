<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\MonevWorkflowService;
use App\Models\MonevProyekModel;
use App\Models\MonevPeriodModel;
use App\Models\MonevAssessmentModel;
use App\Models\MonevAssessmentItemModel;
use App\Models\MonevFindingModel;
use App\Database\Seeds\MonevAssessmentTemplateSeeder;
use RuntimeException;

class MonevWorkflowTest extends CIUnitTestCase
{
    protected MonevWorkflowService $workflowService;
    protected MonevProyekModel $proyekModel;
    protected MonevPeriodModel $periodModel;
    protected MonevAssessmentModel $assessmentModel;
    protected MonevAssessmentItemModel $itemModel;
    protected MonevFindingModel $findingModel;

    protected int $testProyekId;
    protected int $adminUserId = 1;
    protected int $operatorUserId = 2;
    protected int $ppkUserId = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflowService = new MonevWorkflowService();
        $this->proyekModel     = new MonevProyekModel();
        $this->periodModel     = new MonevPeriodModel();
        $this->assessmentModel = new MonevAssessmentModel();
        $this->itemModel       = new MonevAssessmentItemModel();
        $this->findingModel    = new MonevFindingModel();

        // Pastikan template master tersedia
        $seeder = new MonevAssessmentTemplateSeeder(config('Database'));
        $seeder->run();

        // Siapkan dummy user dan dummy proyek jika belum ada
        $db = \Config\Database::connect();
        
        // Pastikan ada proyek untuk testing
        $existingProyek = $this->proyekModel->first();
        if (!$existingProyek) {
            $this->proyekModel->insert([
                'user_id'            => $this->ppkUserId,
                'nama_proyek'        => 'Pembangunan Gedung Laboratorium Terpadu',
                'nomor_kontrak'      => '600/01/KTR/CK/2026',
                'nilai_kontrak'      => 1500000000.00,
                'tgl_mulai'          => '2026-03-01',
                'tgl_selesai'        => '2026-10-31',
                'penyedia'           => 'PT. Maju Konstruksi Jaya',
                'konsultan_pengawas' => 'CV. Rancang Bangun Engineering',
                'direksi_teknis'     => 'Dinas PUTR Kabupaten Lombok Barat',
                'status'             => 'Berjalan',
                'created_at'         => date('Y-m-d H:i:s'),
            ]);
            $this->testProyekId = (int) $this->proyekModel->getInsertID();
        } else {
            $this->testProyekId = (int) $existingProyek['id'];
        }
    }

    /**
     * 1. PPK TIDAK BOLEH menjadi assessor dan DILARANG memulai Monev.
     */
    public function testPpkCannotStartMonev(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("PPK tidak memiliki wewenang untuk menjadi assessor atau membuat Monev.");

        $this->workflowService->startMonev($this->testProyekId, $this->ppkUserId, 'ppk');
    }

    /**
     * 2. Operator dapat memulai Monev, dan sistem mengambil snapshot kontrak & progress verified.
     */
    public function testOperatorCanStartMonevAndCapturesSnapshots(): int
    {
        $res = $this->workflowService->startMonev($this->testProyekId, $this->operatorUserId, 'operator', [
            'jenis_monev' => 'PERIODIC',
        ]);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('period_id', $res);
        $this->assertSame(MonevWorkflowService::STATUS_DRAFT, $res['status']);

        $period = $this->periodModel->getFullPeriodDetails($res['period_id']);
        $this->assertNotNull($period);
        $this->assertSame('0', (string) $period['is_locked']);

        // Verifikasi Snapshot Kontrak (Flow Langkah 5)
        $this->assertIsArray($period['contract_snapshot']);
        $this->assertSame((int) $this->testProyekId, (int) $period['contract_snapshot']['proyek_id']);
        $this->assertNotEmpty($period['contract_snapshot']['nama_proyek']);
        $this->assertNotEmpty($period['contract_snapshot']['nomor_kontrak']);

        // Verifikasi Snapshot Progress (Flow Langkah 6)
        $this->assertIsArray($period['progress_snapshot']);
        $this->assertArrayHasKey('progres_persen', $period['progress_snapshot']);

        // Verifikasi inisialisasi 12 item assessment dari master template
        $this->assertNotNull($period['assessment']);
        $this->assertCount(12, $period['assessment']['items'], "Assessment harus memuat 12 indikator.");

        return $res['period_id'];
    }

    /**
     * 3. Operator dapat menyimpan kunjungan (Visit), mengisi penilaian 12 indikator, dan temuan (Finding).
     * @depends testOperatorCanStartMonevAndCapturesSnapshots
     */
    public function testOperatorCanSaveVisitAssessmentAndFinding(int $periodId): int
    {
        // A. Simpan Visit
        $visitRes = $this->workflowService->saveVisit($periodId, $this->operatorUserId, 'operator', [
            'tgl_kunjungan'         => date('Y-m-d'),
            'waktu_mulai'           => '09:00:00',
            'waktu_selesai'         => '11:30:00',
            'lokasi_spesifik'       => 'Zona A Lantai 2',
            'cuaca'                 => 'Cerah',
            'pihak_hadir_ppk'       => 'Ahmad Fauzi (PPK)',
            'pihak_hadir_penyedia'  => 'Ir. Hendra (Site Manager)',
            'agenda_pemeriksaan'    => 'Pemeriksaan struktur kolom dan kepatuhan K3',
        ]);
        $this->assertSame('success', $visitRes['status']);

        // B. Simpan Penilaian: 10 item BAIK (2), 2 item SANGAT_BAIK (3) -> Total Score: 20 + 6 = 26
        $itemsPayload = [];
        for ($i = 1; $i <= 12; $i++) {
            $code = sprintf('IND-%02d', $i);
            $rating = ($i <= 10) ? 'BAIK' : 'SANGAT_BAIK';
            $itemsPayload[] = [
                'indicator_id' => $i,
                'rating'       => $rating,
                'note'         => "Catatan evaluasi lapangan untuk {$code}",
            ];
        }

        $assessRes = $this->workflowService->saveAssessment(
            $periodId,
            $this->operatorUserId,
            'operator',
            $itemsPayload,
            'Pekerjaan berjalan cukup baik secara keseluruhan.',
            'Disarankan menambah rambu K3 di area tangga dan mempercepat approval shop drawing.'
        );

        $this->assertSame('success', $assessRes['status']);
        $this->assertSame(26, $assessRes['total_score']);
        $this->assertSame(72.22, $assessRes['persentase']); // (26 / 36) * 100
        $this->assertNull($assessRes['kategori']); // Configurable threshold belum aktif

        // C. Tambah Temuan
        $findingRes = $this->workflowService->addFinding($periodId, $this->operatorUserId, 'operator', [
            'kategori_temuan'          => 'K3_LINGKUNGAN',
            'tingkat_keparahan'        => 'SEDANG',
            'deskripsi_temuan'         => 'Pekerja di lantai 2 tidak menggunakan helm keselamatan standar.',
            'rekomendasi_tindakan'     => 'Penyedia wajib mewajibkan seluruh pekerja mengenakan APD lengkap sebelum memasuki site.',
            'batas_waktu_penyelesaian' => date('Y-m-d', strtotime('+3 days')),
        ]);
        $this->assertGreaterThan(0, $findingRes['finding_id']);

        return $periodId;
    }

    /**
     * 4. PPK DILARANG submit assessment dan DILARANG mereview / approve.
     * @depends testOperatorCanSaveVisitAssessmentAndFinding
     */
    public function testPpkCannotSubmitOrReviewMonev(int $periodId): void
    {
        // Coba Submit oleh PPK -> Harus Gagal
        try {
            $this->workflowService->submitMonev($periodId, $this->ppkUserId, 'ppk');
            $this->fail("PPK seharusnya tidak diizinkan submit Monev.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PPK tidak memiliki wewenang', $e->getMessage());
        }

        // Coba Review oleh PPK -> Harus Gagal
        try {
            $this->workflowService->reviewMonev($periodId, $this->ppkUserId, 'ppk', 'APPROVE');
            $this->fail("PPK seharusnya tidak diizinkan mereview Monev.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PPK tidak memiliki wewenang', $e->getMessage());
        }
    }

    /**
     * 5. Operator submit Monev (DRAFT -> SUBMITTED).
     * @depends testOperatorCanSaveVisitAssessmentAndFinding
     */
    public function testOperatorCanSubmitMonev(int $periodId): int
    {
        $res = $this->workflowService->submitMonev($periodId, $this->operatorUserId, 'operator', 'Laporan Monev periode 1 telah lengkap.');
        $this->assertSame(MonevWorkflowService::STATUS_SUBMITTED, $res['status']);

        $period = $this->periodModel->find($periodId);
        $this->assertSame(MonevWorkflowService::STATUS_SUBMITTED, $period['status']);

        return $periodId;
    }

    /**
     * 6. Reviewer dapat me-RETURN Monev, Operator dapat memperbaiki dan men-submit ulang.
     * @depends testOperatorCanSubmitMonev
     */
    public function testReviewerCanReturnAndOperatorCanResubmit(int $periodId): int
    {
        // Admin Reviewer melakukan RETURN
        $returnRes = $this->workflowService->reviewMonev($periodId, $this->adminUserId, 'admin', 'RETURN', 'Tolong perjelas batas waktu perbaikan temuan K3.');
        $this->assertSame(MonevWorkflowService::STATUS_RETURNED, $returnRes['status']);

        // Assessor (Operator) dapat mengedit kembali karena status RETURNED
        $fixRes = $this->workflowService->saveAssessment($periodId, $this->operatorUserId, 'operator', [], 'Revisi ringkasan setelah catatan reviewer.');
        $this->assertSame('success', $fixRes['status']);

        // Assessor submit ulang
        $resubmitRes = $this->workflowService->submitMonev($periodId, $this->operatorUserId, 'operator', 'Catatan perbaikan telah disesuaikan.');
        $this->assertSame(MonevWorkflowService::STATUS_SUBMITTED, $resubmitRes['status']);

        return $periodId;
    }

    /**
     * 7. Reviewer / Admin meng-APPROVE Monev (SUBMITTED -> APPROVED).
     * @depends testReviewerCanReturnAndOperatorCanResubmit
     */
    public function testReviewerCanApproveMonev(int $periodId): int
    {
        $res = $this->workflowService->reviewMonev($periodId, $this->adminUserId, 'admin', 'APPROVE', 'Laporan penilaian telah diverifikasi dan disetujui.');
        $this->assertSame(MonevWorkflowService::STATUS_APPROVED, $res['status']);

        $period = $this->periodModel->find($periodId);
        $this->assertSame(MonevWorkflowService::STATUS_APPROVED, $period['status']);

        return $periodId;
    }

    /**
     * 8. Admin mem-FINALISASI Monev (APPROVED -> FINALIZED) dan Monev menjadi IMMUTABLE (is_locked = 1).
     * @depends testReviewerCanApproveMonev
     */
    public function testAdminCanFinalizeMonevAndLocksPeriod(int $periodId): int
    {
        $res = $this->workflowService->finalizeMonev($periodId, $this->adminUserId, 'admin', 'Berita Acara Monev resmi difinalisasi.');
        $this->assertSame(MonevWorkflowService::STATUS_FINALIZED, $res['status']);
        $this->assertSame(1, $res['is_locked']);

        $period = $this->periodModel->find($periodId);
        $this->assertSame(MonevWorkflowService::STATUS_FINALIZED, $period['status']);
        $this->assertSame('1', (string) $period['is_locked']);

        return $periodId;
    }

    /**
     * 9. Monev yang telah FINALIZED menjadi IMMUTABLE (menolak semua operasi edit/hapus/tambah).
     * @depends testAdminCanFinalizeMonevAndLocksPeriod
     */
    public function testFinalizedMonevIsImmutable(int $periodId): void
    {
        // 1. Coba simpan visit -> Harus gagal
        try {
            $this->workflowService->saveVisit($periodId, $this->adminUserId, 'admin', ['cuaca' => 'Hujan']);
            $this->fail("Monev yang finalized seharusnya menolak perubahan visit.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('IMMUTABLE', $e->getMessage());
        }

        // 2. Coba simpan assessment -> Harus gagal
        try {
            $this->workflowService->saveAssessment($periodId, $this->adminUserId, 'admin', []);
            $this->fail("Monev yang finalized seharusnya menolak perubahan assessment.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('IMMUTABLE', $e->getMessage());
        }

        // 3. Coba tambah finding -> Harus gagal
        try {
            $this->workflowService->addFinding($periodId, $this->adminUserId, 'admin', [
                'deskripsi_temuan' => 'Temuan baru pasca finalisasi',
            ]);
            $this->fail("Monev yang finalized seharusnya menolak penambahan temuan.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('IMMUTABLE', $e->getMessage());
        }

        // 4. Coba submit ulang -> Harus gagal
        try {
            $this->workflowService->submitMonev($periodId, $this->adminUserId, 'admin');
            $this->fail("Monev yang finalized seharusnya menolak submit ulang.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('dikunci', $e->getMessage());
        }
    }
}

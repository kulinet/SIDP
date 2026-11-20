<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\MonevFindingService;
use App\Services\MonevWorkflowService;
use App\Models\MonevFindingModel;
use App\Models\MonevFollowupModel;
use App\Models\MonevPeriodModel;
use App\Models\MonevAssessmentModel;
use App\Models\MonevAssessmentItemModel;
use App\Models\MonevEvidenceModel;
use App\Models\MonevAuditLogModel;
use App\Models\MonevProyekModel;
use App\Database\Seeds\MonevAssessmentTemplateSeeder;
use RuntimeException;
use InvalidArgumentException;

class MonevFindingAndFollowupTest extends CIUnitTestCase
{
    protected MonevFindingService $findingService;
    protected MonevWorkflowService $workflowService;
    protected MonevFindingModel $findingModel;
    protected MonevFollowupModel $followupModel;
    protected MonevPeriodModel $periodModel;
    protected MonevAssessmentModel $assessmentModel;
    protected MonevAssessmentItemModel $itemModel;
    protected MonevEvidenceModel $evidenceModel;
    protected MonevAuditLogModel $auditModel;
    protected MonevProyekModel $proyekModel;

    protected int $testProyekId;
    protected int $adminUserId = 1;
    protected int $operatorUserId = 2;
    protected int $ppkUserId = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->findingService   = new MonevFindingService();
        $this->workflowService  = new MonevWorkflowService();
        $this->findingModel     = new MonevFindingModel();
        $this->followupModel    = new MonevFollowupModel();
        $this->periodModel      = new MonevPeriodModel();
        $this->assessmentModel  = new MonevAssessmentModel();
        $this->itemModel        = new MonevAssessmentItemModel();
        $this->evidenceModel    = new MonevEvidenceModel();
        $this->auditModel       = new MonevAuditLogModel();
        $this->proyekModel      = new MonevProyekModel();

        // Template master
        $seeder = new MonevAssessmentTemplateSeeder(config('Database'));
        $seeder->run();

        // Dapatkan atau buat proyek test
        $existing = $this->proyekModel->first();
        if ($existing) {
            $this->testProyekId = (int) $existing['id'];
        } else {
            $this->proyekModel->insert([
                'user_id'            => $this->ppkUserId,
                'nama_proyek'        => 'Pembangunan Gedung Uji Temuan V2',
                'no_kontrak'         => '600/TEST-FINDING/CK/2026',
                'nilai_kontrak'      => 1000000000.00,
                'tgl_mulai'          => date('Y-m-d', strtotime('-20 days')),
                'tgl_selesai'        => date('Y-m-d', strtotime('+40 days')),
                'nama_penyedia'      => 'PT. Bangun Jaya Persada',
                'konsultan_pengawas' => 'CV. Graha Konsultan',
                'direksi_teknis'     => 'Dinas PUTR',
                'status'             => 'Berjalan',
            ]);
            $this->testProyekId = (int) $this->proyekModel->getInsertID();
        }
    }

    /**
     * Helper: membuat period Monev untuk pengujian
     */
    protected function createTestPeriod(): int
    {
        $res = $this->workflowService->startMonev($this->testProyekId, $this->operatorUserId, 'operator', [
            'jenis_monev' => 'PERIODIC',
        ]);
        return (int) $res['period_id'];
    }

    /**
     * 1. Finding minimal fields & severity mapping.
     */
    public function testCreateFindingWithRequiredFieldsAndSeverity(): void
    {
        $periodId = $this->createTestPeriod();

        $findingData = [
            'category'          => 'K3_LINGKUNGAN',
            'title'             => 'Pekerja Tidak Menggunakan Rompi dan Helm Proyek',
            'description'       => 'Ditemukan 8 pekerja di zona struktur atas tanpa helm keselamatan standar SNI.',
            'severity'          => 'HIGH', // LOW, MEDIUM, HIGH, CRITICAL
            'recommendation'    => 'Penyedia wajib melakukan safety induction harian dan membagikan APD lengkap.',
            'responsible_party' => 'PT. Bangun Jaya Persada (Penyedia)',
            'due_date'          => date('Y-m-d', strtotime('+3 days')),
            'evidence'          => [
                'jenis_eviden' => 'FOTO_LAPANGAN',
                'file_path'    => 'uploads/monev_v2/finding_k3_pekerja.jpg',
                'keterangan'   => 'Foto pelanggaran APD pekerja lantai 2',
            ],
        ];

        $res = $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', $findingData);

        $this->assertIsArray($res);
        $this->assertGreaterThan(0, $res['finding_id']);
        $this->assertSame('HIGH', $res['severity']);
        $this->assertSame(MonevFindingModel::STATUS_OPEN, $res['status']);
        $this->assertSame('Pekerja Tidak Menggunakan Rompi dan Helm Proyek', $res['title']);

        // Periksa data di database
        $finding = $this->findingModel->find($res['finding_id']);
        $this->assertNotNull($finding);
        $this->assertSame($periodId, (int) $finding['monev_period_id']);
        $this->assertSame('K3_LINGKUNGAN', $finding['category']);
        $this->assertSame('HIGH', $finding['severity']);
        $this->assertSame('TINGGI', $finding['tingkat_keparahan']);
        $this->assertSame($this->operatorUserId, (int) $finding['created_by']);
        $this->assertNotEmpty($finding['kode_temuan']);

        // Verifikasi audit log tercatat
        $audit = $this->auditModel->where('monev_period_id', $periodId)
            ->where('action', 'FINDING_CREATED')
            ->orderBy('id', 'DESC')
            ->first();
        $this->assertNotNull($audit);
        $this->assertSame($this->operatorUserId, (int) $audit['actor_id']);
        $this->assertSame(MonevFindingModel::STATUS_OPEN, $audit['new_status']);
    }

    /**
     * 2. Severity normalization: input bahasa Indonesia dipetakan ke standar ENUM.
     */
    public function testSeverityNormalization(): void
    {
        $this->assertSame(MonevFindingModel::SEVERITY_LOW, $this->findingService->normalizeSeverity('RENDAH'));
        $this->assertSame(MonevFindingModel::SEVERITY_MEDIUM, $this->findingService->normalizeSeverity('SEDANG'));
        $this->assertSame(MonevFindingModel::SEVERITY_HIGH, $this->findingService->normalizeSeverity('TINGGI'));
        $this->assertSame(MonevFindingModel::SEVERITY_CRITICAL, $this->findingService->normalizeSeverity('KRITIS'));

        $this->assertSame(MonevFindingModel::SEVERITY_LOW, $this->findingService->normalizeSeverity('low'));
        $this->assertSame(MonevFindingModel::SEVERITY_CRITICAL, $this->findingService->normalizeSeverity('critical'));

        $this->expectException(InvalidArgumentException::class);
        $this->findingService->normalizeSeverity('UNKNOWN_SEVERITY');
    }

    /**
     * 3. Lifecycle Status Finding: OPEN -> IN_PROGRESS -> RESOLVED -> VERIFIED -> CLOSED.
     * Semua perubahan status wajib masuk audit log.
     */
    public function testFindingStatusTransitionsAndAuditLogging(): void
    {
        $periodId = $this->createTestPeriod();

        $findingRes = $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', [
            'title'       => 'Penyimpanan Semen Tidak Menggunakan Palet Kayu',
            'description' => 'Semen diletakkan langsung di atas tanah berpotensi menyerap kelembaban.',
            'severity'    => 'MEDIUM',
        ]);
        $findingId = $resId = $findingRes['finding_id'];

        // Transisi: OPEN -> IN_PROGRESS
        $t1 = $this->findingService->updateFindingStatus($findingId, $this->operatorUserId, 'operator', MonevFindingModel::STATUS_IN_PROGRESS, 'Penyedia sedang menyiapkan palet');
        $this->assertSame(MonevFindingModel::STATUS_OPEN, $t1['old_status']);
        $this->assertSame(MonevFindingModel::STATUS_IN_PROGRESS, $t1['new_status']);

        // Transisi: IN_PROGRESS -> RESOLVED
        $t2 = $this->findingService->updateFindingStatus($findingId, $this->operatorUserId, 'operator', MonevFindingModel::STATUS_RESOLVED, 'Palet kayu telah dipasang');
        $this->assertSame(MonevFindingModel::STATUS_RESOLVED, $t2['new_status']);

        $resolvedFinding = $this->findingModel->find($findingId);
        $this->assertNotEmpty($resolvedFinding['resolved_at'], 'resolved_at wajib terisi saat status RESOLVED');

        // Transisi: RESOLVED -> VERIFIED
        $t3 = $this->findingService->updateFindingStatus($findingId, $this->adminUserId, 'admin', MonevFindingModel::STATUS_VERIFIED, 'Verifikasi lapangan telah sesuai');
        $this->assertSame(MonevFindingModel::STATUS_VERIFIED, $t3['new_status']);

        // Transisi: VERIFIED -> CLOSED
        $t4 = $this->findingService->updateFindingStatus($findingId, $this->adminUserId, 'admin', MonevFindingModel::STATUS_CLOSED, 'Temuan resmi ditutup');
        $this->assertSame(MonevFindingModel::STATUS_CLOSED, $t4['new_status']);

        // Periksa seluruh audit trail status change
        $auditLogs = $this->auditModel->where('monev_period_id', $periodId)
            ->where('action', 'FINDING_STATUS_CHANGED')
            ->findAll();
        $this->assertCount(4, $auditLogs, 'Harus ada 4 catatan audit log perubahan status');
    }

    /**
     * 4. Follow-up / Action Item Creation:
     * Menambahkan follow-up otomatis mengubah finding status yang OPEN menjadi IN_PROGRESS.
     */
    public function testFollowupCreationAutomaticallyUpdatesFindingStatus(): void
    {
        $periodId = $this->createTestPeriod();

        $findingRes = $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', [
            'title'       => 'Pondasi Retak Halus pada Sayap Kanan',
            'description' => 'Retak rambut teramati pasca pelepasan bekisting.',
            'severity'    => 'HIGH',
        ]);
        $findingId = $findingRes['finding_id'];

        // Finding status awal adalah OPEN
        $fBefore = $this->findingModel->find($findingId);
        $this->assertSame(MonevFindingModel::STATUS_OPEN, $fBefore['status']);

        // Tambah follow-up
        $followupRes = $this->findingService->createFollowup($findingId, $this->operatorUserId, 'operator', [
            'action'   => 'Injeksi semen grouting non-shrinkage pada retak sayap kanan',
            'pic'      => 'Mandor Utama Struktur (Bpk. Subur)',
            'deadline' => date('Y-m-d', strtotime('+2 days')),
            'evidence' => [
                'jenis_eviden' => 'FOTO_LAPANGAN',
                'file_path'    => 'uploads/monev_v2/grouting_retak.jpg',
                'keterangan'   => 'Foto proses injeksi grouting',
            ],
        ]);

        $this->assertGreaterThan(0, $followupRes['followup_id']);
        $this->assertSame(MonevFollowupModel::STATUS_OPEN, $followupRes['status']);

        // Finding status otomatis beralih ke IN_PROGRESS
        $fAfter = $this->findingModel->find($findingId);
        $this->assertSame(MonevFindingModel::STATUS_IN_PROGRESS, $fAfter['status'], 'Status temuan wajib berubah otomatis ke IN_PROGRESS saat ditambahkan follow-up');

        // Audit log mencatat FOLLOWUP_CREATED
        $audit = $this->auditModel->where('monev_period_id', $periodId)
            ->where('action', 'FOLLOWUP_CREATED')
            ->first();
        $this->assertNotNull($audit);
    }

    /**
     * 5. Verifikasi Follow-up oleh Assessor/Reviewer/Admin (PPK Dilarang).
     */
    public function testFollowupVerificationRulesAndPermission(): void
    {
        $periodId = $this->createTestPeriod();

        $findingRes = $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', [
            'title'       => 'Genangan Air Dekat Panel Listrik Proyek',
            'description' => 'Terdapat genangan air membahayakan di sekitar panel distribusi.',
            'severity'    => 'CRITICAL',
        ]);
        $findingId = $findingRes['finding_id'];

        $followupRes = $this->findingService->createFollowup($findingId, $this->operatorUserId, 'operator', [
            'action'   => 'Pembuatan saluran pembuang dan peninggian dudukan panel 30cm',
            'pic'      => 'K3 & Elektrikal Site',
            'deadline' => date('Y-m-d', strtotime('+1 day')),
        ]);
        $followupId = $followupRes['followup_id'];

        // A. PPK DILARANG memverifikasi follow-up
        try {
            $this->findingService->verifyFollowup($followupId, $this->ppkUserId, 'ppk', 'APPROVE', 'Diverifikasi oleh PPK');
            $this->fail("PPK seharusnya dilarang memverifikasi follow-up Monev.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('PPK tidak memiliki wewenang', $e->getMessage());
        }

        // B. Admin/Reviewer resmi memverifikasi follow-up (APPROVE)
        $verifyRes = $this->findingService->verifyFollowup($followupId, $this->adminUserId, 'admin', 'APPROVE', 'Saluran air telah dibuat dan panel listrik aman');
        $this->assertSame(MonevFollowupModel::STATUS_VERIFIED, $verifyRes['status']);

        $fup = $this->followupModel->find($followupId);
        $this->assertSame('DITERIMA', $fup['status_verifikasi']);
        $this->assertSame($this->adminUserId, (int) $fup['verified_by_user_id']);
        $this->assertNotEmpty($fup['verified_at']);

        // Karena satu-satunya follow-up telah terverifikasi, status parent finding otomatis beralih ke VERIFIED
        $parentFinding = $this->findingModel->find($findingId);
        $this->assertSame(MonevFindingModel::STATUS_VERIFIED, $parentFinding['status'], 'Parent finding harus terverifikasi jika seluruh follow-up telah diverifikasi');
    }

    /**
     * 6. Pemisahan Evidence Finding vs Evidence Follow-up.
     */
    public function testEvidenceSeparationBetweenFindingAndFollowup(): void
    {
        $periodId = $this->createTestPeriod();

        $findingRes = $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', [
            'title'       => 'Pagar Proyek Rusak Diterjang Angin',
            'description' => 'Seng pembatas di sisi selatan roboh.',
            'severity'    => 'LOW',
        ]);
        $findingId = $findingRes['finding_id'];

        // Attach evidence ke Finding
        $this->findingService->attachEvidence('MONEV_FINDING', $findingId, $this->operatorUserId, [
            'jenis_eviden' => 'FOTO_LAPANGAN',
            'file_path'    => 'uploads/monev_v2/finding_pagar_roboh.jpg',
            'keterangan'   => 'Foto pagar seng roboh',
        ]);

        // Attach follow-up dengan evidence
        $followupRes = $this->findingService->createFollowup($findingId, $this->operatorUserId, 'operator', [
            'action'   => 'Pemasangan tiang pancang baru dan perbaikan seng pagar',
            'pic'      => 'Koordinator Keamanan Lapangan',
            'deadline' => date('Y-m-d', strtotime('+2 days')),
        ]);
        $followupId = $followupRes['followup_id'];

        // Attach evidence ke Follow-up
        $this->findingService->attachEvidence('MONEV_FOLLOWUP', $followupId, $this->operatorUserId, [
            'jenis_eviden' => 'BUKTI_PERBAIKAN',
            'file_path'    => 'uploads/monev_v2/followup_pagar_selesai.jpg',
            'keterangan'   => 'Foto pagar seng telah tegak kembali',
        ]);

        // Ambil data lengkap via model helper
        $details = $this->findingModel->getFindingWithDetails($findingId);

        $this->assertCount(1, $details['evidence'], 'Finding harus memiliki 1 evidence finding');
        $this->assertSame('MONEV_FINDING', $details['evidence'][0]['entity_type']);
        $this->assertSame('uploads/monev_v2/finding_pagar_roboh.jpg', $details['evidence'][0]['file_path']);

        $this->assertCount(1, $details['followups'], 'Finding harus memiliki 1 followup');
        $this->assertCount(1, $details['followups'][0]['evidence'], 'Followup harus memiliki 1 evidence followup');
        $this->assertSame('MONEV_FOLLOWUP', $details['followups'][0]['evidence'][0]['entity_type']);
        $this->assertSame('uploads/monev_v2/followup_pagar_selesai.jpg', $details['followups'][0]['evidence'][0]['file_path']);
    }

    /**
     * 7. Finalization Readiness Rules:
     * - Memblokir finalisasi jika mandatory assessment belum selesai
     * - Memblokir finalisasi jika kesimpulan belum ada
     * - Memblokir finalisasi jika belum APPROVED
     * - Memblokir finalisasi jika temuan CRITICAL / HIGH masih OPEN
     * - TIDAK memblokir finalisasi jika temuan optional (LOW / MEDIUM) masih OPEN
     */
    public function testFinalizationReadinessAndBusinessRules(): void
    {
        $periodId = $this->createTestPeriod();

        // 1. Status masih DRAFT -> Belum siap finalisasi
        $r1 = $this->findingService->checkFinalizationReadiness($periodId);
        $this->assertFalse($r1['is_ready']);
        $this->assertStringContainsString('belum APPROVED', implode(' ', $r1['blockers']));

        // 2. Isi 12 aspek penilaian & kesimpulan
        $itemsPayload = [];
        for ($i = 1; $i <= 12; $i++) {
            $itemsPayload[] = [
                'indicator_id' => $i,
                'rating'       => 'BAIK',
                'note'         => "Catatan evaluasi #{$i}",
            ];
        }
        $this->workflowService->saveAssessment(
            $periodId,
            $this->operatorUserId,
            'operator',
            $itemsPayload,
            'Ringkasan kinerja memenuhi syarat.',
            'Kesimpulan proyek dapat dilanjutkan ke tahap berikutnya.'
        );

        // Submit & Approve
        $this->workflowService->submitMonev($periodId, $this->operatorUserId, 'operator', 'Laporan Monev siap');
        $this->workflowService->reviewMonev($periodId, $this->adminUserId, 'admin', 'APPROVE', 'Disetujui untuk difinalisasi');

        // 3. Tambah temuan CRITICAL yang masih OPEN -> Wajib memblokir finalisasi!
        $criticalFinding = $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', [
            'title'       => 'Pondasi Jembatan Mengalami Settlement Kritis',
            'description' => 'Penurunan pondasi melebihi batas toleransi teknis.',
            'severity'    => 'CRITICAL',
        ]);
        $criticalFindingId = $criticalFinding['finding_id'];

        $r2 = $this->findingService->checkFinalizationReadiness($periodId);
        $this->assertFalse($r2['is_ready']);
        $this->assertStringContainsString('kritis/tinggi', implode(' ', $r2['blockers']));

        // Coba panggil finalizeMonev() -> Wajib throw RuntimeException
        try {
            $this->workflowService->finalizeMonev($periodId, $this->adminUserId, 'admin');
            $this->fail("Monev tidak boleh difinalisasi jika temuan CRITICAL masih OPEN.");
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('belum memenuhi syarat', $e->getMessage());
        }

        // 4. Selesaikan temuan CRITICAL (Status RESOLVED)
        $this->findingService->updateFindingStatus($criticalFindingId, $this->operatorUserId, 'operator', MonevFindingModel::STATUS_RESOLVED, 'Pondasi telah diperkuat perkuatan micro-pile');

        // 5. Tambah temuan optional (LOW dan MEDIUM) yang masih OPEN
        $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', [
            'title'       => 'Papan Nama Proyek Sedikit Miring',
            'description' => 'Papan proyek miring tertiup angin.',
            'severity'    => 'LOW',
        ]);
        $this->findingService->createFinding($periodId, $this->operatorUserId, 'operator', [
            'title'       => 'Pembersihan Sisa Potongan Kayu',
            'description' => 'Sisa potongan reng kayu belum dipindahkan ke tempat pembuangan.',
            'severity'    => 'MEDIUM',
        ]);

        // 6. Cek kesiapan: Temuan optional LOW/MEDIUM yang masih OPEN DILARANG MEMBLOKIR finalisasi!
        $r3 = $this->findingService->checkFinalizationReadiness($periodId);
        $this->assertTrue($r3['is_ready'], 'Temuan optional (LOW/MEDIUM) yang masih OPEN tidak boleh memblokir finalisasi!');
        $this->assertSame(2, $r3['optional_open_count']);
        $this->assertNotEmpty($r3['optional_open_message']);

        // Panggil finalizeMonev() -> Berhasil!
        $finRes = $this->workflowService->finalizeMonev($periodId, $this->adminUserId, 'admin', 'Monev resmi ditutup.');
        $this->assertSame(MonevWorkflowService::STATUS_FINALIZED, $finRes['status']);
        $this->assertSame(1, $finRes['is_locked']);
    }
}

<?php

namespace App\Services;

use App\Models\MonevPeriodModel;
use App\Models\MonevVisitModel;
use App\Models\MonevAssessmentModel;
use App\Models\MonevAssessmentItemModel;
use App\Models\MonevFindingModel;
use App\Models\MonevApprovalModel;
use App\Models\MonevEvidenceModel;
use App\Models\MonevAuditLogModel;
use App\Models\MonevAssessmentTemplateModel;
use App\Models\MonevProyekModel;
use InvalidArgumentException;
use RuntimeException;

class MonevWorkflowService
{
    // Status Monev Period
    public const STATUS_DRAFT      = 'DRAFT';
    public const STATUS_SUBMITTED  = 'SUBMITTED';
    public const STATUS_IN_REVIEW  = 'IN_REVIEW';
    public const STATUS_RETURNED   = 'RETURNED';
    public const STATUS_APPROVED   = 'APPROVED';
    public const STATUS_FINALIZED  = 'FINALIZED';
    public const STATUS_CANCELLED  = 'CANCELLED';

    // Role yang diizinkan mengelola Monev
    public const ALLOWED_ASSESSOR_ROLES = ['admin', 'operator'];
    public const ALLOWED_REVIEWER_ROLES = ['admin'];

    protected MonevPeriodModel $periodModel;
    protected MonevVisitModel $visitModel;
    protected MonevAssessmentModel $assessmentModel;
    protected MonevAssessmentItemModel $itemModel;
    protected MonevFindingModel $findingModel;
    protected MonevApprovalModel $approvalModel;
    protected MonevEvidenceModel $evidenceModel;
    protected MonevAssessmentTemplateModel $templateModel;
    protected MonevProyekModel $proyekModel;
    protected MonevAssessmentService $assessmentService;
    protected MonevIntegrationService $integrationService;
    protected MonevFindingService $findingService;

    public function __construct()
    {
        $this->periodModel        = new MonevPeriodModel();
        $this->visitModel         = new MonevVisitModel();
        $this->assessmentModel    = new MonevAssessmentModel();
        $this->itemModel          = new MonevAssessmentItemModel();
        $this->findingModel       = new MonevFindingModel();
        $this->approvalModel      = new MonevApprovalModel();
        $this->evidenceModel      = new MonevEvidenceModel();
        $this->auditLogModel      = new MonevAuditLogModel();
        $this->templateModel      = new MonevAssessmentTemplateModel();
        $this->proyekModel        = new MonevProyekModel();
        $this->assessmentService  = new MonevAssessmentService();
        $this->integrationService = new MonevIntegrationService();
        $this->findingService     = new MonevFindingService();
    }

    /**
     * Memulai Monev resmi untuk sebuah proyek (Flow Langkah 1 - 6).
     * 
     * PERMISSION:
     * - Hanya Admin dan Operator.
     * - PPK DILARANG KERAS memulai/membuat Monev.
     */
    public function startMonev(int $proyekId, int $userId, string $userRole, array $options = []): array
    {
        $role = strtolower(trim($userRole));
        if ($role === 'ppk') {
            throw new RuntimeException("PPK tidak memiliki wewenang untuk menjadi assessor atau membuat Monev.");
        }
        if (!in_array($role, self::ALLOWED_ASSESSOR_ROLES)) {
            throw new RuntimeException("Role '{$userRole}' tidak diizinkan untuk memulai Monev.");
        }

        // Ambil konteks terintegrasi lengkap dari data existing
        $planDate = $options['tgl_rencana_monev'] ?? date('Y-m-d');
        $context = $this->integrationService->getProjectContext($proyekId, $planDate);

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Snapshot data kontrak (Flow Langkah 5)
        $contractSnapshot = $context['contract_data'];
        $contractSnapshot['snapshot_at'] = date('Y-m-d H:i:s');

        // 2. Snapshot progress verified (Flow Langkah 6)
        $progressSnapshot = [
            'progres_persen'           => $context['progress_metrics']['progress_aktual_verified'],
            'progress_aktual_verified' => $context['progress_metrics']['progress_aktual_verified'],
            'progress_rencana'         => $context['progress_metrics']['progress_rencana'],
            'deviasi'                  => $context['progress_metrics']['deviasi'],
            'status_proyek'            => $context['progress_metrics']['status_proyek'],
            'verified_logs_count'      => $context['progress_metrics']['verified_logs_count'],
            'timeline'                 => $context['timeline_metrics'],
            'system_insights'          => $context['system_insights'],
            'snapshot_at'              => date('Y-m-d H:i:s'),
        ];

        // 3. Hitung periode ke-n
        $lastPeriod = $this->periodModel->where('proyek_id', $proyekId)->orderBy('periode_ke', 'DESC')->first();
        $periodeKe = $lastPeriod ? ((int) $lastPeriod['periode_ke'] + 1) : 1;

        $year = date('Y');
        $nomorMonev = sprintf("MNV/%d/%s/%02d", $proyekId, $year, $periodeKe);

        // 4. Inisialisasi Monev Period (Flow Langkah 4)
        $periodData = [
            'proyek_id'             => $proyekId,
            'nomor_monev'           => $nomorMonev,
            'jenis_monev'           => $options['jenis_monev'] ?? 'PERIODIC',
            'contract_snapshot'     => json_encode($contractSnapshot),
            'progress_snapshot'     => json_encode($progressSnapshot),
            'periode_ke'            => $periodeKe,
            'tgl_rencana_monev'     => $options['tgl_rencana_monev'] ?? date('Y-m-d'),
            'assessor_id'           => $userId,
            'status'                => self::STATUS_DRAFT,
            'is_locked'             => 0,
            'created_at'            => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ];
        $this->periodModel->insert($periodData);
        $periodId = (int) $this->periodModel->getInsertID();

        // 5. Inisialisasi Assessment dari Template Aktif (6 aspek, 12 indikator)
        $activeTemplate = $this->templateModel->getActiveTemplateWithStructure();
        if (!$activeTemplate) {
            throw new RuntimeException("Tidak ada master template Monev yang aktif.");
        }

        $assessmentData = [
            'monev_period_id'           => $periodId,
            'template_id'               => $activeTemplate['id'],
            'template_version_snapshot' => $activeTemplate['versi'] ?? '1.0',
            'skor_total'                => 0.00,
            'kategori_hasil'            => null,
            'created_at'                => date('Y-m-d H:i:s'),
            'updated_at'                => date('Y-m-d H:i:s'),
        ];
        $this->assessmentModel->insert($assessmentData);
        $assessmentId = (int) $this->assessmentModel->getInsertID();

        // Insert 12 item assessment snapshot
        foreach ($activeTemplate['aspects'] as $asp) {
            foreach ($asp['indicators'] as $ind) {
                $itemPayload = [
                    'assessment_id'           => $assessmentId,
                    'indicator_id'            => $ind['id'],
                    'aspek_kode_snapshot'     => $asp['kode_aspek'],
                    'aspek_nama_snapshot'     => $asp['nama_aspek'],
                    'indikator_kode_snapshot' => $ind['kode_indikator'],
                    'indikator_nama_snapshot' => $ind['nama_indikator'],
                    'bobot_persen_snapshot'   => $asp['bobot_persen'],
                    'rating'                  => MonevAssessmentService::RATING_BAIK,
                    'score'                   => 2,
                    'evidence_requirement'    => $ind['evidence_requirement'] ?? null,
                    'catatan_penilai'         => null,
                    'note'                    => null,
                    'assessor_id'             => $userId,
                    'created_at'              => date('Y-m-d H:i:s'),
                ];
                $this->itemModel->insert($itemPayload);
            }
        }

        // Audit Trail
        $this->logAudit($periodId, 'MONEV_STARTED', $userId, $role, null, self::STATUS_DRAFT, [
            'nomor_monev' => $nomorMonev,
            'proyek_id'   => $proyekId,
        ]);

        $db->transComplete();

        return [
            'period_id'   => $periodId,
            'nomor_monev' => $nomorMonev,
            'status'      => self::STATUS_DRAFT,
        ];
    }

    /**
     * Menyimpan data kunjungan lapangan Monev Visit (Flow Langkah 8).
     */
    public function saveVisit(int $periodId, int $userId, string $userRole, array $visitData): array
    {
        $this->assertCanEdit($periodId, $userRole, $userId);

        $existing = $this->visitModel->where('monev_period_id', $periodId)->first();

        $payload = [
            'monev_period_id'       => $periodId,
            'tgl_kunjungan'         => $visitData['tgl_kunjungan'] ?? date('Y-m-d'),
            'waktu_mulai'           => $visitData['waktu_mulai'] ?? null,
            'waktu_selesai'         => $visitData['waktu_selesai'] ?? null,
            'lokasi_spesifik'       => $visitData['lokasi_spesifik'] ?? null,
            'gps_lat'               => $visitData['gps_lat'] ?? null,
            'gps_lng'               => $visitData['gps_lng'] ?? null,
            'cuaca'                 => $visitData['cuaca'] ?? null,
            'pihak_hadir_ppk'       => $visitData['pihak_hadir_ppk'] ?? null,
            'pihak_hadir_penyedia'  => $visitData['pihak_hadir_penyedia'] ?? null,
            'pihak_hadir_konsultan' => $visitData['pihak_hadir_konsultan'] ?? null,
            'agenda_pemeriksaan'    => $visitData['agenda_pemeriksaan'] ?? null,
            'catatan_lapangan'      => $visitData['catatan_lapangan'] ?? null,
        ];

        if ($existing) {
            $this->visitModel->update($existing['id'], $payload);
            $visitId = (int) $existing['id'];
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->visitModel->insert($payload);
            $visitId = (int) $this->visitModel->getInsertID();
        }

        $this->logAudit($periodId, 'VISIT_SAVED', $userId, $userRole, null, null, ['visit_id' => $visitId]);

        return ['visit_id' => $visitId, 'status' => 'success'];
    }

    /**
     * Mengisi / memperbarui lembar penilaian 6 aspek 12 indikator & kesimpulan (Flow Langkah 9, 13, 14).
     */
    public function saveAssessment(int $periodId, int $userId, string $userRole, array $itemsData, ?string $ringkasan = null, ?string $kesimpulan = null): array
    {
        $this->assertCanEdit($periodId, $userRole, $userId);

        $assessment = $this->assessmentModel->where('monev_period_id', $periodId)->first();
        if (!$assessment) {
            throw new RuntimeException("Data assessment untuk periode ini belum tersedia.");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $calcItems = [];

        foreach ($itemsData as $it) {
            $indicatorId = $it['indicator_id'] ?? null;
            $rating = strtoupper(trim($it['rating'] ?? 'BAIK'));
            $score = $this->assessmentService->mapRatingToScore($rating);
            $note = $it['note'] ?? ($it['catatan_penilai'] ?? null);

            $updateItem = [
                'rating'          => $rating,
                'score'           => $score,
                'note'            => $note,
                'catatan_penilai' => $note,
                'assessor_id'     => $userId,
            ];

            if (isset($it['item_id'])) {
                $this->itemModel->update($it['item_id'], $updateItem);
            } elseif ($indicatorId) {
                $this->itemModel->where('assessment_id', $assessment['id'])
                    ->where('indicator_id', $indicatorId)
                    ->set($updateItem)
                    ->update();
            }

            $calcItems[] = ['rating' => $rating, 'score' => $score];
        }

        // Kalkulasi skor total menggunakan MonevAssessmentService
        $scoreResult = $this->assessmentService->calculateScore($calcItems);

        // Update skor pada header assessment
        $this->assessmentModel->update($assessment['id'], [
            'skor_total'     => $scoreResult['total_score'],
            'kategori_hasil' => $scoreResult['kategori'], // null jika threshold belum dikonfigurasi
            'catatan_umum'   => $ringkasan,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        // Update ringkasan dan kesimpulan pada monev_period jika diberikan
        $periodUpdate = [
            'skor_kinerja_total' => $scoreResult['total_score'],
            'kategori_kinerja'   => $scoreResult['kategori'],
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        if ($ringkasan !== null) {
            $periodUpdate['ringkasan_hasil'] = $ringkasan;
        }
        if ($kesimpulan !== null) {
            $periodUpdate['kesimpulan_rekomendasi'] = $kesimpulan;
        }
        $this->periodModel->update($periodId, $periodUpdate);

        $this->logAudit($periodId, 'ASSESSMENT_SAVED', $userId, $userRole, null, null, [
            'total_score' => $scoreResult['total_score'],
            'persentase'  => $scoreResult['persentase'],
        ]);

        $db->transComplete();

        return [
            'status'      => 'success',
            'total_score' => $scoreResult['total_score'],
            'persentase'  => $scoreResult['persentase'],
            'kategori'    => $scoreResult['kategori'],
        ];
    }

    /**
     * Menambahkan temuan masalah di lapangan (Flow Langkah 11).
     */
    public function addFinding(int $periodId, int $userId, string $userRole, array $findingData): array
    {
        $this->assertCanEdit($periodId, $userRole, $userId);
        return $this->findingService->createFinding($periodId, $userId, $userRole, $findingData);
    }

    /**
     * Menambahkan tindakan perbaikan (Follow-up / Action Item) pada sebuah temuan.
     */
    public function addFollowup(int $findingId, int $userId, string $userRole, array $data): array
    {
        return $this->findingService->createFollowup($findingId, $userId, $userRole, $data);
    }

    /**
     * Memperbarui status tindakan perbaikan (Follow-up).
     */
    public function updateFollowupStatus(int $followupId, int $userId, string $userRole, string $newStatus, ?string $notes = null): array
    {
        return $this->findingService->updateFollowupStatus($followupId, $userId, $userRole, $newStatus, $notes);
    }

    /**
     * Memverifikasi tindakan perbaikan (Follow-up).
     */
    public function verifyFollowup(int $followupId, int $userId, string $userRole, string $decision, ?string $notes = null): array
    {
        return $this->findingService->verifyFollowup($followupId, $userId, $userRole, $decision, $notes);
    }

    /**
     * Memperbarui status Finding secara langsung.
     */
    public function updateFindingStatus(int $findingId, int $userId, string $userRole, string $newStatus, ?string $notes = null): array
    {
        return $this->findingService->updateFindingStatus($findingId, $userId, $userRole, $newStatus, $notes);
    }

    /**
     * Memeriksa kesiapan finalisasi Monev berdasarkan aturan bisnis Step 6.
     */
    public function checkFinalizationReadiness(int $periodId): array
    {
        return $this->findingService->checkFinalizationReadiness($periodId);
    }

    /**
     * Submit hasil Monev oleh Operator/Assessor (Flow Langkah 15).
     * Status beralih dari DRAFT / RETURNED ke SUBMITTED.
     */
    public function submitMonev(int $periodId, int $userId, string $userRole, ?string $catatan = null): array
    {
        $role = strtolower(trim($userRole));
        if ($role === 'ppk') {
            throw new RuntimeException("PPK tidak memiliki wewenang untuk men-submit penilaian Monev.");
        }

        $period = $this->periodModel->find($periodId);
        if (!$period) {
            throw new InvalidArgumentException("Monev Period #{$periodId} tidak ditemukan.");
        }

        if ((int) $period['is_locked'] === 1) {
            throw new RuntimeException("Monev Period telah dikunci (Finalized) dan tidak dapat diubah.");
        }

        if (!in_array($period['status'], [self::STATUS_DRAFT, self::STATUS_RETURNED])) {
            throw new RuntimeException("Monev hanya dapat disubmit dari status DRAFT atau RETURNED. Status saat ini: {$period['status']}.");
        }

        // Pastikan hanya assessor bersangkutan atau admin yang boleh submit
        if ($role !== 'admin' && (int) $period['assessor_id'] !== $userId) {
            throw new RuntimeException("Hanya assessor yang ditugaskan atau Administrator yang dapat men-submit hasil Monev ini.");
        }

        $oldStatus = $period['status'];
        $newStatus = self::STATUS_SUBMITTED;

        $db = \Config\Database::connect();
        $db->transStart();

        $this->periodModel->update($periodId, [
            'status'     => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->approvalModel->insert([
            'monev_period_id' => $periodId,
            'stage'           => 'SUBMISSION',
            'action'          => 'SUBMIT',
            'actor_id'        => $userId,
            'catatan'         => $catatan,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit($periodId, 'MONEV_SUBMITTED', $userId, $userRole, $oldStatus, $newStatus, ['catatan' => $catatan]);

        $db->transComplete();

        return ['period_id' => $periodId, 'status' => $newStatus];
    }

    /**
     * Review Monev oleh Reviewer / Admin (Flow Langkah 16, 17, 18).
     * Keputusan: 'APPROVE' atau 'RETURN'.
     */
    public function reviewMonev(int $periodId, int $userId, string $userRole, string $decision, ?string $catatan = null): array
    {
        $role = strtolower(trim($userRole));
        if ($role === 'ppk') {
            throw new RuntimeException("PPK tidak memiliki wewenang untuk mereview atau menyetujui Monev.");
        }
        if (!in_array($role, self::ALLOWED_REVIEWER_ROLES)) {
            throw new RuntimeException("Role '{$userRole}' tidak memiliki hak otorisasi untuk mereview Monev (khusus Administrator / Reviewer resmi).");
        }

        $period = $this->periodModel->find($periodId);
        if (!$period) {
            throw new InvalidArgumentException("Monev Period #{$periodId} tidak ditemukan.");
        }

        if ((int) $period['is_locked'] === 1) {
            throw new RuntimeException("Monev Period telah dikunci dan tidak dapat diubah.");
        }

        if (!in_array($period['status'], [self::STATUS_SUBMITTED, self::STATUS_IN_REVIEW])) {
            throw new RuntimeException("Monev hanya dapat direview jika berstatus SUBMITTED. Status saat ini: {$period['status']}.");
        }

        $decision = strtoupper(trim($decision));
        if (!in_array($decision, ['APPROVE', 'RETURN'])) {
            throw new InvalidArgumentException("Keputusan review tidak valid. Pilihan: APPROVE, RETURN.");
        }

        $oldStatus = $period['status'];
        $newStatus = ($decision === 'APPROVE') ? self::STATUS_APPROVED : self::STATUS_RETURNED;

        $db = \Config\Database::connect();
        $db->transStart();

        $this->periodModel->update($periodId, [
            'status'        => $newStatus,
            'supervisor_id' => $userId,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->approvalModel->insert([
            'monev_period_id' => $periodId,
            'stage'           => 'REVIEW',
            'action'          => $decision,
            'actor_id'        => $userId,
            'catatan'         => $catatan,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit($periodId, "MONEV_{$decision}D", $userId, $userRole, $oldStatus, $newStatus, ['catatan' => $catatan]);

        $db->transComplete();

        return ['period_id' => $periodId, 'status' => $newStatus, 'decision' => $decision];
    }

    /**
     * Finalisasi Monev (Flow Langkah 19, 20).
     * Hanya Admin. Setelah Finalized, Monev menjadi IMMUTABLE (is_locked = 1).
     */
    public function finalizeMonev(int $periodId, int $userId, string $userRole, ?string $catatan = null): array
    {
        $role = strtolower(trim($userRole));
        if ($role !== 'admin') {
            throw new RuntimeException("Hanya Administrator yang berwenang memfinalisasi Monev.");
        }

        $period = $this->periodModel->find($periodId);
        if (!$period) {
            throw new InvalidArgumentException("Monev Period #{$periodId} tidak ditemukan.");
        }

        if ($period['status'] !== self::STATUS_APPROVED) {
            throw new RuntimeException("Monev hanya dapat difinalisasi jika sudah berstatus APPROVED. Status saat ini: {$period['status']}.");
        }

        // Cek kesiapan finalisasi sesuai aturan bisnis (Step 6)
        $readiness = $this->findingService->checkFinalizationReadiness($periodId);
        if (!$readiness['is_ready']) {
            $blockerMsg = implode("; ", $readiness['blockers']);
            throw new RuntimeException("Finalisasi Monev gagal karena belum memenuhi syarat: {$blockerMsg}");
        }

        $oldStatus = $period['status'];
        $newStatus = self::STATUS_FINALIZED;

        $db = \Config\Database::connect();
        $db->transStart();

        $this->periodModel->update($periodId, [
            'status'              => $newStatus,
            'is_locked'           => 1, // Kunci secara permanen (Immutable)
            'tgl_realisasi_monev' => date('Y-m-d'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        $this->approvalModel->insert([
            'monev_period_id' => $periodId,
            'stage'           => 'FINALIZATION',
            'action'          => 'FINALIZE',
            'actor_id'        => $userId,
            'catatan'         => $catatan,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit($periodId, 'MONEV_FINALIZED', $userId, $userRole, $oldStatus, $newStatus, [
            'catatan'   => $catatan,
            'is_locked' => 1,
        ]);

        $db->transComplete();

        return ['period_id' => $periodId, 'status' => $newStatus, 'is_locked' => 1];
    }

    /**
     * Membuka kembali sesi Monev untuk revisi (Flow Revisi / Reopen).
     * Dapat dijalankan oleh Administrator pada status FINALIZED, APPROVED, atau SUBMITTED.
     */
    public function reopenMonev(int $periodId, int $userId, string $userRole, ?string $alasanRevisi = null): array
    {
        $role = strtolower(trim($userRole));
        if ($role !== 'admin') {
            throw new RuntimeException("Hanya Administrator yang memiliki wewenang untuk membuka kembali / merevisi sesi Monev.");
        }

        $period = $this->periodModel->find($periodId);
        if (!$period) {
            throw new InvalidArgumentException("Monev Period #{$periodId} tidak ditemukan.");
        }

        $oldStatus = $period['status'];
        $newStatus = self::STATUS_RETURNED; // Beralih ke status RETURNED (bisa diedit ulang)

        $db = \Config\Database::connect();
        $db->transStart();

        $this->periodModel->update($periodId, [
            'status'     => $newStatus,
            'is_locked'  => 0, // Buka kunci (editable)
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->approvalModel->insert([
            'monev_period_id' => $periodId,
            'stage'           => 'REVISION',
            'action'          => 'REOPEN_FOR_REVISION',
            'actor_id'        => $userId,
            'catatan'         => $alasanRevisi ?: 'Sesi Monev dibuka kembali untuk revisi oleh Administrator.',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit($periodId, 'MONEV_REOPENED_FOR_REVISION', $userId, $userRole, $oldStatus, $newStatus, [
            'alasan_revisi' => $alasanRevisi,
            'is_locked'     => 0,
        ]);

        $db->transComplete();

        return [
            'period_id' => $periodId,
            'status'    => $newStatus,
            'is_locked' => 0,
            'message'   => 'Sesi Monev berhasil dibuka kunci untuk revisi.',
        ];
    }

    /**
     * Helper validasi hak edit untuk melindungi immutability & permission backend.
     */
    public function assertCanEdit(int $periodId, string $userRole, int $userId): array
    {
        $role = strtolower(trim($userRole));
        if ($role === 'ppk') {
            throw new RuntimeException("Akses Ditolak: PPK tidak diizinkan mengubah atau mengisi lembar Monev.");
        }

        $period = $this->periodModel->find($periodId);
        if (!$period) {
            throw new InvalidArgumentException("Monev Period #{$periodId} tidak ditemukan.");
        }

        // Aturan Immutability
        if ((int) $period['is_locked'] === 1 || $period['status'] === self::STATUS_FINALIZED) {
            throw new RuntimeException("Monev Period telah dikunci secara permanen (FINALIZED / IMMUTABLE). Perubahan data tidak diizinkan.");
        }

        // Hanya boleh diedit pada status DRAFT atau RETURNED
        if (!in_array($period['status'], [self::STATUS_DRAFT, self::STATUS_RETURNED])) {
            throw new RuntimeException("Monev dalam status '{$period['status']}' sedang menunggu review dan tidak dapat diedit saat ini.");
        }

        // Assessor yang ditugaskan atau Admin/Operator yang berwenang
        if (!in_array($role, self::ALLOWED_ASSESSOR_ROLES)) {
            throw new RuntimeException("Role '{$userRole}' tidak berwenang mengedit Monev ini. Hanya Admin atau Operator yang diizinkan.");
        }

        return $period;
    }

    /**
     * Helper mencatat audit log.
     */
    protected function logAudit(int $periodId, string $action, int $actorId, string $actorRole, ?string $oldStatus, ?string $newStatus, array $payload = []): void
    {
        $request = service('request');
        $ip = $request instanceof \CodeIgniter\HTTP\CLIRequest ? '127.0.0.1' : $request->getIPAddress();
        $ua = $request instanceof \CodeIgniter\HTTP\CLIRequest ? 'CLI' : substr((string) $request->getUserAgent(), 0, 250);

        $this->auditLogModel->insert([
            'monev_period_id' => $periodId,
            'action'          => $action,
            'actor_id'        => $actorId,
            'actor_role'      => $actorRole,
            'old_status'      => $oldStatus,
            'new_status'      => $newStatus,
            'changes_payload' => json_encode($payload),
            'ip_address'      => $ip,
            'user_agent'      => $ua,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}

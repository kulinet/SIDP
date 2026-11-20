<?php

namespace App\Services;

use App\Models\MonevFindingModel;
use App\Models\MonevFollowupModel;
use App\Models\MonevPeriodModel;
use App\Models\MonevAssessmentModel;
use App\Models\MonevAssessmentItemModel;
use App\Models\MonevEvidenceModel;
use App\Models\MonevAuditLogModel;
use InvalidArgumentException;
use RuntimeException;

class MonevFindingService
{
    protected MonevFindingModel $findingModel;
    protected MonevFollowupModel $followupModel;
    protected MonevPeriodModel $periodModel;
    protected MonevAssessmentModel $assessmentModel;
    protected MonevAssessmentItemModel $assessmentItemModel;
    protected MonevEvidenceModel $evidenceModel;
    protected MonevAuditLogModel $auditModel;

    public function __construct()
    {
        $this->findingModel         = new MonevFindingModel();
        $this->followupModel        = new MonevFollowupModel();
        $this->periodModel          = new MonevPeriodModel();
        $this->assessmentModel      = new MonevAssessmentModel();
        $this->assessmentItemModel  = new MonevAssessmentItemModel();
        $this->evidenceModel        = new MonevEvidenceModel();
        $this->auditModel           = new MonevAuditLogModel();
    }

    /**
     * Standardize severity to LOW, MEDIUM, HIGH, CRITICAL.
     */
    public function normalizeSeverity(string $severity): string
    {
        $s = strtoupper(trim($severity));
        $map = [
            'RENDAH'   => MonevFindingModel::SEVERITY_LOW,
            'SEDANG'   => MonevFindingModel::SEVERITY_MEDIUM,
            'TINGGI'   => MonevFindingModel::SEVERITY_HIGH,
            'KRITIS'   => MonevFindingModel::SEVERITY_CRITICAL,
            'LOW'      => MonevFindingModel::SEVERITY_LOW,
            'MEDIUM'   => MonevFindingModel::SEVERITY_MEDIUM,
            'HIGH'     => MonevFindingModel::SEVERITY_HIGH,
            'CRITICAL' => MonevFindingModel::SEVERITY_CRITICAL,
        ];

        if (!isset($map[$s])) {
            throw new InvalidArgumentException("Tingkat keparahan (severity) '{$severity}' tidak valid. Pilihan: LOW, MEDIUM, HIGH, CRITICAL.");
        }

        return $map[$s];
    }

    /**
     * Membuat Temuan (Finding) baru pada sesi Monev.
     */
    public function createFinding(int $periodId, int $userId, string $userRole, array $data): array
    {
        $period = $this->periodModel->find($periodId);
        if (!$period) {
            throw new InvalidArgumentException("Monev Period #{$periodId} tidak ditemukan.");
        }

        if ((int) $period['is_locked'] === 1) {
            throw new RuntimeException("Monev Period telah dikunci dan tidak dapat ditambah temuan.");
        }

        $description = trim($data['description'] ?? ($data['deskripsi_temuan'] ?? ''));
        if (empty($description)) {
            throw new InvalidArgumentException("Deskripsi temuan (description) wajib diisi.");
        }

        $title = trim($data['title'] ?? ($data['judul_temuan'] ?? ''));
        if (empty($title)) {
            $title = mb_substr($description, 0, 80);
        }

        $category = trim($data['category'] ?? ($data['kategori_temuan'] ?? 'TEKNIS_KONSTRUKSI'));
        $severity = $this->normalizeSeverity($data['severity'] ?? ($data['tingkat_keparahan'] ?? 'MEDIUM'));
        $recommendation = trim($data['recommendation'] ?? ($data['rekomendasi_tindakan'] ?? ''));
        $responsibleParty = trim($data['responsible_party'] ?? ($data['pihak_bertanggung_jawab'] ?? 'Penyedia Jasa Konstruksi'));
        $dueDate = !empty($data['due_date']) ? date('Y-m-d', strtotime($data['due_date'])) : (!empty($data['batas_waktu_penyelesaian']) ? date('Y-m-d', strtotime($data['batas_waktu_penyelesaian'])) : date('Y-m-d', strtotime('+7 days')));

        $count = $this->findingModel->where('monev_period_id', $periodId)->countAllResults();
        $kodeTemuan = $data['kode_temuan'] ?? sprintf("TMN-%d-%02d", $periodId, $count + 1);

        $payload = [
            'monev_period_id'          => $periodId,
            'kode_temuan'              => $kodeTemuan,
            'title'                    => $title,
            'category'                 => $category,
            'kategori_temuan'          => in_array($category, ['TEKNIS_KONSTRUKSI', 'KETERLAMBATAN_WAKTU', 'K3_LINGKUNGAN', 'ADMINISTRATIF', 'LAINNYA']) ? $category : 'LAINNYA',
            'severity'                 => $severity,
            'tingkat_keparahan'        => $this->mapSeverityToIndonesian($severity),
            'deskripsi_temuan'         => $description,
            'lokasi_spesifik'          => $data['lokasi_spesifik'] ?? null,
            'rekomendasi_tindakan'     => !empty($recommendation) ? $recommendation : '-',
            'responsible_party'        => !empty($responsibleParty) ? $responsibleParty : 'Penyedia Jasa Konstruksi',
            'due_date'                 => $dueDate,
            'batas_waktu_penyelesaian' => $dueDate,
            'status'                   => MonevFindingModel::STATUS_OPEN,
            'status_temuan'            => 'OPEN',
            'created_by'               => $userId,
            'created_at'               => date('Y-m-d H:i:s'),
            'updated_at'               => date('Y-m-d H:i:s'),
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $this->findingModel->insert($payload);
        $findingId = (int) $this->findingModel->getInsertID();

        // Simpan evidence jika ada dalam request
        if (!empty($data['evidence'])) {
            $this->attachEvidence('MONEV_FINDING', $findingId, $userId, $data['evidence']);
        }

        // Catat Audit Log
        $this->logAudit($periodId, 'FINDING_CREATED', $userId, $userRole, null, MonevFindingModel::STATUS_OPEN, [
            'finding_id'        => $findingId,
            'kode_temuan'       => $kodeTemuan,
            'title'             => $title,
            'severity'          => $severity,
            'responsible_party' => $responsibleParty,
            'due_date'          => $dueDate,
        ]);

        $db->transComplete();

        return [
            'finding_id'        => $findingId,
            'kode_temuan'       => $kodeTemuan,
            'title'             => $title,
            'severity'          => $severity,
            'status'            => MonevFindingModel::STATUS_OPEN,
            'responsible_party' => $responsibleParty,
            'due_date'          => $dueDate,
        ];
    }

    /**
     * Memperbarui status Finding dengan audit trail wajib.
     */
    public function updateFindingStatus(int $findingId, int $userId, string $userRole, string $newStatus, ?string $notes = null): array
    {
        $finding = $this->findingModel->find($findingId);
        if (!$finding) {
            throw new InvalidArgumentException("Finding #{$findingId} tidak ditemukan.");
        }

        $period = $this->periodModel->find($finding['monev_period_id']);
        if ($period && (int) $period['is_locked'] === 1) {
            throw new RuntimeException("Monev Period telah dikunci dan tidak dapat diubah status temuannya.");
        }

        $status = strtoupper(trim($newStatus));
        if (!in_array($status, MonevFindingModel::VALID_STATUSES)) {
            throw new InvalidArgumentException("Status finding '{$newStatus}' tidak valid. Pilihan: " . implode(', ', MonevFindingModel::VALID_STATUSES));
        }

        $oldStatus = $finding['status'] ?? $finding['status_temuan'];

        $updateData = [
            'status'        => $status,
            'status_temuan' => in_array($status, ['OPEN', 'IN_PROGRESS', 'RESOLVED']) ? $status : ($status === 'VERIFIED' ? 'RESOLVED' : 'RESOLVED'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if (in_array($status, [MonevFindingModel::STATUS_RESOLVED, MonevFindingModel::STATUS_VERIFIED, MonevFindingModel::STATUS_CLOSED])) {
            $updateData['resolved_at'] = date('Y-m-d H:i:s');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->findingModel->update($findingId, $updateData);

        // Catat Audit Log
        $this->logAudit((int) $finding['monev_period_id'], 'FINDING_STATUS_CHANGED', $userId, $userRole, $oldStatus, $status, [
            'finding_id'  => $findingId,
            'kode_temuan' => $finding['kode_temuan'],
            'notes'       => $notes,
        ]);

        $db->transComplete();

        return [
            'finding_id' => $findingId,
            'old_status' => $oldStatus,
            'new_status' => $status,
        ];
    }

    /**
     * Menambahkan Follow-up / Action Item pada sebuah Finding.
     */
    public function createFollowup(int $findingId, int $userId, string $userRole, array $data): array
    {
        $finding = $this->findingModel->find($findingId);
        if (!$finding) {
            throw new InvalidArgumentException("Finding #{$findingId} tidak ditemukan.");
        }

        $period = $this->periodModel->find($finding['monev_period_id']);
        if ($period && (int) $period['is_locked'] === 1) {
            throw new RuntimeException("Monev Period telah dikunci dan tidak dapat ditambah follow-up.");
        }

        $action = trim($data['action'] ?? ($data['deskripsi_perbaikan'] ?? ''));
        if (empty($action)) {
            throw new InvalidArgumentException("Tindakan perbaikan (action) wajib diisi.");
        }

        $pic = trim($data['pic'] ?? ($data['responsible_party'] ?? $finding['responsible_party']));
        $deadline = !empty($data['deadline']) ? date('Y-m-d', strtotime($data['deadline'])) : (!empty($data['tgl_tindak_lanjut']) ? date('Y-m-d', strtotime($data['tgl_tindak_lanjut'])) : date('Y-m-d', strtotime('+3 days')));
        $status = strtoupper(trim($data['status'] ?? MonevFollowupModel::STATUS_OPEN));
        if (!in_array($status, MonevFollowupModel::VALID_STATUSES)) {
            $status = MonevFollowupModel::STATUS_OPEN;
        }

        $payload = [
            'finding_id'          => $findingId,
            'action'              => $action,
            'action_by_user_id'   => $userId,
            'pic'                 => $pic,
            'deadline'            => $deadline,
            'tgl_tindak_lanjut'   => $deadline,
            'deskripsi_perbaikan' => $action,
            'status'              => $status,
            'status_verifikasi'   => 'DIAJUKAN',
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $this->followupModel->insert($payload);
        $followupId = (int) $this->followupModel->getInsertID();

        // Jika finding status masih OPEN, otomatis beralih ke IN_PROGRESS
        $oldFindingStatus = $finding['status'] ?? $finding['status_temuan'];
        if ($oldFindingStatus === MonevFindingModel::STATUS_OPEN) {
            $this->findingModel->update($findingId, [
                'status'        => MonevFindingModel::STATUS_IN_PROGRESS,
                'status_temuan' => 'IN_PROGRESS',
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->logAudit((int) $finding['monev_period_id'], 'FINDING_STATUS_CHANGED', $userId, $userRole, $oldFindingStatus, MonevFindingModel::STATUS_IN_PROGRESS, [
                'finding_id' => $findingId,
                'reason'     => 'Follow-up created',
            ]);
        }

        // Simpan evidence jika ada
        if (!empty($data['evidence'])) {
            $this->attachEvidence('MONEV_FOLLOWUP', $followupId, $userId, $data['evidence']);
        }

        // Catat Audit Log
        $this->logAudit((int) $finding['monev_period_id'], 'FOLLOWUP_CREATED', $userId, $userRole, null, $status, [
            'finding_id'  => $findingId,
            'followup_id' => $followupId,
            'action'      => $action,
            'pic'         => $pic,
            'deadline'    => $deadline,
        ]);

        $db->transComplete();

        return [
            'followup_id' => $followupId,
            'finding_id'  => $findingId,
            'action'      => $action,
            'pic'         => $pic,
            'deadline'    => $deadline,
            'status'      => $status,
        ];
    }

    /**
     * Memperbarui status Follow-up / Action Item dengan audit log.
     */
    public function updateFollowupStatus(int $followupId, int $userId, string $userRole, string $newStatus, ?string $notes = null): array
    {
        $followup = $this->followupModel->find($followupId);
        if (!$followup) {
            throw new InvalidArgumentException("Follow-up #{$followupId} tidak ditemukan.");
        }

        $finding = $this->findingModel->find($followup['finding_id']);
        $periodId = (int) ($finding['monev_period_id'] ?? 0);

        $status = strtoupper(trim($newStatus));
        if (!in_array($status, MonevFollowupModel::VALID_STATUSES)) {
            throw new InvalidArgumentException("Status follow-up '{$newStatus}' tidak valid. Pilihan: " . implode(', ', MonevFollowupModel::VALID_STATUSES));
        }

        $oldStatus = $followup['status'];

        $db = \Config\Database::connect();
        $db->transStart();

        $this->followupModel->update($followupId, [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logAudit($periodId, 'FOLLOWUP_STATUS_CHANGED', $userId, $userRole, $oldStatus, $status, [
            'followup_id' => $followupId,
            'finding_id'  => $followup['finding_id'],
            'notes'       => $notes,
        ]);

        $db->transComplete();

        return [
            'followup_id' => $followupId,
            'old_status'  => $oldStatus,
            'new_status'  => $status,
        ];
    }

    /**
     * Memverifikasi Follow-up / Action Item oleh Assessor/Reviewer/Admin.
     */
    public function verifyFollowup(int $followupId, int $verifierUserId, string $verifierRole, string $decision, ?string $verificationNotes = null): array
    {
        $role = strtolower(trim($verifierRole));
        if ($role === 'ppk') {
            throw new RuntimeException("PPK tidak memiliki wewenang untuk memverifikasi tindakan perbaikan (Follow-up) Monev.");
        }

        $followup = $this->followupModel->find($followupId);
        if (!$followup) {
            throw new InvalidArgumentException("Follow-up #{$followupId} tidak ditemukan.");
        }

        $finding = $this->findingModel->find($followup['finding_id']);
        $periodId = (int) ($finding['monev_period_id'] ?? 0);

        $dec = strtoupper(trim($decision));
        if (!in_array($dec, ['APPROVE', 'REJECT', 'VERIFY'])) {
            throw new InvalidArgumentException("Keputusan verifikasi tidak valid. Pilihan: APPROVE, REJECT.");
        }

        $isApproved = in_array($dec, ['APPROVE', 'VERIFY']);
        $newStatus = $isApproved ? MonevFollowupModel::STATUS_VERIFIED : MonevFollowupModel::STATUS_IN_PROGRESS;
        $verifStatus = $isApproved ? 'DITERIMA' : 'DITOLAK';

        $db = \Config\Database::connect();
        $db->transStart();

        $this->followupModel->update($followupId, [
            'status'                      => $newStatus,
            'status_verifikasi'           => $verifStatus,
            'catatan_verifikasi_assessor' => $verificationNotes,
            'verification_notes'          => $verificationNotes,
            'verified_by_user_id'         => $verifierUserId,
            'verified_at'                 => date('Y-m-d H:i:s'),
            'updated_at'                  => date('Y-m-d H:i:s'),
        ]);

        // Cek jika seluruh followup pada finding ini telah diverifikasi
        $unverifiedCount = $this->followupModel->where('finding_id', $followup['finding_id'])
            ->where('status !=', MonevFollowupModel::STATUS_VERIFIED)
            ->where('status !=', MonevFollowupModel::STATUS_CANCELLED)
            ->countAllResults();

        if ($isApproved && $unverifiedCount === 0) {
            $this->findingModel->update($followup['finding_id'], [
                'status'        => MonevFindingModel::STATUS_VERIFIED,
                'status_temuan' => 'RESOLVED',
                'resolved_at'   => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            $oldStatusForLog = $finding['status'] ?? ($finding['status_temuan'] ?? 'OPEN');
            $this->logAudit($periodId, 'FINDING_STATUS_CHANGED', $verifierUserId, $verifierRole, $oldStatusForLog, MonevFindingModel::STATUS_VERIFIED, [
                'finding_id' => $followup['finding_id'],
                'reason'     => 'All follow-up actions verified',
            ]);
        }

        $this->logAudit($periodId, 'FOLLOWUP_VERIFIED', $verifierUserId, $verifierRole, $followup['status'], $newStatus, [
            'followup_id'        => $followupId,
            'finding_id'         => $followup['finding_id'],
            'decision'           => $dec,
            'verification_notes' => $verificationNotes,
        ]);

        $db->transComplete();

        return [
            'followup_id' => $followupId,
            'status'      => $newStatus,
            'decision'    => $dec,
        ];
    }

    /**
     * Memeriksa kesiapan finalisasi Monev (Readiness Check):
     *
     * Monev dapat FINALIZED hanya jika:
     * 1. Semua mandatory assessment selesai (semua 12 indikator terisi nilai).
     * 2. Kesimpulan tersedia.
     * 3. Approval selesai (status APPROVED).
     * 4. Mandatory findings/follow-up requirement terpenuhi (temuan HIGH/CRITICAL tidak boleh OPEN).
     *
     * Aturan Bisnis:
     * - Jangan memblokir finalisasi hanya karena finding optional (LOW / MEDIUM) masih OPEN.
     */
    public function checkFinalizationReadiness(int $periodId): array
    {
        $period = $this->periodModel->find($periodId);
        if (!$period) {
            throw new InvalidArgumentException("Monev Period #{$periodId} tidak ditemukan.");
        }

        $blockers = [];
        $reasons = [];

        // 1. Cek status approval
        if ($period['status'] !== MonevWorkflowService::STATUS_APPROVED) {
            $blockers[] = "Status Monev belum APPROVED (status saat ini: {$period['status']}).";
        } else {
            $reasons[] = "Status Monev telah APPROVED.";
        }

        // 2. Cek mandatory assessment (12 indikator)
        $assessment = $this->assessmentModel->where('monev_period_id', $periodId)->first();
        if (!$assessment) {
            $blockers[] = "Lembar penilaian kinerja (Assessment) belum dibuat.";
        } else {
            $filledItemsCount = $this->assessmentItemModel->where('assessment_id', $assessment['id'])
                ->where('score IS NOT NULL')
                ->where('score >', 0)
                ->countAllResults();

            if ($filledItemsCount < 12) {
                $blockers[] = "Penilaian mandatory belum lengkap (baru {$filledItemsCount} dari 12 indikator terisi).";
            } else {
                $reasons[] = "Seluruh 12 indikator mandatory telah dinilai lengkap.";
            }

            // 3. Cek kesimpulan
            $kesimpulan = trim($assessment['kesimpulan_rekomendasi'] ?? ($period['kesimpulan_rekomendasi'] ?? ''));
            if (empty($kesimpulan)) {
                $blockers[] = "Kesimpulan dan rekomendasi penilaian akhir belum diisi.";
            } else {
                $reasons[] = "Kesimpulan dan rekomendasi penilaian telah tersedia.";
            }
        }

        // 4. Cek mandatory findings (CRITICAL & HIGH severity)
        $criticalOrHighFindings = $this->findingModel->where('monev_period_id', $periodId)
            ->whereIn('severity', [MonevFindingModel::SEVERITY_CRITICAL, MonevFindingModel::SEVERITY_HIGH])
            ->findAll();

        $openCriticalCount = 0;
        foreach ($criticalOrHighFindings as $f) {
            $status = $f['status'] ?? $f['status_temuan'];
            if (in_array($status, [MonevFindingModel::STATUS_OPEN, MonevFindingModel::STATUS_IN_PROGRESS])) {
                $openCriticalCount++;
                $blockers[] = "Temuan berdampak kritis/tinggi [{$f['kode_temuan']}: {$f['title']}] berstatus '{$status}' dan wajib diselesaikan atau diverifikasi sebelum finalisasi.";
            }
        }

        if ($openCriticalCount === 0) {
            $reasons[] = "Seluruh temuan berkategori kritis dan tinggi (CRITICAL / HIGH) telah terselesaikan/terverifikasi.";
        }

        // Hitung statistik finding optional (LOW / MEDIUM) yang masih OPEN (tidak memblokir)
        $optionalOpenFindings = $this->findingModel->where('monev_period_id', $periodId)
            ->whereIn('severity', [MonevFindingModel::SEVERITY_LOW, MonevFindingModel::SEVERITY_MEDIUM])
            ->whereIn('status', [MonevFindingModel::STATUS_OPEN, MonevFindingModel::STATUS_IN_PROGRESS])
            ->findAll();

        $isReady = empty($blockers);

        return [
            'is_ready'              => $isReady,
            'blockers'              => $blockers,
            'reasons'               => $reasons,
            'optional_open_count'   => count($optionalOpenFindings),
            'optional_open_message' => count($optionalOpenFindings) > 0 
                ? "Terdapat " . count($optionalOpenFindings) . " temuan kategori LOW/MEDIUM yang masih berjalan namun tidak memblokir finalisasi resmi."
                : null,
        ];
    }

    /**
     * Melampirkan evidence pada finding atau follow-up.
     */
    public function attachEvidence(string $entityType, int $entityId, int $userId, array $evidenceData): int
    {
        $payload = [
            'entity_type'     => $entityType, // MONEV_FINDING / MONEV_FOLLOWUP
            'entity_id'       => $entityId,
            'jenis_eviden'    => $evidenceData['jenis_eviden'] ?? 'FOTO_LAPANGAN',
            'file_path'       => $evidenceData['file_path'] ?? 'uploads/evidence/default.jpg',
            'file_name'       => $evidenceData['file_name'] ?? basename($evidenceData['file_path'] ?? 'default.jpg'),
            'file_size'       => $evidenceData['file_size'] ?? 1024,
            'mime_type'       => $evidenceData['mime_type'] ?? 'image/jpeg',
            'file_hash'       => $evidenceData['file_hash'] ?? null,
            'gps_lat'         => $evidenceData['gps_lat'] ?? null,
            'gps_lng'         => $evidenceData['gps_lng'] ?? null,
            'tgl_pengambilan' => $evidenceData['tgl_pengambilan'] ?? date('Y-m-d H:i:s'),
            'keterangan'      => $evidenceData['keterangan'] ?? null,
            'uploaded_by'     => $userId,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        $this->evidenceModel->insert($payload);
        return (int) $this->evidenceModel->getInsertID();
    }

    /**
     * Map english severity to existing indonesian enum.
     */
    protected function mapSeverityToIndonesian(string $severity): string
    {
        return match ($severity) {
            MonevFindingModel::SEVERITY_LOW      => 'RENDAH',
            MonevFindingModel::SEVERITY_HIGH     => 'TINGGI',
            MonevFindingModel::SEVERITY_CRITICAL => 'KRITIS',
            default                              => 'SEDANG',
        };
    }

    /**
     * Log ke tabel monev_audit_log.
     */
    protected function logAudit(?int $periodId, string $action, int $actorId, string $actorRole, ?string $oldStatus, ?string $newStatus, array $payload = []): void
    {
        $req = service('request');
        $ip = $req->getIPAddress() ?? '127.0.0.1';
        $rawUa = (string) ($req->getUserAgent() ?? 'CLI/Test');

        $this->auditModel->insert([
            'monev_period_id' => $periodId,
            'action'          => $action,
            'actor_id'        => $actorId,
            'actor_role'      => $actorRole,
            'old_status'      => $oldStatus,
            'new_status'      => $newStatus,
            'changes_payload' => json_encode($payload),
            'ip_address'      => substr($ip, 0, 45),
            'user_agent'      => substr($rawUa, 0, 250),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevPeriodModel extends Model
{
    protected $table            = 'monev_period';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'proyek_id',
        'nomor_monev',
        'jenis_monev',
        'contract_snapshot',
        'progress_snapshot',
        'periode_ke',
        'tgl_rencana_monev',
        'tgl_realisasi_monev',
        'assessor_id',
        'supervisor_id',
        'status',
        'skor_kinerja_total',
        'kategori_kinerja',
        'ringkasan_hasil',
        'kesimpulan_rekomendasi',
        'final_document_path',
        'is_locked',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Mengambil detail Monev Period lengkap dengan assessment, visit, temuan, dan approval trail.
     */
    public function getFullPeriodDetails(int $periodId): ?array
    {
        $period = $this->select('monev_period.*, users.nama_lengkap as nama_assessor, users.username as username_assessor')
            ->join('users', 'users.id = monev_period.assessor_id', 'left')
            ->where('monev_period.id', $periodId)
            ->first();

        if (!$period) {
            return null;
        }

        if (!empty($period['contract_snapshot']) && is_string($period['contract_snapshot'])) {
            $period['contract_snapshot'] = json_decode($period['contract_snapshot'], true);
        }
        if (!empty($period['progress_snapshot']) && is_string($period['progress_snapshot'])) {
            $period['progress_snapshot'] = json_decode($period['progress_snapshot'], true);
        }

        $db = \Config\Database::connect();

        // Kunjungan
        $period['visit'] = $db->table('monev_visit')
            ->where('monev_period_id', $periodId)
            ->get()
            ->getRowArray();

        // Assessment
        $assessment = $db->table('monev_assessment')
            ->where('monev_period_id', $periodId)
            ->get()
            ->getRowArray();

        if ($assessment) {
            $assessment['items'] = $db->table('monev_assessment_item')
                ->where('assessment_id', $assessment['id'])
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }
        $period['assessment'] = $assessment;

        // Findings
        $period['findings'] = $db->table('monev_finding')
            ->where('monev_period_id', $periodId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // Approvals Trail
        $period['approvals'] = $db->table('monev_approval')
            ->select('monev_approval.*, users.nama_lengkap as nama_actor, users.role as role_actor')
            ->join('users', 'users.id = monev_approval.actor_id', 'left')
            ->where('monev_period_id', $periodId)
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        // Evidences
        $period['evidences'] = $db->table('monev_evidence')
            ->select('monev_evidence.*, users.nama_lengkap as nama_uploader')
            ->join('users', 'users.id = monev_evidence.uploaded_by', 'left')
            ->where('entity_id', $periodId)
            ->get()
            ->getResultArray();

        return $period;
    }
}

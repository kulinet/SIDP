<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevFindingModel extends Model
{
    protected $table            = 'monev_finding';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'monev_period_id',
        'kode_temuan',
        'title',
        'category',
        'kategori_temuan',
        'severity',
        'tingkat_keparahan',
        'deskripsi_temuan',
        'lokasi_spesifik',
        'rekomendasi_tindakan',
        'responsible_party',
        'due_date',
        'batas_waktu_penyelesaian',
        'status',
        'status_temuan',
        'resolved_at',
        'created_by',
    ];

    public const SEVERITY_LOW      = 'LOW';
    public const SEVERITY_MEDIUM   = 'MEDIUM';
    public const SEVERITY_HIGH     = 'HIGH';
    public const SEVERITY_CRITICAL = 'CRITICAL';

    public const STATUS_OPEN        = 'OPEN';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_RESOLVED    = 'RESOLVED';
    public const STATUS_VERIFIED    = 'VERIFIED';
    public const STATUS_CLOSED      = 'CLOSED';

    public const VALID_SEVERITIES = [
        self::SEVERITY_LOW,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_HIGH,
        self::SEVERITY_CRITICAL,
    ];

    public const VALID_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED,
        self::STATUS_VERIFIED,
        self::STATUS_CLOSED,
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Mengambil detail temuan lengkap dengan follow-up dan evidence attachments.
     */
    public function getFindingWithDetails(int $findingId): ?array
    {
        $db = \Config\Database::connect();
        $finding = $this->select('monev_finding.*, users.nama_lengkap as creator_nama, users.role as creator_role')
            ->join('users', 'users.id = monev_finding.created_by', 'left')
            ->where('monev_finding.id', $findingId)
            ->first();

        if (!$finding) {
            return null;
        }

        // Ambil follow-up
        $finding['followups'] = $db->table('monev_followup')
            ->select('monev_followup.*, u1.nama_lengkap as action_by_nama, u2.nama_lengkap as verifier_nama')
            ->join('users u1', 'u1.id = monev_followup.action_by_user_id', 'left')
            ->join('users u2', 'u2.id = monev_followup.verified_by_user_id', 'left')
            ->where('monev_followup.finding_id', $findingId)
            ->orderBy('monev_followup.created_at', 'ASC')
            ->get()
            ->getResultArray();

        // Ambil follow-up evidence untuk tiap follow-up
        foreach ($finding['followups'] as &$fup) {
            $fup['evidence'] = $db->table('monev_evidence')
                ->where('entity_type', 'MONEV_FOLLOWUP')
                ->where('entity_id', $fup['id'])
                ->get()
                ->getResultArray();
        }

        // Ambil finding evidence
        $finding['evidence'] = $db->table('monev_evidence')
            ->where('entity_type', 'MONEV_FINDING')
            ->where('entity_id', $findingId)
            ->get()
            ->getResultArray();

        return $finding;
    }
}

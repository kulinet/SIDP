<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevFollowupModel extends Model
{
    protected $table            = 'monev_followup';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'finding_id',
        'action',
        'action_by_user_id',
        'pic',
        'deadline',
        'tgl_tindak_lanjut',
        'deskripsi_perbaikan',
        'status',
        'status_verifikasi',
        'catatan_verifikasi_assessor',
        'verification_notes',
        'verified_by_user_id',
        'verified_at',
    ];

    public const STATUS_OPEN        = 'OPEN';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED   = 'COMPLETED';
    public const STATUS_VERIFIED    = 'VERIFIED';
    public const STATUS_OVERDUE     = 'OVERDUE';
    public const STATUS_CANCELLED   = 'CANCELLED';

    public const VALID_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_VERIFIED,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

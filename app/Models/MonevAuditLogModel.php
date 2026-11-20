<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevAuditLogModel extends Model
{
    protected $table            = 'monev_audit_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'monev_period_id',
        'action',
        'actor_id',
        'actor_role',
        'old_status',
        'new_status',
        'changes_payload',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $useTimestamps = false;
}

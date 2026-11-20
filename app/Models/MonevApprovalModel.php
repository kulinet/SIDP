<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevApprovalModel extends Model
{
    protected $table            = 'monev_approval';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'monev_period_id',
        'stage',
        'action',
        'actor_id',
        'catatan',
        'signature_hash',
    ];

    protected $useTimestamps = false;
}

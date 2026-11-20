<?php

namespace App\Models;

use CodeIgniter\Model;

class UserLogModel extends Model
{
    protected $table            = 'user_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'user_id', 
        'username', 
        'role', 
        'action', 
        'ip_address', 
        'user_agent', 
        'created_at'
    ];

    // Dates
    protected $useTimestamps = false; // We manage created_at manually or via allowedFields if needed
}

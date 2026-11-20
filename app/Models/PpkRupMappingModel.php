<?php

namespace App\Models;

use CodeIgniter\Model;

class PpkRupMappingModel extends Model
{
    protected $table            = 'ppk_rup_mappings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'kd_paket', 'type'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'user_id'  => 'required|integer',
        'kd_paket' => 'required|alpha_numeric_space|min_length[3]|max_length[100]',
        'type'     => 'required|alpha_numeric_space|max_length[50]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}

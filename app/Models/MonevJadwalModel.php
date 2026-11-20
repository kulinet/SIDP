<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevJadwalModel extends Model
{
    protected $table            = 'monev_jadwal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'proyek_id', 'item_pekerjaan', 'bobot', 'target_mingguan'
    ];

    // Dates
    protected $useTimestamps = false;
    
    // Validation
    protected $validationRules      = [
        'proyek_id'      => 'required|integer',
        'item_pekerjaan' => 'required|min_length[2]|max_length[255]',
        'bobot'          => 'required|decimal',
        'target_mingguan'=> 'permit_empty'
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevAssessmentAspectModel extends Model
{
    protected $table            = 'monev_assessment_aspects';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'template_id',
        'urutan',
        'kode_aspek',
        'nama_aspek',
        'bobot_persen',
        'deskripsi',
    ];

    protected $useTimestamps = false;
}

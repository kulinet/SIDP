<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevAssessmentModel extends Model
{
    protected $table            = 'monev_assessment';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'monev_period_id',
        'template_id',
        'template_version_snapshot',
        'skor_total',
        'kategori_hasil',
        'catatan_umum',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

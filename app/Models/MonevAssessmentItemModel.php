<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevAssessmentItemModel extends Model
{
    protected $table            = 'monev_assessment_item';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'assessment_id',
        'indicator_id',
        'aspek_kode_snapshot',
        'aspek_nama_snapshot',
        'indikator_kode_snapshot',
        'indikator_nama_snapshot',
        'bobot_persen_snapshot',
        'rating',
        'score',
        'evidence_requirement',
        'catatan_penilai',
        'note',
        'assessor_id',
    ];

    protected $useTimestamps = false;
}

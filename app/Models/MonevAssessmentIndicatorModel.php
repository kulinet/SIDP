<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevAssessmentIndicatorModel extends Model
{
    protected $table            = 'monev_assessment_indicators';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'aspect_id',
        'urutan',
        'kode_indikator',
        'nama_indikator',
        'deskripsi_kriteria',
        'panduan_nilai_1',
        'panduan_nilai_2',
        'panduan_nilai_3',
        'evidence_requirement',
    ];

    protected $useTimestamps = false;
}

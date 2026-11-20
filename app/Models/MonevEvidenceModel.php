<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevEvidenceModel extends Model
{
    protected $table            = 'monev_evidence';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'entity_type',
        'entity_id',
        'jenis_eviden',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'file_hash',
        'gps_lat',
        'gps_lng',
        'tgl_pengambilan',
        'keterangan',
        'uploaded_by',
    ];

    protected $useTimestamps = false;
}

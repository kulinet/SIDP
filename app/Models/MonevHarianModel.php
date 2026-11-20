<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevHarianModel extends Model
{
    protected $table            = 'monev_harian';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'proyek_id', 'item_id', 'tanggal', 'volume_selesai', 'satuan',
        'lokasi', 'tenaga_kerja', 'peralatan', 'kendala', 'keterangan',
        'foto_awal', 'foto_proses', 'foto_hasil', 'progres_persen',
        'is_verified', 'verified_at'
    ];

    // Dates
    protected $useTimestamps = false;
    
    // Validation
    protected $validationRules      = [
        'proyek_id'      => 'required|integer',
        'item_id'        => 'required|integer',
        'tanggal'        => 'required|valid_date[Y-m-d]',
        'volume_selesai' => 'required|decimal',
        'satuan'         => 'required|max_length[50]',
        'lokasi'         => 'required|max_length[255]',
        'progres_persen' => 'required|decimal',
        'is_verified'    => 'permit_empty|integer'
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}

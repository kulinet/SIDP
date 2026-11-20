<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevDokumenModel extends Model
{
    protected $table            = 'monev_dokumen';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'proyek_id', 'nama_dokumen', 'kategori', 'file_path'
    ];

    // Dates
    protected $useTimestamps = false;
    
    // Validation
    protected $validationRules      = [
        'proyek_id'    => 'required|integer',
        'nama_dokumen' => 'required|max_length[255]',
        'kategori'     => 'required|max_length[100]',
        'file_path'    => 'required|max_length[255]'
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}

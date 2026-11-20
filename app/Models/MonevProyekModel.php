<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevProyekModel extends Model
{
    protected $table            = 'monev_proyek';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 'kd_rup', 'nama_proyek', 'lokasi', 'no_kontrak', 'nilai_kontrak',
        'tgl_mulai', 'tgl_selesai', 'nama_penyedia', 'konsultan_pengawas',
        'ppk', 'sumber_dana', 'jenis_pekerjaan', 'metode_kontrak', 'gps_kordinat', 'polygon_lokasi', 'target_progres_mingguan', 'file_kontrak', 'dokumen_mc',
        'pphp', 'direksi_teknis', 'status', 'weekly_ai_analysis'
    ];

    // Dates
    protected $useTimestamps = false; // created_at handled by MySQL DEFAULT
    
    // Validation
    protected $validationRules      = [
        'user_id'            => 'required|integer',
        'kd_rup'             => 'permit_empty|max_length[50]',
        'nama_proyek'        => 'required|min_length[3]|max_length[255]',
        'no_kontrak'         => 'required|min_length[2]|max_length[100]',
        'nilai_kontrak'      => 'required|decimal',
        'tgl_mulai'          => 'required|valid_date[Y-m-d]',
        'tgl_selesai'        => 'required|valid_date[Y-m-d]',
        'nama_penyedia'      => 'required|min_length[2]|max_length[255]',
        'status'             => 'required|in_list[Draft,Berjalan,Selesai,Terlambat]'
    ];
    
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}

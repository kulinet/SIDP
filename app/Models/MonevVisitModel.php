<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevVisitModel extends Model
{
    protected $table            = 'monev_visit';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'monev_period_id',
        'tgl_kunjungan',
        'waktu_mulai',
        'waktu_selesai',
        'lokasi_spesifik',
        'gps_lat',
        'gps_lng',
        'cuaca',
        'pihak_hadir_ppk',
        'pihak_hadir_penyedia',
        'pihak_hadir_konsultan',
        'agenda_pemeriksaan',
        'catatan_lapangan',
    ];

    protected $useTimestamps = false;
}

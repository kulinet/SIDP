<?php

namespace App\Models;

use CodeIgniter\Model;

class RupSwakelolaModel extends Model
{
    protected $table = 'rup_swakelola';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_rup', 'nama_paket', 'kd_satker', 'nama_satker', 'pagu', 
        'tipe_swakelola', 'volume_pekerjaan', 'uraian_pekerjaan', 'mak',
        'tgl_awal_pelaksanaan', 'tgl_akhir_pelaksanaan', 
        'status_umumkan_rup', 'tahun', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
}

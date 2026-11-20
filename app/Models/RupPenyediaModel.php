<?php

namespace App\Models;

use CodeIgniter\Model;

class RupPenyediaModel extends Model
{
    protected $table = 'rup_penyedia';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_rup', 'nama_paket', 'kd_satker', 'nama_satker', 'pagu', 
        'jenis_pengadaan', 'metode_pengadaan', 'volume_pekerjaan', 
        'uraian_pekerjaan', 'spesifikasi_pekerjaan', 'mak', 'tgl_awal_pemilihan', 'tgl_akhir_pemilihan', 
        'status_umumkan_rup', 'status_pdn', 'sumber_dana', 'tahun', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class LpsePencatatanSwakelolaModel extends Model
{
    protected $table = 'lpse_pencatatan_swakelola';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_rup', 'tahun', 'kd_paket', 'nama_paket', 'nama_satker', 'kd_satker',
        'pagu', 'realisasi', 'jenis_realisasi', 'no_pembayaran', 'ket_realisasi',
        'status', 'nama_pelaksana', 'alasan_pembatalan', 'status_swakelola_pct_ket'
    ];
    protected $useTimestamps = true;
}

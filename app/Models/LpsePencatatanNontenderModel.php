<?php

namespace App\Models;

use CodeIgniter\Model;

class LpsePencatatanNontenderModel extends Model
{
    protected $table = 'lpse_pencatatan_nontender';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_rup', 'tahun', 'kd_paket', 'nama_paket', 'nama_satker', 'kd_satker', 
        'pagu', 'realisasi', 'nama_penyedia', 'kd_penyedia', 'npwp_penyedia', 
        'jenis_realisasi', 'kategori_pengadaan', 'mtd_pemilihan', 'sumber_dana', 
        'tgl_buat_paket', 'tgl_selesai_paket',
        'no_pembayaran', 'jenis_pembayaran', 'ket_realisasi', 'status'
    ];
    protected $useTimestamps = true;
}

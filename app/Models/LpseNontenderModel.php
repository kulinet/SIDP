<?php

namespace App\Models;

use CodeIgniter\Model;

class LpseNontenderModel extends Model
{
    protected $table = 'lpse_nontender';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_rup', 'tahun', 'kd_paket', 'nama_paket', 'nama_satker', 'kd_satker', 'pagu', 'hps', 
        'nilai_kontrak', 'nilai_negosiasi', 'nilai_pdn_kontrak', 'nilai_umk_kontrak',
        'nama_penyedia', 'kd_penyedia', 'npwp_penyedia', 'alamat_penyedia', 'bentuk_usaha',
        'metode', 'jenis_pengadaan', 
        'tgl_kontrak', 'no_kontrak', 'status', 'kualifikasi_paket', 'kontrak_pembayaran',
        'no_spmk_spp', 'waktu_penyelesaian', 'tgl_mulai_pekerjaan', 'tgl_selesai_pekerjaan',
        'tgl_bap', 'no_bap', 'progres_pekerjaan', 'tgl_bast', 'no_bast', 'besar_pembayaran'
    ];
    protected $useTimestamps = true;
}

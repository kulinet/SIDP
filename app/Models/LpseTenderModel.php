<?php

namespace App\Models;

use CodeIgniter\Model;

class LpseTenderModel extends Model
{
    protected $table = 'lpse_tender';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_rup', 'tahun', 'kd_paket', 'nama_paket', 'nama_satker', 'kd_satker', 'pagu', 'hps', 
        'nilai_penawaran', 'nilai_kontrak', 'nama_penyedia', 'kd_penyedia', 'npwp_penyedia', 'alamat_penyedia', 'bentuk_usaha',
        'metode', 'tgl_pemenang', 'tgl_kontrak', 
        'no_kontrak', 'status', 'jenis_pengadaan', 'kgr_nama', 'mtd_evaluasi', 'mtd_kualifikasi', 
        'kualifikasi_paket', 'kontrak_pembayaran', 'status_kontrak', 'sumber_dana',
        'no_spmk_spp', 'waktu_penyelesaian', 'tgl_mulai_pekerjaan', 'tgl_selesai_pekerjaan'
    ];
    protected $useTimestamps = true;
}

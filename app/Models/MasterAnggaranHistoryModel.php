<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterAnggaranHistoryModel extends Model
{
    protected $table = 'master_anggaran_history';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'master_anggaran_id',
        'kd_satker',
        'nama_satker',
        'kd_program_str',
        'nama_program',
        'pagu_sebelum',
        'pagu_sesudah',
        'selisih',
        'jenis_perubahan',
        'komentar',
        'user_id',
        'user_nama',
        'tahun',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
}

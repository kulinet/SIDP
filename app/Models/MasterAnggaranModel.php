<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterAnggaranModel extends Model
{
    protected $table = 'master_anggaran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_satker',
        'nama_satker',
        'kd_program_str',
        'nama_program',
        'pagu_program',
        'pagu_awal',
        'is_edited',
        'jenis_perubahan',
        'keterangan_perubahan',
        'tahun',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
}

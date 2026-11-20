<?php

namespace App\Models;

use CodeIgniter\Model;

class InaprocVendorSyncModel extends Model
{
    protected $table = 'inaproc_vendor_sync';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kode_penyedia',
        'nama_penyedia',
        'bentuk_usaha',
        'jenis_perusahaan',
        'npwp_penyedia',
        'kpp_code',
        'lokasi_perusahaan',
        'flag_lokasi',
        'alamat_penyedia',
        'email',
        'telepon',
        'nib',
        'status_aktif',
        'status_umkk',
        'kbli_name',
        'kbli_id',
        'raw_response',
        'last_sync_status',
        'last_sync_at',
        'error_message',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}

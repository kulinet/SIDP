<?php

namespace App\Models;

use CodeIgniter\Model;

class MonevProgresSpseModel extends Model
{
    protected $table            = 'monev_progres_spse';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'no', 'satker', 'kategori',
        'rup_penyedia', 'rup_swakelola', 'rup_pl', 'rup_juksung', 'rup_epurchasing', 'rup_dikecualikan', 'rup_tender',
        'epl_dilaksanakan', 'epl_belum_input', 'epl_sudah_input', 'epl_pct',
        'tender_dilaksanakan', 'tender_belum_input', 'tender_sudah_input', 'tender_pct',
        'non_tender_belum', 'non_tender_sudah', 'non_tender_pct',
        'swakelola_belum', 'swakelola_sudah', 'swakelola_pct',
        'epurchasing_jml', 'epurchasing_belum', 'epurchasing_sudah', 'epurchasing_pct',
        'keterangan', 'tahun', 'update_data', 'update_rup',
        'created_at', 'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class EpurchasingModel extends Model
{
    protected $table = 'epurchasing';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'kd_paket', 'nama_paket', 'pagu', 'total_harga', 'nama_satker', 'kd_satker', 'tahun',
        'sumber_dana', 'status', 'order_date', 'kode_penyedia', 'nama_penyedia', 'product_id',
        'nama_produk', 'products_json', 'count_product', 'rup_desc', 'total_qty',
        'shipment_status', 'rup_code', 'mak', 'shipping_fee', 'flag_minikom',
        'rekan_id', 'kode_klpd', 'is_swasta'
    ];

    protected $useTimestamps = true;
}

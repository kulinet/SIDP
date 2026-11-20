<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiFieldsToEpurchasing extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('epurchasing')) {
            return;
        }

        $existingCols = $this->db->getFieldNames('epurchasing');
        $newCols = [];

        if (!in_array('nama_produk', $existingCols)) {
            $newCols['nama_produk'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
        }
        if (!in_array('products_json', $existingCols)) {
            $newCols['products_json'] = ['type' => 'LONGTEXT', 'null' => true, 'default' => null];
        }
        if (!in_array('count_product', $existingCols)) {
            $newCols['count_product'] = ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => 0];
        }
        if (!in_array('mak', $existingCols)) {
            $newCols['mak'] = ['type' => 'TEXT', 'null' => true, 'default' => null];
        }
        if (!in_array('shipping_fee', $existingCols)) {
            $newCols['shipping_fee'] = ['type' => 'DECIMAL', 'constraint' => '20,2', 'null' => true, 'default' => 0];
        }
        if (!in_array('flag_minikom', $existingCols)) {
            $newCols['flag_minikom'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
        }
        if (!in_array('rekan_id', $existingCols)) {
            $newCols['rekan_id'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
        }
        if (!in_array('kode_klpd', $existingCols)) {
            $newCols['kode_klpd'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
        }
        if (!in_array('is_swasta', $existingCols)) {
            $newCols['is_swasta'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => null];
        }

        if (!empty($newCols)) {
            $this->forge->addColumn('epurchasing', $newCols);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('epurchasing')) {
            return;
        }

        $existingCols = $this->db->getFieldNames('epurchasing');
        $dropCols = array_intersect(
            ['nama_produk', 'products_json', 'count_product', 'mak', 'shipping_fee', 'flag_minikom', 'rekan_id', 'kode_klpd', 'is_swasta'],
            $existingCols
        );

        if (!empty($dropCols)) {
            $this->forge->dropColumn('epurchasing', $dropCols);
        }
    }
}

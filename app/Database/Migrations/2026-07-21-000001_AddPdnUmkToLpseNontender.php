<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPdnUmkToLpseNontender extends Migration
{
    public function up()
    {
        // Tambah kolom nilai_pdn_kontrak dan nilai_umk_kontrak ke lpse_nontender
        $fields = [];

        // Cek apakah kolom sudah ada sebelum menambahkan
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'nilai_pdn_kontrak'");
        if ($query->getNumRows() === 0) {
            $fields['nilai_pdn_kontrak'] = [
                'type'    => 'DOUBLE',
                'null'    => true,
                'default' => 0,
                'after'   => 'nilai_negosiasi',
            ];
        }

        $query2 = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'nilai_umk_kontrak'");
        if ($query2->getNumRows() === 0) {
            $fields['nilai_umk_kontrak'] = [
                'type'    => 'DOUBLE',
                'null'    => true,
                'default' => 0,
                'after'   => 'nilai_pdn_kontrak',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('lpse_nontender', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('lpse_nontender', 'nilai_pdn_kontrak');
        $this->forge->dropColumn('lpse_nontender', 'nilai_umk_kontrak');
    }
}

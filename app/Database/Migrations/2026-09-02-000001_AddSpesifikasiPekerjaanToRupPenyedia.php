<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSpesifikasiPekerjaanToRupPenyedia extends Migration
{
    public function up()
    {
        $query = $this->db->query("SHOW COLUMNS FROM `rup_penyedia` LIKE 'spesifikasi_pekerjaan'");
        if ($query->getNumRows() === 0) {
            $this->forge->addColumn('rup_penyedia', [
                'spesifikasi_pekerjaan' => [
                    'type'  => 'TEXT',
                    'null'  => true,
                    'after' => 'uraian_pekerjaan',
                ],
            ]);
        }
    }

    public function down()
    {
        $query = $this->db->query("SHOW COLUMNS FROM `rup_penyedia` LIKE 'spesifikasi_pekerjaan'");
        if ($query->getNumRows() > 0) {
            $this->forge->dropColumn('rup_penyedia', 'spesifikasi_pekerjaan');
        }
    }
}

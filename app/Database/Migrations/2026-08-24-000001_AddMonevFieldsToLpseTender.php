<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMonevFieldsToLpseTender extends Migration
{
    public function up()
    {
        $fields = [];

        // Check and add no_spmk_spp
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_tender` LIKE 'no_spmk_spp'");
        if ($query->getNumRows() === 0) {
            $fields['no_spmk_spp'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'status_kontrak',
            ];
        }

        // Check and add waktu_penyelesaian
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_tender` LIKE 'waktu_penyelesaian'");
        if ($query->getNumRows() === 0) {
            $fields['waktu_penyelesaian'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'no_spmk_spp',
            ];
        }

        // Check and add tgl_mulai_pekerjaan
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_tender` LIKE 'tgl_mulai_pekerjaan'");
        if ($query->getNumRows() === 0) {
            $fields['tgl_mulai_pekerjaan'] = [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'waktu_penyelesaian',
            ];
        }

        // Check and add tgl_selesai_pekerjaan
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_tender` LIKE 'tgl_selesai_pekerjaan'");
        if ($query->getNumRows() === 0) {
            $fields['tgl_selesai_pekerjaan'] = [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'tgl_mulai_pekerjaan',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('lpse_tender', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('lpse_tender', 'no_spmk_spp');
        $this->forge->dropColumn('lpse_tender', 'waktu_penyelesaian');
        $this->forge->dropColumn('lpse_tender', 'tgl_mulai_pekerjaan');
        $this->forge->dropColumn('lpse_tender', 'tgl_selesai_pekerjaan');
    }
}

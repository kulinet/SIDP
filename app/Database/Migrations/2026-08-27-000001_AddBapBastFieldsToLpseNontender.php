<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBapBastFieldsToLpseNontender extends Migration
{
    public function up()
    {
        $fields = [];

        // Check and add tgl_bap
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'tgl_bap'");
        if ($query->getNumRows() === 0) {
            $fields['tgl_bap'] = [
                'type' => 'DATE',
                'null' => true,
                'after' => 'tgl_selesai_pekerjaan',
            ];
        }

        // Check and add no_bap
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'no_bap'");
        if ($query->getNumRows() === 0) {
            $fields['no_bap'] = [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'tgl_bap',
            ];
        }

        // Check and add progres_pekerjaan
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'progres_pekerjaan'");
        if ($query->getNumRows() === 0) {
            $fields['progres_pekerjaan'] = [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
                'after' => 'no_bap',
            ];
        }

        // Check and add tgl_bast
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'tgl_bast'");
        if ($query->getNumRows() === 0) {
            $fields['tgl_bast'] = [
                'type' => 'DATE',
                'null' => true,
                'after' => 'progres_pekerjaan',
            ];
        }

        // Check and add no_bast
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'no_bast'");
        if ($query->getNumRows() === 0) {
            $fields['no_bast'] = [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'tgl_bast',
            ];
        }

        // Check and add besar_pembayaran
        $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE 'besar_pembayaran'");
        if ($query->getNumRows() === 0) {
            $fields['besar_pembayaran'] = [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'default' => 0.00,
                'after' => 'no_bast',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('lpse_nontender', $fields);
        }
    }

    public function down()
    {
        $columns = ['tgl_bap', 'no_bap', 'progres_pekerjaan', 'tgl_bast', 'no_bast', 'besar_pembayaran'];
        foreach ($columns as $col) {
            $query = $this->db->query("SHOW COLUMNS FROM `lpse_nontender` LIKE '{$col}'");
            if ($query->getNumRows() > 0) {
                $this->forge->dropColumn('lpse_nontender', $col);
            }
        }
    }
}

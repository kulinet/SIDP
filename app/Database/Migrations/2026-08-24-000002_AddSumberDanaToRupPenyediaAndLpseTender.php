<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSumberDanaToRupPenyediaAndLpseTender extends Migration
{
    public function up()
    {
        // 1. Add sumber_dana to rup_penyedia
        $query1 = $this->db->query("SHOW COLUMNS FROM `rup_penyedia` LIKE 'sumber_dana'");
        if ($query1->getNumRows() === 0) {
            $this->forge->addColumn('rup_penyedia', [
                'sumber_dana' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'status_pdn',
                ],
            ]);
        }

        // 2. Add sumber_dana to lpse_tender
        $query2 = $this->db->query("SHOW COLUMNS FROM `lpse_tender` LIKE 'sumber_dana'");
        if ($query2->getNumRows() === 0) {
            $this->forge->addColumn('lpse_tender', [
                'sumber_dana' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'status_kontrak',
                ],
            ]);
        }
    }

    public function down()
    {
        // 1. Drop from rup_penyedia
        $query1 = $this->db->query("SHOW COLUMNS FROM `rup_penyedia` LIKE 'sumber_dana'");
        if ($query1->getNumRows() > 0) {
            $this->forge->dropColumn('rup_penyedia', 'sumber_dana');
        }

        // 2. Drop from lpse_tender
        $query2 = $this->db->query("SHOW COLUMNS FROM `lpse_tender` LIKE 'sumber_dana'");
        if ($query2->getNumRows() > 0) {
            $this->forge->dropColumn('lpse_tender', 'sumber_dana');
        }
    }
}

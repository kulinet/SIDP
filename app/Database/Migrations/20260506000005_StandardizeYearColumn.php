<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StandardizeYearColumn extends Migration
{
    public function up()
    {
        $tables = [
            'rup_penyedia',
            'rup_swakelola',
            'lpse_tender',
            'lpse_nontender',
            'epurchasing',
            'master_anggaran'
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                if ($this->db->fieldExists('tahun_anggaran', $table)) {
                    $this->forge->modifyColumn($table, [
                        'tahun_anggaran' => [
                            'name' => 'tahun',
                            'type' => 'INT',
                            'constraint' => 4,
                            'null' => true,
                        ],
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $tables = [
            'rup_penyedia',
            'rup_swakelola',
            'lpse_tender',
            'lpse_nontender',
            'epurchasing',
            'master_anggaran'
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                if ($this->db->fieldExists('tahun', $table)) {
                    $this->forge->modifyColumn($table, [
                        'tahun' => [
                            'name' => 'tahun_anggaran',
                            'type' => 'INT',
                            'constraint' => 4,
                            'null' => true,
                        ],
                    ]);
                }
            }
        }
    }
}

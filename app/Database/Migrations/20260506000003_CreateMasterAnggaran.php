<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterAnggaran extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kd_satker' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'nama_satker' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kd_program_str' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'nama_program' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pagu_program' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'tahun' => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->createTable('master_anggaran');
    }

    public function down()
    {
        $this->forge->dropTable('master_anggaran');
    }
}

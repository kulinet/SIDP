<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPelaksanaToRecording extends Migration
{
    public function up()
    {
        $fields = [
            'nama_pelaksana' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'nama_satker'
            ]
        ];
        $this->forge->addColumn('lpse_pencatatan_swakelola', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('lpse_pencatatan_swakelola', 'nama_pelaksana');
    }
}

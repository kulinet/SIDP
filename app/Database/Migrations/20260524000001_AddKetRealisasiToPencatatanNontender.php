<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKetRealisasiToPencatatanNontender extends Migration
{
    public function up()
    {
        $this->forge->addColumn('lpse_pencatatan_nontender', [
            'jenis_pembayaran' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'jenis_realisasi',
            ],
            'ket_realisasi' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'no_pembayaran',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('lpse_pencatatan_nontender', 'jenis_pembayaran');
        $this->forge->dropColumn('lpse_pencatatan_nontender', 'ket_realisasi');
    }
}

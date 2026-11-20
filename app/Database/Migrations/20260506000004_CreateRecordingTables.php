<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecordingTables extends Migration
{
    public function up()
    {
        // 1. Table: lpse_pencatatan_nontender
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kd_rup' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tahun' => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'kd_paket' => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'nama_paket' => ['type' => 'TEXT', 'null' => true],
            'nama_satker' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kd_satker' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'pagu' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'realisasi' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nama_penyedia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jenis_realisasi' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->createTable('lpse_pencatatan_nontender');

        // 2. Table: lpse_pencatatan_swakelola
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kd_rup' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tahun' => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'kd_paket' => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'nama_paket' => ['type' => 'TEXT', 'null' => true],
            'nama_satker' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kd_satker' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'pagu' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'realisasi' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'jenis_realisasi' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ket_realisasi' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->createTable('lpse_pencatatan_swakelola');
    }

    public function down()
    {
        $this->forge->dropTable('lpse_pencatatan_nontender');
        $this->forge->dropTable('lpse_pencatatan_swakelola');
    }
}

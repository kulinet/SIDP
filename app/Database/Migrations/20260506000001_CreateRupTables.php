<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRupTables extends Migration
{
    public function up()
    {
        // 1. Table: rup_penyedia
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kd_rup' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'null'       => true,
            ],
            'nama_paket' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'kd_satker' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'nama_satker' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'pagu' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,2',
                'default'    => 0,
            ],
            'jenis_pengadaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'metode_pengadaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'volume_pekerjaan' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'uraian_pekerjaan' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'tgl_awal_pemilihan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'tgl_akhir_pemilihan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'status_umumkan_rup' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'tahun' => [
                'type'       => 'INT',
                'constraint' => 4,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('kd_rup');
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->addKey('jenis_pengadaan');
        $this->forge->createTable('rup_penyedia');

        // 2. Table: rup_swakelola
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kd_rup' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'null'       => true,
            ],
            'nama_paket' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'kd_satker' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'nama_satker' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'pagu' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,2',
                'default'    => 0,
            ],
            'tipe_swakelola' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'volume_pekerjaan' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'uraian_pekerjaan' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'tgl_awal_pelaksanaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'tgl_akhir_pelaksanaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'status_umumkan_rup' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'tahun' => [
                'type'       => 'INT',
                'constraint' => 4,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('kd_rup');
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->createTable('rup_swakelola');
    }

    public function down()
    {
        $this->forge->dropTable('rup_penyedia');
        $this->forge->dropTable('rup_swakelola');
    }
}

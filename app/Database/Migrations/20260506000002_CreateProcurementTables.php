<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProcurementTables extends Migration
{
    public function up()
    {
        // 1. Table: lpse_tender
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kd_rup' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tahun' => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'kd_paket' => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'nama_paket' => ['type' => 'TEXT', 'null' => true],
            'nama_satker' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kd_satker' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'pagu' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'hps' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nilai_penawaran' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nilai_kontrak' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nama_penyedia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'metode' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tgl_pemenang' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tgl_kontrak' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'no_kontrak' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'jenis_pengadaan' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kgr_nama' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'mtd_evaluasi' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'mtd_kualifikasi' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kualifikasi_paket' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kontrak_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status_kontrak' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->createTable('lpse_tender');

        // 2. Table: lpse_nontender
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kd_rup' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tahun' => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'kd_paket' => ['type' => 'BIGINT', 'constraint' => 20, 'null' => true],
            'nama_paket' => ['type' => 'TEXT', 'null' => true],
            'nama_satker' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kd_satker' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'pagu' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'hps' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nilai_kontrak' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nilai_negosiasi' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nama_penyedia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'metode' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'jenis_pengadaan' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tgl_kontrak' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'no_kontrak' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kualifikasi_paket' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'kontrak_pembayaran' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->createTable('lpse_nontender');

        // 3. Table: epurchasing
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kd_paket' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'nama_paket' => ['type' => 'TEXT', 'null' => true],
            'pagu' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'total_harga' => ['type' => 'DECIMAL', 'constraint' => '20,2', 'default' => 0],
            'nama_satker' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'kd_satker' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'tahun' => ['type' => 'INT', 'constraint' => 4, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->createTable('epurchasing');
    }

    public function down()
    {
        $this->forge->dropTable('lpse_tender');
        $this->forge->dropTable('lpse_nontender');
        $this->forge->dropTable('epurchasing');
    }
}

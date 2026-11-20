<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonevTables extends Migration
{
    public function up()
    {
        // 1. Table monev_proyek
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kd_rup' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'nama_proyek' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'no_kontrak' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'nilai_kontrak' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,2',
                'default'    => 0.00,
            ],
            'tgl_mulai' => [
                'type' => 'DATE',
            ],
            'tgl_selesai' => [
                'type' => 'DATE',
            ],
            'nama_penyedia' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'konsultan_pengawas' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'pphp' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'direksi_teknis' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Draft', 'Berjalan', 'Selesai', 'Terlambat'],
                'default'    => 'Draft',
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('monev_proyek', true);

        // 2. Table monev_jadwal
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'proyek_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'item_pekerjaan' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'bobot' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'target_mingguan' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        // Add foreign key if necessary, or just index
        $this->forge->addKey('proyek_id');
        $this->forge->createTable('monev_jadwal', true);

        // 3. Table monev_harian
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'proyek_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tanggal' => [
                'type' => 'DATE',
            ],
            'volume_selesai' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
            'satuan' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'lokasi' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'tenaga_kerja' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'peralatan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'kendala' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'foto_awal' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'foto_proses' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'foto_hasil' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'progres_persen' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
            ],
            'is_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('proyek_id');
        $this->forge->createTable('monev_harian', true);

        // 4. Table monev_dokumen
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'proyek_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nama_dokumen' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'kategori' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('proyek_id');
        $this->forge->createTable('monev_dokumen', true);

        // 5. Table ppk_rup_mappings
        // Cek dulu apakah tabelnya sudah ada dari migrasi lain, jika belum buatkan
        $db = \Config\Database::connect();
        if (!$db->tableExists('ppk_rup_mappings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'kd_paket' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                ],
                'type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                ],
                'created_at datetime default current_timestamp',
                'updated_at datetime default current_timestamp on update current_timestamp',
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->createTable('ppk_rup_mappings');
        }
    }

    public function down()
    {
        $this->forge->dropTable('monev_dokumen', true);
        $this->forge->dropTable('monev_harian', true);
        $this->forge->dropTable('monev_jadwal', true);
        $this->forge->dropTable('monev_proyek', true);
        $this->forge->dropTable('ppk_rup_mappings', true);
    }
}

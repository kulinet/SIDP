<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMasterAnggaranHistory extends Migration
{
    public function up()
    {
        // 1. Add fields to master_anggaran
        $fields = [
            'pagu_awal' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,2',
                'null'       => true,
                'after'      => 'pagu_program'
            ],
            'is_edited' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'pagu_awal'
            ],
            'jenis_perubahan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'is_edited'
            ],
            'keterangan_perubahan' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'jenis_perubahan'
            ],
        ];

        if ($this->db->tableExists('master_anggaran')) {
            $existingColumns = $this->db->getFieldNames('master_anggaran');
            $toAdd = [];
            foreach ($fields as $col => $def) {
                if (!in_array($col, $existingColumns)) {
                    $toAdd[$col] = $def;
                }
            }
            if (!empty($toAdd)) {
                $this->forge->addColumn('master_anggaran', $toAdd);
            }
            
            // Populate initial pagu_awal with current pagu_program if null
            $this->db->query("UPDATE `master_anggaran` SET `pagu_awal` = `pagu_program` WHERE `pagu_awal` IS NULL");
        }

        // 2. Create master_anggaran_history table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'master_anggaran_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'kd_satker' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'nama_satker' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'kd_program_str' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'nama_program' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'pagu_sebelum' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,2',
                'default'    => 0.00,
            ],
            'pagu_sesudah' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,2',
                'default'    => 0.00,
            ],
            'selisih' => [
                'type'       => 'DECIMAL',
                'constraint' => '20,2',
                'default'    => 0.00,
            ],
            'jenis_perubahan' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'komentar' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'user_nama' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'tahun' => [
                'type'       => 'INT',
                'constraint' => 4,
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
        $this->forge->addKey(['kd_satker', 'tahun']);
        $this->forge->addKey('master_anggaran_id');
        $this->forge->createTable('master_anggaran_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('master_anggaran_history', true);
        if ($this->db->tableExists('master_anggaran')) {
            $cols = ['pagu_awal', 'is_edited', 'jenis_perubahan', 'keterangan_perubahan'];
            $existing = $this->db->getFieldNames('master_anggaran');
            $toDrop = array_intersect($cols, $existing);
            if (!empty($toDrop)) {
                $this->forge->dropColumn('master_anggaran', $toDrop);
            }
        }
    }
}

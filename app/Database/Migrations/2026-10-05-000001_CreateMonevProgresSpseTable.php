<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonevProgresSpseTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('monev_progres_spse')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,
            ],
            'no' => [
                'type' => 'INT',
            ],
            'satker' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'kategori' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'Dinas',
                'null'       => true,
            ],
            'rup_penyedia' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'rup_swakelola' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'rup_pl' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'rup_juksung' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'rup_epurchasing' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'rup_dikecualikan' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'rup_tender' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'epl_dilaksanakan' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'epl_belum_input' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'epl_sudah_input' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'epl_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'null'       => true,
            ],
            'tender_dilaksanakan' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'tender_belum_input' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'tender_sudah_input' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'tender_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'null'       => true,
            ],
            'non_tender_belum' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'non_tender_sudah' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'non_tender_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'null'       => true,
            ],
            'swakelola_belum' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'swakelola_sudah' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'swakelola_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'null'       => true,
            ],
            'epurchasing_jml' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'epurchasing_belum' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'epurchasing_sudah' => [
                'type'    => 'INT',
                'default' => 0,
                'null'    => true,
            ],
            'epurchasing_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0.00,
                'null'       => true,
            ],
            'keterangan' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'default'    => '',
                'null'       => true,
            ],
            'tahun' => [
                'type'    => 'INT',
                'default' => 2026,
                'null'    => true,
            ],
            'update_data' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'default'    => '',
                'null'       => true,
            ],
            'update_rup' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'default'    => '',
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
        $this->forge->addUniqueKey(['no', 'tahun'], 'uk_no_tahun');
        $this->forge->createTable('monev_progres_spse', true);
    }

    public function down()
    {
        $this->forge->dropTable('monev_progres_spse', true);
    }
}

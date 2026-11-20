<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToLpsePencatatanNontender extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('lpse_pencatatan_nontender')) {
            return;
        }

        $existingCols = $this->db->getFieldNames('lpse_pencatatan_nontender');
        $fields = [];

        if (!in_array('kategori_pengadaan', $existingCols)) {
            $fields['kategori_pengadaan'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'jenis_realisasi',
            ];
        }

        if (!in_array('mtd_pemilihan', $existingCols)) {
            $fields['mtd_pemilihan'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'kategori_pengadaan',
            ];
        }

        if (!in_array('sumber_dana', $existingCols)) {
            $fields['sumber_dana'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'mtd_pemilihan',
            ];
        }

        if (!in_array('tgl_buat_paket', $existingCols)) {
            $fields['tgl_buat_paket'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'sumber_dana',
            ];
        }

        if (!in_array('tgl_selesai_paket', $existingCols)) {
            $fields['tgl_selesai_paket'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'tgl_buat_paket',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('lpse_pencatatan_nontender', $fields);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('lpse_pencatatan_nontender')) {
            return;
        }

        $colsToDrop = ['kategori_pengadaan', 'mtd_pemilihan', 'sumber_dana', 'tgl_buat_paket', 'tgl_selesai_paket'];
        $existingCols = $this->db->getFieldNames('lpse_pencatatan_nontender');

        foreach ($colsToDrop as $col) {
            if (in_array($col, $existingCols)) {
                $this->forge->dropColumn('lpse_pencatatan_nontender', $col);
            }
        }
    }
}

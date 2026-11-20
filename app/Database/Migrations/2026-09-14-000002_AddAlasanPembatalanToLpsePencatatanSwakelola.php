<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAlasanPembatalanToLpsePencatatanSwakelola extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $fields = [];

        if (!$db->fieldExists('alasan_pembatalan', 'lpse_pencatatan_swakelola')) {
            $fields['alasan_pembatalan'] = [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'status',
            ];
        }

        if (!$db->fieldExists('status_swakelola_pct_ket', 'lpse_pencatatan_swakelola')) {
            $fields['status_swakelola_pct_ket'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'alasan_pembatalan',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('lpse_pencatatan_swakelola', $fields);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        if ($db->fieldExists('status_swakelola_pct_ket', 'lpse_pencatatan_swakelola')) {
            $this->forge->dropColumn('lpse_pencatatan_swakelola', 'status_swakelola_pct_ket');
        }

        if ($db->fieldExists('alasan_pembatalan', 'lpse_pencatatan_swakelola')) {
            $this->forge->dropColumn('lpse_pencatatan_swakelola', 'alasan_pembatalan');
        }
    }
}

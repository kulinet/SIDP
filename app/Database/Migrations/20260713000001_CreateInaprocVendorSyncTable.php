<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInaprocVendorSyncTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kode_penyedia' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'nama_penyedia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bentuk_usaha' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jenis_perusahaan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'npwp_penyedia' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status_aktif' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'status_umkk' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'kbli_name' => ['type' => 'TEXT', 'null' => true],
            'kbli_id' => ['type' => 'TEXT', 'null' => true],
            'raw_response' => ['type' => 'LONGTEXT', 'null' => true],
            'last_sync_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'last_sync_at' => ['type' => 'DATETIME', 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode_penyedia');
        $this->forge->createTable('inaproc_vendor_sync');
    }

    public function down()
    {
        $this->forge->dropTable('inaproc_vendor_sync');
    }
}

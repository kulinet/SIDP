<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonevPeriodDocumentsTable extends Migration
{
    public function up()
    {
        // monev_period_documents (Versioning V1, V2 for Final Documents)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'monev_period_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'version' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'document_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'hash_signature' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('monev_period_id');
        $this->forge->addForeignKey('monev_period_id', 'monev_period', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('monev_period_documents', true);
    }

    public function down()
    {
        $this->forge->dropTable('monev_period_documents', true);
    }
}

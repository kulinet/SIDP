<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChatbotKnowledgeTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'document_title' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'chunk_text' => [
                'type' => 'TEXT',
            ],
            'embedding_vector' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->createTable('chatbot_knowledge');
    }

    public function down()
    {
        $this->forge->dropTable('chatbot_knowledge');
    }
}

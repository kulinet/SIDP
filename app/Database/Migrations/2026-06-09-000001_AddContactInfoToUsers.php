<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactInfoToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'nomor_hp' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
                'after'      => 'nama_lengkap',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
                'after'      => 'nomor_hp',
            ],
        ];
        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'nomor_hp');
        $this->forge->dropColumn('users', 'email');
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOperatorRole extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'operator', 'opd'],
                'default'    => 'opd',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'opd'],
                'default'    => 'opd',
            ],
        ]);
    }
}

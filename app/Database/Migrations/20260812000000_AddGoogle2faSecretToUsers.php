<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGoogle2faSecretToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'google2fa_secret' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'google2fa_secret');
    }
}

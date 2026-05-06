<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDashboardCharts extends Migration
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
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'menu_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'chart_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50', // bar, pie, line, doughnut
                'default'    => 'bar',
            ],
            'label_field' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'value_field' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'aggregation' => [
                'type'       => 'VARCHAR',
                'constraint' => '50', // count, sum
                'default'    => 'count',
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
        $this->forge->addForeignKey('menu_id', 'dynamic_menus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('dashboard_charts');
    }

    public function down()
    {
        $this->forge->dropTable('dashboard_charts');
    }
}

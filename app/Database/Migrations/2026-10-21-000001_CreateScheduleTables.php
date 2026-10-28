<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateScheduleTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'type'       => ['type' => 'ENUM', 'constraint' => ['dyeing', 'finishing'], 'default' => 'dyeing'],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('machines');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'machine_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_no'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'customer'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'process'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'start_at'   => ['type' => 'DATETIME'],
            'end_at'     => ['type' => 'DATETIME'],
            'status'     => ['type' => 'ENUM', 'constraint' => ['draft', 'submitted', 'approved', 'rejected', 'running', 'done'], 'default' => 'draft'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['machine_id', 'start_at']);
        $this->forge->addForeignKey('machine_id', 'machines', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('schedules');
    }

    public function down()
    {
        $this->forge->dropTable('schedules', true);
        $this->forge->dropTable('machines', true);
    }
}

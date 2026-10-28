<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNewFieldsToSchedules extends Migration
{
    public function up()
    {
        $fields = [
            'category'     => ['type' => 'ENUM', 'constraint' => ['Produksi', 'Perbaikan', 'Percobaan'], 'default' => 'Produksi'],
            'design'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'color'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'mp_no'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'wash_machine' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'progress'     => ['type' => 'INT', 'constraint' => 3, 'default' => 0],
            'dye_lot_no'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'dop_no'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'note'         => ['type' => 'TEXT', 'null' => true],
        ];
        $this->forge->addColumn('schedules', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('schedules', ['category', 'design', 'color', 'mp_no', 'wash_machine', 'progress', 'dye_lot_no', 'dop_no', 'note']);
    }
}

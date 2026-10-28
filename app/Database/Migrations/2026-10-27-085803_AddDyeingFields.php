<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDyeingFields extends Migration
{
    public function up()
    {
        $this->forge->addColumn('schedules', [
            'category' => [
                'type'       => 'ENUM',
                'constraint' => ['produksi', 'perbaikan', 'litbang', 'lain-lain'],
                'default'    => 'produksi',
                'after'      => 'division'
            ],
        ]);

        $this->forge->addColumn('schedule_items', [
            'design_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'article'
            ],
            'mp_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'design_name'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('schedules', 'category');
        $this->forge->dropColumn('schedule_items', ['design_name', 'mp_no']);
    }
}

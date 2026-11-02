<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProgressToSchedules extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('progress', 'schedules')) {
            $this->forge->addColumn('schedules', [
                'progress' => [
                    'type'       => 'INT',
                    'constraint' => 3,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'actual_end',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('progress', 'schedules')) {
            $this->forge->dropColumn('schedules', 'progress');
        }
    }
}

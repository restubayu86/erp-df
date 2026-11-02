<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddScheduleItemsColumns extends Migration
{
    public function up()
    {
        // Add new columns to schedule_items for per-item configuration
        $this->forge->addColumn('schedule_items', [
            'dye_lot_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'Auto-generated: JDYYMMDD+MachineCode+Counter'
            ],
            'kategori' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'produksi, perbaikan, litbang, lain-lain'
            ],
            'shift_id' => [
                'type'       => 'INT',
                'null'       => true,
                'comment'    => 'Shift untuk item ini (per-item, bisa berbeda dari schedule header)'
            ],
            'start_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Jam rencana mulai per-item'
            ],
            'end_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'comment'    => 'Jam rencana selesai per-item'
            ],
            'priority' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'comment'    => 'normal, high, urgent'
            ],
        ]);

        // Add indexes for performance
        $this->db->query('ALTER TABLE schedule_items ADD INDEX idx_dye_lot (dye_lot_no)');
        $this->db->query('ALTER TABLE schedule_items ADD INDEX idx_shift (shift_id)');
    }

    public function down()
    {
        $this->forge->dropColumn('schedule_items', [
            'dye_lot_no',
            'kategori',
            'shift_id',
            'start_at',
            'end_at',
            'priority'
        ]);
    }
}

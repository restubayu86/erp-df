<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * PRD 6.2 & 6.3: master shift (pola + kalender), proses, parameter, dan kapasitas mesin.
 * Aman dijalankan ulang (setiap tabel/kolom dicek dulu).
 */
class CreateMasterTables extends Migration
{
    public function up()
    {
        $id = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true];
        $ts = [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];

        if (! $this->db->tableExists('shifts')) {
            $this->forge->addField([
                'id'         => $id,
                'code'       => ['type' => 'VARCHAR', 'constraint' => 10],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 50],
                'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('shifts');
        } else {
            if (! $this->db->fieldExists('code', 'shifts')) {
                $this->forge->addColumn('shifts', [
                    'code'       => ['type' => 'VARCHAR', 'constraint' => 10, 'after' => 'id'],
                    'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'after' => 'name'],
                    'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'sort_order'],
                ]);
                $this->db->query("UPDATE shifts SET code = LOWER(name) WHERE code IS NULL OR code = ''");
                $this->db->query("ALTER TABLE shifts ADD UNIQUE (code)");
            }
        }

        if (! $this->db->tableExists('shift_patterns')) {
            $this->forge->addField([
                'id'          => $id,
                'code'        => ['type' => 'VARCHAR', 'constraint' => 30],
                'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
                'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ] + $ts);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('shift_patterns');
        } else {
            if (! $this->db->fieldExists('code', 'shift_patterns')) {
                $this->forge->addColumn('shift_patterns', [
                    'code'      => ['type' => 'VARCHAR', 'constraint' => 30, 'after' => 'id'],
                    'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'description'],
                ]);
                $this->db->query("ALTER TABLE shift_patterns ADD UNIQUE (code)");
            }
        }

        if (! $this->db->tableExists('shift_pattern_items')) {
            $this->forge->addField([
                'id'              => $id,
                'pattern_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'shift_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'start_time'      => ['type' => 'TIME'],
                'end_time'        => ['type' => 'TIME'],
                'crosses_midnight' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['pattern_id', 'shift_id']);
            $this->forge->addForeignKey('pattern_id', 'shift_patterns', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('shift_id', 'shifts', 'id', 'RESTRICT', 'CASCADE');
            $this->forge->createTable('shift_pattern_items');
        }

        if (! $this->db->tableExists('shift_calendar')) {
            $this->forge->addField([
                'work_date'  => ['type' => 'DATE'],
                'pattern_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'reason'     => ['type' => 'VARCHAR', 'constraint' => 255],
                'set_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            ] + $ts);
            $this->forge->addKey('work_date', true);
            $this->forge->addForeignKey('pattern_id', 'shift_patterns', 'id', 'RESTRICT', 'CASCADE');
            $this->forge->createTable('shift_calendar');
        }

        if (! $this->db->tableExists('processes')) {
            $this->forge->addField([
                'id'               => $id,
                'division'         => ['type' => 'ENUM', 'constraint' => ['dyeing', 'finishing']],
                'code'             => ['type' => 'VARCHAR', 'constraint' => 30],
                'name'             => ['type' => 'VARCHAR', 'constraint' => 100],
                'std_duration_min' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'is_active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('processes');
        }

        if (! $this->db->tableExists('parameters')) {
            $this->forge->addField([
                'id'        => $id,
                'code'      => ['type' => 'VARCHAR', 'constraint' => 30],
                'name'      => ['type' => 'VARCHAR', 'constraint' => 100],
                'uom'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'data_type' => ['type' => 'ENUM', 'constraint' => ['number', 'text'], 'default' => 'number'],
                'decimals'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('parameters');
        }

        if (! $this->db->tableExists('parameter_divisions')) {
            $this->forge->addField([
                'id'           => $id,
                'parameter_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'division'     => ['type' => 'ENUM', 'constraint' => ['dyeing', 'finishing']],
                'is_required'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'has_actual'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'is_primary'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'sort_order'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['parameter_id', 'division']);
            $this->forge->addForeignKey('parameter_id', 'parameters', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('parameter_divisions');
        }

        // Kapasitas mesin (PRD 6.2)
        if (! $this->db->fieldExists('capacity', 'machines')) {
            $this->forge->addColumn('machines', [
                'capacity'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'type'],
                'capacity_param_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'capacity'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('capacity', 'machines')) {
            $this->forge->dropColumn('machines', ['capacity', 'capacity_param_id']);
        }
        foreach (['parameter_divisions', 'parameters', 'processes', 'shift_calendar', 'shift_pattern_items', 'shift_patterns', 'shifts'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}

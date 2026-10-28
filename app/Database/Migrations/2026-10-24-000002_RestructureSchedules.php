<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * PRD 6.4: schedules (header) + schedule_items + schedule_item_params + schedule_logs (diperluas).
 *
 * PERHATIAN: data uji lama (SO-0001..SO-0024) DIHAPUS. Disetujui pemilik project (PRD pertanyaan terbuka no. 12).
 * Isi ulang dengan: php spark db:seed DatabaseSeeder
 */
class RestructureSchedules extends Migration
{
    public function up()
    {
        // Data uji lama dibuang; urutan drop memperhatikan foreign key.
        $this->forge->dropTable('schedule_item_params', true);
        $this->forge->dropTable('schedule_items', true);
        $this->forge->dropTable('schedule_logs', true);
        $this->forge->dropTable('schedules', true);

        $uint = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true];

        // ---------- schedules (header, dipakai bersama Dyeing & Finishing) ----------
        $this->forge->addField([
            'id'                 => $uint + ['auto_increment' => true],
            'schedule_no'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'division'           => ['type' => 'ENUM', 'constraint' => ['dyeing', 'finishing']],
            'machine_id'         => $uint,
            'process_id'         => $uint + ['null' => true],
            'work_date'          => ['type' => 'DATE'],
            'shift_id'           => $uint,
            'sequence'           => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'priority'           => ['type' => 'ENUM', 'constraint' => ['normal', 'high', 'urgent'], 'default' => 'normal'],
            'start_at'           => ['type' => 'DATETIME'],
            'end_at'             => ['type' => 'DATETIME'],
            'status'             => ['type' => 'ENUM', 'constraint' => ['draft', 'submitted', 'revision', 'rejected', 'approved', 'running', 'paused', 'done', 'cancelled'], 'default' => 'draft'],
            'revision_no'        => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'copied_from_id'     => $uint + ['null' => true],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'actual_start'       => ['type' => 'DATETIME', 'null' => true],
            'actual_end'         => ['type' => 'DATETIME', 'null' => true],
            'current_operator_id' => $uint + ['null' => true],
            'current_shift_id'   => $uint + ['null' => true],
            'current_shift_date' => ['type' => 'DATE', 'null' => true],
            'created_by'         => $uint + ['null' => true],
            'submitted_by'       => $uint + ['null' => true],
            'submitted_at'       => ['type' => 'DATETIME', 'null' => true],
            'reviewed_by'        => $uint + ['null' => true],
            'reviewed_at'        => ['type' => 'DATETIME', 'null' => true],
            'review_note'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'started_by'         => $uint + ['null' => true],
            'finished_by'        => $uint + ['null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('schedule_no');
        $this->forge->addKey(['division', 'work_date', 'status']);
        $this->forge->addKey(['machine_id', 'start_at', 'end_at']);
        $this->forge->addKey(['work_date', 'shift_id', 'machine_id', 'sequence']);
        $this->forge->addKey(['status', 'current_shift_date', 'current_shift_id']);
        $this->forge->addForeignKey('machine_id', 'machines', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('process_id', 'processes', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('shift_id', 'shifts', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('schedules');

        // ---------- schedule_items (order / lot) ----------
        $this->forge->addField([
            'id'             => $uint + ['auto_increment' => true],
            'schedule_id'    => $uint,
            'line_no'        => ['type' => 'SMALLINT', 'constraint' => 6, 'default' => 1],
            'order_no'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'customer_name'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'article'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'color_code'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'color_name'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'lot_no'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'source_type'    => ['type' => 'ENUM', 'constraint' => ['manual', 'ifs', 'dyeing'], 'default' => 'manual'],
            'source_item_id' => $uint + ['null' => true],
            'notes'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['schedule_id', 'line_no']);
        $this->forge->addKey('order_no');
        $this->forge->addKey('source_item_id');
        $this->forge->addForeignKey('schedule_id', 'schedules', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('schedule_items');

        // ---------- schedule_item_params (nilai parameter teknis, terpisah dari header & item) ----------
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'schedule_item_id' => $uint,
            'parameter_id'     => $uint,
            'kind'             => ['type' => 'ENUM', 'constraint' => ['plan', 'actual'], 'default' => 'plan'],
            'value_num'        => ['type' => 'DECIMAL', 'constraint' => '14,3', 'null' => true],
            'value_text'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'updated_by'       => $uint + ['null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['schedule_item_id', 'parameter_id', 'kind']);
        $this->forge->addForeignKey('schedule_item_id', 'schedule_items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('parameter_id', 'parameters', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('schedule_item_params');

        // ---------- schedule_logs (diperluas: action + changes) ----------
        $this->forge->addField([
            'id'          => $uint + ['auto_increment' => true],
            'schedule_id' => $uint,
            'action'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'from_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'to_status'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'user_id'     => $uint + ['null' => true], // NULL = sistem / Auto ACC
            'note'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'changes'     => ['type' => 'LONGTEXT', 'null' => true], // JSON nilai lama-baru
            'created_at'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('schedule_id');
        $this->forge->createTable('schedule_logs');
    }

    public function down()
    {
        // Struktur datar lama tidak dikembalikan (data uji sudah dibuang di up()).
        foreach (['schedule_logs', 'schedule_item_params', 'schedule_items', 'schedules'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}

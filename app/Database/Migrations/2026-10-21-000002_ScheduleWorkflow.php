<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Aman dijalankan ulang: setiap langkah dicek dulu, jadi kalau migration ini
 * pernah gagal di tengah jalan, menjalankan `php spark migrate` lagi akan melanjutkan.
 * Catatan: tabel setting kita bernama `app_settings` karena `settings` sudah dipakai Shield.
 */
class ScheduleWorkflow extends Migration
{
    public function up()
    {
        if (! $this->hasColumn('schedules', 'work_date')) {
            $this->forge->addColumn('schedules', [
                'work_date'    => ['type' => 'DATE', 'null' => true, 'after' => 'customer'],
                'shift'        => ['type' => 'ENUM', 'constraint' => ['pagi', 'siang', 'malam'], 'default' => 'pagi', 'after' => 'work_date'],
                'priority'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'after' => 'shift'],
                'reviewed_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'reviewed_at'  => ['type' => 'DATETIME', 'null' => true],
                'review_note'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'actual_start' => ['type' => 'DATETIME', 'null' => true],
                'actual_end'   => ['type' => 'DATETIME', 'null' => true],
            ]);
        }

        $this->forge->modifyColumn('schedules', [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'submitted', 'approved', 'rejected', 'running', 'paused', 'done', 'cancelled'],
                'default'    => 'draft',
            ],
        ]);

        // data lama (mis. dari seeder versi awal): isi work_date dan urutan awal
        $this->db->query('UPDATE schedules SET work_date = DATE(start_at) WHERE work_date IS NULL');
        $this->db->query('UPDATE schedules SET priority = id WHERE priority = 0');

        if ($this->db->query("SHOW INDEX FROM schedules WHERE Key_name = 'idx_sched_scope'")->getNumRows() === 0) {
            $this->db->query('CREATE INDEX idx_sched_scope ON schedules (work_date, shift, machine_id, priority)');
        }

        // riwayat perubahan status (monitoring)
        if (! $this->hasTable('schedule_logs')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'schedule_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'from_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'to_status'   => ['type' => 'VARCHAR', 'constraint' => 20],
                'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // NULL = sistem / auto ACC
                'note'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'  => ['type' => 'DATETIME'],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('schedule_id');
            $this->forge->createTable('schedule_logs');
        }

        // setting key-value aplikasi (BUKAN `settings`, itu milik Shield)
        if (! $this->hasTable('app_settings')) {
            $this->forge->addField([
                'name'  => ['type' => 'VARCHAR', 'constraint' => 100],
                'value' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            ]);
            $this->forge->addKey('name', true);
            $this->forge->createTable('app_settings');
            $this->db->table('app_settings')->insert(['name' => 'schedule.auto_approve', 'value' => '0']);
        }
    }

    // Pakai SQL biasa agar tidak bergantung pada method tertentu milik class koneksi
    private function hasTable(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getNumRows() > 0;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $this->db->escape($column))->getNumRows() > 0;
    }

    public function down()
    {
        $this->forge->dropTable('app_settings', true);
        $this->forge->dropTable('schedule_logs', true);
        $this->db->query('DROP INDEX idx_sched_scope ON schedules');
        $this->forge->dropColumn('schedules', ['work_date', 'shift', 'priority', 'reviewed_by', 'reviewed_at', 'review_note', 'actual_start', 'actual_end']);
    }
}

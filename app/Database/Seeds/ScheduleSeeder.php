<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Data uji (menggantikan SO-0001..SO-0024). Jalankan SETELAH MasterSeeder: php spark db:seed DatabaseSeeder
 * Mengisi jadwal contoh untuk tanggal produksi hari ini pada tiap mesin, dengan 1-2 item per jadwal.
 */
class ScheduleSeeder extends Seeder
{
    public function run()
    {
        helper('text');
        $cal  = new \App\Modules\Core\Services\ShiftCalendarService($this->db);
        $date = $cal->productionDate();
        $now  = date('Y-m-d H:i:s');

        if ($this->db->table('schedules')->where('work_date', $date)->countAllResults() > 0) {
            return;   // sudah ada data pada tanggal ini
        }

        $params  = array_column($this->db->table('parameters')->get()->getResultArray(), 'id', 'code');
        $procs   = $this->db->table('processes')->get()->getResultArray();
        $statuses = ['approved', 'approved', 'submitted', 'draft', 'approved'];
        $n = 0;

        foreach ($this->db->table('machines')->where('is_active', 1)->orderBy('code')->get()->getResultArray() as $m) {
            $div   = $m['type'];
            $myProc = array_values(array_filter($procs, static fn ($p) => $p['division'] === $div));
            $seq   = 0;

            foreach ($cal->activeShifts($date) as $si => $shift) {
                for ($k = 0; $k < 2; $k++) {
                    $n++;
                    $seq++;
                    $proc  = $myProc[($n) % count($myProc)];
                    $start = strtotime($shift['start_at'] . ' +' . ($k * 3) . ' hours');
                    $end   = $start + 2 * 3600;
                    $no    = 'SCH-' . ($div === 'dyeing' ? 'DYE' : 'FIN') . '-' . date('ym') . '-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);

                    $this->db->table('schedules')->insert([
                        'schedule_no' => $no, 'division' => $div, 'machine_id' => $m['id'], 'process_id' => $proc['id'],
                        'work_date' => $date, 'shift_id' => $shift['shift_id'], 'sequence' => $seq,
                        'priority' => $n % 7 === 0 ? 'urgent' : ($n % 4 === 0 ? 'high' : 'normal'),
                        'start_at' => date('Y-m-d H:i:s', $start), 'end_at' => date('Y-m-d H:i:s', $end),
                        'status' => $statuses[$n % count($statuses)], 'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $sid = (int) $this->db->insertID();
                    $this->db->table('schedule_logs')->insert(['schedule_id' => $sid, 'action' => 'create', 'to_status' => 'draft', 'created_at' => $now]);

                    foreach (range(1, $k + 1) as $line) {
                        $this->db->table('schedule_items')->insert([
                            'schedule_id' => $sid, 'line_no' => $line, 'order_no' => sprintf('SO-%04d', $n * 10 + $line),
                            'customer_name' => 'Customer ' . chr(65 + $n % 5), 'article' => 'ART-' . (100 + $n % 9),
                            'color_name' => ['Navy', 'Hitam', 'Merah', 'Abu'][$n % 4], 'lot_no' => 'LOT-' . $n . $line,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                        $iid = (int) $this->db->insertID();
                        $code = $div === 'dyeing' ? 'BERAT' : 'PANJANG';
                        $this->db->table('schedule_item_params')->insert([
                            'schedule_item_id' => $iid, 'parameter_id' => $params[$code], 'kind' => 'plan',
                            'value_num' => $div === 'dyeing' ? 200 + $n * 7 : 1500 + $n * 40, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }
}

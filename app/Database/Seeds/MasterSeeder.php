<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Data master awal (PRD 5.9, 6.2, 6.3, 6.5). Aman dijalankan berulang: baris yang sudah ada dilewati.
 * Jam shift, proses, dan parameter adalah CONTOH AWAL - sesuaikan dengan keputusan manajemen/pabrik.
 */
class MasterSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---- Shift (nama saja, jam ada di pola) ----
        foreach ([['pagi', 'Pagi', 1], ['siang', 'Siang', 2], ['malam', 'Malam', 3]] as [$code, $name, $sort]) {
            $this->once('shifts', ['code' => $code], ['name' => $name, 'sort_order' => $sort, 'is_active' => 1]);
        }
        $shift = array_column($this->db->table('shifts')->get()->getResultArray(), 'id', 'code');

        // ---- Pola shift ----
        $patterns = [
            'NORMAL'        => ['Normal 3 shift', 'Pola default: 3 shift x 8 jam', [
                ['pagi', '07:00', '15:00', 0], ['siang', '15:00', '23:00', 0], ['malam', '23:00', '07:00', 1],
            ]],
            'PENDEK-2SHIFT' => ['Pendek 2 shift', 'Contoh: 2 shift x 5 jam (keputusan manajemen)', [
                ['pagi', '07:00', '12:00', 0], ['siang', '12:00', '17:00', 0],
            ]],
            'LIBUR'         => ['Libur', 'Tidak ada shift aktif', []],
        ];
        $patternId = [];
        foreach ($patterns as $code => [$name, $desc, $items]) {
            $patternId[$code] = $this->once('shift_patterns', ['code' => $code], [
                'name' => $name, 'description' => $desc, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($items as [$sc, $start, $end, $cross]) {
                $this->once('shift_pattern_items', ['pattern_id' => $patternId[$code], 'shift_id' => $shift[$sc]], [
                    'start_time' => $start . ':00', 'end_time' => $end . ':00', 'crosses_midnight' => $cross,
                ]);
            }
        }

        // ---- Proses (contoh) ----
        $processes = [
            ['dyeing', 'DYE-REAKTIF', 'Dyeing Reaktif', 480],
            ['dyeing', 'DYE-DISPERSE', 'Dyeing Disperse', 420],
            ['dyeing', 'DYE-WASH', 'Washing / Soaping', 180],
            ['finishing', 'FIN-STENTER', 'Stenter', 240],
            ['finishing', 'FIN-COMPACTOR', 'Compactor', 180],
            ['finishing', 'FIN-SETTING', 'Heat Setting', 150],
        ];
        foreach ($processes as [$div, $code, $name, $std]) {
            $this->once('processes', ['code' => $code], ['division' => $div, 'name' => $name, 'std_duration_min' => $std, 'is_active' => 1]);
        }

        // ---- Parameter (PRD 6.3) ----
        $parameters = [
            // code, nama, satuan, desimal
            ['BERAT', 'Berat Kain', 'kg', 1],
            ['PANJANG', 'Panjang Kain', 'm', 1],
            ['LEBAR', 'Lebar Kain', 'cm', 0],
            ['GRAMASI', 'Gramasi', 'gsm', 0],
            ['JML_ROLL', 'Jumlah Roll', 'roll', 0],
            ['LIQUOR_RATIO', 'Liquor Ratio', '1:x', 1],
            ['SUHU', 'Suhu Proses', '°C', 0],
            ['SPEED', 'Kecepatan', 'm/menit', 0],
            ['OVERFEED', 'Overfeed', '%', 0],
            ['NO_PROGRAM', 'No Program', '', 0],
            ['NO_NOZZLE', 'No Nozzle', '', 0],
            ['CUCI_MESIN', 'Cuci Mesin', '', 0],
        ];
        $param = [];
        foreach ($parameters as [$code, $name, $uom, $dec]) {
            $param[$code] = $this->once('parameters', ['code' => $code], [
                'name' => $name, 'uom' => $uom, 'data_type' => 'number', 'decimals' => $dec, 'is_active' => 1,
            ]);
        }

        // code => [division => [required, has_actual, primary, sort]]
        $map = [
            'BERAT'        => ['dyeing' => [1, 1, 1, 1], 'finishing' => [0, 0, 0, 2]],
            'PANJANG'      => ['dyeing' => [0, 0, 0, 2], 'finishing' => [1, 1, 1, 1]],
            'LEBAR'        => ['dyeing' => [0, 0, 0, 3], 'finishing' => [0, 1, 0, 3]],
            'GRAMASI'      => ['finishing' => [0, 1, 0, 4]],
            'JML_ROLL'     => ['dyeing' => [0, 1, 0, 4], 'finishing' => [0, 1, 0, 5]],
            'LIQUOR_RATIO' => ['dyeing' => [0, 0, 0, 5]],
            'SUHU'         => ['dyeing' => [0, 0, 0, 6], 'finishing' => [0, 0, 0, 6]],
            'SPEED'        => ['finishing' => [0, 0, 0, 7]],
            'OVERFEED'     => ['finishing' => [0, 0, 0, 8]],
            'NO_PROGRAM'   => ['dyeing' => [0, 0, 0, 7]],
            'NO_NOZZLE'    => ['dyeing' => [0, 0, 0, 8]],
            'CUCI_MESIN'   => ['dyeing' => [0, 0, 0, 9]],
        ];
        foreach ($map as $code => $divs) {
            foreach ($divs as $div => [$req, $act, $pri, $sort]) {
                $this->once('parameter_divisions', ['parameter_id' => $param[$code], 'division' => $div], [
                    'is_required' => $req, 'has_actual' => $act, 'is_primary' => $pri, 'sort_order' => $sort,
                ]);
            }
        }

        // ---- Mesin: pastikan ada + kapasitas contoh ----
        $machines = [
            ['JD-01', 'Jet Dyeing 1', 'dyeing', 500, 'BERAT'],
            ['JD-02', 'Jet Dyeing 2', 'dyeing', 500, 'BERAT'],
            ['JD-03', 'Jet Dyeing 3', 'dyeing', 750, 'BERAT'],
            ['ST-01', 'Stenter 1', 'finishing', 3000, 'PANJANG'],
            ['ST-02', 'Stenter 2', 'finishing', 3000, 'PANJANG'],
        ];
        foreach ($machines as [$code, $name, $type, $cap, $pcode]) {
            $mid = $this->once('machines', ['code' => $code], ['name' => $name, 'type' => $type, 'is_active' => 1, 'created_at' => $now]);
            $this->db->table('machines')->where('id', $mid)->where('capacity', null)
                ->update(['capacity' => $cap, 'capacity_param_id' => $param[$pcode]]);
        }

        // ---- Pengaturan (PRD 6.5) ----
        $settings = [
            'schedule.overshift_grace_minutes'    => '15',
            'schedule.handover_escalation_minutes' => '15',
            'ifs.provider'                         => 'dummy',
            'ifs.qty_tolerance_percent'            => '5',
            'shift.default_pattern_id'             => (string) $patternId['NORMAL'],
        ];
        foreach ($settings as $name => $value) {
            $this->db->query('INSERT IGNORE INTO `app_settings` (`name`, `value`) VALUES (?, ?)', [$name, $value]);
        }
        $this->db->query('INSERT IGNORE INTO `app_settings` (`name`, `value`) VALUES (?, ?)', ['schedule.auto_approve', '0']);
    }

    /** Sisipkan baris bila belum ada (cocok pada $where); kembalikan id-nya. */
    private function once(string $table, array $where, array $data): int
    {
        $row = $this->db->table($table)->where($where)->get()->getRowArray();
        if ($row) {
            return (int) ($row['id'] ?? 0);
        }
        $this->db->table($table)->insert($where + $data);

        return (int) $this->db->insertID();
    }
}

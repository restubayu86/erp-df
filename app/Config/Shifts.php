<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;

/** ASUMSI jam shift — sesuaikan dengan pabrik. Urutan array = urutan tampil di TV. */
class Shifts extends BaseConfig
{
    public array $list = [
        'pagi'  => ['label' => 'Pagi',  'start' => '07:00', 'end' => '15:00'],
        'siang' => ['label' => 'Siang', 'start' => '15:00', 'end' => '23:00'],
        'malam' => ['label' => 'Malam', 'start' => '23:00', 'end' => '07:00'],
    ];

    private function minutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }

    /** Kode shift yang sedang berjalan. */
    public function current(?int $ts = null): string
    {
        $ts  ??= time();
        $now  = (int) date('G', $ts) * 60 + (int) date('i', $ts);

        foreach ($this->list as $code => $s) {
            $a = $this->minutes($s['start']);
            $b = $this->minutes($s['end']);
            $in = $a < $b ? ($now >= $a && $now < $b) : ($now >= $a || $now < $b);
            if ($in) {
                return $code;
            }
        }
        return array_key_first($this->list);
    }

    /** Tanggal produksi: shift malam lewat tengah malam masih milik tanggal sebelumnya. */
    public function productionDate(?int $ts = null): string
    {
        $ts  ??= time();
        $now  = (int) date('G', $ts) * 60 + (int) date('i', $ts);
        $s    = $this->list[$this->current($ts)];

        $crossesMidnight = $this->minutes($s['start']) > $this->minutes($s['end']);
        if ($crossesMidnight && $now < $this->minutes($s['end'])) {
            return date('Y-m-d', strtotime('-1 day', $ts));
        }
        return date('Y-m-d', $ts);
    }
}

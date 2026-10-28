<?php

namespace App\Modules\Core\Services;

use App\Models\SettingModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Satu-satunya sumber jam efektif shift (PRD 5.9 / 6.2): form schedule, deteksi overshift,
 * delegasi, tablet, dan TV semua memakai class ini.
 *
 * Jam efektif (tanggal, shift): pola di shift_calendar untuk tanggal itu; bila tidak ada,
 * pola default (app_settings shift.default_pattern_id). Shift yang tidak ada di pola = tidak aktif.
 */
class ShiftCalendarService
{
    private BaseConnection $db;

    /** @var array<string, list<array<string,mixed>>> */
    private array $cache = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function defaultPatternId(): ?int
    {
        $v = (new SettingModel())->getValue('shift.default_pattern_id', '');

        return $v !== '' ? (int) $v : null;
    }

    /** Pola yang berlaku pada tanggal: ['id','code','name','reason','source' => calendar|default] atau null. */
    public function pattern(string $date): ?array
    {
        $row = $this->db->table('shift_calendar c')
            ->select('p.id, p.code, p.name, c.reason')
            ->join('shift_patterns p', 'p.id = c.pattern_id')
            ->where('c.work_date', $date)
            ->get()->getRowArray();
        if ($row) {
            return $row + ['source' => 'calendar'];
        }

        $id = $this->defaultPatternId();
        if (! $id) {
            return null;
        }
        $row = $this->db->table('shift_patterns')->select('id, code, name')->where('id', $id)->get()->getRowArray();

        return $row ? $row + ['reason' => null, 'source' => 'default'] : null;
    }

    /**
     * Shift aktif pada tanggal, urut sort_order.
     * @return list<array{shift_id:int,code:string,name:string,start:string,end:string,start_at:string,end_at:string,crosses_midnight:bool}>
     */
    public function activeShifts(string $date): array
    {
        if (isset($this->cache[$date])) {
            return $this->cache[$date];
        }

        $pattern = $this->pattern($date);
        if (! $pattern) {
            return $this->cache[$date] = [];
        }

        $rows = $this->db->table('shift_pattern_items i')
            ->select('s.id AS shift_id, s.code, s.name, i.start_time, i.end_time, i.crosses_midnight')
            ->join('shifts s', 's.id = i.shift_id')
            ->where('i.pattern_id', (int) $pattern['id'])
            ->where('s.is_active', 1)
            ->orderBy('s.sort_order')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $startAt = $date . ' ' . $r['start_time'];
            $endDate = ((int) $r['crosses_midnight'] === 1 || $r['end_time'] <= $r['start_time'])
                ? date('Y-m-d', strtotime($date . ' +1 day')) : $date;
            $out[] = [
                'shift_id'         => (int) $r['shift_id'],
                'code'             => $r['code'],
                'name'             => $r['name'],
                'start'            => substr($r['start_time'], 0, 5),
                'end'              => substr($r['end_time'], 0, 5),
                'start_at'         => $startAt,
                'end_at'           => $endDate . ' ' . $r['end_time'],
                'crosses_midnight' => $endDate !== $date,
            ];
        }

        return $this->cache[$date] = $out;
    }

    /** Jendela waktu shift pada tanggal, atau null bila shift tidak aktif hari itu. */
    public function shiftWindow(string $date, int $shiftId): ?array
    {
        foreach ($this->activeShifts($date) as $s) {
            if ($s['shift_id'] === $shiftId) {
                return $s;
            }
        }

        return null;
    }

    /**
     * Shift yang sedang berjalan pada $ts (default: sekarang).
     * @return array{date:string, shift:?array}  date = tanggal produksi (tanggal shift DIMULAI)
     */
    public function current(?int $ts = null): array
    {
        $ts ??= time();
        $now = date('Y-m-d H:i:s', $ts);

        // Hari kemarin dicek dulu: shift malam yang melewati tengah malam masih milik tanggal kemarin.
        foreach ([date('Y-m-d', $ts - 86400), date('Y-m-d', $ts)] as $d) {
            foreach ($this->activeShifts($d) as $s) {
                if ($now >= $s['start_at'] && $now < $s['end_at']) {
                    return ['date' => $d, 'shift' => $s];
                }
            }
        }

        return ['date' => date('Y-m-d', $ts), 'shift' => null];
    }

    /** Tanggal produksi saat ini. */
    public function productionDate(?int $ts = null): string
    {
        return $this->current($ts)['date'];
    }

    /** Shift aktif berikutnya setelah waktu tertentu (bisa di tanggal berikutnya, aturan D10). */
    public function nextActive(string $afterDateTime): ?array
    {
        $base = strtotime($afterDateTime);
        for ($i = -1; $i <= 14; $i++) {
            $d = date('Y-m-d', strtotime("+{$i} day", $base));
            foreach ($this->activeShifts($d) as $s) {
                if ($s['start_at'] > $afterDateTime) {
                    return ['date' => $d, 'shift' => $s];
                }
            }
        }

        return null;
    }

    /** Tetapkan pola untuk rentang tanggal (Admin, alasan wajib). Mengembalikan jumlah hari yang ditetapkan. */
    public function assignRange(string $from, string $to, int $patternId, string $reason, ?int $userId): int
    {
        $start = strtotime($from);
        $end   = strtotime($to);
        if (! $start || ! $end || $end < $start || ($end - $start) > 366 * 86400) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $n   = 0;
        for ($t = $start; $t <= $end; $t = strtotime('+1 day', $t)) {
            $this->db->query(
                'INSERT INTO shift_calendar (work_date, pattern_id, reason, set_by, created_at, updated_at) VALUES (?,?,?,?,?,?) '
                . 'ON DUPLICATE KEY UPDATE pattern_id = VALUES(pattern_id), reason = VALUES(reason), set_by = VALUES(set_by), updated_at = VALUES(updated_at)',
                [date('Y-m-d', $t), $patternId, $reason, $userId, $now, $now]
            );
            $n++;
        }
        $this->cache = [];

        return $n;
    }

    /** Hapus penetapan: tanggal kembali memakai pola default. */
    public function clearDate(string $date): void
    {
        $this->db->table('shift_calendar')->where('work_date', $date)->delete();
        $this->cache = [];
    }

    /** Ringkasan untuk /api/shift/effective. */
    public function effective(string $date): array
    {
        $p = $this->pattern($date);

        return [
            'date'    => $date,
            'pattern' => $p ? ['code' => $p['code'], 'name' => $p['name'], 'source' => $p['source'], 'reason' => $p['reason']] : null,
            'shifts'  => $this->activeShifts($date),
        ];
    }
}

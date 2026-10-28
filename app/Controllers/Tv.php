<?php

namespace App\Controllers;

use App\Modules\Core\Services\ScheduleService;
use App\Modules\Core\Services\ShiftCalendarService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Layar TV (PRD TV-01..07): /display/{dyeing|finishing}. Tanpa login, dilindungi token (filter tvtoken). Hanya baca. */
class Tv extends BaseController
{
    use ResponseTrait;

    private function division(string $d): string
    {
        if (! in_array($d, ['dyeing', 'finishing'], true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $d;
    }

    public function board(string $division)
    {
        return view('tv/schedule', ['token' => (string) $this->request->getGet('key'), 'division' => $this->division($division)]);
    }

    public function data(string $division)
    {
        $division = $this->division($division);
        $cal      = new ShiftCalendarService();
        $cur      = $cal->current();
        $date     = $cur['date'];

        // Jam efektif shift hari ini (SHF-05), dikunci per kode shift untuk tampilan
        $shifts = [];
        foreach ($cal->activeShifts($date) as $s) {
            $shifts[$s['code']] = ['label' => $s['name'], 'start' => $s['start'], 'end' => $s['end']];
        }

        $rows = db_connect()->table('schedules s')
            ->select('sh.code AS shift, s.priority, s.status, s.start_at, s.end_at, s.revision_no, m.code AS machine_code, m.name AS machine, '
                . 'p.name AS process, u.username AS operator, '
                . '(SELECT GROUP_CONCAT(DISTINCT i.order_no ORDER BY i.line_no SEPARATOR ", ") FROM schedule_items i WHERE i.schedule_id = s.id) AS orders, '
                . '(SELECT GROUP_CONCAT(DISTINCT i.customer_name ORDER BY i.line_no SEPARATOR ", ") FROM schedule_items i WHERE i.schedule_id = s.id) AS customers, '
                . 's.id')
            ->join('machines m', 'm.id = s.machine_id')
            ->join('shifts sh', 'sh.id = s.shift_id')
            ->join('processes p', 'p.id = s.process_id', 'left')
            ->join('users u', 'u.id = s.current_operator_id', 'left')
            ->where('s.division', $division)->where('s.deleted_at', null)
            ->where('s.work_date', $date)
            ->whereIn('s.status', config('ScheduleFlow')->tvVisible)   // yang diajukan ulang (submitted) otomatis tidak tampil
            ->orderBy('sh.sort_order')->orderBy('m.code')->orderBy('s.sequence')
            ->get()->getResultArray();

        $over = (new ScheduleService($cal))->overshiftRows($division);
        foreach ($rows as &$r) {
            $r['overshift'] = isset($over[(int) $r['id']]);
            unset($r['id']);
        }

        return $this->respond([
            'updated'       => date('c'),
            'date'          => $date,
            'division'      => $division,
            'current_shift' => $cur['shift']['code'] ?? null,
            'shifts'        => $shifts,
            'rows'          => $rows,
        ]);
    }
}

<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Services\ShiftCalendarService;

/** Pola & kalender shift - khusus Admin (K9, permission shift.manage). Perubahan langsung berlaku, alasan wajib. */
class ShiftCalendar extends BaseController
{
    public function index(): string
    {
        $db  = db_connect();
        $cal = new ShiftCalendarService($db);

        $patterns = $db->table('shift_patterns')->orderBy('id')->get()->getResultArray();
        foreach ($patterns as &$p) {
            $p['items'] = $db->table('shift_pattern_items i')->select('s.name, i.start_time, i.end_time, i.crosses_midnight')
                ->join('shifts s', 's.id = i.shift_id')->where('i.pattern_id', $p['id'])->orderBy('s.sort_order')->get()->getResultArray();
        }

        $today = date('Y-m-d');
        $rows  = $db->table('shift_calendar c')->select('c.work_date, c.reason, c.updated_at, p.code, p.name, u.username')
            ->join('shift_patterns p', 'p.id = c.pattern_id')->join('users u', 'u.id = c.set_by', 'left')
            ->where('c.work_date >=', date('Y-m-d', strtotime('-7 day')))->orderBy('c.work_date')->limit(120)->get()->getResultArray();

        return $this->page('admin/shift_calendar', [
            'patterns'  => $patterns,
            'calendar'  => $rows,
            'today'     => $today,
            'todayInfo' => $cal->effective($today),
            'defaultId' => $cal->defaultPatternId(),
        ], 'Pola & Kalender Shift', ['Pengaturan' => null, 'Kalender Shift' => null]);
    }

    public function assign()
    {
        $p      = $this->request->getPost();
        $from   = (string) ($p['date_from'] ?? '');
        $to     = (string) (($p['date_to'] ?? '') ?: $from);
        $reason = trim((string) ($p['reason'] ?? ''));
        $pid    = (int) ($p['pattern_id'] ?? 0);

        $exists = db_connect()->table('shift_patterns')->where('id', $pid)->where('is_active', 1)->countAllResults() > 0;
        if (! $exists || ! strtotime($from) || ! strtotime($to) || $to < $from || $reason === '') {
            return redirect()->back()->withInput()->with('error', 'Lengkapi pola, tanggal (dari ≤ sampai), dan alasan keputusan.');
        }

        $n = (new ShiftCalendarService())->assignRange($from, $to, $pid, mb_substr($reason, 0, 255), (int) auth()->id());

        return $n > 0
            ? redirect()->to('master/shift-calendar')->with('message', "Pola shift ditetapkan untuk {$n} hari. Jadwal yang sudah ada tidak berubah otomatis.")
            : redirect()->back()->withInput()->with('error', 'Rentang tanggal tidak valid (maksimal 366 hari).');
    }

    public function clear()
    {
        $date = (string) $this->request->getPost('work_date');
        if (strtotime($date)) {
            (new ShiftCalendarService())->clearDate($date);
        }

        return redirect()->to('master/shift-calendar')->with('message', 'Penetapan dihapus; tanggal memakai pola default.');
    }
}

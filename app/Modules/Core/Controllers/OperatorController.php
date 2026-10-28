<?php

namespace App\Modules\Core\Controllers;

use App\Modules\BaseModuleController;
use App\Modules\Core\Services\ScheduleService;
use App\Modules\Core\Services\ShiftCalendarService;
use App\Modules\Core\Services\WorkflowService;

/**
 * Tablet operator (sama untuk semua modul). Fase ini: daftar antrean per mesin untuk shift terpilih.
 * Jadwal running/paused dari shift sebelumnya tetap tampil agar tidak "hilang" saat pergantian shift.
 */
abstract class OperatorController extends BaseModuleController
{
    public function index(): string
    {
        $cal    = new ShiftCalendarService();
        $now    = $cal->current();
        $date   = (string) ($this->request->getGet('date') ?: $now['date']);
        $shifts = $cal->activeShifts($date);

        $shiftId = (int) $this->request->getGet('shift');
        if (! array_filter($shifts, static fn ($s) => $s['shift_id'] === $shiftId)) {
            $shiftId = $date === $now['date'] && $now['shift'] ? $now['shift']['shift_id'] : ($shifts[0]['shift_id'] ?? 0);
        }

        $svc  = new ScheduleService($cal);
        $prim = $svc->primaryParam($this->moduleType);
        $qty  = $prim
            ? '(SELECT SUM(ip.value_num) FROM schedule_items i JOIN schedule_item_params ip ON ip.schedule_item_id = i.id '
              . "AND ip.kind = 'plan' AND ip.parameter_id = " . (int) $prim['id'] . ' WHERE i.schedule_id = s.id)'
            : 'NULL';

        $rows = db_connect()->table('schedules s')
            ->select('s.*, m.name AS machine, m.code AS machine_code, p.name AS process_name, sh.name AS shift_name, '
                . '(SELECT i.order_no FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS order_first, '
                . '(SELECT i.customer_name FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS customer_first, '
                . '(SELECT COUNT(*) FROM schedule_items i WHERE i.schedule_id = s.id) AS item_count, ' . $qty . ' AS qty_primary')
            ->join('machines m', 'm.id = s.machine_id')
            ->join('shifts sh', 'sh.id = s.shift_id')
            ->join('processes p', 'p.id = s.process_id', 'left')
            ->where('s.division', $this->moduleType)->where('s.deleted_at', null)
            ->groupStart()
                ->groupStart()
                    ->where('s.work_date', $date)->where('s.shift_id', $shiftId)
                    ->whereIn('s.status', ['approved', 'running', 'paused', 'done'])
                ->groupEnd()
                ->orWhereIn('s.status', ['running', 'paused'])
            ->groupEnd()
            ->orderBy('m.code')->orderBy('s.sequence')
            ->get()->getResultArray();

        $byMachine = [];
        foreach ($rows as $r) {
            $byMachine[$r['machine_code'] . ' – ' . $r['machine']][] = $r;
        }

        $mod = ucfirst($this->moduleName);

        return $this->modulePage('operator/index', [
            'byMachine' => $byMachine,
            'shiftId'   => $shiftId,
            'date'      => $date,
            'shifts'    => $shifts,
            'overshift' => $svc->overshiftRows($this->moduleType),
            'workflow'  => new WorkflowService(),
            'labels'    => config('ScheduleFlow')->labels,
            'qtyUom'    => $prim['uom'] ?? '',
        ], "Tablet Operator {$mod}", [$mod => null, 'Tablet Operator' => null]);
    }
}

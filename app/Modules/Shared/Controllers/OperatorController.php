<?php

namespace App\Modules\Shared\Controllers;

use App\Libraries\ScheduleWorkflow;
use App\Modules\BaseModuleController;

/** Halaman tablet operator, sama untuk semua modul. */
abstract class OperatorController extends BaseModuleController
{
    public function index(): string
    {
        $cfg   = config('Shifts');
        $shift = $this->request->getGet('shift');
        $shift = isset($cfg->list[$shift]) ? $shift : $cfg->current();
        $date  = $this->request->getGet('date') ?: $cfg->productionDate();

        $rows = db_connect()->table('schedules s')
            ->select('s.*, m.name AS machine, m.code AS machine_code')
            ->join('machines m', 'm.id = s.machine_id')
            ->where('m.type', $this->moduleType)
            ->where('s.work_date', $date)
            ->where('s.shift', $shift)
            ->whereIn('s.status', ['approved', 'running', 'paused', 'done'])
            ->orderBy('m.name')
            ->orderBy('s.priority')
            ->get()->getResultArray();

        $byMachine = [];
        foreach ($rows as $r) {
            $byMachine[$r['machine']][] = $r;
        }

        $mod = ucfirst($this->moduleName);

        return $this->modulePage('operator/index', [
            'byMachine' => $byMachine,
            'shift'     => $shift,
            'date'      => $date,
            'shifts'    => $cfg->list,
            'workflow'  => new ScheduleWorkflow(),
            'labels'    => config('ScheduleFlow')->labels,
        ], "Tugas {$mod} - Shift " . $cfg->list[$shift]['label'], [$mod => null, 'Tablet Operator' => null]);
    }
}

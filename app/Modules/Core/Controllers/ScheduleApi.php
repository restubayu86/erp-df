<?php

namespace App\Modules\Core\Controllers;

use CodeIgniter\RESTful\ResourceController;

/**
 * Schedule API Controller
 * API endpoints untuk schedule operations
 */
class ScheduleApi extends ResourceController
{
    protected $format = 'json';

    /**
     * Get related production schedule for repair category
     * Used when kategori = "perbaikan" to load related production data
     * 
     * Query params:
     * - machine: machine_id
     * - date: work_date (YYYY-MM-DD)
     * - kategori: kategori filter (default: produksi)
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function related()
    {
        $machineId = $this->request->getGet('machine');
        $date = $this->request->getGet('date');
        $kategori = $this->request->getGet('kategori') ?? 'produksi';
        
        // Validate inputs
        if (!$machineId || !$date) {
            return $this->failValidationErrors('Parameter machine dan date wajib diisi');
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->failValidationErrors('Format tanggal tidak valid (gunakan YYYY-MM-DD)');
        }
        
        // Find related production schedule
        $db = db_connect();
        $schedule = $db->table('schedules s')
            ->select('s.id, s.schedule_no, s.process_id, s.work_date, s.shift_id')
            ->where('s.machine_id', $machineId)
            ->where('s.work_date', $date)
            ->where('s.category', $kategori)
            ->where('s.deleted_at', null)
            ->whereNotIn('s.status', ['cancelled', 'rejected'])
            ->orderBy('s.created_at', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();
        
        if (!$schedule) {
            return $this->failNotFound('Tidak ada jadwal produksi terkait pada mesin dan tanggal ini');
        }
        
        // Get first item details from related schedule
        $item = $db->table('schedule_items')
            ->where('schedule_id', $schedule['id'])
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();
        
        if (!$item) {
            // Schedule has no items, just return schedule data
            return $this->respond([
                'id' => $schedule['id'],
                'schedule_no' => $schedule['schedule_no'],
                'process_id' => $schedule['process_id'],
                'work_date' => $schedule['work_date'],
                'shift_id' => $schedule['shift_id'],
                'design_first' => null,
                'color_first' => null,
                'lot_first' => null,
                'mp_first' => null,
                'parameters' => []
            ]);
        }
        
        // Get parameters for first item
        $params = [];
        $paramRows = $db->table('schedule_item_params ip')
            ->select('p.code, ip.value_num, ip.value_text')
            ->join('parameters p', 'p.id = ip.parameter_id')
            ->where('ip.schedule_item_id', $item['id'])
            ->where('ip.kind', 'plan')
            ->get()
            ->getResultArray();
        
        foreach ($paramRows as $p) {
            $params[$p['code']] = $p['value_num'] ?? $p['value_text'];
        }
        
        // Return complete related production data
        return $this->respond([
            'id' => $schedule['id'],
            'schedule_no' => $schedule['schedule_no'],
            'process_id' => $schedule['process_id'],
            'work_date' => $schedule['work_date'],
            'shift_id' => $schedule['shift_id'],
            'design_first' => $item['design_name'] ?? null,
            'color_first' => $item['color_name'] ?? null,
            'lot_first' => $item['lot_no'] ?? null,
            'mp_first' => $item['mp_no'] ?? null,
            'parameters' => $params
        ]);
    }
}

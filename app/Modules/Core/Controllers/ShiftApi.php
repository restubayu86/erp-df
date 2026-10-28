<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Services\ShiftCalendarService;

/** GET /api/shift/effective?date=YYYY-MM-DD : shift aktif + jam efektif (PRD 7). */
class ShiftApi extends BaseController
{
    public function effective()
    {
        $date = (string) ($this->request->getGet('date') ?: date('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Tanggal tidak valid.']);
        }

        return $this->response->setJSON(['ok' => true] + (new ShiftCalendarService())->effective($date));
    }
}

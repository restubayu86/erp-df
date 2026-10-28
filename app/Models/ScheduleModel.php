<?php
namespace App\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table         = 'schedules';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'machine_id', 'order_no', 'customer', 'process', 'work_date', 'shift', 'priority',
        'start_at', 'end_at', 'status', 'created_by',
        'reviewed_by', 'reviewed_at', 'review_note', 'actual_start', 'actual_end',
        'category', 'design', 'color', 'mp_no', 'wash_machine', 'progress', 'dye_lot_no', 'dop_no', 'note',
    ];
    protected $useTimestamps = true;
}

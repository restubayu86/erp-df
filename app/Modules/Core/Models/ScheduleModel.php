<?php

namespace App\Modules\Core\Models;

use CodeIgniter\Model;

class ScheduleModel extends Model
{
    protected $table         = 'schedules';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'schedule_no', 'division', 'machine_id', 'process_id', 'work_date', 'shift_id', 'sequence', 'priority',
        'start_at', 'end_at', 'status', 'revision_no', 'copied_from_id', 'notes', 'actual_start', 'actual_end',
        'current_operator_id', 'current_shift_id', 'current_shift_date', 'created_by',
        'submitted_by', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_note', 'started_by', 'finished_by',
    ];
}

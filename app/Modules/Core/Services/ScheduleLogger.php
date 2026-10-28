<?php

namespace App\Modules\Core\Services;

/** Satu pintu penulisan schedule_logs (PRD AUD-01). Log tidak pernah dihapus. */
class ScheduleLogger
{
    public static function write(
        int $scheduleId,
        string $action,
        ?string $from,
        ?string $to,
        ?int $userId,
        ?string $note = null,
        ?array $changes = null
    ): void {
        db_connect()->table('schedule_logs')->insert([
            'schedule_id' => $scheduleId,
            'action'      => $action,
            'from_status' => $from,
            'to_status'   => $to,
            'user_id'     => $userId,
            'note'        => $note !== null ? mb_substr($note, 0, 255) : null,
            'changes'     => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}

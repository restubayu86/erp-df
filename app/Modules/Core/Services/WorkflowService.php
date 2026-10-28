<?php

namespace App\Modules\Core\Services;

use App\Models\SettingModel;
use App\Modules\Core\Models\ScheduleModel;

/** State machine & approval (PRD 5.1-5.3). Satu pintu semua perubahan status. */
class WorkflowService
{
    /** Status tujuan yang boleh dilakukan $user dari status $from. */
    public function allowedFor(string $from, $user): array
    {
        $out = [];
        foreach (config('ScheduleFlow')->transitions[$from] ?? [] as $to => $perm) {
            if ($this->can($user, $perm)) {
                $out[] = $to;
            }
        }

        return $out;
    }

    /** @return array{ok:bool,message:string} */
    public function move(int $id, string $to, $user, ?string $note = null): array
    {
        $flow  = config('ScheduleFlow');
        $model = new ScheduleModel();
        $row   = $model->find($id);

        if (! $row) {
            return $this->fail('Schedule tidak ditemukan.');
        }
        if (! $user->can('division.' . $row['division'])) {
            return $this->fail('Anda tidak punya akses ke divisi ini.');
        }

        $perm = $flow->transitions[$row['status']][$to] ?? null;
        if ($perm === null) {
            return $this->fail("Status {$row['status']} tidak bisa diubah ke {$to}.");
        }
        if (! $this->can($user, $perm)) {
            return $this->fail('Anda tidak punya hak untuk aksi ini.');
        }

        $note = trim((string) $note);
        if (isset($flow->noteRequired[$to]) && $note === '') {
            return $this->fail($flow->noteRequired[$to]);
        }

        if ($to === 'running') {
            $busy = $model->where('machine_id', $row['machine_id'])->where('status', 'running')->where('id !=', $id)->first();
            if ($busy) {
                return $this->fail("Mesin masih menjalankan {$busy['schedule_no']}. Selesaikan dulu sebelum memulai yang lain.");
            }
        }

        $db = db_connect();
        $db->transStart();

        $this->apply($model, $row, $to, (int) $user->id, $note !== '' ? $note : null);

        // Auto approve: submitted -> approved otomatis, dicatat sebagai sistem
        if ($to === 'submitted' && $this->autoApprove()) {
            $this->apply($model, $model->find($id), 'approved', null, 'Auto ACC');
        }

        $db->transComplete();

        return $db->transStatus() ? ['ok' => true, 'message' => 'Status diperbarui.'] : $this->fail('Gagal menyimpan.');
    }

    /**
     * Schedule approved yang diedit field kritisnya diajukan ulang (PRD 5.3):
     * kembali ke submitted, revision_no + 1, hilang dari tablet/TV sampai disetujui lagi.
     */
    public function resubmit(int $id, ?int $userId, array $changes): void
    {
        $model = new ScheduleModel();
        $row   = $model->find($id);
        $now   = date('Y-m-d H:i:s');

        $model->update($id, [
            'status'       => 'submitted',
            'revision_no'  => (int) $row['revision_no'] + 1,
            'submitted_by' => $userId,
            'submitted_at' => $now,
        ]);
        ScheduleLogger::write($id, 'resubmit', $row['status'], 'submitted', $userId, 'Diedit setelah disetujui, diajukan ulang', $changes);

        if ($this->autoApprove()) {
            $this->apply($model, $model->find($id), 'approved', null, 'Auto ACC');
        }
    }

    private function apply(ScheduleModel $model, array $row, string $to, ?int $userId, ?string $note): void
    {
        $now  = date('Y-m-d H:i:s');
        $data = ['status' => $to];

        switch ($to) {
            case 'submitted':
                $data += ['submitted_by' => $userId, 'submitted_at' => $now];
                // diajukan ulang setelah dikembalikan Kabag: tandai sebagai revisi ke-n
                if ($row['status'] === 'revision') {
                    $data['revision_no'] = (int) $row['revision_no'] + 1;
                }
                break;

            case 'approved':
            case 'revision':
            case 'rejected':
                $data += ['reviewed_by' => $userId, 'reviewed_at' => $now, 'review_note' => $note !== null ? mb_substr($note, 0, 255) : null];
                break;

            case 'running':
                if (empty($row['actual_start'])) {
                    // Waktu aktual = waktu server saat tombol ditekan (aturan bisnis no. 7)
                    $cur = (new ShiftCalendarService())->current();
                    $data['actual_start']        = $now;
                    $data['started_by']          = $userId;
                    $data['current_operator_id'] = $userId;
                    $data['current_shift_id']    = $cur['shift']['shift_id'] ?? $row['shift_id'];
                    $data['current_shift_date']  = $cur['shift'] ? $cur['date'] : $row['work_date'];
                }
                break;

            case 'done':
                $data += ['actual_end' => $now, 'finished_by' => $userId];
                break;
        }

        $model->update($row['id'], $data);

        ScheduleLogger::write(
            (int) $row['id'],
            config('ScheduleFlow')->actions[$to] ?? 'update',
            $row['status'],
            $to,
            $userId,
            $note
        );
    }

    private function autoApprove(): bool
    {
        return (new SettingModel())->getValue('schedule.auto_approve', '0') === '1';
    }

    /** @param string|list<string> $perm */
    private function can($user, $perm): bool
    {
        foreach ((array) $perm as $p) {
            if ($user->can($p)) {
                return true;
            }
        }

        return false;
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}

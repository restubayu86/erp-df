<?php
namespace App\Libraries;

use App\Models\ScheduleModel;
use App\Models\SettingModel;

/** Satu pintu untuk semua perubahan status: validasi alur + hak akses + log + auto ACC. */
class ScheduleWorkflow
{
    /** Status tujuan yang boleh dilakukan $user dari status $from. */
    public function allowedFor(string $from, $user): array
    {
        $out = [];
        foreach (config('ScheduleFlow')->transitions[$from] ?? [] as $to => $perm) {
            if ($user->can($perm)) {
                $out[] = $to;
            }
        }
        return $out;
    }

    /** @return array{ok: bool, message: string} */
    public function move(int $id, string $to, $user, ?string $note = null): array
    {
        $model = new ScheduleModel();
        $row   = $model->find($id);

        if (! $row) {
            return $this->fail('Schedule tidak ditemukan.');
        }

        $perm = config('ScheduleFlow')->transitions[$row['status']][$to] ?? null;
        if ($perm === null) {
            return $this->fail("Status {$row['status']} tidak bisa diubah ke {$to}.");
        }
        if (! $user->can($perm)) {
            return $this->fail('Anda tidak punya hak untuk aksi ini.');
        }
        if ($to === 'rejected' && trim((string) $note) === '') {
            return $this->fail('Alasan penolakan wajib diisi.');
        }
        if ($to === 'running') {
            $busy = $model->where('machine_id', $row['machine_id'])->where('status', 'running')->where('id !=', $id)->first();
            if ($busy) {
                return $this->fail("Mesin masih menjalankan order {$busy['order_no']}. Selesaikan atau pause dulu.");
            }
        }

        $db = db_connect();
        $db->transStart();

        $this->apply($model, $row, $to, (int) $user->id, $note);

        if ($to === 'submitted' && (new SettingModel())->getValue('schedule.auto_approve', '0') === '1') {
            $this->apply($model, $model->find($id), 'approved', null, 'Auto ACC');
        }

        $db->transComplete();

        return $db->transStatus() ? ['ok' => true, 'message' => 'Status diperbarui.'] : $this->fail('Gagal menyimpan.');
    }

    private function apply(ScheduleModel $model, array $row, string $to, ?int $userId, ?string $note): void
    {
        $now  = date('Y-m-d H:i:s');
        $data = ['status' => $to];

        if ($to === 'approved' || $to === 'rejected') {
            $data += ['reviewed_by' => $userId, 'reviewed_at' => $now, 'review_note' => $note];
        }
        if ($to === 'running' && empty($row['actual_start'])) {
            $data['actual_start'] = $now;
        }
        if ($to === 'done') {
            $data['actual_end'] = $now;
        }

        $model->update($row['id'], $data);

        db_connect()->table('schedule_logs')->insert([
            'schedule_id' => $row['id'],
            'from_status' => $row['status'],
            'to_status'   => $to,
            'user_id'     => $userId,
            'note'        => $note,
            'created_at'  => $now,
        ]);
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}

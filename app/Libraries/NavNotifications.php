<?php

namespace App\Libraries;

/**
 * Notifikasi navbar berbasis kondisi (bukan event): dihitung langsung dari tabel schedules,
 * dibatasi ke bagian (Dyeing / Finishing) tempat user bekerja. Tidak ada status "dibaca";
 * notifikasi hilang sendiri begitu pekerjaannya selesai.
 */
class NavNotifications
{
    /**
     * @return list<array{icon:string,tone:string,title:string,text:string,url:string,count:int,time:?string}>
     */
    public function forUser($user): array
    {
        if (! $user) {
            return [];
        }
        $types = user_modules($user);
        if (! $types) {
            return [];
        }

        try {
            return $this->build($user, $types);
        } catch (\Throwable $e) {
            log_message('error', 'NavNotifications: ' . $e->getMessage());
            return [];   // navbar tidak boleh membuat halaman error (mis. tabel belum dimigrasi)
        }
    }

    private function base(array $types, string $status)
    {
        return db_connect()->table('schedules s')
            ->select('COUNT(*) AS n, MAX(s.updated_at) AS last_at')
            ->whereIn('s.division', $types)
            ->where('s.deleted_at', null)
            ->where('s.status', $status);
    }

    private function build($user, array $types): array
    {
        $out = [];
        $fmt = static fn (?string $t) => $t ? date('d M H:i', strtotime($t)) : null;

        // Kepala Bagian: schedule menunggu ACC
        if ($user->can('schedule.approve')) {
            $r = $this->base($types, 'submitted')->get()->getRowArray();
            if ((int) $r['n'] > 0) {
                $out[] = [
                    'icon' => 'clock', 'tone' => 'warning', 'count' => (int) $r['n'],
                    'title' => 'Menunggu ACC',
                    'text'  => "{$r['n']} schedule menunggu persetujuan Anda.",
                    'url'   => site_url($types[0] . '/schedule?status[]=submitted'), 'time' => $fmt($r['last_at']),
                ];
            }
        }

        // Scheduler: schedule buatan sendiri yang ditolak
        if ($user->can('schedule.edit')) {
            $r = $this->base($types, 'rejected')->where('s.created_by', (int) $user->id)->get()->getRowArray();
            if ((int) $r['n'] > 0) {
                $out[] = [
                    'icon' => 'alert-circle', 'tone' => 'danger', 'count' => (int) $r['n'],
                    'title' => 'Schedule ditolak',
                    'text'  => "{$r['n']} schedule Anda ditolak dan perlu direvisi.",
                    'url'   => site_url($types[0] . '/schedule?status[]=rejected'), 'time' => $fmt($r['last_at']),
                ];
            }
        }

        // Operator: order siap dijalankan pada shift & tanggal produksi saat ini
        if ($user->can('operator.access')) {
            $cal = new \App\Modules\Core\Services\ShiftCalendarService();
            $cur = $cal->current();
            if ($cur['shift']) {
                $r = db_connect()->table('schedules s')
                    ->select('COUNT(*) AS n, MAX(s.updated_at) AS last_at')
                    ->whereIn('s.division', $types)->where('s.deleted_at', null)
                    ->where('s.work_date', $cur['date'])->where('s.shift_id', $cur['shift']['shift_id'])
                    ->whereIn('s.status', ['approved', 'paused'])
                    ->get()->getRowArray();
                if ((int) $r['n'] > 0) {
                    $out[] = [
                        'icon' => 'play-circle', 'tone' => 'primary', 'count' => (int) $r['n'],
                        'title' => 'Siap dijalankan',
                        'text'  => "{$r['n']} order menunggu dijalankan di shift {$cur['shift']['name']}.",
                        'url'   => site_url($types[0] . '/operator'), 'time' => $fmt($r['last_at']),
                    ];
                }
            }
        }

        return $out;
    }
}

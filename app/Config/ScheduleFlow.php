<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Alur status schedule (PRD 5.1 / 5.2). Format: status_asal => [status_tujuan => permission (atau daftar, salah satu cukup)].
 *
 * draft ─Ajukan→ submitted ─Approve→ approved ─Mulai→ running ⇄ paused
 *   ↑                 ├─Revisi→ revision ─Ajukan→ submitted        └Selesai→ done
 *   │                 └─Tolak→ rejected (final)
 *   └ approved yang diedit Scheduler (field kritis) kembali ke submitted (diajukan ulang, 5.3)
 * Auto approve (app_settings schedule.auto_approve=1): submitted langsung approved, dicatat "Auto ACC".
 */
class ScheduleFlow extends BaseConfig
{
    public array $transitions = [
        'draft'     => ['submitted' => 'schedule.submit', 'cancelled' => 'schedule.edit'],
        'submitted' => ['approved' => 'schedule.approve', 'revision' => 'schedule.approve', 'rejected' => 'schedule.approve'],
        'revision'  => ['submitted' => 'schedule.submit', 'cancelled' => 'schedule.edit'],
        'approved'  => ['running' => 'operator.execute', 'cancelled' => ['schedule.approve', 'schedule.edit']],
        'running'   => ['paused' => 'operator.execute', 'done' => 'operator.execute'],
        'paused'    => ['running' => 'operator.execute', 'cancelled' => 'schedule.approve'],
    ];

    /** Aksi pada schedule_logs.action untuk tiap status tujuan. */
    public array $actions = [
        'submitted' => 'submit', 'approved' => 'approve', 'revision' => 'revise', 'rejected' => 'reject',
        'running' => 'start', 'paused' => 'pause', 'done' => 'finish', 'cancelled' => 'cancel',
    ];

    /** Tujuan yang wajib disertai catatan/alasan (aturan bisnis no. 6). */
    public array $noteRequired = ['revision' => 'Catatan revisi wajib diisi.', 'rejected' => 'Alasan penolakan wajib diisi.', 'paused' => 'Alasan jeda wajib diisi.'];

    /** [label, warna badge] */
    public array $labels = [
        'draft'     => ['Draft',        'secondary'],
        'submitted' => ['Menunggu ACC', 'warning'],
        'revision'  => ['Revisi',       'info'],
        'rejected'  => ['Ditolak',      'danger'],
        'approved'  => ['Disetujui',    'success'],
        'running'   => ['Berjalan',     'success'],
        'paused'    => ['Jeda',         'warning'],
        'done'      => ['Selesai',      'secondary'],
        'cancelled' => ['Batal',        'secondary'],
    ];

    public array $editable   = ['draft', 'revision', 'approved'];   // boleh diedit Scheduler (approved -> diajukan ulang)
    public array $tvVisible  = ['approved', 'running', 'paused', 'done'];
    public array $inactive   = ['cancelled', 'rejected'];           // tidak ikut bentrok / hitungan
}

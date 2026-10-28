<?php
// Gabungkan ke app/Config/AuthGroups.php hasil `php spark shield:setup`
namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    public string $defaultGroup = 'viewer';

    public array $groups = [
        'admin'     => ['title' => 'Admin',             'description' => 'Akses penuh, termasuk master data serta pola & kalender shift'],
        'scheduler' => ['title' => 'Scheduler',         'description' => 'Membuat/mengubah/menyalin jadwal, mengajukan ke Kepala Bagian'],
        'kabag'     => ['title' => 'Kepala Bagian',     'description' => 'Approve / revisi / reject jadwal, assign operator, pantau overshift'],
        'operator'  => ['title' => 'Operator Produksi', 'description' => 'Menjalankan jadwal di tablet, delegasi & terima serah terima'],
        'viewer'    => ['title' => 'Viewer',            'description' => 'Hanya melihat (dashboard & laporan)'],
        'display'   => ['title' => 'Display TV',        'description' => 'Akun read-only untuk layar TV'],
    ];

    /** PRD 8.1. Permission lama (report.*, checksheet.*, tv.manage, settings.manage) dipertahankan untuk menu yang belum jadi. */
    public array $permissions = [
        'schedule.view'           => 'Lihat schedule',
        'schedule.create'         => 'Buat schedule (termasuk Salin sebagai Baru)',
        'schedule.edit'           => 'Ubah / urutkan schedule (approved yang diubah diajukan ulang)',
        'schedule.delete'         => 'Hapus schedule draft',
        'schedule.submit'         => 'Ajukan / ajukan ulang schedule ke Kepala Bagian',
        'schedule.approve'        => 'Approve / minta revisi / reject, assign paksa operator',
        'schedule.pull_dyeing'    => 'Tarik data Dyeing ke Finishing',
        'schedule.link_ifs'       => 'Tautkan data IFS ke item schedule',
        'schedule.delegate'       => 'Buat delegasi (serah terima) overshift',
        'schedule.accept_handover' => 'Terima / tolak serah terima',
        'operator.access'         => 'Buka tablet operator',
        'operator.execute'        => 'Mulai / jeda / lanjut / selesai proses',
        'display.view'            => 'Lihat layar TV',
        'master.manage'           => 'Kelola master (mesin, shift, proses, parameter)',
        'shift.manage'            => 'Kelola pola & kalender shift',
        'users.manage'            => 'Kelola user & role',
        'reports.view'            => 'Lihat laporan',
        'division.dyeing'         => 'Bekerja di bagian Dyeing',
        'division.finishing'      => 'Bekerja di bagian Finishing',
        // --- lama, menunggu modul laporan/checksheet ---
        'report.view'             => 'Lihat laporan produksi',
        'report.create'           => 'Input laporan produksi',
        'report.edit'             => 'Ubah laporan produksi',
        'checksheet.view'         => 'Lihat checksheet',
        'checksheet.fill'         => 'Isi checksheet',
        'checksheet.manage'       => 'Kelola template checksheet',
        'tv.manage'               => 'Kelola tampilan TV',
        'settings.manage'         => 'Setting schedule (auto ACC)',
    ];

    /** PRD 8.2. Operator login dengan akun pribadi (TAB-01) dan tidak punya schedule.view. */
    public array $matrix = [
        'admin'     => ['schedule.*', 'operator.*', 'display.*', 'master.*', 'shift.*', 'users.*', 'reports.*', 'division.*',
                        'report.*', 'checksheet.*', 'tv.*', 'settings.*'],
        'scheduler' => ['schedule.view', 'schedule.create', 'schedule.edit', 'schedule.delete', 'schedule.submit',
                        'schedule.pull_dyeing', 'schedule.link_ifs', 'report.view', 'checksheet.view'],
        'kabag'     => ['schedule.view', 'schedule.approve', 'schedule.link_ifs', 'schedule.delegate',
                        'operator.access', 'operator.execute', 'display.view', 'settings.manage',
                        'report.view', 'checksheet.view'],
        'operator'  => ['operator.access', 'operator.execute', 'schedule.delegate', 'schedule.accept_handover', 'display.view',
                        'report.view', 'report.create', 'report.edit', 'checksheet.view', 'checksheet.fill'],
        'viewer'    => ['schedule.view', 'display.view', 'reports.view', 'report.view', 'checksheet.view'],
        'display'   => ['display.view'],
    ];
}

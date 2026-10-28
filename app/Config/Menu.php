<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Menu extends BaseConfig
{
    public string $appName = 'ERP DF';

    /**
     * Modul per bagian. Kode modul sama dengan machines.type (dyeing / finishing).
     * 'perm' = permission Shield yang menandai user bekerja di bagian itu
     * (admin otomatis punya semuanya lewat 'module.*'). Atur per user:
     *   php spark erp:module <username> <dyeing|finishing|both|none>  (permission division.*)
     */
    public array $modules = [
        'dyeing'    => ['label' => 'Dyeing',    'icon' => 'droplet', 'perm' => 'division.dyeing'],
        'finishing' => ['label' => 'Finishing', 'icon' => 'layers',  'perm' => 'division.finishing'],
    ];

    /**
     * Isi dropdown tiap modul. {module} diganti kode modul.
     * 'path'  = awal URL untuk menandai menu aktif.
     * 'ready' = false -> tampil nonaktif ("Segera") karena halamannya belum dibuat.
     */
    public array $moduleItems = [
        ['label' => 'Schedule',         'icon' => 'calendar',     'url' => '{module}/schedule',    'path' => '{module}/schedule',  'perm' => 'schedule.view',   'ready' => true],
        ['label' => 'Tablet Operator',  'icon' => 'tablet',       'url' => '{module}/operator',    'path' => '{module}/operator',  'perm' => 'operator.access', 'ready' => true],
        ['label' => 'Laporan Produksi', 'icon' => 'file-text',    'url' => '{module}/report',      'path' => '{module}/report',    'perm' => 'report.view',     'ready' => false],
        ['label' => 'Checksheet',       'icon' => 'check-square', 'url' => '{module}/checksheet',  'path' => '{module}/checksheet','perm' => 'checksheet.view', 'ready' => false],
    ];

    /** Urutan menu utama. ['type' => 'modules'] diganti menu modul sesuai bagian user. */
    public array $items = [
        ['label' => 'Dashboard', 'icon' => 'home', 'url' => '/', 'path' => '', 'perm' => null, 'ready' => true],
        ['type' => 'modules'],
        ['label' => 'Pengaturan', 'icon' => 'settings', 'perm' => null, 'children' => [
            ['label' => 'Setting Schedule', 'icon' => 'sliders', 'url' => 'admin/settings', 'path' => 'admin/settings', 'perm' => 'settings.manage', 'ready' => true],
            ['label' => 'Kalender Shift',   'icon' => 'clock',   'url' => 'master/shift-calendar', 'path' => 'master/shift-calendar', 'perm' => 'shift.manage', 'ready' => true],
            ['label' => 'User & Role',      'icon' => 'users',   'url' => 'admin/users',    'path' => 'admin/users',    'perm' => 'users.manage',    'ready' => false],
            ['label' => 'Layar TV',         'icon' => 'tv',      'url' => 'admin/tv',       'path' => 'admin/tv',       'perm' => 'tv.manage',       'ready' => false],
        ]],
    ];
}

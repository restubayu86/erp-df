<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

service('auth')->routes($routes);   // login/logout bawaan Shield

// Area internal: wajib login + permission
$routes->group('', ['filter' => 'session'], static function (RouteCollection $routes) {
    $routes->get('/', 'Home::index');

    // Modul produksi (PRD 7): /{div}/schedule/..., controller di app/Modules/{Namespace}/Controllers.
    // Akses divisi (division.dyeing / division.finishing) dicek di BaseModuleController.
    foreach (['dyeing' => 'Dyeing', 'finishing' => 'Finishing'] as $slug => $ns) {
        $routes->group($slug, ['namespace' => "App\\Modules\\{$ns}\\Controllers"], static function (RouteCollection $routes) {
            // daftar, tabel, insight
            $routes->get('schedule',                 'Schedule::index',     ['filter' => 'permission:schedule.view']);
            $routes->get('schedule/datatable',       'Schedule::datatable', ['filter' => 'permission:schedule.view']);
            $routes->get('schedule/insight',         'Schedule::insight',   ['filter' => 'permission:schedule.view']);

            // buat / salin (?copy_from=ID) / simpan
            $routes->get('schedule/create',          'Schedule::create',    ['filter' => 'permission:schedule.create']);
            $routes->post('schedule',                'Schedule::store',     ['filter' => 'permission:schedule.create']);

            // detail, edit, hapus, urutan
            $routes->get('schedule/(:num)',          'Schedule::show/$1',   ['filter' => 'permission:schedule.view']);
            $routes->get('schedule/(:num)/edit',     'Schedule::edit/$1',   ['filter' => 'permission:schedule.edit']);
            $routes->post('schedule/(:num)',         'Schedule::update/$1', ['filter' => 'permission:schedule.edit']);
            $routes->post('schedule/(:num)/delete',  'Schedule::delete/$1', ['filter' => 'permission:schedule.delete']);
            $routes->post('schedule/reorder',        'Schedule::reorder',   ['filter' => 'permission:schedule.edit']);

            // alur status (PRD 5.1)
            $routes->post('schedule/(:num)/submit',  'Schedule::submit/$1',  ['filter' => 'permission:schedule.submit']);
            $routes->post('schedule/(:num)/approve', 'Schedule::approve/$1', ['filter' => 'permission:schedule.approve']);
            $routes->post('schedule/(:num)/revise',  'Schedule::revise/$1',  ['filter' => 'permission:schedule.approve']);
            $routes->post('schedule/(:num)/reject',  'Schedule::reject/$1',  ['filter' => 'permission:schedule.approve']);
            // umum (dipakai tablet operator & tombol batal); izin per transisi dicek di WorkflowService
            $routes->post('schedule/(:num)/transition', 'Schedule::transition/$1', ['filter' => 'permission:schedule.view,operator.access']);

            // tablet operator (Sprint 5: antrean per mesin)
            $routes->get('operator', 'Operator::index', ['filter' => 'permission:operator.access']);
        });
    }

    // Jam efektif shift pada tanggal tertentu (PRD 7)
    $routes->get('api/shift/effective', '\App\Modules\Core\Controllers\ShiftApi::effective', ['filter' => 'permission:schedule.view,operator.access']);

    // API IFS (PRD 5.6)
    $routes->get('api/ifs/datatable', '\App\Modules\Core\Controllers\IfsApi::datatable', ['filter' => 'permission:schedule.create,schedule.edit']);
    $routes->get('api/ifs/detail/(:segment)', '\App\Modules\Core\Controllers\IfsApi::detail/$1', ['filter' => 'permission:schedule.create,schedule.edit']);

    // Pola & kalender shift: khusus Admin (K9)
    $routes->get('master/shift-calendar',          '\App\Modules\Core\Controllers\ShiftCalendar::index',  ['filter' => 'permission:shift.manage']);
    $routes->post('master/shift-calendar',         '\App\Modules\Core\Controllers\ShiftCalendar::assign', ['filter' => 'permission:shift.manage']);
    $routes->post('master/shift-calendar/delete',  '\App\Modules\Core\Controllers\ShiftCalendar::clear',  ['filter' => 'permission:shift.manage']);

    $routes->get('admin/settings',  'Admin\Settings::index', ['filter' => 'permission:settings.manage']);
    $routes->post('admin/settings', 'Admin\Settings::save',  ['filter' => 'permission:settings.manage']);
});

// Layar TV (PRD TV-01, TV-07): tanpa session, dilindungi token perangkat.
$routes->group('display', ['filter' => 'tvtoken'], static function (RouteCollection $routes) {
    $routes->get('api/board/(:segment)', 'Tv::data/$1');
    $routes->get('(:segment)',           'Tv::board/$1');
});

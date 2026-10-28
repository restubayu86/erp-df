<?php

namespace App\Controllers;

class Home extends BaseController
{
    /** Arahkan ke Schedule modul aktif user; bila belum punya bagian kerja, tampilkan pesan. */
    public function index()
    {
        $module = current_module();   // dari helper 'menu'

        return $module
            ? redirect()->to($module . '/schedule')
            : $this->page('home', [], 'Dashboard');
    }
}

<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class Settings extends BaseController
{
    public function index()
    {
        $auto = (new SettingModel())->getValue('schedule.auto_approve', '0') === '1';
        return $this->page('admin/settings', ['auto' => $auto], 'Setting Schedule', ['Pengaturan' => null]);
    }

    public function save()
    {
        (new SettingModel())->setValue('schedule.auto_approve', $this->request->getPost('auto_approve') ? '1' : '0');
        return redirect()->to('admin/settings')->with('message', 'Setting tersimpan.');
    }
}

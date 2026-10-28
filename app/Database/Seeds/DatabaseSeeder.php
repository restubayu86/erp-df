<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/** php spark db:seed DatabaseSeeder  -> master + data uji. Di produksi cukup: php spark db:seed MasterSeeder */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('MasterSeeder');
        $this->call('ScheduleSeeder');
    }
}

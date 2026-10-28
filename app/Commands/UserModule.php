<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Models\UserModel;

/** Menentukan bagian kerja user -> menu modul (Dyeing / Finishing) yang tampil di navbar. */
class UserModule extends BaseCommand
{
    protected $group       = 'ERP';
    protected $name        = 'erp:module';
    protected $description = 'Atur bagian kerja user (modul Dyeing / Finishing).';
    protected $usage       = 'erp:module <username> <dyeing|finishing|both|none>';
    protected $arguments   = [
        'username' => 'Username user.',
        'bagian'   => 'dyeing, finishing, both (keduanya), atau none (cabut semua).',
    ];

    public function run(array $params)
    {
        $username = $params[0] ?? CLI::prompt('Username');
        $bagian   = strtolower($params[1] ?? CLI::prompt('Bagian', ['dyeing', 'finishing', 'both', 'none']));

        $wanted = match ($bagian) {
            'dyeing'    => ['dyeing'],
            'finishing' => ['finishing'],
            'both'      => ['dyeing', 'finishing'],
            'none'      => [],
            default     => null,
        };
        if ($wanted === null) {
            CLI::error("Bagian '{$bagian}' tidak dikenal. Pilih: dyeing, finishing, both, none.");
            return EXIT_ERROR;
        }

        $user = model(UserModel::class)->where('username', $username)->first();
        if (! $user) {
            CLI::error("User '{$username}' tidak ditemukan.");
            return EXIT_ERROR;
        }

        foreach (['dyeing', 'finishing'] as $code) {
            in_array($code, $wanted, true)
                ? $user->addPermission("division.{$code}")
                : $user->removePermission("division.{$code}");
        }

        CLI::write("Bagian {$username}: " . ($wanted ? implode(', ', $wanted) : '(kosong)'), 'green');
        return EXIT_SUCCESS;
    }
}

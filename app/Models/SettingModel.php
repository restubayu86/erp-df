<?php

namespace App\Models;

use CodeIgniter\Model;

/** Setting aplikasi (tabel app_settings). Tabel `settings` dipakai Shield, jangan dicampur. */
class SettingModel extends Model
{
    protected $table            = 'app_settings';
    protected $primaryKey       = 'name';
    protected $useAutoIncrement = false;
    protected $allowedFields    = ['name', 'value'];

    public function getValue(string $name, string $default = ''): string
    {
        $row = $this->find($name);
        return $row['value'] ?? $default;
    }

    public function setValue(string $name, string $value): void
    {
        $this->db->query(
            'INSERT INTO `app_settings` (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$name, $value]
        );
    }
}

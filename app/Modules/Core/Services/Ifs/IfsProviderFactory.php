<?php

namespace App\Modules\Core\Services\Ifs;

use App\Models\SettingModel;

/**
 * Factory untuk mendapatkan instance IfsProvider yang aktif berdasarkan pengaturan.
 */
class IfsProviderFactory
{
    public static function make(): IfsProviderInterface
    {
        $settings = new SettingModel();
        $provider = $settings->getValue('ifs.provider', 'dummy');

        switch ($provider) {
            case 'dummy':
                return new DummyIfsProvider();
            // case 'api':
            //     return new ApiIfsProvider();
            default:
                return new DummyIfsProvider();
        }
    }
}

<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Services\Ifs\IfsProviderFactory;
use Hermawan\DataTables\DataTable;

class IfsApi extends BaseController
{
    /**
     * Endpoint untuk DataTables modal pemilihan IFS.
     */
    public function datatable()
    {
        $user = auth()->user();
        if (! $user || (! $user->can('division.dyeing') && ! $user->can('division.finishing'))) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Forbidden']);
        }

        $provider = IfsProviderFactory::make();
        $builder  = $provider->builder($this->request->getGet());

        return DataTable::of($builder)
            ->add('action', function($row) {
                return '<button type="button" class="btn btn-primary btn-xs btn-select-ifs" data-dop="'.esc($row->dop_no).'">'
                     . '<span class="fas fa-check me-1"></span>Pilih</button>';
            })
            ->toJson(true);
    }

    /**
     * Ambil detail satu data IFS untuk autofill.
     */
    public function detail(string $dopNo)
    {
        $user = auth()->user();
        if (! $user || (! $user->can('division.dyeing') && ! $user->can('division.finishing'))) {
            return $this->response->setStatusCode(403)->setJSON(['message' => 'Forbidden']);
        }

        $provider = IfsProviderFactory::make();
        $data     = $provider->find($dopNo);

        if (!$data) {
            return $this->response->setStatusCode(404)->setJSON(['message' => 'Data tidak ditemukan']);
        }

        return $this->response->setJSON($data);
    }
}

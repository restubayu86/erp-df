<?php

namespace App\Modules\Core\Services\Ifs;

/**
 * Interface untuk penyedia data IFS.
 * Memungkinkan peralihan antara data dummy dan API nyata tanpa mengubah logika bisnis.
 */
interface IfsProviderInterface
{
    /**
     * Query builder untuk pencarian data IFS (DataTable atau pencarian manual).
     * 
     * @param array $filters Filter pencarian (q, customer, dll)
     */
    public function builder(array $filters = []): \CodeIgniter\Database\BaseBuilder;

    /**
     * Ambil satu data IFS berdasarkan DOP No.
     */
    public function find(string $dopNo): ?array;
}

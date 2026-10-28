<?php

namespace App\Modules\Core\Services\Ifs;

use CodeIgniter\Database\BaseBuilder;

/**
 * Implementasi IFS Provider menggunakan tabel dummy lokal.
 */
class DummyIfsProvider implements IfsProviderInterface
{
    protected $db;
    protected $table = 'ifs_dummy_orders';

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function builder(array $filters = []): BaseBuilder
    {
        $builder = $this->db->table($this->table);

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $builder->groupStart()
                ->like('dop_no', $q)
                ->orLike('order_no', $q)
                ->orLike('customer_name', $q)
                ->orLike('article', $q)
            ->groupEnd();
        }

        return $builder;
    }

    public function find(string $dopNo): ?array
    {
        return $this->db->table($this->table)
            ->where('dop_no', $dopNo)
            ->get()
            ->getRowArray();
    }
}

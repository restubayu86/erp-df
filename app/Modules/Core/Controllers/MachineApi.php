<?php

namespace App\Modules\Core\Controllers;

use CodeIgniter\RESTful\ResourceController;

class MachineApi extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $builder = db_connect()->table('machines')
            ->select('id, code, name, type, is_active')
            ->where('is_active', 1);

        $type = $this->request->getGet('type');
        if (!empty($type)) {
            $builder->where('type', $type);
        }

        $machines = $builder->orderBy('code', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respond($machines);
    }

    public function types()
    {
        $types = db_connect()->table('machines')
            ->select('type')
            ->distinct()
            ->where('type IS NOT NULL')
            ->where('type !=', '')
            ->where('is_active', 1)
            ->orderBy('type', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respond(array_column($types, 'type'));
    }

    public function create()
    {
        $db = db_connect();
        
        $input = $this->request->getJSON() ?: (object)[];
        $data = [
            'code'      => strtoupper(trim((string)($input->code ?? $this->request->getPost('code')))),
            'name'      => trim((string)($input->name ?? $this->request->getPost('name'))),
            'type'      => trim((string)($input->type ?? $this->request->getPost('type'))),
            'is_active' => 1,
        ];

        // Validasi
        if (empty($data['code']) || empty($data['name']) || empty($data['type'])) {
            return $this->fail('Semua field harus diisi', 400);
        }

        // Validasi format: DY-01, FIN-01, JD-01
        if (!preg_match('/^[A-Z0-9]+-[0-9]{2,}$/', $data['code'])) {
            return $this->fail('Format Kode Mesin harus seperti DY-01 atau FIN-01 (Huruf-Angka 2 digit)', 400);
        }

        // Cek duplikat code atau name
        $exists = $db->table('machines')
            ->groupStart()
                ->where('code', $data['code'])
                ->orWhere('name', $data['name'])
            ->groupEnd()
            ->where('is_active', 1)
            ->countAllResults();
        if ($exists > 0) {
            return $this->fail('Kode atau Nama mesin sudah ada', 400);
        }

        $db->table('machines')->insert($data);
        $data['id'] = $db->insertID();

        return $this->respondCreated($data);
    }

    public function update($id = null)
    {
        $db = db_connect();
        
        $input = $this->request->getJSON() ?: (object)[];
        $data = [
            'code'      => strtoupper(trim((string)($input->code ?? $this->request->getPost('code')))),
            'name'      => trim((string)($input->name ?? $this->request->getPost('name'))),
            'type'      => trim((string)($input->type ?? $this->request->getPost('type'))),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Validasi
        if (empty($data['code']) || empty($data['name']) || empty($data['type'])) {
            return $this->fail('Semua field harus diisi', 400);
        }

        // Validasi format
        if (!preg_match('/^[A-Z0-9]+-[0-9]{2,}$/', $data['code'])) {
            return $this->fail('Format Kode Mesin harus seperti DY-01 atau FIN-01 (Huruf-Angka 2 digit)', 400);
        }

        // Cek duplikat code atau name (kecuali diri sendiri)
        $exists = $db->table('machines')
            ->groupStart()
                ->where('code', $data['code'])
                ->orWhere('name', $data['name'])
            ->groupEnd()
            ->where('id !=', $id)
            ->where('is_active', 1)
            ->countAllResults();
        if ($exists > 0) {
            return $this->fail('Kode atau Nama mesin sudah ada', 400);
        }

        $db->table('machines')->where('id', $id)->update($data);

        return $this->respond(['id' => $id] + $data);
    }

    public function delete($id = null)
    {
        $db = db_connect();
        
        // Cek apakah mesin digunakan di schedule
        $used = $db->table('schedules')->where('machine_id', $id)->countAllResults();
        if ($used > 0) {
            return $this->fail('Mesin tidak bisa dihapus karena sudah digunakan di jadwal', 400);
        }

        $db->table('machines')->where('id', $id)->update(['is_active' => 0]);

        return $this->respondDeleted(['id' => $id]);
    }
}


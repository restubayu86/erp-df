<?php

namespace App\Modules\Shared\Controllers;

use App\Libraries\ScheduleWorkflow;
use App\Models\ScheduleModel;
use App\Modules\BaseModuleController;
use Hermawan\DataTables\DataTable;

/** Logika schedule yang sama untuk semua modul. Subclass cukup mengisi $moduleName & $moduleType. */
abstract class ScheduleController extends BaseModuleController
{
    private const RULES = [
        'machine_id' => 'required|is_natural_no_zero',
        'order_no'   => 'required|max_length[50]',
        'customer'   => 'permit_empty|max_length[100]',
        'process'    => 'required|max_length[50]',
        'work_date'  => 'required|valid_date[Y-m-d]',
        'shift'      => 'required|in_list[pagi,siang,malam]',
        'start_at'   => 'required',
        'end_at'     => 'required',
    ];

    // ---------- Daftar + DataTables ----------

    public function index(): string
    {
        $date = config('Shifts')->productionDate();

        return $this->modulePage('schedule/index', [
            'machines'    => $this->getMachines(),
            'defaultDate' => $date,
            'labels'      => config('ScheduleFlow')->labels,
            'stats'       => $this->stats($date),
        ], 'Jadwal Produksi', [ucfirst($this->moduleName) => null, 'Schedule' => null]);
    }

    /** Ringkasan untuk kartu di atas tabel (tanggal produksi hari ini). */
    private function stats(string $date): array
    {
        $rows = db_connect()->table('schedules s')
            ->select('s.status, COUNT(*) AS n')
            ->join('machines m', 'm.id = s.machine_id')
            ->where('m.type', $this->moduleType)
            ->where('s.work_date', $date)
            ->groupBy('s.status')
            ->get()->getResultArray();

        $by = array_column($rows, 'n', 'status');

        return [
            'total'   => (int) array_sum($by),
            'running' => (int) ($by['running'] ?? 0),
            'waiting' => (int) ($by['submitted'] ?? 0),
            'done'    => (int) ($by['done'] ?? 0),
        ];
    }

    public function datatable()
    {
        $req     = $this->request;
        $builder = db_connect()->table('schedules s')
            ->select('s.priority AS priority, s.work_date AS work_date, s.shift AS shift, m.name AS machine, '
                . 's.order_no AS order_no, s.customer AS customer, s.process AS process, '
                . 's.start_at AS start_at, s.end_at AS end_at, s.status AS status, s.id AS id, '
                . 's.category AS category, s.design AS design, s.color AS color, s.mp_no AS mp_no, '
                . 's.wash_machine AS wash_machine, s.progress AS progress, s.dye_lot_no AS dye_lot_no, '
                . 's.dop_no AS dop_no, s.note AS note')
            ->join('machines m', 'm.id = s.machine_id')
            ->where('m.type', $this->moduleType);

        if ($v = $req->getGet('work_date')) {
            $builder->where('s.work_date', $v);
        }
        if ($v = $req->getGet('machine_id')) {
            $builder->where('s.machine_id', (int) $v);
        }
        if ($v = $req->getGet('status')) {
            $builder->where('s.status', $v);
        }

        $user = auth()->user();
        $flow = config('ScheduleFlow');
        $mod  = $this->moduleName;

        return DataTable::of($builder)
            // Pembeda terakhir setelah urutan dari DataTables (mesin, prioritas, tanggal): shift pagi > siang > malam
            ->postQuery(static function ($b) {
                $b->orderBy("FIELD(s.shift,'pagi','siang','malam')", '', false)->orderBy('s.id', 'asc');
            })
            ->add('action', static function ($row) use ($user, $flow, $mod) {
                $btn = static fn(string $to, string $icon, string $tone, string $title) =>
                '<button type="button" class="btn btn-sm btn-falcon-' . $tone . ' btn-trans" data-id="'
                    . (int) $row->id . '" data-to="' . $to . '" title="' . $title . '">'
                    . '<span class="fas fa-' . $icon . '"></span></button>';

                $out = '<div class="btn-group btn-group-sm" role="group">';
                
                // Edit
                if ($user->can('schedule.edit') && in_array($row->status, $flow->editable, true)) {
                    $out .= '<a href="' . site_url("{$mod}/schedule/{$row->id}/edit")
                        . '" class="btn btn-sm btn-falcon-default" title="Ubah"><span class="fas fa-edit"></span></a>';
                }

                // Submit / Cancel (Draft)
                if ($row->status === 'draft') {
                    if ($user->can('schedule.submit')) $out .= $btn('submitted', 'paper-plane', 'primary', 'Ajukan');
                    if ($user->can('schedule.edit'))   $out .= $btn('cancelled', 'ban', 'warning', 'Batal');
                }

                // Approve / Reject (Submitted)
                if ($row->status === 'submitted' && $user->can('schedule.approve')) {
                    $out .= $btn('approved', 'check', 'success', 'ACC') 
                          . $btn('rejected', 'times', 'danger', 'Tolak');
                }

                // Revisi / Cancel (Rejected)
                if ($row->status === 'rejected' && $user->can('schedule.edit')) {
                    $out .= $btn('draft', 'redo', 'warning', 'Revisi')
                          . $btn('cancelled', 'ban', 'warning', 'Batal');
                }

                // Cancel (Approved)
                if ($row->status === 'approved' && $user->can('schedule.approve')) {
                    $out .= $btn('cancelled', 'ban', 'warning', 'Batal');
                }

                // Delete (Hanya Draft & Scheduler/Admin)
                if ($row->status === 'draft' && $user->can('schedule.edit')) {
                    $out .= '<button type="button" class="btn btn-sm btn-falcon-danger btn-delete" data-id="'
                        . (int) $row->id . '" title="Hapus"><span class="fas fa-trash"></span></button>';
                }

                $out .= '</div>';
                return $out;
            }, 'last')
            ->toJson(true);
    }

    // ---------- Buat / ubah ----------

    public function new(): string
    {
        return $this->form(null, 'Buat Schedule ' . ucfirst($this->moduleName));
    }

    public function create()
    {
        if (! $this->validate(self::RULES) || ! ($data = $this->collect())) {
            return redirect()->back()->withInput()->with('error', 'Data belum valid. Pastikan jam selesai setelah jam mulai.');
        }

        $model = new ScheduleModel();
        $max   = $model->selectMax('priority')
            ->where('machine_id', $data['machine_id'])
            ->where('work_date', $data['work_date'])
            ->first();

        $data['priority']   = (int) ($max['priority'] ?? 0) + 1;
        $data['status']     = 'draft';
        $data['created_by'] = auth()->id();
        $model->insert($data);

        return redirect()->to($this->moduleName . '/schedule')
            ->with('message', 'Schedule dibuat (draft). Klik "Ajukan" untuk dikirim ke Kepala Bagian.');
    }

    public function edit(int $id)
    {
        $row = $this->findEditable($id);

        return $row
            ? $this->form($row, 'Ubah Schedule ' . ucfirst($this->moduleName))
            : redirect()->to($this->moduleName . '/schedule')->with('error', 'Schedule tidak bisa diubah pada status ini.');
    }

    public function update(int $id)
    {
        if (! $this->findEditable($id)) {
            return redirect()->to($this->moduleName . '/schedule')->with('error', 'Schedule tidak bisa diubah pada status ini.');
        }
        if (! $this->validate(self::RULES) || ! ($data = $this->collect())) {
            return redirect()->back()->withInput()->with('error', 'Data belum valid. Pastikan jam selesai setelah jam mulai.');
        }

        (new ScheduleModel())->update($id, $data);

        return redirect()->to($this->moduleName . '/schedule')->with('message', 'Schedule disimpan.');
    }

    // ---------- Aksi AJAX (JSON) ----------

    /** Body: {items: [{id, priority}, ...]} - hanya satu mesin + satu tanggal sekaligus. */
    public function reorder()
    {
        $items = $this->request->getJSON(true)['items'] ?? [];
        $ids   = array_map(static fn($i) => (int) ($i['id'] ?? 0), $items);

        if (! $items || in_array(0, $ids, true)) {
            return $this->fail('Data urutan tidak valid.');
        }

        $rows = db_connect()->table('schedules s')
            ->select('s.id, s.status, s.machine_id, s.work_date, s.priority')
            ->join('machines m', 'm.id = s.machine_id')
            ->where('m.type', $this->moduleType)
            ->whereIn('s.id', $ids)
            ->get()->getResultArray();

        $scopes = array_unique(array_map(static fn($r) => $r['machine_id'] . '|' . $r['work_date'], $rows));
        if (count($rows) !== count($ids) || count($scopes) !== 1) {
            return $this->fail('Urutan hanya bisa diubah untuk satu mesin dan satu tanggal.');
        }
        foreach ($rows as $r) {
            if (! in_array($r['status'], config('ScheduleFlow')->reorderable, true)) {
                return $this->fail('Ada schedule yang statusnya tidak bisa diurutkan ulang.');
            }
        }

        // Prioritas baru hanya boleh menukar nilai prioritas yang sudah dimiliki baris-baris ini
        $old = array_map('intval', array_column($rows, 'priority'));
        $new = array_map(static fn($i) => (int) $i['priority'], $items);
        sort($old);
        sort($new);
        if ($old !== $new) {
            return $this->fail('Urutan tidak valid. Muat ulang halaman lalu coba lagi.');
        }

        $db    = db_connect();
        $model = new ScheduleModel();
        $db->transStart();
        foreach ($items as $i) {
            $model->update((int) $i['id'], ['priority' => max(1, (int) $i['priority'])]);
        }
        $db->transComplete();

        return $db->transStatus()
            ? $this->response->setJSON(['ok' => true])
            : $this->fail('Gagal menyimpan urutan.', 500);
    }

    /** Body: {to: 'submitted'|..., note?: string} */
    public function transition(int $id)
    {
        $body   = $this->request->getJSON(true) ?? [];
        $result = (new ScheduleWorkflow())->move($id, (string) ($body['to'] ?? ''), auth()->user(), $body['note'] ?? null);

        return $this->response
            ->setStatusCode($result['ok'] ? 200 : 422)
            ->setJSON(['ok' => $result['ok'], 'message' => $result['message']]);
    }

    public function delete(int $id)
    {
        $row = (new ScheduleModel())->find($id);
        if (! $row || $row['status'] !== 'draft' || ! auth()->user()->can('schedule.edit')) {
            return $this->fail('Schedule tidak bisa dihapus.');
        }
        (new ScheduleModel())->delete($id);

        return $this->response->setJSON(['ok' => true]);
    }

    // ---------- Helper ----------

    private function fail(string $message, int $code = 422)
    {
        return $this->response->setStatusCode($code)->setJSON(['ok' => false, 'message' => $message]);
    }

    private function findEditable(int $id): ?array
    {
        $row = (new ScheduleModel())->find($id);

        return $row && in_array($row['status'], config('ScheduleFlow')->editable, true) ? $row : null;
    }

    private function form(?array $row, string $title): string
    {
        $mod = ucfirst($this->moduleName);

        return $this->modulePage('schedule/form', [
            'row'      => $row,
            'machines' => $this->getMachines(),
            'shifts'   => config('Shifts')->list,
        ], $title, [$mod => null, 'Schedule' => $this->moduleName . '/schedule', $title => null]);
    }

    private function collect(): ?array
    {
        $p     = $this->request->getPost();
        $start = strtotime($p['start_at'] ?? '');
        $end   = strtotime($p['end_at'] ?? '');

        if (! $start || ! $end || $end <= $start) {
            return null;
        }

        return [
            'machine_id'   => (int) $p['machine_id'],
            'order_no'     => trim($p['order_no']),
            'customer'     => trim($p['customer'] ?? ''),
            'process'      => trim($p['process']),
            'work_date'    => $p['work_date'],
            'shift'        => $p['shift'],
            'start_at'     => date('Y-m-d H:i:s', $start),
            'end_at'       => date('Y-m-d H:i:s', $end),
            'category'     => trim($p['category'] ?? 'Produksi'),
            'design'       => trim($p['design'] ?? ''),
            'color'        => trim($p['color'] ?? ''),
            'mp_no'        => trim($p['mp_no'] ?? ''),
            'wash_machine' => trim($p['wash_machine'] ?? ''),
            'progress'     => (int) ($p['progress'] ?? 0),
            'dye_lot_no'   => trim($p['dye_lot_no'] ?? ''),
            'dop_no'       => trim($p['dop_no'] ?? ''),
            'note'         => trim($p['note'] ?? ''),
        ];
    }
}

<?php

namespace App\Modules\Core\Controllers;

use App\Modules\BaseModuleController;
use App\Modules\Core\Models\ScheduleModel;
use App\Modules\Core\Services\ScheduleService;
use App\Modules\Core\Services\ShiftCalendarService;
use App\Modules\Core\Services\WorkflowService;
use CodeIgniter\Database\BaseBuilder;
use Hermawan\DataTables\DataTable;

/** Schedule Produksi (PRD fase 1). Subclass cukup mengisi $moduleName & $moduleType. Logika bisnis ada di Service. */
abstract class ScheduleController extends BaseModuleController
{
    // ---------------------------------------------------------------- daftar

    public function index(): string
    {
        $cal  = new ShiftCalendarService();
        $date = $cal->productionDate();
        $svc  = new ScheduleService($cal);
        $prim = $svc->primaryParam($this->moduleType);
        $user = auth()->user();

        return $this->modulePage('schedule/index', [
            'machines'    => $this->getMachines(),
            'shifts'      => db_connect()->table('shifts')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray(),
            'defaultDate' => $date,
            'filters'     => $this->request->getGet() ?: [],
            'labels'      => config('ScheduleFlow')->labels,
            'qtyUom'      => $prim['uom'] ?? '',
            'canCreate'   => $user->can('schedule.create'),
            'canReorder'  => $user->can('schedule.edit'),
        ], 'Schedule Produksi ' . ucfirst($this->moduleName), [ucfirst($this->moduleName) => null, 'Schedule Produksi' => null]);
    }

    /** Kartu insight (PRD 5.8), dimuat via AJAX. */
    public function insight()
    {
        $date = $this->request->getGet('date') ?: (new ShiftCalendarService())->productionDate();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
            return $this->failJson('Tanggal tidak valid.');
        }

        return $this->response->setJSON((new ScheduleService())->insight($this->moduleType, $date));
    }

    public function datatable()
    {
        $svc     = new ScheduleService();
        $over    = $svc->overshiftRows($this->moduleType);
        $primary = $svc->primaryParam($this->moduleType);

        $qty = $primary
            ? '(SELECT SUM(ip.value_num) FROM schedule_items i JOIN schedule_item_params ip ON ip.schedule_item_id = i.id '
              . "AND ip.kind = 'plan' AND ip.parameter_id = " . (int) $primary['id'] . ' WHERE i.schedule_id = s.id)'
            : 'NULL';

        $builder = db_connect()->table('schedules s')
            ->select("s.id AS id, s.sequence AS sequence, s.schedule_no AS schedule_no, s.work_date AS work_date, sh.name AS shift, "
                . "s.category AS category, "
                . "p.name AS process, s.start_at AS start_at, s.end_at AS end_at, s.priority AS priority, s.status AS status, "
                . "s.revision_no AS revision_no, u.username AS current_operator, m.code AS machine_code, "
                . "CONCAT(m.code, ' – ', m.name) AS machine, "
                . '(SELECT i.order_no FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS order_first, '
                . '(SELECT i.customer_name FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS customer_first, '
                . '(SELECT i.design_name FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS design_first, '
                . '(SELECT i.color_name FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS color_first, '
                . '(SELECT i.mp_no FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS mp_first, '
                . '(SELECT i.notes FROM schedule_items i WHERE i.schedule_id = s.id ORDER BY i.line_no LIMIT 1) AS item_notes, '
                . '(SELECT i.source_item_id FROM schedule_items i WHERE i.schedule_id = s.id AND i.source_type = \'ifs\' ORDER BY i.line_no LIMIT 1) AS dop_no, '
                . '(SELECT COUNT(*) FROM schedule_items i WHERE i.schedule_id = s.id) AS item_count, '
                . $qty . ' AS qty_primary')
            ->join('machines m', 'm.id = s.machine_id')
            ->join('shifts sh', 'sh.id = s.shift_id')
            ->join('processes p', 'p.id = s.process_id', 'left')
            ->join('users u', 'u.id = s.current_operator_id', 'left')
            ->where('s.division', $this->moduleType)
            ->where('s.deleted_at', null);
        $params = ['BERAT', 'PANJANG', 'JML_ROLL', 'LIQUOR_RATIO', 'SUHU', 'NO_PROGRAM', 'NO_NOZZLE', 'CUCI_MESIN'];
        foreach ($params as $p) {
            $builder->select("(SELECT ip.value_num FROM schedule_items i JOIN schedule_item_params ip ON ip.schedule_item_id = i.id JOIN parameters p ON p.id = ip.parameter_id WHERE i.schedule_id = s.id AND p.code = '{$p}' AND ip.kind = 'plan' LIMIT 1) AS param_{$p}");
        }

        $this->applyFilters($builder);

        $user = auth()->user();
        $mod  = $this->moduleName;

        $result = DataTable::of($builder)
            ->add('dye_lot_no', function($row) {
                if ($row->category === 'produksi') {
                    $date = date('ymd', strtotime($row->work_date));
                    $mc = preg_replace('/[^0-9]/', '', $row->machine_code);
                    $mc = str_pad($mc, 2, '0', STR_PAD_LEFT);
                    $seq = str_pad($row->sequence, 2, '0', STR_PAD_LEFT);
                    return "JD{$date}{$mc}{$seq}";
                }
                return $row->schedule_no;
            })
            ->add('overshift', static fn ($row) => isset($over[(int) $row->id]) ? 1 : 0, 'last')
            ->add('action', fn ($row) => $this->actions($row, $user, $mod), 'last')
            ->toJson(true);

        $json = json_decode(is_string($result) ? $result : (string) $result->getBody(), true) ?: [];
        // Total jadwal per mesin untuk header grup: dihitung server atas seluruh hasil filter (K13)
        $json['machineTotals'] = $this->machineTotals($over);

        return $this->response->setJSON($json);
    }

    /** Filter dari query string. Pencarian bebas (q) mencakup no. schedule, order, customer, lot. */
    private function applyFilters(BaseBuilder $b): void
    {
        $g  = $this->request->getGet();
        $db = db_connect();

        if (! empty($g['date_from'])) {
            $b->where('s.work_date >=', $g['date_from']);
        }
        if (! empty($g['date_to'])) {
            $b->where('s.work_date <=', $g['date_to']);
        }
        if (! empty($g['machine_id'])) {
            $b->where('s.machine_id', (int) $g['machine_id']);
        }
        if (! empty($g['shift_id'])) {
            $b->where('s.shift_id', (int) $g['shift_id']);
        }
        $statuses = array_values(array_intersect((array) ($g['status'] ?? []), array_keys(config('ScheduleFlow')->labels)));
        if ($statuses) {
            $b->whereIn('s.status', $statuses);
        }
        if (! empty($g['priority']) && in_array($g['priority'], ScheduleService::PRIORITIES, true)) {
            $b->where('s.priority', $g['priority']);
        }
        if (($q = trim((string) ($g['q'] ?? ''))) !== '') {
            $e = $db->escapeLikeString($q);
            $b->groupStart()
                ->like('s.schedule_no', $q)
                ->orWhere(
                    "EXISTS (SELECT 1 FROM schedule_items i WHERE i.schedule_id = s.id AND (i.order_no LIKE '%{$e}%' ESCAPE '!' "
                    . "OR i.customer_name LIKE '%{$e}%' ESCAPE '!' OR i.lot_no LIKE '%{$e}%' ESCAPE '!' "
                    . "OR i.design_name LIKE '%{$e}%' ESCAPE '!' OR i.mp_no LIKE '%{$e}%' ESCAPE '!'))",
                    null,
                    false
                )
                ->groupEnd();
        }
    }

    /** @return array<string, array{total:int, summary:array<string,int>, overshift:int}> */
    private function machineTotals(array $over): array
    {
        $b = db_connect()->table('schedules s')
            ->select("CONCAT(m.code, ' – ', m.name) AS machine, s.status, COUNT(*) AS n")
            ->join('machines m', 'm.id = s.machine_id')
            ->where('s.division', $this->moduleType)->where('s.deleted_at', null)
            ->groupBy(['m.id', 's.status']);
        $this->applyFilters($b);

        $out = [];
        foreach ($b->get()->getResultArray() as $r) {
            $out[$r['machine']]['total'] = ($out[$r['machine']]['total'] ?? 0) + (int) $r['n'];
            $out[$r['machine']]['summary'][$r['status']] = (int) $r['n'];
            $out[$r['machine']]['overshift'] ??= 0;
        }

        if ($over) {
            $ids = db_connect()->table('schedules s')->select("s.id, CONCAT(m.code, ' – ', m.name) AS machine")
                ->join('machines m', 'm.id = s.machine_id')
                ->where('s.division', $this->moduleType)->where('s.deleted_at', null)->whereIn('s.id', array_keys($over));
            $this->applyFilters($ids);
            foreach ($ids->get()->getResultArray() as $r) {
                $out[$r['machine']]['overshift'] = ($out[$r['machine']]['overshift'] ?? 0) + 1;
            }
        }

        return $out;
    }

    /** Tombol aksi per baris, sesuai status & permission (PRD 5.8). */
    private function actions(object $row, $user, string $mod): string
    {
        $act = static fn (string $a, string $icon, string $tone, string $title) =>
            '<button type="button" class="btn btn-falcon-default btn-sm btn-act" data-act="' . $a . '" data-id="' . (int) $row->id
            . '" title="' . $title . '"><span class="fas fa-' . $icon . ' text-' . $tone . '"></span></button>';
        $link = static fn (string $url, string $icon, string $title) =>
            '<a href="' . esc($url) . '" class="btn btn-falcon-default btn-sm" title="' . $title . '"><span class="fas fa-' . $icon . '"></span></a>';

        $flow = config('ScheduleFlow');
        
        // 1. Dropdown untuk CRUD (Detail, Ubah, Salin, Hapus)
        $dropdown = '<div class="dropdown font-sans-serif d-inline-block">
            <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal" type="button" id="dt-dropdown-' . $row->id . '" data-bs-toggle="dropdown" data-bs-boundary="window" data-bs-popper-config=\'{"strategy":"fixed"}\' aria-haspopup="true" aria-expanded="false">
                <span class="fas fa-ellipsis-h fs-11"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end border py-0" aria-labelledby="dt-dropdown-' . $row->id . '">
                <div class="py-2">
                    <a class="dropdown-item" href="' . site_url("{$mod}/schedule/{$row->id}") . '">Detail</a>';
        
        if ($user->can('schedule.edit') && in_array($row->status, $flow->editable, true)) {
            $dropdown .= '<a class="dropdown-item" href="' . site_url("{$mod}/schedule/{$row->id}/edit") . '">Ubah</a>';
        }
        if ($user->can('schedule.create')) {
            $dropdown .= '<a class="dropdown-item" href="' . site_url("{$mod}/schedule/create?copy_from={$row->id}") . '">Salin sebagai baru</a>';
        }
        if ($row->status === 'draft' && $user->can('schedule.delete')) {
            $dropdown .= '<div class="dropdown-divider"></div>';
            $dropdown .= '<a class="dropdown-item text-danger btn-act" href="#!" data-act="delete" data-id="' . (int) $row->id . '"><span class="fas fa-trash me-1"></span>Hapus</a>';
        }
        $dropdown .= '</div></div></div>';

        // 2. Group button untuk Status (Ajukan, Approve, Batal, dll)
        $statusBtns = '<div class="btn-group btn-group-sm ms-2" role="group">';
        
        if (in_array($row->status, ['draft', 'revision'], true) && $user->can('schedule.submit')) {
            $statusBtns .= $act('submit', 'paper-plane', 'primary', 'Ajukan');
        }
        if ($row->status === 'submitted' && $user->can('schedule.approve')) {
            $statusBtns .= $act('approve', 'check', 'success', 'Approve');
            $statusBtns .= $act('revise', 'undo', 'warning', 'Minta revisi');
            $statusBtns .= $act('reject', 'times', 'danger', 'Reject');
        }
        if (in_array($row->status, ['draft', 'revision'], true) && $user->can('schedule.edit')) {
            $statusBtns .= $act('cancel', 'ban', 'warning', 'Batalkan');
        }
        if ($row->status === 'approved' && ($user->can('schedule.approve') || $user->can('schedule.edit'))) {
            $statusBtns .= $act('cancel', 'ban', 'warning', 'Batalkan');
        }
        $statusBtns .= '</div>';

        return '<div class="d-flex align-items-center">' . $dropdown . $statusBtns . '</div>';
    }

    // ---------------------------------------------------------------- buat / salin

    public function create()
    {
        $svc  = new ScheduleService();
        $copy = (int) $this->request->getGet('copy_from');

        $header = [];
        $items  = [];
        $note   = null;
        if ($copy) {
            $payload = $svc->copyPayload($copy, $this->moduleType);
            if (! $payload) {
                return redirect()->to($this->moduleName . '/schedule')->with('error', 'Schedule yang akan disalin tidak ditemukan.');
            }
            [$header, $items, $note] = [$payload['header'], $payload['items'], 'Disalin dari ' . $payload['from'] . '. Lengkapi tanggal, shift, dan jam, lalu simpan sebagai draft baru.'];
        }

        return $this->form(null, $header, $items, 'Buat Schedule ' . ucfirst($this->moduleName), $note);
    }

    public function store()
    {
        $svc = new ScheduleService();
        [$h, $items] = $svc->normalizeInput($this->request->getPost());
        $copy = (int) $this->request->getPost('copied_from_id');

        $res = $svc->create($this->moduleType, $h, $items, (int) auth()->id(), $copy ?: null);
        if (! $res['ok']) {
            return redirect()->back()->withInput()->with('form_errors', $res['errors']);
        }

        $redirectUrl = count($res['ids'] ?? []) === 1 
            ? site_url($this->moduleName . '/schedule/' . $res['ids'][0])
            : site_url($this->moduleName . '/schedule');

        return redirect()->to($redirectUrl)
            ->with('message', $this->flash('Schedule dibuat sebagai draft. Klik "Ajukan" untuk dikirim ke Kepala Bagian.', $res['warnings']));
    }

    // ---------------------------------------------------------------- detail / edit

    public function show(int $id)
    {
        $svc = new ScheduleService();
        $row = $svc->find($id);
        if (! $row || $row['division'] !== $this->moduleType) {
            return redirect()->to($this->moduleName . '/schedule')->with('error', 'Schedule tidak ditemukan.');
        }

        $user = auth()->user();

        return $this->modulePage('schedule/show', [
            'row'     => $row,
            'logs'    => $svc->logs($id),
            'defs'    => $svc->paramDefs($this->moduleType),
            'labels'  => config('ScheduleFlow')->labels,
            'allowed' => (new WorkflowService())->allowedFor($row['status'], $user),
            'user'    => $user,
        ], $row['schedule_no'], [ucfirst($this->moduleName) => null, 'Schedule Produksi' => $this->moduleName . '/schedule', $row['schedule_no'] => null]);
    }

    public function edit(int $id)
    {
        $svc = new ScheduleService();
        $row = $svc->find($id);
        if (! $row || $row['division'] !== $this->moduleType) {
            return redirect()->to($this->moduleName . '/schedule')->with('error', 'Schedule tidak ditemukan.');
        }
        if (! in_array($row['status'], config('ScheduleFlow')->editable, true)) {
            return redirect()->to($this->moduleName . '/schedule/' . $id)->with('error', 'Schedule berstatus ' . $row['status'] . ' tidak bisa diubah (running/paused terkunci).');
        }

        $items = [];
        foreach ($row['items'] as $it) {
            $r = ['id' => $it['id'], 'params' => []];
            foreach (['order_no', 'customer_name', 'article', 'design_name', 'color_code', 'color_name', 'lot_no', 'mp_no'] as $f) {
                $r[$f] = $it[$f];
            }
            foreach ($it['params'] as $code => $kinds) {
                if (isset($kinds['plan'])) {
                    $r['params'][$code] = $kinds['plan'];
                }
            }
            $items[] = $r;
        }

        return $this->form($row, $row, $items, 'Ubah ' . $row['schedule_no']);
    }

    public function update(int $id)
    {
        $row = $this->own($id);
        if (! $row) {
            return redirect()->to($this->moduleName . '/schedule')->with('error', 'Schedule tidak ditemukan.');
        }

        $svc = new ScheduleService();
        [$h, $items] = $svc->normalizeInput($this->request->getPost());
        $res = $svc->update($id, $h, $items, (int) auth()->id());
        if (! $res['ok']) {
            return redirect()->back()->withInput()->with('form_errors', $res['errors']);
        }

        $msg = ! empty($res['resubmitted'])
            ? 'Perubahan disimpan. Jadwal diajukan ulang ke Kepala Bagian dan sementara tidak tampil di tablet/TV.'
            : 'Schedule disimpan.';

        return redirect()->to($this->moduleName . '/schedule/' . $id)->with('message', $this->flash($msg, $res['warnings']));
    }

    public function delete(int $id)
    {
        if (! $this->own($id)) {
            return $this->failJson('Schedule tidak ditemukan.');
        }
        $res = (new ScheduleService())->delete($id, (int) auth()->id());

        return $res['ok'] ? $this->response->setJSON($res) : $this->failJson($res['message']);
    }

    /** Body: {items: [{id, sequence}, ...]} - hanya satu mesin + satu tanggal sekaligus. */
    public function reorder()
    {
        $items = $this->request->getJSON(true)['items'] ?? [];
        $res   = (new ScheduleService())->reorder($this->moduleType, $items, (int) auth()->id());

        return $res['ok'] ? $this->response->setJSON($res) : $this->failJson($res['message']);
    }

    // ---------------------------------------------------------------- alur status

    public function submit(int $id)  { return $this->moveTo($id, 'submitted'); }
    public function approve(int $id) { return $this->moveTo($id, 'approved'); }
    public function revise(int $id)  { return $this->moveTo($id, 'revision'); }
    public function reject(int $id)  { return $this->moveTo($id, 'rejected'); }

    /** Body: {to: 'cancelled'|'running'|..., note?: string} */
    public function transition(int $id)
    {
        return $this->moveTo($id, (string) (($this->request->getJSON(true)['to'] ?? '')));
    }

    private function moveTo(int $id, string $to)
    {
        if (! $this->own($id)) {
            return $this->failJson('Schedule tidak ditemukan.');
        }
        $body = $this->request->getJSON(true) ?? [];
        $res  = (new WorkflowService())->move($id, $to, auth()->user(), $body['note'] ?? null);

        return $this->response->setStatusCode($res['ok'] ? 200 : 422)->setJSON(['ok' => $res['ok'], 'message' => $res['message']]);
    }

    // ---------------------------------------------------------------- helper

    private function own(int $id): ?array
    {
        $row = (new ScheduleModel())->find($id);

        return $row && $row['division'] === $this->moduleType ? $row : null;
    }

    private function failJson(string $message, int $code = 422)
    {
        return $this->response->setStatusCode($code)->setJSON(['ok' => false, 'message' => $message]);
    }

    private function flash(string $message, array $warnings): string
    {
        return $warnings ? $message . ' Perhatian: ' . implode(' ', $warnings) : $message;
    }

    /** Form header + item (parameter dinamis per divisi, SCH-03). */
    private function form(?array $row, array $header, array $items, string $title, ?string $notice = null): string
    {
        $svc = new ScheduleService();
        $cal = new ShiftCalendarService();
        $old = old('items');

        // nilai: input lama (setelah gagal validasi) > data tersimpan / salinan
        $h = static fn (string $k, $d = '') => old($k, $header[$k] ?? $d);
        $dt = static fn (string $k) => ($x = old($k, $header[$k] ?? '')) ? date('Y-m-d\TH:i', strtotime((string) $x)) : '';

        if (is_array($old)) {
            [, $items] = $svc->normalizeInput(['items' => $old]);
        }
        if (! $items) {
            $items = [['id' => 0, 'params' => []]];
        }

        $workDate = (string) $h('work_date', '');
        $mod      = ucfirst($this->moduleName);

        return $this->modulePage('schedule/form', [
            'row'       => $row,
            'action'    => $row ? site_url($this->moduleName . '/schedule/' . $row['id']) : site_url($this->moduleName . '/schedule'),
            'v'         => [
                'machine_id' => $h('machine_id'), 'process_id' => $h('process_id'), 'work_date' => $workDate,
                'shift_id'   => $h('shift_id'), 'priority' => $h('priority', 'normal'), 'notes' => $h('notes'),
                'start_at'   => $dt('start_at'), 'end_at' => $dt('end_at'), 'category' => $h('category', 'produksi'),
            ],
            'items'     => $items,
            'machines'  => $this->getMachines(),
            'processes' => $svc->processes($this->moduleType),
            'defs'      => $svc->paramDefs($this->moduleType),
            'shifts'    => $workDate !== '' ? $cal->activeShifts($workDate) : [],
            'copyFrom'  => $this->request->getGet('copy_from') ?: $this->request->getPost('copied_from_id'),
            'notice'    => $notice,
            'errors'    => session('form_errors') ?? [],
            'approved'  => $row && $row['status'] === 'approved',
        ], $title, [$mod => null, 'Schedule Produksi' => $this->moduleName . '/schedule', $title => null]);
    }
}

<?php

namespace App\Modules\Core\Services;

use App\Models\SettingModel;
use App\Modules\Core\Models\ScheduleModel;

/** CRUD schedule + validasi (bentrok mesin, divisi, shift aktif, parameter wajib). PRD 5.3, 5.7 A, 5.10, 10. */
class ScheduleService
{
    public const PRIORITIES = ['normal', 'high', 'urgent'];
    public const CATEGORIES = ['produksi', 'perbaikan', 'litbang', 'lain-lain'];
    /** Field kritis (PRD 5.3): mengubahnya pada schedule approved = diajukan ulang. */
    public const CRITICAL = ['machine_id', 'process_id', 'work_date', 'shift_id', 'start_at', 'end_at', 'sequence', 'priority', 'category'];
    private const ITEM_TEXT = [
        'order_no' => 50, 'customer_name' => 100, 'article' => 100, 
        'color_code' => 30, 'color_name' => 50, 'lot_no' => 50,
        'design_name' => 100, 'mp_no' => 50
    ];

    private $db;
    private ShiftCalendarService $cal;

    public function __construct(?ShiftCalendarService $cal = null)
    {
        $this->db  = db_connect();
        $this->cal = $cal ?? new ShiftCalendarService($this->db);
    }

    // ---------------------------------------------------------------- master / lookup

    /** Parameter yang berlaku di divisi, urut sort_order (PRD 6.3). */
    public function paramDefs(string $division): array
    {
        return $this->db->table('parameter_divisions pd')
            ->select('p.id, p.code, p.name, p.uom, p.data_type, p.decimals, pd.is_required, pd.has_actual, pd.is_primary, pd.sort_order')
            ->join('parameters p', 'p.id = pd.parameter_id')
            ->where('pd.division', $division)->where('p.is_active', 1)
            ->orderBy('pd.sort_order')->get()->getResultArray();
    }

    public function primaryParam(string $division): ?array
    {
        foreach ($this->paramDefs($division) as $d) {
            if ((int) $d['is_primary'] === 1) {
                return $d;
            }
        }

        return null;
    }

    public function processes(string $division): array
    {
        return $this->db->table('processes')->where('division', $division)->where('is_active', 1)->orderBy('name')->get()->getResultArray();
    }

    // ---------------------------------------------------------------- input

    /** Rapikan input form menjadi [header, items]. Item yang kosong total dibuang. */
    public function normalizeInput(array $post): array
    {
        $dt = static fn ($v) => ($t = strtotime((string) $v)) ? date('Y-m-d H:i:s', $t) : null;

        $header = [
            'machine_id' => (int) ($post['machine_id'] ?? 0),
            'process_id' => ($post['process_id'] ?? '') !== '' ? (int) $post['process_id'] : null,
            'category'   => in_array($post['category'] ?? '', self::CATEGORIES, true) ? $post['category'] : 'produksi',
            'work_date'  => trim((string) ($post['work_date'] ?? '')),
            'shift_id'   => (int) ($post['shift_id'] ?? 0),
            'start_at'   => $dt($post['start_at'] ?? ''),
            'end_at'     => $dt($post['end_at'] ?? ''),
            'priority'   => in_array($post['priority'] ?? '', self::PRIORITIES, true) ? $post['priority'] : 'normal',
            'notes'      => ($n = trim((string) ($post['notes'] ?? ''))) !== '' ? $n : null,
        ];

        $items = [];
        foreach ((array) ($post['items'] ?? []) as $it) {
            if (! is_array($it)) {
                continue;
            }
            $row = [
                'id'             => (int) ($it['id'] ?? 0),
                'source_type'    => in_array($it['source_type'] ?? '', ['manual', 'ifs', 'dyeing'], true) ? $it['source_type'] : 'manual',
                'source_item_id' => ($s = trim((string) ($it['source_item_id'] ?? ''))) !== '' ? $s : null,
                'notes'          => ($n = trim((string) ($it['notes'] ?? ''))) !== '' ? $n : null,
                'params'         => []
            ];
            $any = false;
            foreach (array_keys(self::ITEM_TEXT) as $f) {
                $v = trim((string) ($it[$f] ?? ''));
                $row[$f] = $v !== '' ? $v : null;
                $any = $any || $v !== '';
            }
            foreach ((array) ($it['params'] ?? []) as $code => $v) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $row['params'][(string) $code] = $v;
                    $any = true;
                }
            }
            if ($any) {
                $items[] = $row;
            }
        }

        return [$header, $items];
    }

    // ---------------------------------------------------------------- validasi

    /** @return array{errors:list<string>, warnings:list<string>} */
    public function validate(string $division, array $h, array $items, ?int $ignoreId = null): array
    {
        $err  = [];
        $warn = [];

        $machine = $h['machine_id'] ? $this->db->table('machines')->where('id', $h['machine_id'])->get()->getRowArray() : null;
        if (! $machine || (int) $machine['is_active'] !== 1) {
            $err[] = 'Mesin wajib dipilih.';
        } elseif ($machine['type'] !== $division) {
            $err[] = "Mesin {$machine['code']} bukan mesin {$division}.";   // SCH-05
        }

        if ($h['process_id']) {
            $p = $this->db->table('processes')->where('id', $h['process_id'])->get()->getRowArray();
            if (! $p || $p['division'] !== $division) {
                $err[] = 'Proses tidak sesuai divisi.';
            }
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $h['work_date']) || ! strtotime($h['work_date'])) {
            $err[] = 'Tanggal produksi tidak valid.';
        }
        if (! $h['start_at'] || ! $h['end_at']) {
            $err[] = 'Jam mulai dan selesai wajib diisi.';
        } elseif ($h['end_at'] <= $h['start_at']) {
            $err[] = 'Jam selesai harus setelah jam mulai.';
        }

        // Shift harus aktif pada tanggal tsb & jam mulai di dalam jendela shift (aturan 15)
        if (! $err) {
            $win = $this->cal->shiftWindow($h['work_date'], $h['shift_id']);
            if (! $win) {
                $err[] = 'Shift yang dipilih tidak aktif pada tanggal ' . $h['work_date'] . '.';
            } else {
                if ($h['start_at'] < $win['start_at'] || $h['start_at'] >= $win['end_at']) {
                    $err[] = "Jam mulai harus di dalam shift {$win['name']} ({$win['start']}–{$win['end']}).";
                } elseif ($h['end_at'] > $win['end_at']) {
                    $warn[] = "Jadwal selesai melewati akhir shift {$win['name']} ({$win['end']}) dan akan overshift.";
                }
            }
        }

        // Bentrok waktu pada mesin yang sama (SCH-04, aturan 3)
        if (! $err && $machine) {
            $q = $this->db->table('schedules')->select('id, schedule_no, start_at, end_at')
                ->where('machine_id', $h['machine_id'])->where('deleted_at', null)
                ->whereNotIn('status', config('ScheduleFlow')->inactive)
                ->where('start_at <', $h['end_at'])->where('end_at >', $h['start_at']);
            if ($ignoreId) {
                $q->where('id !=', $ignoreId);
            }
            foreach ($q->limit(3)->get()->getResultArray() as $c) {
                $err[] = "Bentrok dengan {$c['schedule_no']} (" . substr($c['start_at'], 5, 11) . ' – ' . substr($c['end_at'], 5, 11) . ') pada mesin yang sama.';
            }
        }

        // Item & parameter
        if (! $items) {
            $err[] = 'Minimal satu item (order) harus diisi.';
        }
        $defs = $this->paramDefs($division);
        $byCode = array_column($defs, null, 'code');
        foreach ($items as $i => $it) {
            $n = $i + 1;
            if (empty($it['order_no'])) {
                $err[] = "Item #{$n}: No. Order wajib diisi.";
            }
            foreach (self::ITEM_TEXT as $f => $max) {
                if (mb_strlen((string) $it[$f]) > $max) {
                    $err[] = "Item #{$n}: {$f} terlalu panjang (maks {$max}).";
                }
            }
            foreach ($defs as $d) {
                if ((int) $d['is_required'] === 1 && ! isset($it['params'][$d['code']])) {
                    $err[] = "Item #{$n}: {$d['name']} wajib diisi.";   // aturan parameter wajib per divisi
                }
            }
            foreach ($it['params'] as $code => $v) {
                if (! isset($byCode[$code])) {
                    continue;   // parameter bukan milik divisi ini diabaikan (aturan 11)
                }
                if ($byCode[$code]['data_type'] === 'number' && ! is_numeric(str_replace(',', '.', $v))) {
                    $err[] = "Item #{$n}: {$byCode[$code]['name']} harus berupa angka.";
                }
            }
        }

        return ['errors' => array_values(array_unique($err)), 'warnings' => $warn];
    }

    // ---------------------------------------------------------------- create / update / copy / delete

    /** @return array{ok:bool,ids?:list<int>,errors:list<string>,warnings:list<string>} */
    public function create(string $division, array $h, array $items, int $userId, ?int $copiedFrom = null): array
    {
        // Validasi per item (karena sekarang 1 item = 1 schedule)
        $allErrors = [];
        $allWarnings = [];
        $createdIds = [];

        $this->db->transStart();
        foreach ($items as $item) {
            $singleItems = [$item];
            $v = $this->validate($division, $h, $singleItems);
            if ($v['errors']) {
                $allErrors = array_merge($allErrors, $v['errors']);
                continue;
            }
            $allWarnings = array_merge($allWarnings, $v['warnings']);

            $model = new ScheduleModel();
            $data = $h + [
                'schedule_no'    => $this->nextNumber($division),
                'division'       => $division,
                'sequence'       => $this->nextSequence($h['machine_id'], $h['work_date']),
                'status'         => 'draft',
                'revision_no'    => 0,
                'copied_from_id' => $copiedFrom,
                'created_by'     => $userId,
            ];
            $id = (int) $model->insert($data);
            $this->saveItems($id, $division, $singleItems, $userId);
            $createdIds[] = $id;

            $note = null;
            if ($copiedFrom) {
                $src  = $model->find($copiedFrom);
                $note = 'disalin dari ' . ($src['schedule_no'] ?? "#{$copiedFrom}");
            }
            ScheduleLogger::write($id, $copiedFrom ? 'copy' : 'create', null, 'draft', $userId, $note);
        }
        $this->db->transComplete();

        if ($allErrors) {
            return ['ok' => false, 'errors' => array_values(array_unique($allErrors)), 'warnings' => []];
        }

        return $this->db->transStatus() ? ['ok' => true, 'ids' => $createdIds, 'warnings' => array_values(array_unique($allWarnings))] : ['ok' => false, 'errors' => ['Gagal menyimpan schedule.'], 'warnings' => []];
    }

    /** Edit pada draft/revision bebas; pada approved field kritis -> diajukan ulang (PRD 5.3). */
    public function update(int $id, array $h, array $items, int $userId): array
    {
        $model = new ScheduleModel();
        $old   = $model->find($id);
        if (! $old) {
            return ['ok' => false, 'errors' => ['Schedule tidak ditemukan.'], 'warnings' => []];
        }
        if (! in_array($old['status'], config('ScheduleFlow')->editable, true)) {
            return ['ok' => false, 'errors' => ["Schedule berstatus {$old['status']} tidak bisa diubah."], 'warnings' => []];
        }

        $v = $this->validate($old['division'], $h, $items, $id);
        if ($v['errors']) {
            return ['ok' => false] + $v;
        }

        $h['sequence'] = ((int) $h['machine_id'] === (int) $old['machine_id'] && $h['work_date'] === $old['work_date'])
            ? (int) $old['sequence'] : $this->nextSequence($h['machine_id'], $h['work_date']);

        $changes = [];
        foreach (array_merge(self::CRITICAL, ['notes']) as $k) {
            if ((string) ($old[$k] ?? '') !== (string) ($h[$k] ?? '')) {
                $changes[$k] = ['old' => $old[$k] ?? null, 'new' => $h[$k] ?? null];
            }
        }
        $before = $this->snapshot($id);

        $this->db->transStart();
        $model->update($id, $h);
        $this->saveItems($id, $old['division'], $items, $userId);

        $after = $this->snapshot($id);
        if ($before !== $after) {
            $changes['items'] = ['old' => $before, 'new' => $after];
        }

        $critical = (bool) array_intersect(array_keys($changes), array_merge(self::CRITICAL, ['items']));
        if ($old['status'] === 'approved' && $critical) {
            (new WorkflowService())->resubmit($id, $userId, $changes);
        } else {
            ScheduleLogger::write($id, 'update', $old['status'], $old['status'], $userId, null, $changes ?: null);
        }
        $this->db->transComplete();

        $resubmitted = $old['status'] === 'approved' && $critical;

        return $this->db->transStatus()
            ? ['ok' => true, 'id' => $id, 'resubmitted' => $resubmitted] + $v
            : ['ok' => false, 'errors' => ['Gagal menyimpan schedule.'], 'warnings' => []];
    }

    /** Hapus (soft delete) - hanya draft (PRD endpoint DELETE). */
    public function delete(int $id, int $userId): array
    {
        $model = new ScheduleModel();
        $row   = $model->find($id);
        if (! $row || $row['status'] !== 'draft') {
            return ['ok' => false, 'message' => 'Hanya schedule draft yang bisa dihapus.'];
        }
        $model->delete($id);
        ScheduleLogger::write($id, 'delete', 'draft', null, $userId);

        return ['ok' => true, 'message' => 'Schedule dihapus.'];
    }

    /** Ubah urutan antrean satu mesin pada satu tanggal (SCH-10). Approved yang berubah urutan diajukan ulang. */
    public function reorder(string $division, array $items, int $userId): array
    {
        $ids = array_map(static fn ($i) => (int) ($i['id'] ?? 0), $items);
        if (! $items || in_array(0, $ids, true)) {
            return ['ok' => false, 'message' => 'Data urutan tidak valid.'];
        }

        $rows = $this->db->table('schedules')->select('id, status, machine_id, work_date, sequence')
            ->where('division', $division)->where('deleted_at', null)->whereIn('id', $ids)->get()->getResultArray();

        $scopes = array_unique(array_map(static fn ($r) => $r['machine_id'] . '|' . $r['work_date'], $rows));
        if (count($rows) !== count($ids) || count($scopes) !== 1) {
            return ['ok' => false, 'message' => 'Urutan hanya bisa diubah untuk satu mesin dan satu tanggal.'];
        }
        $allowed = ['draft', 'revision', 'submitted', 'approved'];
        foreach ($rows as $r) {
            if (! in_array($r['status'], $allowed, true)) {
                return ['ok' => false, 'message' => 'Ada schedule yang statusnya tidak bisa diurutkan ulang.'];
            }
        }

        $old = array_map('intval', array_column($rows, 'sequence'));
        $new = array_map(static fn ($i) => (int) $i['sequence'], $items);
        sort($old);
        sort($new);
        if ($old !== $new) {
            return ['ok' => false, 'message' => 'Urutan tidak valid. Muat ulang halaman lalu coba lagi.'];
        }

        $byId  = array_column($rows, null, 'id');
        $model = new ScheduleModel();
        $wf    = new WorkflowService();
        $this->db->transStart();
        foreach ($items as $i) {
            $r = $byId[(int) $i['id']];
            if ((int) $r['sequence'] === (int) $i['sequence']) {
                continue;
            }
            $model->update((int) $r['id'], ['sequence' => (int) $i['sequence']]);
            $chg = ['sequence' => ['old' => (int) $r['sequence'], 'new' => (int) $i['sequence']]];
            $r['status'] === 'approved'
                ? $wf->resubmit((int) $r['id'], $userId, $chg)
                : ScheduleLogger::write((int) $r['id'], 'update', $r['status'], $r['status'], $userId, 'urutan diubah', $chg);
        }
        $this->db->transComplete();

        return $this->db->transStatus() ? ['ok' => true, 'message' => 'Urutan disimpan.'] : ['ok' => false, 'message' => 'Gagal menyimpan urutan.'];
    }

    // ---------------------------------------------------------------- baca

    /** Header + item + parameter, untuk form/detail. */
    public function find(int $id): ?array
    {
        $row = $this->db->table('schedules s')
            ->select('s.*, m.code AS machine_code, m.name AS machine_name, p.name AS process_name, sh.name AS shift_name, u.username AS operator_name, cu.username AS creator_name')
            ->join('machines m', 'm.id = s.machine_id')
            ->join('shifts sh', 'sh.id = s.shift_id')
            ->join('processes p', 'p.id = s.process_id', 'left')
            ->join('users u', 'u.id = s.current_operator_id', 'left')
            ->join('users cu', 'cu.id = s.created_by', 'left')
            ->where('s.id', $id)->where('s.deleted_at', null)->get()->getRowArray();
        if (! $row) {
            return null;
        }
        $row['items'] = $this->items($id);

        return $row;
    }

    /** @return list<array<string,mixed>> item dengan 'params' => [code => ['plan'=>x,'actual'=>y]] */
    public function items(int $scheduleId): array
    {
        $items = $this->db->table('schedule_items')->where('schedule_id', $scheduleId)->orderBy('line_no')->get()->getResultArray();
        if (! $items) {
            return [];
        }
        $params = $this->db->table('schedule_item_params ip')
            ->select('ip.schedule_item_id, ip.kind, ip.value_num, ip.value_text, p.code, p.data_type')
            ->join('parameters p', 'p.id = ip.parameter_id')
            ->whereIn('ip.schedule_item_id', array_column($items, 'id'))->get()->getResultArray();

        $map = [];
        foreach ($params as $p) {
            $map[$p['schedule_item_id']][$p['code']][$p['kind']] = $p['data_type'] === 'number' ? $this->num($p['value_num']) : $p['value_text'];
        }
        foreach ($items as &$it) {
            $it['params'] = $map[$it['id']] ?? [];
        }

        return $items;
    }

    public function logs(int $scheduleId): array
    {
        return $this->db->table('schedule_logs l')->select('l.*, u.username')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->where('l.schedule_id', $scheduleId)->orderBy('l.id', 'desc')->get()->getResultArray();
    }

    /** Bahan form "Salin sebagai Baru" (PRD 5.10): tanggal/shift/jam kosong, item disalin tanpa id & sumber. */
    public function copyPayload(int $id, string $division): ?array
    {
        $src = $this->find($id);
        if (! $src || $src['division'] !== $division) {
            return null;
        }
        $items = [];
        foreach ($src['items'] as $it) {
            $row = ['id' => 0, 'source_type' => 'manual', 'params' => []];
            foreach (array_keys(self::ITEM_TEXT) as $f) {
                $row[$f] = $it[$f];
            }
            foreach ($it['params'] as $code => $kinds) {
                if (isset($kinds['plan'])) {
                    $row['params'][$code] = $kinds['plan'];
                }
            }
            $items[] = $row;
        }

        return [
            'header' => ['machine_id' => $src['machine_id'], 'process_id' => $src['process_id'], 'priority' => $src['priority'], 'notes' => $src['notes'],
                'work_date' => '', 'shift_id' => '', 'start_at' => '', 'end_at' => ''],
            'items'  => $items,
            'from'   => $src['schedule_no'],
        ];
    }

    // ---------------------------------------------------------------- insight & agregasi

    public function insight(string $division, string $date): array
    {
        $now   = date('Y-m-d H:i:s');
        $inact = config('ScheduleFlow')->inactive;
        $base  = fn () => $this->db->table('schedules s')->where('s.division', $division)->where('s.deleted_at', null);

        $by = array_column(
            $base()->select('s.status, COUNT(*) AS n')->where('s.work_date', $date)->groupBy('s.status')->get()->getResultArray(),
            'n',
            'status'
        );
        $today = 0;
        foreach ($by as $st => $n) {
            if (! in_array($st, $inact, true)) {
                $today += (int) $n;
            }
        }

        $late = $base()->where('s.status', 'approved')->where('s.start_at <', $now)->countAllResults();

        // Utilisasi: jam terjadwal / (jumlah mesin aktif x jam shift aktif hari itu)
        $sched = (int) ($base()->select('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, s.start_at, s.end_at)),0) AS m')->where('s.work_date', $date)
            ->whereNotIn('s.status', $inact)->get()->getRow('m') ?? 0);
        $avail = 0;
        foreach ($this->cal->activeShifts($date) as $s) {
            $avail += (int) round((strtotime($s['end_at']) - strtotime($s['start_at'])) / 60);
        }
        $machines = $this->db->table('machines')->where('type', $division)->where('is_active', 1)->countAllResults();
        $util     = ($avail > 0 && $machines > 0) ? (int) min(100, round($sched / ($avail * $machines) * 100)) : 0;

        $prim = $this->primaryParam($division);
        $qty  = 0.0;
        if ($prim) {
            $qty = (float) ($this->db->table('schedules s')
                ->select('COALESCE(SUM(ip.value_num),0) AS q')
                ->join('schedule_items i', 'i.schedule_id = s.id')
                ->join('schedule_item_params ip', "ip.schedule_item_id = i.id AND ip.kind = 'plan' AND ip.parameter_id = " . (int) $prim['id'], 'inner', false)
                ->where('s.division', $division)->where('s.deleted_at', null)->where('s.work_date', $date)->whereNotIn('s.status', $inact)
                ->get()->getRow('q') ?? 0);
        }

        return [
            'today'       => $today,
            'waiting'     => (int) $base()->where('s.status', 'submitted')->countAllResults(),
            'approved'    => (int) ($by['approved'] ?? 0),
            'running'     => (int) ($by['running'] ?? 0),
            'overshift'   => count($this->overshiftRows($division)),
            'late'        => (int) $late,
            'utilization' => $util,
            'qty'         => $qty,
            'qty_uom'     => $prim['uom'] ?? '',
            'qty_name'    => $prim['name'] ?? 'Qty',
        ];
    }

    /**
     * Schedule running/paused yang sudah melewati akhir shift pemegang + toleransi (PRD 5.5).
     * Dihitung on-the-fly (tanpa cron). @return array<int,int> schedule_id => machine_id
     */
    public function overshiftRows(string $division): array
    {
        $grace = (int) (new SettingModel())->getValue('schedule.overshift_grace_minutes', '15');
        $now   = date('Y-m-d H:i:s');
        $rows  = $this->db->table('schedules')->select('id, machine_id, current_shift_id, current_shift_date')
            ->where('division', $division)->where('deleted_at', null)->whereIn('status', ['running', 'paused'])
            ->where('current_shift_id IS NOT NULL', null, false)->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $win = $this->cal->shiftWindow((string) $r['current_shift_date'], (int) $r['current_shift_id']);
            if ($win && $now > date('Y-m-d H:i:s', strtotime($win['end_at']) + $grace * 60)) {
                $out[(int) $r['id']] = (int) $r['machine_id'];
            }
        }

        return $out;
    }

    // ---------------------------------------------------------------- internal

    private function nextNumber(string $division): string
    {
        $prefix = 'SCH-' . ($division === 'dyeing' ? 'DYE' : 'FIN') . '-' . date('ym') . '-';
        // termasuk yang soft-deleted agar nomor tidak pernah dipakai ulang
        $last = $this->db->query('SELECT schedule_no FROM schedules WHERE schedule_no LIKE ? ORDER BY schedule_no DESC LIMIT 1 FOR UPDATE', [$prefix . '%'])->getRowArray();
        $n    = $last ? (int) substr($last['schedule_no'], -4) + 1 : 1;

        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    private function nextSequence(int $machineId, string $date): int
    {
        $r = $this->db->query('SELECT COALESCE(MAX(sequence),0) AS m FROM schedules WHERE machine_id = ? AND work_date = ? AND deleted_at IS NULL FOR UPDATE', [$machineId, $date])->getRowArray();

        return (int) $r['m'] + 1;
    }

    /** Sinkronkan item & parameter rencana: update yang punya id, tambah yang baru, hapus yang tidak dikirim. */
    private function saveItems(int $scheduleId, string $division, array $items, int $userId): void
    {
        $now  = date('Y-m-d H:i:s');
        $defs = array_column($this->paramDefs($division), null, 'code');

        $existing = array_column($this->db->table('schedule_items')->select('id')->where('schedule_id', $scheduleId)->get()->getResultArray(), 'id');
        $keep     = [];

        // geser line_no lama agar tidak bentrok dengan unique (schedule_id, line_no) saat disusun ulang
        $this->db->query('UPDATE schedule_items SET line_no = line_no + 1000 WHERE schedule_id = ?', [$scheduleId]);

        foreach ($items as $i => $it) {
            $row = ['line_no' => $i + 1, 'updated_at' => $now];
            foreach (array_keys(self::ITEM_TEXT) as $f) {
                $row[$f] = $it[$f];
            }

            $itemId = (int) ($it['id'] ?? 0);
            if ($itemId && in_array($itemId, $existing, true)) {
                $this->db->table('schedule_items')->where('id', $itemId)->update($row);
            } else {
                $this->db->table('schedule_items')->insert($row + [
                    'schedule_id' => $scheduleId, 'source_type' => $it['source_type'] ?? 'manual',
                    'source_item_id' => $it['source_item_id'] ?? null, 'created_at' => $now,
                ]);
                $itemId = (int) $this->db->insertID();
            }
            $keep[] = $itemId;

            foreach ($defs as $code => $d) {
                $raw = $it['params'][$code] ?? null;
                if ($raw === null || $raw === '') {
                    $this->db->table('schedule_item_params')->where(['schedule_item_id' => $itemId, 'parameter_id' => $d['id'], 'kind' => 'plan'])->delete();
                    continue;
                }
                $num = $d['data_type'] === 'number' ? (float) str_replace(',', '.', (string) $raw) : null;
                $this->db->query(
                    'INSERT INTO schedule_item_params (schedule_item_id, parameter_id, kind, value_num, value_text, updated_by, created_at, updated_at) '
                    . "VALUES (?,?,'plan',?,?,?,?,?) ON DUPLICATE KEY UPDATE value_num = VALUES(value_num), value_text = VALUES(value_text), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)",
                    [$itemId, $d['id'], $num, $d['data_type'] === 'text' ? (string) $raw : null, $userId, $now, $now]
                );
            }
        }

        $drop = array_diff($existing, $keep);
        if ($drop) {
            $this->db->table('schedule_items')->whereIn('id', $drop)->delete();   // parameter ikut terhapus (CASCADE)
        }
    }

    /** Potret item + parameter rencana, untuk mendeteksi perubahan kritis dan mencatat nilai lama-baru. */
    private function snapshot(int $scheduleId): array
    {
        $out = [];
        foreach ($this->items($scheduleId) as $it) {
            $row = [];
            foreach (array_keys(self::ITEM_TEXT) as $f) {
                $row[$f] = $it[$f];
            }
            $row['params'] = [];
            foreach ($it['params'] as $code => $kinds) {
                if (isset($kinds['plan'])) {
                    $row['params'][$code] = $kinds['plan'];
                }
            }
            ksort($row['params']);
            $out[] = $row;
        }

        return $out;
    }

    private function num($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');

        return $s === '' ? '0' : $s;
    }
}

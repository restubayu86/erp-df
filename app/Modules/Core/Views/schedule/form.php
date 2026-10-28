<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Schedule<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendors/datatables.net-bs5/dataTables.bootstrap5.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$statusNote = $row ? ($row['status'] === 'approved' ? 'Jadwal ini sudah disetujui. Perubahan data penting (mesin, tanggal, shift, jam, urutan, prioritas, item, parameter) akan mengajukannya ulang ke Kepala Bagian.' : null) : null;
$sel = static fn ($a, $b) => (string) $a === (string) $b ? 'selected' : '';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger fs-10"><strong>Belum bisa disimpan:</strong><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul></div>
<?php endif ?>
<?php if ($notice): ?><div class="alert alert-info fs-10"><?= esc($notice) ?></div><?php endif ?>
<?php if ($statusNote): ?><div class="alert alert-warning fs-10"><span class="fas fa-exclamation-triangle me-1"></span><?= esc($statusNote) ?></div><?php endif ?>

<form method="post" action="<?= esc($action) ?>" id="schForm">
  <?= csrf_field() ?>
  <?php if ($copyFrom): ?><input type="hidden" name="copied_from_id" value="<?= (int) $copyFrom ?>"><?php endif ?>

  <div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Jadwal</h6></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label" for="work_date">Tanggal produksi</label>
          <input type="date" class="form-control" id="work_date" name="work_date" required value="<?= esc((string) $v['work_date']) ?>">
          <div class="form-text" id="patternInfo"></div>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="shift_id">Shift</label>
          <select class="form-select" id="shift_id" name="shift_id" required data-selected="<?= esc((string) $v['shift_id']) ?>">
            <option value="">Pilih tanggal dulu</option>
            <?php foreach ($shifts as $s): ?>
              <option value="<?= $s['shift_id'] ?>" data-start="<?= esc($s['start_at']) ?>" data-end="<?= esc($s['end_at']) ?>" <?= $sel($v['shift_id'], $s['shift_id']) ?>>
                <?= esc($s['name']) ?> (<?= esc($s['start']) ?>–<?= esc($s['end']) ?>)
              </option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="machine_id">Mesin</label>
          <select class="form-select" id="machine_id" name="machine_id" required>
            <option value="">Pilih mesin</option>
            <?php foreach ($machines as $m): ?>
              <option value="<?= (int) $m['id'] ?>" <?= $sel($v['machine_id'], $m['id']) ?>><?= esc($m['code'] . ' – ' . $m['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="process_id">Proses</label>
          <select class="form-select" id="process_id" name="process_id">
            <option value="">–</option>
            <?php foreach ($processes as $p): ?>
              <option value="<?= (int) $p['id'] ?>" data-std="<?= (int) $p['std_duration_min'] ?>" <?= $sel($v['process_id'], $p['id']) ?>><?= esc($p['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="category">Kategori</label>
          <select class="form-select" id="category" name="category">
            <?php foreach (['produksi' => 'Produksi', 'perbaikan' => 'Perbaikan', 'litbang' => 'Litbang', 'lain-lain' => 'Lain-lain'] as $k => $l): ?>
              <option value="<?= $k ?>" <?= $sel($v['category'] ?? 'produksi', $k) ?>><?= $l ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="start_at">Rencana mulai</label>
          <input type="datetime-local" class="form-control" id="start_at" name="start_at" required value="<?= esc($v['start_at']) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label" for="end_at">Rencana selesai</label>
          <input type="datetime-local" class="form-control" id="end_at" name="end_at" required value="<?= esc($v['end_at']) ?>">
          <div class="form-text" id="endInfo"></div>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="priority">Prioritas</label>
          <select class="form-select" id="priority" name="priority">
            <?php foreach (['normal' => 'Normal', 'high' => 'Tinggi', 'urgent' => 'Urgent'] as $k => $l): ?>
              <option value="<?= $k ?>" <?= $sel($v['priority'], $k) ?>><?= $l ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="notes">Catatan</label>
          <input class="form-control" id="notes" name="notes" value="<?= esc((string) $v['notes']) ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">Item / Order <span class="text-600 fs-10">(parameter mengikuti divisi <?= esc(ucfirst($moduleName)) ?>)</span></h6>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-falcon-default btn-sm" id="btnIfs"><span class="fas fa-link me-1"></span>Ambil dari IFS</button>
        <button type="button" class="btn btn-falcon-default btn-sm" id="btnAddItem"><span class="fas fa-plus me-1"></span>Tambah item</button>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive scrollbar">
        <table class="table table-sm fs-10 mb-0" id="itemsTable">
          <thead class="bg-body-tertiary">
            <tr>
              <th style="width:2rem">#</th><th style="min-width:130px">No. Order *</th><th style="min-width:130px">Customer</th>
              <th style="min-width:110px">Desain</th><th style="min-width:110px">Warna</th><th style="min-width:110px">Lot</th>
              <th style="min-width:110px">No. MP</th>
              <?php foreach ($defs as $d): ?>
                <th style="min-width:110px"><?= esc($d['name']) ?><?= $d['uom'] ? ' (' . esc($d['uom']) . ')' : '' ?><?= (int) $d['is_required'] ? ' *' : '' ?></th>
              <?php endforeach ?>
              <th style="width:2rem"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit"><?= $row ? 'Simpan perubahan' : 'Simpan sebagai draft' ?></button>
    <a class="btn btn-falcon-default" href="<?= site_url($moduleName . '/schedule') ?>">Batal</a>
  </div>
</form>

<div class="modal fade" id="ifsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ambil Data dari IFS</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive scrollbar">
          <table class="table table-sm fs-10 mb-0 w-100" id="ifsTable">
            <thead class="bg-body-tertiary">
              <tr>
                <th>DOP No</th>
                <th>Order No</th>
                <th>Customer</th>
                <th>Artikel</th>
                <th>Qty</th>
                <th></th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendors/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendors/datatables.net/dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendors/datatables.net-bs5/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= base_url('assets/vendors/select2/select2.full.min.js') ?>"></script>
<script>
(function () {
  const jq = window.jQuery || window.$;
  if (jq) {
    jq(function() {
      jq('select.form-select').select2({
        theme: 'bootstrap-5',
        width: '100%'
      });
    });
  }
  const DEFS = <?= json_encode(array_map(static fn ($d) => ['code' => $d['code'], 'type' => $d['data_type'], 'req' => (int) $d['is_required']], $defs)) ?>;
  const ITEMS = <?= json_encode($items) ?>;
  const APPROVED = <?= json_encode((bool) $approved) ?>;
  const API = <?= json_encode(site_url('api/shift/effective')) ?>;
  const $ = (s) => document.querySelector(s);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const tbody = $('#itemsTable tbody');
  let n = 0;

  function addRow(it = {}) {
    const i = n++;
    const f = (name, val, extra = '') => `<td><input class="form-control form-control-sm" name="items[${i}][${name}]" value="${esc(val)}" ${extra}></td>`;
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="text-600 align-middle">${tbody.children.length + 1}<input type="hidden" name="items[${i}][id]" value="${it.id || 0}"><input type="hidden" name="items[${i}][source_type]" value="${esc(it.source_type || 'manual')}"><input type="hidden" name="items[${i}][source_item_id]" value="${esc(it.source_item_id || '')}"></td>`
      + f('order_no', it.order_no, 'maxlength="50" required') + f('customer_name', it.customer_name, 'maxlength="100"')
      + f('design_name', it.design_name || it.article, 'maxlength="100"') + f('color_name', it.color_name, 'maxlength="50"') + f('lot_no', it.lot_no, 'maxlength="50"')
      + f('mp_no', it.mp_no, 'maxlength="50"')
      + DEFS.map((d) => `<td><input class="form-control form-control-sm" name="items[${i}][params][${d.code}]" value="${esc((it.params || {})[d.code])}" ${d.type === 'number' ? 'inputmode="decimal"' : ''} ${d.req ? 'required' : ''}></td>`).join('')
      + `<td class="align-middle"><button type="button" class="btn btn-link text-danger p-0 btn-del" title="Hapus item"><span class="fas fa-times"></span></button></td>`;
    tbody.appendChild(tr);
  }
  function renumber() { [...tbody.children].forEach((tr, k) => { tr.firstElementChild.firstChild.nodeValue = k + 1; }); }
  ITEMS.forEach(addRow);
  $('#btnAddItem').addEventListener('click', () => addRow());
  tbody.addEventListener('click', (e) => {
    if (!e.target.closest('.btn-del')) return;
    if (tbody.children.length > 1) { e.target.closest('tr').remove(); renumber(); }
  });

  // ---- IFS
  let ifsTable = null;
  const ifsModal = new bootstrap.Modal($('#ifsModal'));
  $('#btnIfs').addEventListener('click', () => {
    ifsModal.show();
    if (!ifsTable) {
      ifsTable = $('#ifsTable');
      const jq = window.jQuery || window.$;
      if (jq) {
        jq(ifsTable).DataTable({
          processing: true, serverSide: true, ajax: API_IFS + '/datatable',
          columns: [
            { data: 'dop_no' }, { data: 'order_no' }, { data: 'customer_name' },
            { data: 'article' }, { data: 'qty', render: (v, t, r) => v + ' ' + r.uom },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' }
          ],
          language: { search: '', searchPlaceholder: 'Cari DOP/Order/Customer...' }
        });

        jq(ifsTable).on('click', '.btn-select-ifs', async function() {
          const dop = this.dataset.dop;
          try {
            const d = await (await fetch(API_IFS + '/detail/' + dop, { credentials: 'same-origin' })).json();
            addRow({
              order_no: d.order_no, customer_name: d.customer_name, article: d.article,
              color_name: d.color_name, source_type: 'ifs', source_item_id: d.dop_no,
              params: { BERAT: d.qty, PANJANG: d.qty } // autofill qty ke parameter utama
            });
            ifsModal.hide();
          } catch (e) { alert('Gagal mengambil detail IFS'); }
        });
      }
    } else {
      const jq = window.jQuery || window.$;
      if (jq) jq(ifsTable).DataTable().ajax.reload();
    }
  });

  // ---- shift efektif sesuai tanggal (kalender shift)
  const shiftSel = $('#shift_id');
  async function loadShifts(keepSelected) {
    const date = $('#work_date').value;
    if (!date) return;
    const keep = keepSelected ? (shiftSel.dataset.selected || shiftSel.value) : '';
    try {
      const d = await (await fetch(API + '?date=' + date, { credentials: 'same-origin' })).json();
      shiftSel.innerHTML = d.shifts.length
        ? '<option value="">Pilih shift</option>' + d.shifts.map((s) => `<option value="${s.shift_id}" data-start="${esc(s.start_at)}" data-end="${esc(s.end_at)}">${esc(s.name)} (${esc(s.start)}–${esc(s.end)})</option>`).join('')
        : '<option value="">Tidak ada shift aktif pada tanggal ini</option>';
      if (keep) shiftSel.value = keep;
      if (jq) jq(shiftSel).trigger('change.select2');
      $('#patternInfo').textContent = d.pattern ? 'Pola: ' + d.pattern.name + (d.pattern.reason ? ' – ' + d.pattern.reason : '') : '';
    } catch (e) { /* biarkan pilihan yang ada */ }
  }
  const toLocal = (s) => String(s).replace(' ', 'T').slice(0, 16);
  $('#work_date').addEventListener('change', () => { shiftSel.dataset.selected = ''; loadShifts(false); });
  shiftSel.addEventListener('change', () => {
    const o = shiftSel.selectedOptions[0];
    if (o && o.dataset.start && !$('#start_at').value) $('#start_at').value = toLocal(o.dataset.start);
    hint();
  });

  // ---- estimasi selesai dari durasi standar proses (bila belum diisi) + peringatan overshift
  function addMin(v, m) { const d = new Date(v); d.setMinutes(d.getMinutes() + m); return new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16); }
  function hint() {
    const o = shiftSel.selectedOptions[0], end = $('#end_at').value;
    $('#endInfo').textContent = (o && o.dataset.end && end && end > toLocal(o.dataset.end)) ? 'Melewati akhir shift – akan overshift.' : '';
  }
  function suggestEnd() {
    const std = parseInt($('#process_id').selectedOptions[0]?.dataset.std || '0', 10), st = $('#start_at').value;
    if (std > 0 && st && !$('#end_at').value) { $('#end_at').value = addMin(st, std); hint(); }
  }
  $('#process_id').addEventListener('change', suggestEnd); $('#start_at').addEventListener('change', suggestEnd); $('#end_at').addEventListener('change', hint);

  if (shiftSel.options.length <= 1 && $('#work_date').value) loadShifts(true);
  hint();

  $('#schForm').addEventListener('submit', (e) => {
    if (APPROVED && !confirm('Jadwal sudah disetujui. Jika ada perubahan data penting, jadwal diajukan ulang ke Kepala Bagian dan sementara tidak tampil di tablet/TV. Lanjutkan?')) e.preventDefault();
  });
})();
</script>
<?= $this->endSection() ?>

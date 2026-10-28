<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Schedule<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.4.1/css/rowReorder.bootstrap5.min.css">
<style>td.reorder-handle{cursor:grab;white-space:nowrap}td.reorder-handle::before{content:"\2630  ";opacity:.5}</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Action Card -->
<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h4 class="mb-0">Jadwal Produksi</h4>
    <p class="text-body-secondary fs-9 mb-0">Kelola jadwal produksi dan prioritas mesin</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-phoenix-secondary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
      <i class="fa fa-filter"></i> Filter
    </button>
    <a href="<?= site_url($moduleName . '/schedule/new') ?>" class="btn btn-primary btn-sm">
      <i class="fa fa-plus"></i> Jadwal Baru
    </a>
  </div>
</div>

<!-- Insight Cards -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="text-body-secondary mb-0">Total Order</h6>
          <i class="fa fa-list text-primary"></i>
        </div>
        <h4 class="mt-2 mb-0">12</h4>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="text-body-secondary mb-0">Berjalan</h6>
          <i class="fa fa-play text-success"></i>
        </div>
        <h4 class="mt-2 mb-0">3</h4>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="text-body-secondary mb-0">Menunggu</h6>
          <i class="fa fa-clock text-warning"></i>
        </div>
        <h4 class="mt-2 mb-0">5</h4>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="text-body-secondary mb-0">Selesai</h6>
          <i class="fa fa-check text-info"></i>
        </div>
        <h4 class="mt-2 mb-0">4</h4>
      </div>
    </div>
  </div>
</div>

<!-- Filter Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="filterOffcanvasLabel">Filter Jadwal</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div class="mb-3">
      <label class="form-label fs-9" for="fDate">Tanggal Produksi</label>
      <input type="date" id="fDate" class="form-control form-control-sm" value="<?= esc($defaultDate) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label fs-9" for="fMachine">Mesin</label>
      <select id="fMachine" class="form-select form-select-sm">
        <option value="">Semua mesin</option>
        <?php foreach ($machines as $m): ?>
          <option value="<?= (int) $m['id'] ?>"><?= esc($m['name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label fs-9" for="fStatus">Status</label>
      <select id="fStatus" class="form-select form-select-sm">
        <option value="">Semua status</option>
        <?php foreach ($labels as $code => $l): ?>
          <option value="<?= esc($code) ?>"><?= esc($l[0]) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <hr>
    <p id="hint" class="fs-9 text-warning d-none">
      <i class="fa fa-info-circle"></i> Seret baris untuk mengubah prioritas.
    </p>
    <p id="hintOff" class="fs-9 text-body-tertiary">
      Pilih tanggal dan mesin untuk mengaktifkan pengurutan prioritas.
    </p>
  </div>
</div>

<!-- DataTable Card -->
<div class="card">
  <div class="card-body p-3">
    <table id="tblSchedule" class="table table-hover table-sm fs-9 mb-0 w-100">
      <thead>
        <tr>
          <th>Prioritas</th><th>Tanggal</th><th>Shift</th><th>Mesin</th><th>No. Order</th>
          <th>Customer</th><th>Proses</th><th>Mulai</th><th>Selesai</th><th>Status</th><th></th>
        </tr>
      </thead>
    </table>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/rowreorder/1.4.1/js/dataTables.rowReorder.min.js"></script>
<script>
$(function () {
  const LABELS = <?= json_encode($labels) ?>;
  const text = $.fn.dataTable.render.text();
  const esc  = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const DEFAULT_ORDER = [[1,'asc'],[2,'asc'],[3,'asc'],[0,'asc']];   // tanggal, shift, mesin, prioritas

  const table = $('#tblSchedule').DataTable({
    processing: true,
    serverSide: true,
    pageLength: 100,
    order: DEFAULT_ORDER,
    ajax: {
      url: '<?= path_url($moduleName . '/schedule/datatable') ?>',
      data: (d) => {
        d.work_date  = $('#fDate').val();
        d.machine_id = $('#fMachine').val();
        d.status     = $('#fStatus').val();
      },
    },
    rowReorder: { dataSrc: 'priority', selector: 'td.reorder-handle', update: false },
    columns: [
      { data: 'priority', className: 'reorder-handle', render: text },
      { data: 'work_date', render: text },
      { data: 'shift',     render: (d) => esc(d).toUpperCase() },
      { data: 'machine',   render: text },
      { data: 'order_no',  render: text },
      { data: 'customer',  render: text },
      { data: 'process',   render: text },
      { data: 'start_at',  render: (d) => esc(d).slice(5, 16) },
      { data: 'end_at',    render: (d) => esc(d).slice(5, 16) },
      { data: 'status',    render: (d) => {
          const l = LABELS[d] || [d, 'secondary'];
          return `<span class="badge badge-phoenix badge-phoenix-${esc(l[1])}">${esc(l[0])}</span>`;
        } },
      { data: 'action', orderable: false, searchable: false },
    ],
  });

  // Pengurutan prioritas hanya aktif bila tabel terfilter 1 tanggal + 1 mesin
  function applyScope() {
    const scoped = $('#fDate').val() && $('#fMachine').val();
    if (scoped) { table.order([0, 'asc']); table.rowReorder.enable(); }
    else        { table.order(DEFAULT_ORDER); table.rowReorder.disable(); }
    $('#hint').toggleClass('d-none', !scoped);
    $('#hintOff').toggleClass('d-none', !!scoped);
    table.ajax.reload();
  }
  table.rowReorder.disable();
  $('#fDate, #fMachine').on('change', applyScope);
  $('#fStatus').on('change', () => table.ajax.reload());

  table.on('row-reorder', async (e, diff) => {
    if (!diff.length) return;
    const items = diff.map(d => ({ id: table.row(d.node).data().id, priority: Number(d.newData) }));
    try { await erp.post('<?= $moduleName ?>/schedule/reorder', { items }); }
    catch (err) { alert(err.message); }
    table.ajax.reload(null, false);
  });

  // Ajukan / ACC / Tolak / Revisi
  $('#tblSchedule').on('click', '.btn-trans', async function () {
    if (await erp.transition($(this).data('id'), $(this).data('to'))) table.ajax.reload(null, false);
  });
});
</script>
<?= $this->endSection() ?>

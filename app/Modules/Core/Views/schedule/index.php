<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Schedule Produksi<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.4.1/css/rowReorder.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.bootstrap5.min.css">
<link rel="stylesheet" href="<?= base_url('assets/vendors/flatpickr/flatpickr.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css') ?>">
<style>
  td.reorder-handle {
    cursor: grab;
    white-space: nowrap;
  }

  td.reorder-handle::before {
    content: "\2630  ";
    opacity: .5;
  }

  #tblSchedule tr.dtrg-group td {
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    font-size: 0.9rem;
  }

  #tblSchedule tr.dtrg-group .group-icon {
    display: inline-block;
    width: 1rem;
    transition: transform .15s;
  }

  #tblSchedule tr.dtrg-group.is-collapsed .group-icon {
    transform: rotate(-90deg);
  }

  .insight-card {
    cursor: pointer;
  }

  .insight-card:hover {
    box-shadow: 0 0 0 .1rem var(--bs-primary);
  }

  #scheduleCard .dataTables_wrapper .row {
    margin: 0;
  }

  .table-responsive {
    overflow-x: auto !important;
    flex: 1;
    overflow-y: auto !important;
  }

  #scheduleCard {
    display: flex;
    flex-direction: column;
    height: 75vh;
  }

  #scheduleCard .card-body {
    flex: 1;
    overflow: hidden !important;
    display: flex;
    flex-direction: column;
  }

  #scheduleCard .dataTables_wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  #scheduleCard .dataTables_wrapper .table-responsive {
    flex: 1;
  }

  .badge {
    font-size: 0.85rem !important;
  }

  #tblSchedule th,
  #tblSchedule td {
    padding: 0.6rem 0.75rem !important;
    white-space: nowrap;
  }

  #tblSchedule thead th {
    font-weight: 600;
    font-size: 0.8rem;
    letter-spacing: 0.02em;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$cards = [
  // id, label, tone, ikon, filter saat diklik
  ['today',       'Jadwal',          'primary', 'fa-list',            ['status' => []]],
  ['waiting',     'Menunggu Approval', 'warning', 'fa-clock',         ['status' => ['submitted'], 'all_dates' => 1]],
  ['approved',    'Disetujui',       'info',    'fa-check',           ['status' => ['approved']]],
  ['running',     'Berjalan',        'success', 'fa-play',            ['status' => ['running']]],
  ['overshift',   'Overshift',       'danger',  'fa-hourglass-end',   ['status' => ['running', 'paused'], 'all_dates' => 1]],
  ['late',        'Terlambat Mulai', 'danger',  'fa-exclamation-triangle', ['status' => ['approved'], 'all_dates' => 1]],
  ['utilization', 'Utilisasi Mesin', 'secondary', 'fa-chart-pie',     null],
  ['qty',         'Total Qty',       'secondary', 'fa-weight-hanging', null],
];
?>
<div class="row g-3 mb-3" id="insightRow">
  <?php foreach ($cards as [$id, $label, $tone, $icon, $flt]): ?>
    <div class="col-6 col-md-3 col-xxl">
      <div class="card h-100 <?= $flt ? 'insight-card' : '' ?>" data-card="<?= $id ?>" <?= $flt ? "data-filter='" . esc(json_encode($flt), 'attr') . "'" : '' ?>>
        <div class="card-body py-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fs-10 text-700"><?= esc($label) ?></span>
            <span class="fas <?= $icon ?> text-<?= $tone ?>"></span>
          </div>
          <div class="fs-6 fw-semi-bold text-<?= $tone ?>" data-val="<?= $id ?>">–</div>
          <div class="fs-11 text-600" data-sub="<?= $id ?>"></div>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<div class="row gx-3">
  <div class="col-xxl-10 col-xl-9">
    <div class="card" id="scheduleCard">
      <div class="card-header border-bottom border-200 px-0">
        <div class="d-lg-flex justify-content-between">
          <div class="row flex-between-center gy-2 px-x1">
            <div class="col-auto pe-0">
              <h6 class="mb-0">Jadwal Produksi</h6>
            </div>
            <div class="col-auto">
              <input class="form-control form-control-sm shadow-none" id="qSearch" type="search" placeholder="Cari no. schedule / order / customer / lot" style="min-width:280px" aria-label="Cari">
            </div>
          </div>
          <div class="d-flex align-items-center justify-content-end gap-2 px-x1 mt-2 mt-lg-0">
            <button class="btn btn-falcon-default me-1 btn-sm" type="button" id="btnFullscreen">
              <span class="fas fa-expand" id="iconFullscreen"></span>
            </button>
            <button class="btn btn-falcon-default me-1 d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas"><span class="fas fa-filter"></span> Filter</button>
            <button class="btn btn-falcon-default btn-sm" id="btnExpandAll" type="button" title="Buka semua mesin"><span class="fas fa-angle-double-down"></span></button>
            <button class="btn btn-falcon-default btn-sm" id="btnCollapseAll" type="button" title="Tutup semua mesin"><span class="fas fa-angle-double-up"></span></button>
            <?php if ($canCreate): ?>
              <a href="<?= site_url($moduleName . '/schedule/create') ?>" class="btn btn-falcon-primary btn-sm"><span class="fas fa-plus"></span> Jadwal Baru</a>
            <?php endif ?>
          </div>
        </div>
      </div>
      <div class="card-body p-0">
        <div id="dtError" class="alert alert-danger fs-10 m-3 d-none" role="alert"></div>
        <table id="tblSchedule" class="table table-hover table-sm fs-10 mb-0 w-100">
          <thead class="bg-body-tertiary text-800">
            <tr>
              <th>Aksi</th>
              <th>Urutan</th>
              <th>Dye Lot No /<br> Kategori</th>
              <th>No DOP</th>
              <th>Tanggal /<br> Shift</th>
              <th>Desain /<br> Segment</th>
              <th>Warna</th>
              <th>No. MP</th>
              <th>Catatan</th>
              <th>Proses</th>
              <th>Cuci Mesin</th>
              <th>Jam Rencana</th>
              <th>Prioritas</th>
              <th>Status</th>
              <th>Operator</th>
              <th class="d-none">Mesin</th>
              <th class="d-none">Kode</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xxl-2 col-xl-3">
    <div class="offcanvas offcanvas-end offcanvas-filter-sidebar border-0 dark__bg-card-dark h-auto rounded-xl-3" tabindex="-1" id="filterOffcanvas">
      <div class="offcanvas-header d-flex flex-between-center d-xl-none bg-body-tertiary">
        <h6 class="fs-9 mb-0 fw-semi-bold">Filter</h6>
        <button class="btn-close text-reset d-xl-none shadow-none" type="button" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
      </div>
      <div class="card scrollbar shadow-none shadow-show-xl">
        <div class="card-header bg-body-tertiary d-none d-xl-block">
          <h6 class="mb-0">Filter</h6>
        </div>
        <div class="card-body">
          <div class="btn-group btn-group-sm w-100 mb-3" role="group" aria-label="Pintasan periode">
            <button type="button" class="btn btn-falcon-secondary" data-preset="today">Hari ini</button>
            <button type="button" class="btn btn-falcon-secondary" data-preset="tomorrow">Besok</button>
            <button type="button" class="btn btn-falcon-secondary" data-preset="week">Minggu ini</button>
          </div>
          <button type="button" class="btn btn-sm btn-falcon-secondary w-100 mb-3" data-preset="approval"><span class="fas fa-clock me-1 text-warning"></span>Perlu Approval</button>
          <form id="filterForm" onsubmit="return false">
            <label class="mb-1 fs-10" for="fDateRange">Rentang tanggal</label>
            <input type="text" id="fDateRange" class="form-control form-control-sm mb-2" placeholder="Pilih rentang tanggal">
            <label class="mb-1 fs-10" for="fMachine">Mesin</label>
            <select id="fMachine" class="form-select form-select-sm mb-2">
              <option value="">Semua mesin</option>
              <?php foreach ($machines as $m): ?><option value="<?= (int) $m['id'] ?>"><?= esc($m['code'] . ' – ' . $m['name']) ?></option><?php endforeach ?>
            </select>
            <label class="mb-1 fs-10" for="fShift">Shift</label>
            <select id="fShift" class="form-select form-select-sm mb-2">
              <option value="">Semua shift</option>
              <?php foreach ($shifts as $s): ?><option value="<?= (int) $s['id'] ?>"><?= esc((string)$s['name']) ?></option><?php endforeach ?>
            </select>
            <label class="mb-1 fs-10" for="fStatus">Status (bisa lebih dari satu)</label>
            <select id="fStatus" class="form-select form-select-sm mb-2" multiple>
              <?php foreach ($labels as $code => $l): ?><option value="<?= esc((string)$code) ?>"><?= esc((string)$l[0]) ?></option><?php endforeach ?>
            </select>
            <label class="mb-1 fs-10" for="fPriority">Prioritas</label>
            <select id="fPriority" class="form-select form-select-sm mb-2">
              <option value="">Semua</option>
              <option value="normal">Normal</option>
              <option value="high">Tinggi</option>
              <option value="urgent">Urgent</option>
            </select>
            <p id="hint" class="fs-10 text-warning mb-0 d-none"><span class="fas fa-info-circle me-1"></span>Seret ☰ untuk mengubah urutan. Hanya di dalam satu mesin dan satu tanggal.</p>
            <p id="hintOff" class="fs-10 text-600 mb-0">Pilih satu tanggal (dari = sampai) untuk mengaktifkan pengurutan.</p>
          </form>
        </div>
        <div class="card-footer border-top border-200 py-x1">
          <button class="btn btn-primary w-100" id="btnApply" type="button">Terapkan</button>
          <button class="btn btn-falcon-secondary btn-sm w-100 mt-2" id="btnReset" type="button">Reset</button>
        </div>
      </div>
    </div>
  </div>
</div>

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

<div class="modal fade" id="ifsDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail IFS</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="ifsDetailContent"></div>
    </div>
  </div>
</div>

<div class="modal fade" id="paramModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Parameter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="paramDetailContent"></div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/rowreorder/1.4.1/js/dataTables.rowReorder.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
<script src="<?= base_url('assets/vendors/flatpickr/flatpickr.min.js') ?>"></script>
<script src="<?= base_url('assets/vendors/select2/select2.full.min.js') ?>"></script>
<script>
  $(function() {
    const MOD = <?= json_encode($moduleName) ?>;
    const LABELS = <?= json_encode($labels) ?>;
    const TODAY = <?= json_encode((string) $defaultDate) ?>;
    const CAN_REORDER = <?= json_encode((bool) $canReorder) ?>;
    const INIT = <?= json_encode((object) $filters) ?>;
    const COLLAPSE_KEY = 'sch.collapsed.' + MOD;
    const PRIO = {
      normal: ['Normal', 'secondary'],
      high: ['Tinggi', 'warning'],
      urgent: ['Urgent', 'danger']
    };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    } [c]));
    const hm = (d) => esc(String(d ?? '').slice(11, 16));
    const dm = (d) => esc(String(d ?? '').slice(5, 10));
    const addDays = (iso, n) => {
      const d = new Date(iso + 'T00:00:00');
      d.setDate(d.getDate() + n);
      return d.toLocaleDateString('en-CA');
    };
    const num = (v) => v === null || v === undefined || v === '' ? '–' : Number(v).toLocaleString('id-ID', {
      maximumFractionDigits: 2
    });

    let collapsed = {};
    try {
      collapsed = JSON.parse(localStorage.getItem(COLLAPSE_KEY) || '{}');
    } catch (e) {}
    const saveCollapsed = () => {
      try {
        localStorage.setItem(COLLAPSE_KEY, JSON.stringify(collapsed));
      } catch (e) {}
    };
    let totals = {};

    const datePicker = flatpickr('#fDateRange', {
      mode: 'range',
      dateFormat: 'Y-m-d',
      defaultDate: [TODAY, TODAY]
    });

    $('#fMachine, #fShift, #fStatus, #fPriority').select2({
      theme: 'bootstrap-5',
      width: '100%'
    });

    // ---- filter <-> form <-> URL
    function readFilters() {
      const dates = datePicker.selectedDates;
      return {
        date_from: dates[0] ? dates[0].toLocaleDateString('en-CA') : '',
        date_to: dates[1] ? dates[1].toLocaleDateString('en-CA') : (dates[0] ? dates[0].toLocaleDateString('en-CA') : ''),
        machine_id: $('#fMachine').val(),
        shift_id: $('#fShift').val(),
        status: $('#fStatus').val() || [],
        priority: $('#fPriority').val(),
        q: $('#qSearch').val().trim(),
      };
    }

    function writeFilters(f) {
      if (f.date_from && f.date_to) {
        datePicker.setDate([f.date_from, f.date_to]);
      } else if (f.date_from) {
        datePicker.setDate([f.date_from, f.date_from]);
      } else {
        datePicker.clear();
      }
      $('#fMachine').val(f.machine_id || '').trigger('change');
      $('#fShift').val(f.shift_id || '').trigger('change');
      $('#fStatus').val([].concat(f.status || [])).trigger('change');
      $('#fPriority').val(f.priority || '').trigger('change');
      $('#qSearch').val(f.q || '');
    }

    function pushUrl() {
      const f = readFilters(),
        p = new URLSearchParams();
      Object.entries(f).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach((x) => p.append(k + '[]', x));
        else if (v) p.set(k, v);
      });
      history.replaceState(null, '', location.pathname + (p.toString() ? '?' + p : ''));
    }
    const hasInit = Object.keys(INIT).length > 0;
    writeFilters(hasInit ? INIT : {
      date_from: TODAY,
      date_to: TODAY
    });

    // ---- tabel
    $.fn.dataTable.ext.errMode = 'none';
    const showError = (m) => $('#dtError').text(m).removeClass('d-none');
    const text = $.fn.dataTable.render.text();

    const table = $('#tblSchedule').DataTable({
      processing: true,
      serverSide: true,
      searching: false,
      pageLength: 50,
      lengthMenu: [25, 50, 100, 200],
      orderFixed: {
        pre: [
          [15, 'asc'],
          [4, 'asc']
        ]
      },
      order: [
        [1, 'asc']
      ],
      dom: "<'table-responsive scrollbar't><'card-footer border-top border-200'<'row g-2 align-items-center'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4 d-flex justify-content-sm-end'p>>>",
      language: {
        lengthMenu: 'Tampil _MENU_',
        info: '_START_–_END_ dari _TOTAL_',
        infoEmpty: 'Tidak ada data',
        emptyTable: 'Belum ada jadwal pada filter ini',
        zeroRecords: 'Data tidak ditemukan',
        processing: 'Memuat...',
        paginate: {
          previous: '‹',
          next: '›'
        }
      },
      ajax: {
        url: <?= json_encode(site_url($moduleName . '/schedule/datatable')) ?>,
        data: (d) => Object.assign(d, readFilters()),
        dataSrc: (json) => {
          $('#dtError').addClass('d-none');
          totals = json.machineTotals || {};
          return json.data || [];
        },
        error: (xhr) => showError('Gagal memuat data (HTTP ' + xhr.status + '): ' + String(xhr.responseText || '').replace(/<[^>]+>/g, ' ').trim().slice(0, 300)),
      },
      rowGroup: {
        dataSrc: 'machine',
        startRender: (rows, group) => {
          const isC = !!collapsed[group];
          rows.nodes().each((r) => {
            r.style.display = isC ? 'none' : '';
          });
          const cols = rows.context[0].aoColumns.filter((c) => c.bVisible).length;
          const t = totals[group] || {
            total: rows.count(),
            summary: {},
            overshift: 0
          };
          const statusIcons = {
            'draft': 'fa-file-alt text-secondary',
            'submitted': 'fa-clock text-warning',
            'revision': 'fa-undo text-info',
            'rejected': 'fa-times-circle text-danger',
            'approved': 'fa-check-circle text-success',
            'running': 'fa-play-circle text-success',
            'paused': 'fa-pause-circle text-warning',
            'done': 'fa-check-double text-secondary',
            'cancelled': 'fa-ban text-secondary'
          };
          const chips = Object.entries(t.summary || {}).map(([st, n]) => {
            const l = LABELS[st] || [st, 'secondary'];
            const icon = statusIcons[st] || 'fa-circle';
            return `<span class="badge rounded-pill badge-subtle-${esc(l[1])} ms-1" title="${esc(l[0])}"><span class="fas ${icon} me-1"></span>${n}</span>`;
          }).join('');
          const over = t.overshift ? `<span class="badge rounded-pill bg-danger ms-1" title="Overshift"><span class="fas fa-hourglass-end me-1"></span>${t.overshift}</span>` : '';
          return $('<tr/>').attr('data-name', group).toggleClass('is-collapsed', isC).append($('<td/>').attr('colspan', cols).html(
            `<span class="fas fa-chevron-down group-icon text-600 me-1"></span><span class="fas fa-industry text-primary me-2"></span>${esc(group)}` +
            `<span class="badge rounded-pill badge-subtle-secondary ms-2" title="Total Jadwal"><span class="fas fa-list me-1"></span>${t.total}</span>${chips}${over}`));
        },
      },
      rowReorder: {
        dataSrc: 'sequence',
        selector: 'td.reorder-handle',
        update: false
      },
      columns: [{
          data: 'action',
          orderable: false,
          className: 'text-nowrap align-middle'
        },
        {
          data: 'sequence',
          className: 'reorder-handle align-middle',
          render: text
        },
        {
          data: 'dye_lot_no',
          orderable: false,
          className: 'align-middle',
          render: (d, t, r) => {
            const cats = {
              'produksi': ['Produksi', 'primary', 'fa-cogs'],
              'perbaikan': ['Perbaikan', 'warning', 'fa-tools'],
              'litbang': ['Litbang', 'info', 'fa-flask'],
              'lain-lain': ['Lain-lain', 'secondary', 'fa-tag']
            };
            const c = cats[r.category] || [r.category, 'secondary', 'fa-tag'];
            const color = r.category === 'perbaikan' ? 'text-warning' : 'text-primary';
            return `<a href="<?= site_url($moduleName . '/schedule') ?>/${r.id}" class="fw-semi-bold ${color}">${esc(d)}</a>` +
              `<div class="mt-1"><span class="badge badge-subtle-${c[1]}"><span class="fas ${c[2]} me-1"></span>${esc(c[0])}</span></div>`;
          }
        },
        {
          data: 'dop_no',
          orderable: false,
          className: 'align-middle',
          render: (d) => d ? `<a href="#!" class="btn-ifs-detail" data-dop="${esc(d)}">${esc(d)}</a>` : '–'
        },
        {
          data: 'work_date',
          orderable: false,
          className: 'align-middle',
          render: (d, t, r) => {
            const shiftColors = {
              'Pagi': 'primary',
              'Siang': 'warning',
              'Malam': 'dark'
            };
            const color = shiftColors[r.shift] || 'success';
            return `${esc(d)}<div class="mt-1"><span class="badge badge-subtle-${color}"><span class="fas fa-business-time me-1"></span>${esc(r.shift)}</span></div>`;
          }
        },
        {
          data: 'design_first',
          orderable: false,
          className: 'align-middle',
          render: (d, t, r) => {
            const params = {
              'Berat Kain (kg)': r.param_BERAT,
              'Panjang Kain (m)': r.param_PANJANG,
              'Jumlah Roll': r.param_JML_ROLL,
              'LR': r.param_LIQUOR_RATIO,
              'Suhu (°C)': r.param_SUHU,
              'No Program': r.param_NO_PROGRAM,
              'No Nozzle': r.param_NO_NOZZLE
            };
            const pStr = btoa(JSON.stringify(params));
            const val = d || '–';
            const parts = val.split(' / ');
            const design = parts[0];
            const segment = parts.length > 1 ? ' / ' + parts.slice(1).join(' / ') : '';

            return `<span class="fw-bold fs-9 text-emphasis">${esc(design)}</span><span class="text-600">${esc(segment)}</span>` +
              ` <button type="button" class="btn btn-falcon-default btn-sm ms-2 btn-param-detail px-2 py-1" data-params="${pStr}" title="Lihat Parameter"><span class="fas fa-sliders-h text-primary fs-10"></span></button>`;
          }
        },
        {
          data: 'color_first',
          orderable: false,
          className: 'align-middle',
          render: (d) => esc(d || '–')
        },
        {
          data: 'mp_first',
          orderable: false,
          className: 'align-middle',
          render: (d) => esc(d || '–')
        },
        {
          data: 'item_notes',
          orderable: false,
          className: 'align-middle',
          render: (d) => esc(d || '–')
        },
        {
          data: 'process',
          orderable: false,
          className: 'align-middle',
          render: (d) => esc(d || '–')
        },
        {
          data: 'param_CUCI_MESIN',
          orderable: false,
          className: 'align-middle',
          render: (d) => esc(d || '–')
        },
        {
          data: 'start_at',
          orderable: false,
          className: 'align-middle',
          render: (d, t, r) => `<span class="text-primary fw-bold">${hm(d)}</span>–<span class="text-danger fw-bold">${hm(r.end_at)}</span><div class="text-600">${dm(d)}${String(r.end_at).slice(0, 10) !== String(d).slice(0, 10) ? ' → ' + dm(r.end_at) : ''}</div>`
        },
        {
          data: 'priority',
          orderable: false,
          className: 'align-middle',
          render: (d) => {
            const prioMap = {
              'normal': ['Normal', 'secondary', 'fa-minus'],
              'high': ['Tinggi', 'warning', 'fa-arrow-up'],
              'urgent': ['Urgent', 'danger', 'fa-exclamation']
            };
            const p = prioMap[d] || [d, 'secondary', 'fa-minus'];
            return `<span class="badge badge-subtle-${p[1]}"><span class="fas ${p[2]} me-1"></span>${esc(p[0])}</span>`;
          }
        },
        {
          data: 'status',
          orderable: false,
          className: 'align-middle',
          render: (d, t, r) => {
            const statusIconsMap = {
              'draft': 'fa-file-alt',
              'submitted': 'fa-clock',
              'revision': 'fa-undo',
              'rejected': 'fa-times-circle',
              'approved': 'fa-check-circle',
              'running': 'fa-play-circle',
              'paused': 'fa-pause-circle',
              'done': 'fa-check-double',
              'cancelled': 'fa-ban'
            };
            const l = LABELS[d] || [d, 'secondary'];
            const icon = statusIconsMap[d] || 'fa-circle';
            return `<span class="badge rounded-pill badge-subtle-${esc(l[1])}"><span class="fas ${icon} me-1"></span>${esc(l[0])}</span>` +
              (r.revision_no > 0 ? ` <span class="badge badge-subtle-info">Rev. ${r.revision_no}</span>` : '') +
              (r.overshift ? ' <span class="badge bg-danger"><span class="fas fa-hourglass-end me-1"></span>Overshift</span>' : '');
          }
        },
        {
          data: 'current_operator',
          orderable: false,
          className: 'align-middle',
          render: (d) => esc(d || '–')
        },
        {
          data: 'machine',
          visible: false,
          orderable: false
        },
        {
          data: 'machine_code',
          visible: false
        },
      ],
    });
    table.on('error.dt', (e, s, n, m) => showError(m));

    // ---- grup mesin: buka/tutup, diingat di browser
    function setCollapsed($tr, c) {
      collapsed[$tr.attr('data-name')] = c;
      saveCollapsed();
      $tr.toggleClass('is-collapsed', c);
      $tr.nextUntil('tr.dtrg-group').toggle(!c);
    }
    $('#tblSchedule tbody').on('click', 'tr.dtrg-group', function() {
      setCollapsed($(this), !$(this).hasClass('is-collapsed'));
    });
    $('#btnCollapseAll').on('click', () => $('#tblSchedule tr.dtrg-group').each(function() {
      setCollapsed($(this), true);
    }));
    $('#btnExpandAll').on('click', () => $('#tblSchedule tr.dtrg-group').each(function() {
      setCollapsed($(this), false);
    }));

    // ---- fullscreen toggle & icon update (FontAwesome JS compatible)
    const cardEl = document.getElementById('scheduleCard');
    const btnFs = document.getElementById('btnFullscreen');

    if (btnFs && cardEl) {
      const applyState = (full) => {
        const iconClass = full ? 'fa-compress' : 'fa-expand';
        btnFs.innerHTML = `<span class="fas ${iconClass}" id="iconFullscreen"></span>`;
        btnFs.setAttribute('title', full ? 'Keluar Layar Penuh' : 'Layar Penuh');
      };

      btnFs.addEventListener('click', () => {
        const doc = document;
        const isFull = !!(doc.fullscreenElement || doc.webkitFullscreenElement || doc.mozFullScreenElement || doc.msFullscreenElement);

        if (!isFull) {
          const req = cardEl.requestFullscreen || cardEl.webkitRequestFullscreen || cardEl.mozRequestFullScreen || cardEl.msRequestFullscreen;
          if (req) req.call(cardEl);
        } else {
          const exit = doc.exitFullscreen || doc.webkitExitFullscreen || doc.mozCancelFullScreen || doc.msExitFullscreen;
          if (exit) exit.call(doc);
        }
        // Immediate UI update for better responsiveness
        applyState(!isFull);
      });

      ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
        document.addEventListener(evt, () => {
          const isFull = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
          applyState(isFull);
        });
      });
    }

    // ---- kartu insight
    async function loadInsight() {
      try {
        const f = readFilters();
        const date = f.date_from === f.date_to && f.date_from ? f.date_from : TODAY;
        const r = await fetch(<?= json_encode(site_url($moduleName . '/schedule/insight')) ?> + '?date=' + date, {
          credentials: 'same-origin'
        });
        const d = await r.json();
        const set = (k, v, sub) => {
          $(`[data-val="${k}"]`).text(v);
          $(`[data-sub="${k}"]`).text(sub || '');
        };
        set('today', d.today, date === TODAY ? 'hari ini' : date);
        set('waiting', d.waiting, 'semua tanggal');
        set('approved', d.approved);
        set('running', d.running);
        set('overshift', d.overshift);
        set('late', d.late, 'lewat jam mulai');
        set('utilization', d.utilization + '%', 'jam terjadwal ÷ jam tersedia');
        set('qty', num(d.qty) + ' ' + (d.qty_uom || ''), d.qty_name);
      } catch (e) {
        /* kartu opsional; tabel tetap jalan */
      }
    }

    function apply() {
      const f = readFilters(),
        one = f.date_from && f.date_from === f.date_to;
      CAN_REORDER && one ? table.rowReorder.enable() : table.rowReorder.disable();
      $('#hint').toggleClass('d-none', !(CAN_REORDER && one));
      $('#hintOff').toggleClass('d-none', CAN_REORDER && one);
      pushUrl();
      table.ajax.reload();
      loadInsight();
    }
    apply();

    $('#btnApply').on('click', () => {
      apply();
      const oc = bootstrap.Offcanvas.getInstance(document.getElementById('filterOffcanvas'));
      if (oc) oc.hide();
    });
    $('#btnReset').on('click', () => {
      writeFilters({
        date_from: TODAY,
        date_to: TODAY
      });
      apply();
    });
    let timer;
    $('#qSearch').on('input', () => {
      clearTimeout(timer);
      timer = setTimeout(apply, 350);
    });

    $('[data-preset]').on('click', function() {
      const p = $(this).data('preset');
      if (p === 'today') writeFilters({
        date_from: TODAY,
        date_to: TODAY
      });
      if (p === 'tomorrow') {
        const t = addDays(TODAY, 1);
        writeFilters({
          date_from: t,
          date_to: t
        });
      }
      if (p === 'week') {
        const d = new Date(TODAY + 'T00:00:00');
        const mon = addDays(TODAY, -((d.getDay() + 6) % 7));
        writeFilters({
          date_from: mon,
          date_to: addDays(mon, 6)
        });
      }
      if (p === 'approval') writeFilters({
        status: ['submitted']
      });
      apply();
    });
    $('.insight-card[data-filter]').on('click', function() {
      const f = $(this).data('filter');
      const current = readFilters();
      const base = f.all_dates ? {} : {
        date_from: current.date_from || TODAY,
        date_to: current.date_to || TODAY
      };
      writeFilters(Object.assign(base, {
        status: f.status
      }));
      apply();
    });

    // ---- urutan antar baris (satu mesin, satu tanggal)
    table.on('row-reorder', async (e, diff) => {
      if (!diff.length) return;
      const rows = diff.map((d) => table.row(d.node).data());
      if (new Set(rows.map((r) => r.machine)).size > 1 || new Set(rows.map((r) => r.work_date)).size > 1) {
        alert('Urutan hanya bisa diubah di dalam mesin dan tanggal yang sama.');
        table.ajax.reload(null, false);
        return;
      }
      try {
        await erp.post(MOD + '/schedule/reorder', {
          items: diff.map((d, i) => ({
            id: rows[i].id,
            sequence: Number(d.newData)
          }))
        });
      } catch (err) {
        alert(err.message);
      }
      table.ajax.reload(null, false);
      loadInsight();
    });

    // ---- IFS
    const API_IFS = <?= json_encode(site_url('api/ifs')) ?>;
    let ifsTable = null;
    const ifsModal = document.getElementById('ifsModal') ? new bootstrap.Modal(document.getElementById('ifsModal')) : null;
    const ifsDetailModal = document.getElementById('ifsDetailModal') ? new bootstrap.Modal(document.getElementById('ifsDetailModal')) : null;

    if (document.getElementById('btnIfs')) {
      $('#btnIfs').addEventListener('click', () => {
        ifsModal.show();
        if (!ifsTable) {
          ifsTable = $('#ifsTable');
          const jq = window.jQuery || window.$;
          if (jq) {
            jq(ifsTable).DataTable({
              processing: true,
              serverSide: true,
              ajax: API_IFS + '/datatable',
              columns: [{
                  data: 'dop_no'
                }, {
                  data: 'order_no'
                }, {
                  data: 'customer_name'
                },
                {
                  data: 'article'
                }, {
                  data: 'qty',
                  render: (v, t, r) => v + ' ' + r.uom
                },
                {
                  data: 'action',
                  orderable: false,
                  searchable: false,
                  className: 'text-end'
                }
              ],
              language: {
                search: '',
                searchPlaceholder: 'Cari DOP/Order/Customer...'
              }
            });

            jq(ifsTable).on('click', '.btn-select-ifs', async function() {
              const dop = this.dataset.dop;
              try {
                const d = await (await fetch(API_IFS + '/detail/' + dop, {
                  credentials: 'same-origin'
                })).json();
                addRow({
                  order_no: d.order_no,
                  customer_name: d.customer_name,
                  article: d.article,
                  color_name: d.color_name,
                  source_type: 'ifs',
                  source_item_id: d.dop_no,
                  params: {
                    BERAT: d.qty,
                    PANJANG: d.qty
                  }
                });
                ifsModal.hide();
              } catch (e) {
                alert('Gagal mengambil detail IFS');
              }
            });
          }
        } else {
          const jq = window.jQuery || window.$;
          if (jq) jq(ifsTable).DataTable().ajax.reload();
        }
      });
    }

    $('#tblSchedule').on('click', '.btn-ifs-detail', async function(e) {
      e.preventDefault();
      const dop = this.dataset.dop;
      if (!dop) return;
      try {
        const response = await fetch(API_IFS + '/detail/' + dop, {
          credentials: 'same-origin'
        });
        if (!response.ok) throw new Error('Data tidak ditemukan');
        const d = await response.json();
        $('#ifsDetailContent').html(`<dl class="row">
          <dt class="col-sm-4">DOP No</dt><dd class="col-sm-8">${esc(d.dop_no)}</dd>
          <dt class="col-sm-4">Order / Customer</dt><dd class="col-sm-8">${esc(d.order_no)} / ${esc(d.customer_name)}</dd>
          <dt class="col-sm-4">Artikel</dt><dd class="col-sm-8">${esc(d.article)}</dd>
          <dt class="col-sm-4">Qty</dt><dd class="col-sm-8">${esc(d.qty)} ${esc(d.uom)}</dd>
        </dl>`);
        ifsDetailModal.show();
      } catch (e) {
        alert(e.message);
      }
    });

    const paramModal = new bootstrap.Modal(document.getElementById('paramModal'));
    $('#tblSchedule').on('click', '.btn-param-detail', function(e) {
      e.preventDefault();
      try {
        const data = JSON.parse(atob(this.dataset.params));
        let html = '<dl class="row mb-0">';
        for (const [k, v] of Object.entries(data)) {
          let val = '–';
          if (v !== null && v !== undefined && v !== '') {
            if (k === 'LR') {
              const numVal = Number(v);
              val = '1:' + (!isNaN(numVal) ? Math.round(numVal) : v);
            } else {
              const numVal = Number(v);
              val = !isNaN(numVal) ? numVal.toLocaleString('id-ID', {
                maximumFractionDigits: 2
              }) : v;
            }
          }
          html += `<dt class="col-sm-6">${esc(k)}</dt><dd class="col-sm-6">${esc(val)}</dd>`;
        }
        html += '</dl>';
        $('#paramDetailContent').html(html);
        paramModal.show();
      } catch (err) {
        alert('Gagal memuat detail parameter');
      }
    });
    $('#tblSchedule').on('click', '.btn-act', async function(e) {
      e.preventDefault();
      const id = $(this).data('id'),
        act = $(this).data('act');
      try {
        if (act === 'delete') {
          if (!confirm('Hapus draft ini?')) return;
          await erp.post(`${MOD}/schedule/${id}/delete`, {});
        } else if (act === 'cancel') {
          if (!confirm('Batalkan schedule ini?')) return;
          await erp.post(`${MOD}/schedule/${id}/transition`, {
            to: 'cancelled'
          });
        } else if (act === 'submit') {
          await erp.post(`${MOD}/schedule/${id}/submit`, {});
        } else if (act === 'approve') {
          await erp.post(`${MOD}/schedule/${id}/approve`, {});
        } else {
          const note = (prompt(act === 'revise' ? 'Catatan revisi untuk Scheduler:' : 'Alasan penolakan:') || '').trim();
          if (!note) return;
          await erp.post(`${MOD}/schedule/${id}/${act}`, {
            note
          });
        }
        table.ajax.reload(null, false);
        loadInsight();
      } catch (err) {
        alert(err.message);
      }
    });
  });
</script>
<?= $this->endSection() ?>
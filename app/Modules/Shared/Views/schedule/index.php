<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Schedule<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.4.1/css/rowReorder.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.bootstrap5.min.css">
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
  }

  #tblSchedule tr.dtrg-group .group-icon {
    display: inline-block;
    width: 1rem;
    transition: transform .15s;
  }

  #tblSchedule tr.dtrg-group.is-collapsed .group-icon {
    transform: rotate(-90deg);
  }

  #scheduleCard .dataTables_info,
  #scheduleCard .dataTables_length label {
    font-size: .8333333333rem;
    margin-bottom: 0;
  }

  #scheduleCard .dataTables_wrapper .row {
    margin: 0;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// $stats dikirim dari controller: total, running, waiting, done
$stats = ($stats ?? []) + ['total' => 0, 'running' => 0, 'waiting' => 0, 'done' => 0];
$cards = [
  ['Total Order', $stats['total'],   'warning', 'corner-1.png', 'fa-list'],
  ['Berjalan',    $stats['running'], 'success', 'corner-2.png', 'fa-play'],
  ['Menunggu',    $stats['waiting'], 'info',    'corner-3.png', 'fa-clock'],
  ['Selesai',     $stats['done'],    'primary', 'corner-4.png', 'fa-check'],
];
?>

<!-- Kartu ringkasan -->
<div class="row g-3 mb-3">
  <?php foreach ($cards as [$label, $value, $tone, $bg, $icon]): ?>
    <div class="col-sm-6 col-xxl-3">
      <div class="card overflow-hidden h-100">
        <div class="bg-holder bg-card" style="background-image:url(<?= base_url('assets/img/icons/spot-illustrations/' . $bg) ?>);"></div>
        <div class="card-header pb-0 position-relative">
          <h6 class="mb-0"><?= esc($label) ?>
            <span class="badge rounded-pill ms-2 badge-subtle-<?= $tone ?>"><span class="fas <?= $icon ?>"></span></span>
          </h6>
        </div>
        <div class="card-body position-relative">
          <div class="display-4 fs-5 mb-1 fw-normal font-sans-serif text-<?= $tone ?>"><?= (int) $value ?></div>
          <span class="fs-10 text-600">order</span>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<div class="row gx-3">
  <!-- Tabel -->
  <div class="col-xxl-10 col-xl-9">
    <div class="card" id="scheduleCard">
      <div class="card-header border-bottom border-200 px-0">
        <div class="d-lg-flex justify-content-between">
          <div class="row flex-between-center gy-2 px-x1">
            <div class="col-auto pe-0">
              <h6 class="mb-0">Jadwal Produksi</h6>
            </div>
            <div class="col-auto">
              <div class="input-group input-search-width">
                <input class="form-control form-control-sm shadow-none" id="qSearch" type="search" placeholder="Cari order / customer" aria-label="Cari">
                <button class="btn btn-sm btn-outline-secondary border-300 hover-border-secondary" type="button" tabindex="-1">
                  <span class="fa fa-search fs-10"></span>
                </button>
              </div>
            </div>
          </div>
          <div class="border-bottom border-200 my-3 d-lg-none"></div>
          <div class="d-flex align-items-center justify-content-between justify-content-lg-end px-x1">
            <button class="btn btn-sm btn-falcon-default d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" aria-controls="filterOffcanvas">
              <span class="fas fa-filter" data-fa-transform="shrink-4 down-1"></span><span class="ms-1 d-none d-sm-inline-block">Filter</span>
            </button>
            <div class="bg-300 mx-3 d-none d-lg-block d-xl-none" style="width:1px;height:29px"></div>
            <div class="btn-group btn-group-sm" id="groupTools" role="group" aria-label="Grup mesin">
              <button class="btn btn-falcon-default btn-sm" id="btnExpandAll" type="button" title="Buka semua mesin">
                <span class="fas fa-angle-double-down" data-fa-transform="shrink-2"></span>
              </button>
              <button class="btn btn-falcon-default btn-sm" id="btnCollapseAll" type="button" title="Tutup semua mesin">
                <span class="fas fa-angle-double-up" data-fa-transform="shrink-2"></span>
              </button>
            </div>
            <a href="<?= site_url($moduleName . '/schedule/new') ?>" class="btn btn-falcon-default btn-sm">
              <span class="fas fa-plus" data-fa-transform="shrink-3"></span><span class="d-none d-sm-inline-block ms-1">Jadwal Baru</span>
            </a>
          </div>
        </div>
      </div>

      <div class="card-body p-0">
        <div id="dtError" class="alert alert-danger fs-10 m-3 d-none" role="alert"></div>
        <table id="tblSchedule" class="table table-hover table-sm fs-10 mb-0 w-100">
          <thead class="bg-body-tertiary text-800">
            <tr>
              <th>Action</th>
              <th>Prioritas</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Tanggal</th>
              <th>Shift</th>
              <th>Desain</th>
              <th>Warna</th>
              <th>No. MP</th>
              <th>Proses</th>
              <th>Cuci Mesin</th>
              <th>Mulai</th>
              <th>Selesai</th>
              <th>Customer</th>
              <th>Progress</th>
              <th>Dye Lot No</th>
              <th>DOP</th>
              <th>No. Order</th>
              <th>Catatan</th>
              <th class="d-none">Mesin</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

  <!-- Filter -->
  <div class="col-xxl-2 col-xl-3">
    <div class="offcanvas offcanvas-end offcanvas-filter-sidebar border-0 dark__bg-card-dark h-auto rounded-xl-3" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
      <div class="offcanvas-header d-flex flex-between-center d-xl-none bg-body-tertiary">
        <h6 class="fs-9 mb-0 fw-semi-bold" id="filterOffcanvasLabel">Filter</h6>
        <button class="btn-close text-reset d-xl-none shadow-none" type="button" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
      </div>
      <div class="card scrollbar shadow-none shadow-show-xl">
        <div class="card-header bg-body-tertiary d-none d-xl-block">
          <h6 class="mb-0">Filter</h6>
        </div>
        <div class="card-body">
          <form id="filterForm" onsubmit="return false">
            <div class="mb-2 mt-n2">
              <label class="mb-1" for="fDate">Tanggal Produksi</label>
              <input type="date" id="fDate" class="form-control form-control-sm" value="<?= esc((string) $defaultDate) ?>">
            </div>
            <div class="mb-2">
              <label class="mb-1 mt-2" for="fMachine">Mesin</label>
              <select id="fMachine" class="form-select form-select-sm">
                <option value="">Semua mesin</option>
                <?php foreach ($machines as $m): ?>
                  <option value="<?= (int) $m['id'] ?>"><?= esc((string) $m['name']) ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="mb-1 mt-2" for="fStatus">Status</label>
              <select id="fStatus" class="form-select form-select-sm">
                <option value="">Semua status</option>
                <?php foreach ($labels as $code => $l): ?>
                  <option value="<?= esc((string) $code) ?>"><?= esc((string) $l[0]) ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <hr class="my-3">
            <p id="hint" class="fs-10 text-warning mb-0 d-none">
              <span class="fas fa-info-circle me-1"></span>Seret baris untuk mengubah prioritas. Hanya bisa di dalam mesin yang sama.
            </p>
            <p id="hintOff" class="fs-10 text-600 mb-0">
              Isi tanggal produksi untuk mengaktifkan pengurutan prioritas.
            </p>
          </form>
        </div>
        <div class="card-footer border-top border-200 py-x1">
          <button class="btn btn-primary w-100" id="btnApply" type="button">Terapkan</button>
          <button class="btn btn-falcon-default btn-sm w-100 mt-2" id="btnReset" type="button">Reset</button>
        </div>
      </div>
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
<script>
  $(function() {
    const LABELS = <?= json_encode($labels) ?>;
    const DEFAULT_DATE = <?= json_encode((string) $defaultDate) ?>;
    const text = $.fn.dataTable.render.text();
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    } [c]));
    const shortTime = (d) => esc(String(d ?? '').slice(5, 16));
    // mesin (grup), prioritas, tanggal. Shift diurutkan server (pagi > siang > malam) sebagai pembeda terakhir.
    const DEFAULT_ORDER = [
      [19, 'asc'],
      [1, 'asc'],
      [4, 'asc']
    ];

    // Status grup yang ditutup: { 'Nama Mesin': true }
    const collapsedGroups = {};

    // Pesan error tampil di halaman supaya mudah dilacak
    $.fn.dataTable.ext.errMode = 'none';
    const showError = (msg) => $('#dtError').text(msg).removeClass('d-none');
    const hideError = () => $('#dtError').addClass('d-none').text('');

    const table = $('#tblSchedule').DataTable({
      processing: true,
      serverSide: true,
      pageLength: 100,
      lengthMenu: [25, 50, 100, 200],
      order: DEFAULT_ORDER,
      dom: "<'table-responsive scrollbar't>" +
        "<'card-footer border-top border-200'<'row g-2 align-items-center'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4 d-flex justify-content-sm-end'p>>>",
      language: {
        lengthMenu: 'Tampil _MENU_',
        info: '_START_–_END_ dari _TOTAL_',
        infoEmpty: 'Tidak ada data',
        emptyTable: 'Belum ada jadwal',
        zeroRecords: 'Data tidak ditemukan',
        processing: 'Memuat...',
        paginate: {
          previous: '‹',
          next: '›'
        }
      },
      ajax: {
        url: '<?= site_url($moduleName . '/schedule/datatable') ?>',
        data: (d) => {
          d.work_date = $('#fDate').val();
          d.machine_id = $('#fMachine').val();
          d.status = $('#fStatus').val();
        },
        dataSrc: (json) => {
          if (!json || !Array.isArray(json.data)) {
            showError('Respons server tidak berisi "data": ' + JSON.stringify(json).slice(0, 300));
            return [];
          }
          if (json.error) {
            showError(json.error);
            return [];
          }
          hideError();
          return json.data;
        },
        error: (xhr) => showError('Gagal memuat data (HTTP ' + xhr.status + '): ' + String(xhr.responseText || '').replace(/<[^>]+>/g, ' ').trim().slice(0, 300)),
      },
      rowGroup: {
        dataSrc: (row) => row.machine || 'Tanpa mesin',
        // Baris grup yang sedang ditutup tetap tertutup setelah draw (ganti halaman, reload, dll.)
        startRender: (rows, group) => {
          const collapsed = !!collapsedGroups[group];
          rows.nodes().each((r) => {
            r.style.display = collapsed ? 'none' : '';
          });
          const cols = rows.context[0].aoColumns.filter((c) => c.bVisible).length;

          return $('<tr/>')
            .attr('data-name', group)
            .toggleClass('is-collapsed', collapsed)
            .append($('<td/>').attr('colspan', cols).html(
              `<span class="fas fa-chevron-down group-icon text-600 me-1"></span>` +
              `<span class="fas fa-industry text-primary me-2"></span>${esc(group)}` +
              `<span class="badge rounded-pill badge-subtle-primary ms-2">${rows.count()} order</span>`
            ));
        }
      },
      rowReorder: {
        dataSrc: 'priority',
        selector: 'td.reorder-handle',
        update: false
      },
      columns: [{
          data: 'action',
          orderable: false,
          searchable: false,
          className: 'text-nowrap'
        },
        {
          data: 'priority',
          className: 'reorder-handle',
          render: text
        },
        {
          data: 'category',
          render: text
        },
        {
          data: 'status',
          render: (d) => {
            const l = LABELS[d] || [d, 'secondary'];
            return `<span class="badge rounded-pill badge-subtle-${esc(l[1])}">${esc(l[0])}</span>`;
          }
        },
        {
          data: 'work_date',
          render: text
        },
        {
          data: 'shift',
          orderable: false,
          render: (d) => esc(d).toUpperCase()
        },
        {
          data: 'design',
          render: text
        },
        {
          data: 'color',
          render: text
        },
        {
          data: 'mp_no',
          render: text
        },
        {
          data: 'process',
          render: text
        },
        {
          data: 'wash_machine',
          render: text
        },
        {
          data: 'start_at',
          render: shortTime
        },
        {
          data: 'end_at',
          render: shortTime
        },
        {
          data: 'customer',
          render: text
        },
        {
          data: 'progress',
          render: (d) => {
            const val = parseInt(d) || 0;
            return `<div class="progress" style="height: 15px;" title="${val}%">
              <div class="progress-bar" role="progressbar" style="width: ${val}%" aria-valuenow="${val}" aria-valuemin="0" aria-valuemax="100">${val}%</div>
            </div>`;
          }
        },
        {
          data: 'dye_lot_no',
          render: text
        },
        {
          data: 'dop_no',
          render: text
        },
        {
          data: 'order_no',
          render: text
        },
        {
          data: 'note',
          render: text
        },
        {
          data: 'machine',
          visible: false,
          render: text
        }, // tampil sebagai header grup
      ],
    });

    table.on('error.dt', (e, settings, techNote, message) => showError(message));

    // Collapse / expand grup mesin (tanpa reload data)
    function setCollapsed($tr, collapsed) {
      collapsedGroups[$tr.attr('data-name')] = collapsed;
      $tr.toggleClass('is-collapsed', collapsed);
      $tr.nextUntil('tr.dtrg-group').toggle(!collapsed);
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

    // Pencarian di header card
    let timer;
    $('#qSearch').on('input', function() {
      clearTimeout(timer);
      const v = this.value;
      timer = setTimeout(() => table.search(v).draw(), 300);
    });

    // Pengurutan prioritas aktif bila tanggal produksi dipilih (prioritas berlaku per mesin per tanggal)
    function syncReorder() {
      const can = !!$('#fDate').val();
      can ? table.rowReorder.enable() : table.rowReorder.disable();
      $('#hint').toggleClass('d-none', !can);
      $('#hintOff').toggleClass('d-none', can);
    }

    function applyScope() {
      syncReorder();
      table.ajax.reload();
    }
    syncReorder();

    $('#btnApply').on('click', function() {
      applyScope();
      const oc = bootstrap.Offcanvas.getInstance(document.getElementById('filterOffcanvas'));
      if (oc) oc.hide();
    });
    $('#btnReset').on('click', function() {
      $('#fDate').val(DEFAULT_DATE);
      $('#fMachine, #fStatus').val('');
      applyScope();
    });

    table.on('row-reorder', async (e, diff) => {
      if (!diff.length) return;

      const rows = diff.map((d) => table.row(d.node).data());

      // Baris tidak boleh dipindah ke mesin lain (atau tanggal lain)
      if (new Set(rows.map((r) => r.machine)).size > 1 || new Set(rows.map((r) => r.work_date)).size > 1) {
        alert('Urutan hanya bisa diubah di dalam mesin yang sama.');
        table.ajax.reload(null, false);
        return;
      }

      const items = diff.map((d, i) => ({
        id: rows[i].id,
        priority: Number(d.newData)
      }));
      try {
        await erp.post('<?= $moduleName ?>/schedule/reorder', {
          items
        });
      } catch (err) {
        alert(err.message);
      }
      table.ajax.reload(null, false);
    });

    // Hapus schedule
    $('#tblSchedule').on('click', '.btn-delete', async function() {
      if (!confirm('Yakin ingin menghapus schedule ini?')) return;
      const id = $(this).data('id');
      try {
        await erp.post('<?= $moduleName ?>/schedule/' + id + '/delete', {});
        table.ajax.reload(null, false);
      } catch (err) {
        alert(err.message);
      }
    });

    // Ajukan / ACC / Tolak / Revisi / Batal
    $('#tblSchedule').on('click', '.btn-trans', async function() {
      const id = $(this).data('id');
      const to = $(this).data('to');
      const note = (to === 'rejected') ? prompt('Alasan penolakan:') : null;
      if (to === 'rejected' && note === null) return;

      try {
        await erp.post('<?= $moduleName ?>/schedule/' + id + '/transition', {
          to,
          note
        });
        table.ajax.reload(null, false);
      } catch (err) {
        alert(err.message);
      }
    });
  });
</script>
<?= $this->endSection() ?>
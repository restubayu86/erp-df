<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Schedule<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/vendors/datatables.net-bs5/dataTables.bootstrap5.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2/select2.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/vendors/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css') ?>">
<style>
  .item-table-redesign { font-size: 0.8rem; }
  .item-table-redesign td { padding: 0.35rem !important; vertical-align: middle; }
  .item-table-redesign input.form-control-sm { font-size: 0.75rem; height: 1.8rem; }
  .item-table-redesign select.form-select-sm { font-size: 0.75rem; height: 1.8rem; }
  .dye-lot-display { font-weight: 600; color: #2563eb; }
  .param-modal-grid { font-size: 0.75rem; }
  .param-modal-grid .table-sm td { padding: 0.25rem; }
  .param-modal-grid input { font-size: 0.75rem; height: 1.8rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$statusNote = $row ? ($row['status'] === 'approved' ? 'Jadwal ini sudah disetujui. Perubahan akan mengajukannya ulang ke Kepala Bagian.' : null) : null;
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

  <!-- HEADER: Simplified (Tanggal, Mesin, Kategori, Catatan) -->
  <div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Jadwal</h6></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="work_date">Tanggal Produksi *</label>
          <input type="date" class="form-control" id="work_date" name="work_date" required value="<?= esc((string) $v['work_date']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="machine_id">Mesin *</label>
          <div class="input-group">
            <select class="form-select select2-header" id="machine_id" name="machine_id" required>
              <option value="">Pilih mesin</option>
              <?php foreach ($machines as $m): ?>
                <option value="<?= (int) $m['id'] ?>" data-code="<?= esc($m['code']) ?>" <?= $sel($v['machine_id'], $m['id']) ?>><?= esc($m['code'] . ' – ' . $m['name']) ?></option>
              <?php endforeach ?>
            </select>
            <button type="button" class="btn btn-outline-secondary" id="btnMasterMesin" title="Master Mesin">
              <span class="fas fa-cog"></span>
            </button>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="notes">Catatan</label>
          <input class="form-control" id="notes" name="notes" value="<?= esc((string) $v['notes']) ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ITEM/ORDER TABLE: New Redesigned Structure (13 columns) -->
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0">Item / Order</h6>
      <button type="button" class="btn btn-falcon-default btn-sm" id="btnAddItem">
        <span class="fas fa-plus me-1"></span>Tambah Item
      </button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive scrollbar">
        <table class="table table-sm mb-0 item-table-redesign" id="itemsTable">
          <thead class="bg-secondary text-white">
            <tr>
              <th style="width:2rem; color:white;">#</th>
              <th style="min-width:110px; color:white;">Dye Lot No</th>
              <th style="min-width:85px; color:white;">Kategori *</th>
              <th style="min-width:120px; color:white;">Proses *</th>
              <th style="min-width:85px; color:white;">Shift *</th>
              <th style="min-width:110px; color:white;">Desain</th>
              <th style="min-width:90px; color:white;">Warna</th>
              <th style="min-width:80px; color:white;">Lot</th>
              <th style="min-width:70px; color:white;">MP</th>
              <th style="min-width:140px; color:white;">Jam Rencana *</th>
              <th style="min-width:90px; color:white;">Prioritas</th>
              <th style="width:65px; color:white;">Parameter</th>
              <th style="width:75px; color:white;">Cuci Mesin</th>
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

<!-- MASTER MESIN MODAL -->
<div class="modal fade" id="masterMesinModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Master Mesin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Form Input Mesin -->
        <div class="card mb-3">
          <div class="card-body">
            <form id="formMesin">
              <input type="hidden" id="mesin_id" name="id">
              <input type="hidden" id="mesin_type" name="type" value="<?= esc($moduleType) ?>">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label" for="mesin_code_suffix">Kode Mesin *</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text" id="mesin_prefix"><?= esc($moduleType === 'dyeing' ? 'JD-' : 'ST-') ?></span>
                    <input type="text" class="form-control" id="mesin_code_suffix" placeholder="01" required maxlength="5">
                    <input type="hidden" id="mesin_code" name="code">
                  </div>
                </div>
                <div class="col-md-5">
                  <label class="form-label" for="mesin_name">Nama Mesin *</label>
                  <input type="text" class="form-control" id="mesin_name" name="name" placeholder="Nama mesin" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Type</label>
                  <div class="form-control bg-body-tertiary text-primary fw-bold"><?= esc(ucfirst($moduleType)) ?></div>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm" id="btnSaveMesin">
                  <span class="fas fa-save me-1"></span>Simpan
                </button>
                <button type="button" class="btn btn-secondary btn-sm" id="btnResetMesin">
                  <span class="fas fa-times me-1"></span>Batal
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Table List Mesin -->
        <div class="table-responsive" style="max-height: 400px;">
          <table class="table table-sm table-bordered table-hover">
            <thead class="bg-secondary text-white position-sticky" style="top: 0; z-index: 10;">
              <tr>
                <th style="width: 100px; color:white;">Kode Mesin</th>
                <th style="color:white;">Nama Mesin</th>
                <th style="width: 100px; color:white;">Type</th>
                <th style="width: 160px; color:white;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="tableMesinBody">
              <tr>
                <td colspan="4" class="text-center text-muted">Loading...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SHARED PARAMETER MODAL -->
<div class="modal fade" id="parameterModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Parameter - Semua Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body param-modal-grid">
        <div class="table-responsive scrollbar" style="max-height: 500px;">
          <table class="table table-sm table-bordered">
            <thead class="bg-secondary text-white position-sticky" style="top: 0; z-index: 10;">
              <tr>
                <th style="min-width:120px; color:white;">Dye Lot / Item</th>
                <th style="min-width:110px; color:white;">Berat Kain (kg)</th>
                <th style="min-width:110px; color:white;">Panjang Kain (m)</th>
                <th style="min-width:100px; color:white;">Jumlah Roll</th>
                <th style="min-width:80px; color:white;">LR</th>
                <th style="min-width:90px; color:white;">Suhu (°C)</th>
                <th style="min-width:100px; color:white;">No Program</th>
                <th style="min-width:100px; color:white;">No Nozzle</th>
              </tr>
            </thead>
            <tbody id="parameterGridBody">
              <!-- Generated dynamically -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="btnSaveParameters">Simpan</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendors/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendors/select2/select2.full.min.js') ?>"></script>
<script>
(function () {
  const jq = window.jQuery || window.$;
  const qs = (s) => document.querySelector(s);
  const qsa = (s) => document.querySelectorAll(s);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const toLocal = (s) => {
    if (!s) return '';
    const d = new Date(String(s).replace(' ', 'T'));
    return d.toISOString().slice(0, 16);
  };
  
  // Data from PHP
  const DEFS = <?= json_encode(array_map(static fn ($d) => ['code' => $d['code'], 'name' => $d['name'], 'uom' => $d['uom'], 'type' => $d['data_type'], 'req' => (int) $d['is_required']], $defs)) ?>;
  const ITEMS = <?= json_encode($items) ?>;
  const PROCESSES = <?= json_encode($processes) ?>;
  const SHIFTS = <?= json_encode($shifts) ?>;
  const MACHINES = <?= json_encode($machines) ?>;
  const APPROVED = <?= json_encode((bool) $approved) ?>;
  
  // Initialize Select2 for header
  if (jq) {
    jq(function() {
      jq('.select2-header').select2({ theme: 'bootstrap-5', width: '100%' });
    });
  }
  
  const tbody = qs('#itemsTable tbody');
  let n = 0;
  let machinesData = [...MACHINES]; // Moved up
  
  // ===== DYE LOT AUTO-GENERATION =====
  function generateDyeLotNo(itemIndex) {
    const workDate = qs('#work_date').value;
    const machineId = qs('#machine_id').value;
    
    if (!workDate || !machineId) return '';
    
    // Use machinesData (which is updated via API) instead of static MACHINES
    const machine = machinesData.find(m => m.id == machineId);
    if (!machine || !machine.code) return '';
    
    const datePart = workDate.replace(/-/g, '').slice(2); // YYMMDD
    // Extract numeric part or code part safely from format like DY-01, FIN-01, JD-01, or 01
    const cleanCode = String(machine.code).replace(/[^a-zA-Z0-9]/g, '');
    const machinePart = cleanCode.slice(-2).padStart(2, '0');
    const counter = String(parseInt(itemIndex || 0, 10) + 1).padStart(2, '0');
    
    return `JD${datePart}${machinePart}${counter}`;
  }

  function updateAllDyeLots() {
    [...tbody.querySelectorAll('tr')].forEach((row) => {
      const itemIndex = row.dataset.itemIndex;
      const newDyeLot = generateDyeLotNo(itemIndex);
      row.dataset.dyeLot = newDyeLot;
      
      const hiddenDyeLot = row.querySelector(`input[name="items[${itemIndex}][dye_lot_no]"]`);
      if (hiddenDyeLot) hiddenDyeLot.value = newDyeLot;
      
      const displayDyeLot = row.querySelector('.dye-lot-display');
      if (displayDyeLot) displayDyeLot.textContent = newDyeLot;
    });
  }

  // Update dye lots when date or machine changes
  qs('#work_date').addEventListener('change', updateAllDyeLots);
  qs('#machine_id').addEventListener('change', updateAllDyeLots);
  
  // ===== PROCESS AUTO-FILL =====
  function onProcessChange(itemIndex) {
    const row = tbody.querySelector(`tr[data-item-index="${itemIndex}"]`);
    if (!row) return;
    
    const processSelect = row.querySelector('.item-process');
    const designInput = row.querySelector('.item-design');
    const colorInput = row.querySelector('.item-color');
    
    if (!processSelect.value) {
      designInput.value = '';
      colorInput.value = '';
      return;
    }
    
    const process = PROCESSES.find(p => p.id == processSelect.value);
    if (process) {
      designInput.value = process.design_template || '';
      colorInput.value = process.color_template || '';
    }
  }
  
  // ===== NOTIFICATION HELPER (FALCON TOAST) =====
  function showToast(message, type = 'success') {
    const toastContainer = qs('.toast-container') || (function() {
      const container = document.createElement('div');
      container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      container.style.zIndex = '1100';
      document.body.appendChild(container);
      return container;
    })();

    const toastId = 'toast-' + Date.now();
    const icon = type === 'success' ? 'check-circle' : (type === 'danger' ? 'exclamation-circle' : 'info-circle');
    
    const toastHtml = `
      <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-${type} text-white">
          <span class="fas fa-${icon} me-2"></span>
          <strong class="me-auto">Notifikasi</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="bootstrap.Toast.getOrCreateInstance(document.getElementById('${toastId}')).hide()"></button>
        </div>
        <div class="toast-body">
          ${esc(message)}
        </div>
      </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }

  // ===== CONFIRMATION HELPER (MODAL) =====
  function showConfirm(message, onConfirm) {
    const modalId = 'confirm-modal-' + Date.now();
    const modalHtml = `
      <div class="modal fade" id="${modalId}" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 400px">
          <div class="modal-content">
            <div class="modal-body p-4">
              <div class="d-flex align-items-center">
                <span class="fas fa-question-circle text-primary fs-3 me-3"></span>
                <p class="mb-0 fw-semi-bold">${esc(message)}</p>
              </div>
            </div>
            <div class="modal-footer bg-light border-0">
              <button class="btn btn-link text-600 px-3" type="button" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-primary px-4" type="button" id="${modalId}-btn">Ya, Lanjutkan</button>
            </div>
          </div>
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modalEl = document.getElementById(modalId);
    const modal = new bootstrap.Modal(modalEl);
    
    document.getElementById(`${modalId}-btn`).addEventListener('click', () => {
      modal.hide();
      onConfirm();
    });
    
    modalEl.addEventListener('hidden.bs.modal', () => modalEl.remove());
    modal.show();
  }

  // ===== KATEGORI PERBAIKAN - LOAD RELATED =====
  async function onKategoriChange(itemIndex) {
    const row = tbody.querySelector(`tr[data-item-index="${itemIndex}"]`);
    if (!row) return;
    
    const kategoriSelect = row.querySelector('.item-kategori');
    if (kategoriSelect.value !== 'perbaikan') return;
    
    const workDate = qs('#work_date').value;
    const machineId = qs('#machine_id').value;
    
    if (!workDate || !machineId) {
      showToast('Pilih tanggal dan mesin terlebih dahulu', 'warning');
      kategoriSelect.value = 'produksi';
      return;
    }
    
    try {
      const response = await fetch(
        `<?= site_url("api/schedule/related") ?>?machine=${machineId}&date=${workDate}&kategori=produksi`,
        { credentials: 'same-origin' }
      );
      
      if (!response.ok) throw new Error('Tidak ada jadwal produksi terkait');
      
      const related = await response.json();
      
      const processSelect = row.querySelector('.item-process');
      const designInput = row.querySelector('.item-design');
      const colorInput = row.querySelector('.item-color');
      const lotInput = row.querySelector('.item-lot');
      const mpInput = row.querySelector('.item-mp');
      
      processSelect.value = related.process_id || '';
      designInput.value = related.design_first || '';
      colorInput.value = related.color_first || '';
      lotInput.value = related.lot_first || '';
      mpInput.value = related.mp_first || '';
      
      if (jq) jq(processSelect).trigger('change');
      showToast('Data produksi terkait berhasil dimuat');
      
    } catch (e) {
      console.error('Load related error:', e);
      showToast('Gagal load jadwal produksi terkait: ' + e.message, 'danger');
      kategoriSelect.value = 'produksi';
    }
  }
  
  // ===== SHIFT AUTO-FILL JAM =====
  function onShiftChange(itemIndex) {
    const row = tbody.querySelector(`tr[data-item-index="${itemIndex}"]`);
    if (!row) return;
    
    const shiftSelect = row.querySelector('.item-shift');
    const jamStartInput = row.querySelector('.item-jam-start');
    const jamEndInput = row.querySelector('.item-jam-end');
    
    if (!shiftSelect.value) {
      jamStartInput.value = '';
      jamEndInput.value = '';
      return;
    }
    
    const shift = SHIFTS.find(s => s.shift_id == shiftSelect.value);
    if (shift) {
      jamStartInput.value = toLocal(shift.start_at);
      jamEndInput.value = toLocal(shift.end_at);
    }
  }
  
  // ===== ADD ROW - FIXED TO CREATE ALL PARAMETER INPUTS =====
  function addRow(itemData = {}) {
    const itemIndex = n++;
    const dyeLotNo = generateDyeLotNo(itemIndex);
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.dataset.itemIndex = itemIndex;
    tr.dataset.dyeLot = dyeLotNo;
    
    const kategori = itemData.kategori || 'produksi';
    const shift = itemData.shift_id || '';
    const jamStart = itemData.start_at || '';
    const jamEnd = itemData.end_at || '';
    const priority = itemData.priority || 'normal';
    const falseValues = [null, undefined, '', 0, '0', 'tidak', 'no', false];
    const cuchiMesinChecked = !falseValues.includes(itemData.params?.CUCI_MESIN);
    
    tr.innerHTML = `
      <td class="text-600 align-middle" style="font-weight:600">${tbody.querySelectorAll('tr').length + 1}
        <input type="hidden" name="items[${itemIndex}][id]" value="${itemData.id || 0}">
        <input type="hidden" name="items[${itemIndex}][dye_lot_no]" value="${esc(dyeLotNo)}">
      </td>
      <td class="align-middle text-primary fw-bold dye-lot-display">${esc(dyeLotNo)}</td>
      <td>
        <select class="form-select form-select-sm item-kategori" name="items[${itemIndex}][kategori]" data-item-index="${itemIndex}">
          <option value="produksi" ${kategori === 'produksi' ? 'selected' : ''}>Produksi</option>
          <option value="perbaikan" ${kategori === 'perbaikan' ? 'selected' : ''}>Perbaikan</option>
        </select>
      </td>
      <td>
        <select class="form-select form-select-sm item-process" name="items[${itemIndex}][process_id]" data-item-index="${itemIndex}">
          <option value="">Pilih proses</option>
          ${PROCESSES.map(p => `<option value="${p.id}" ${itemData.process_id == p.id ? 'selected' : ''}>${esc(p.name)}</option>`).join('')}
        </select>
      </td>
      <td>
        <select class="form-select form-select-sm item-shift" name="items[${itemIndex}][shift_id]" data-item-index="${itemIndex}">
          <option value="">Pilih shift</option>
          ${SHIFTS.map(s => `<option value="${s.shift_id}" ${shift == s.shift_id ? 'selected' : ''}>${esc(s.name)}</option>`).join('')}
        </select>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm item-design" name="items[${itemIndex}][design_name]" placeholder="Desain" value="${esc(itemData.design_name || '')}">
      </td>
      <td>
        <input type="text" class="form-control form-control-sm item-color" name="items[${itemIndex}][color_name]" placeholder="Warna" value="${esc(itemData.color_name || '')}">
      </td>
      <td>
        <input type="text" class="form-control form-control-sm item-lot" name="items[${itemIndex}][lot_no]" placeholder="Lot" value="${esc(itemData.lot_no || '')}">
      </td>
      <td>
        <input type="text" class="form-control form-control-sm item-mp" name="items[${itemIndex}][mp_no]" placeholder="MP" value="${esc(itemData.mp_no || '')}">
      </td>
      <td>
        <div style="display:flex; flex-direction:column; gap:2px;">
          <input type="datetime-local" class="form-control form-control-sm item-jam-start" name="items[${itemIndex}][start_at]" value="${esc(jamStart)}" style="font-size:0.7rem;">
          <input type="datetime-local" class="form-control form-control-sm item-jam-end" name="items[${itemIndex}][end_at]" value="${esc(jamEnd)}" style="font-size:0.7rem;">
        </div>
      </td>
      <td>
        <select class="form-select form-select-sm item-priority" name="items[${itemIndex}][priority]">
          <option value="normal" ${priority === 'normal' ? 'selected' : ''}>Normal</option>
          <option value="high" ${priority === 'high' ? 'selected' : ''}>High</option>
          <option value="urgent" ${priority === 'urgent' ? 'selected' : ''}>Urgent</option>
        </select>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-falcon-default btn-sm px-2 py-1 btn-edit-params" data-item-index="${itemIndex}" title="Edit Parameter">
          <span class="fas fa-sliders-h text-primary" style="font-size:0.85rem;"></span>
        </button>
      </td>
      <td class="text-center">
        <div class="form-check form-switch d-inline-block m-0">
          <input type="hidden" name="items[${itemIndex}][params][CUCI_MESIN]" value="0">
          <input class="form-check-input" type="checkbox" name="items[${itemIndex}][params][CUCI_MESIN]" value="1" ${cuchiMesinChecked ? 'checked' : ''} id="cuci_${itemIndex}">
        </div>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-link text-danger p-0 btn-del" style="font-size:0.9rem;" title="Hapus">
          <span class="fas fa-times"></span>
        </button>
      </td>
    `;
    
    tbody.appendChild(tr);
    
    // Create ALL parameter hidden inputs in the row
    DEFS.forEach(def => {
      if (def.code === 'CUCI_MESIN') return; // Skip, already created as checkbox
      const paramValue = itemData.params?.[def.code] || '';
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = `items[${itemIndex}][params][${def.code}]`;
      hiddenInput.value = paramValue;
      tr.appendChild(hiddenInput);
    });
    
    // Reinit select2
    if (jq) {
      jq(tr).find('select.form-select-sm').select2({ theme: 'bootstrap-5', width: '100%', minimumResultsForSearch: 5 });
    }
    
    // Attach event listeners
    tr.querySelector('.item-kategori').addEventListener('change', () => onKategoriChange(itemIndex));
    tr.querySelector('.item-process').addEventListener('change', () => onProcessChange(itemIndex));
    tr.querySelector('.item-shift').addEventListener('change', () => onShiftChange(itemIndex));
    tr.querySelector('.btn-edit-params').addEventListener('click', () => openParameterModal());
    tr.querySelector('.btn-del').addEventListener('click', () => deleteRow(itemIndex));
  }
  
  function renumberItems() {
    [...tbody.querySelectorAll('tr')].forEach((tr, k) => {
      const numCell = tr.querySelector('td:first-child');
      if (numCell) {
        const texts = [...numCell.childNodes].filter(n => n.nodeType === 3);
        if (texts.length > 0) {
          texts[0].nodeValue = String(k + 1);
        }
      }
    });
    
    // Also update dye lots because the counter part depends on row index
    updateAllDyeLots();
  }
  
  function deleteRow(itemIndex) {
    if (tbody.querySelectorAll('tr').length <= 1) {
      showToast('Minimal 1 item harus ada', 'warning');
      return;
    }
    showConfirm('Hapus item ini dari jadwal?', () => {
      const row = tbody.querySelector(`tr[data-item-index="${itemIndex}"]`);
      if (row) row.remove();
      renumberItems();
      showToast('Item berhasil dihapus');
    });
  }
  
  // ===== PARAMETER MODAL - FIXED TO READ EXISTING INPUTS =====
  function openParameterModal() {
    const body = qs('#parameterGridBody');
    body.innerHTML = '';
    
    [...tbody.querySelectorAll('tr')].forEach((row) => {
      const itemIndex = row.dataset.itemIndex;
      const dyeLot = row.dataset.dyeLot || '';
      
      const tr = document.createElement('tr');
      const params = DEFS.filter(d => d.code !== 'CUCI_MESIN');
      
      let html = `<td><small class="fw-bold text-nowrap">${esc(dyeLot)}</small></td>`;
      params.forEach(param => {
        // Read value from hidden input in the row
        const hiddenInput = row.querySelector(`input[name="items[${itemIndex}][params][${param.code}]"]`);
        const value = hiddenInput ? hiddenInput.value : '';
        
        html += `<td><input type="text" class="form-control form-control-sm param-input" data-item="${itemIndex}" data-param="${param.code}" value="${esc(value)}" ${param.type === 'number' ? 'inputmode="decimal"' : ''} placeholder="${esc(param.uom || '')}"></td>`;
      });
      
      tr.innerHTML = html;
      body.appendChild(tr);
    });
    
    new bootstrap.Modal(qs('#parameterModal')).show();
  }
  
  // Save parameter modal
  qs('#btnSaveParameters')?.addEventListener('click', () => {
    const paramInputs = qs('#parameterGridBody').querySelectorAll('.param-input');
    paramInputs.forEach(input => {
      const itemIndex = input.dataset.item;
      const paramCode = input.dataset.param;
      const value = input.value;
      
      // Update hidden input
      const hiddenInput = tbody.querySelector(`tr[data-item-index="${itemIndex}"] input[name="items[${itemIndex}][params][${paramCode}]"]`);
      if (hiddenInput) hiddenInput.value = value;
    });
    
    bootstrap.Modal.getInstance(qs('#parameterModal')).hide();
  });
  
  // ===== INITIALIZE =====
  // Load existing items or create 1 blank row
  if (ITEMS.length > 0) {
    ITEMS.forEach(addRow);
  } else {
    addRow(); // Create blank row for new form
  }
  
  qs('#btnAddItem').addEventListener('click', () => {
    addRow();
  });
  
  // ===== FORM VALIDATION =====
  qs('#schForm').addEventListener('submit', (e) => {
    const errors = [];
    const rows = tbody.querySelectorAll('tr');
    
    if (rows.length === 0) {
      errors.push('Minimal 1 item harus ditambahkan');
    }
    
    [...rows].forEach((row, idx) => {
      const itemIndex = row.dataset.itemIndex;
      const processSelect = row.querySelector('.item-process');
      const shiftSelect = row.querySelector('.item-shift');
      const jamStartInput = row.querySelector('.item-jam-start');
      const jamEndInput = row.querySelector('.item-jam-end');
      
      if (!processSelect.value) {
        errors.push(`Item #${idx + 1}: Proses harus dipilih`);
      }
      if (!shiftSelect.value) {
        errors.push(`Item #${idx + 1}: Shift harus dipilih`);
      }
      if (!jamStartInput.value || !jamEndInput.value) {
        errors.push(`Item #${idx + 1}: Jam rencana harus diisi`);
      } else if (jamEndInput.value <= jamStartInput.value) {
        errors.push(`Item #${idx + 1}: Jam selesai harus setelah jam mulai`);
      }
    });
    
    if (errors.length > 0) {
      e.preventDefault();
      const errorMsg = errors.map((err, i) => `${i + 1}. ${err}`).join('<br>');
      showToast(errorMsg, 'danger');
    }
  });
  
  // Trigger initial process/shift fill jika ada data existing
  if (ITEMS.length > 0) {
    [...tbody.querySelectorAll('tr')].forEach((row) => {
      const itemIndex = row.dataset.itemIndex;
      const processSelect = row.querySelector('.item-process');
      const shiftSelect = row.querySelector('.item-shift');
      if (processSelect.value) onProcessChange(itemIndex);
      if (shiftSelect.value) onShiftChange(itemIndex);
    });
  }
  
  // ===== MASTER MESIN MODAL =====
  
  // Get CSRF token
  const csrfHeader = 'X-CSRF-TOKEN';
  const csrfHash = '<?= csrf_hash() ?>';
  
  function getTypeBadgeColor(type) {
    const t = String(type || '').toLowerCase();
    if (t === 'dyeing' || t.includes('dye') || t.includes('jd')) return 'primary';
    if (t === 'finishing' || t.includes('finish') || t.includes('st')) return 'success';
    if (t.includes('print')) return 'warning';
    if (t.includes('lab') || t.includes('litbang')) return 'info';
    return 'secondary';
  }

  function loadMesinList() {
    const tbody = qs('#tableMesinBody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Loading...</td></tr>';
    
    fetch('<?= site_url("api/machines") ?>?type=<?= esc($moduleType) ?>', { 
      credentials: 'same-origin',
      headers: { 
        'X-Requested-With': 'XMLHttpRequest',
        [csrfHeader]: csrfHash
      }
    })
      .then(r => r.json())
      .then(data => {
        machinesData = data;
        if (data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Belum ada data mesin</td></tr>';
          return;
        }
        
        tbody.innerHTML = data.map(m => `
          <tr>
            <td class="fw-bold">${esc(m.code)}</td>
            <td>${esc(m.name)}</td>
            <td><span class="badge badge-subtle-${getTypeBadgeColor(m.type)}">${esc(m.type || '-')}</span></td>
            <td class="text-center">
              <button type="button" class="btn btn-outline-success btn-sm px-2 py-0 me-1 btn-pilih-mesin" data-id="${m.id}">Pilih</button>
              <button type="button" class="btn btn-link btn-sm text-primary p-0 me-2 btn-edit-mesin" data-id="${m.id}" title="Edit">
                <span class="fas fa-edit"></span>
              </button>
              <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-delete-mesin" data-id="${m.id}" title="Hapus">
                <span class="fas fa-trash"></span>
              </button>
            </td>
          </tr>
        `).join('');
        
        // Attach event listeners
        tbody.querySelectorAll('.btn-pilih-mesin').forEach(btn => {
          btn.addEventListener('click', () => pilihMesin(btn.dataset.id));
        });
        tbody.querySelectorAll('.btn-edit-mesin').forEach(btn => {
          btn.addEventListener('click', () => editMesin(btn.dataset.id));
        });
        tbody.querySelectorAll('.btn-delete-mesin').forEach(btn => {
          btn.addEventListener('click', () => deleteMesin(btn.dataset.id));
        });
        
        // Refresh dropdown header
        refreshMesinDropdown();
      })
      .catch(err => {
        console.error('Load mesin error:', err);
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Gagal load data</td></tr>';
      });
  }

  function refreshMesinDropdown() {
    const select = qs('#machine_id');
    const currentValue = select.value;
    
    // Rebuild options
    select.innerHTML = '<option value="">Pilih mesin</option>' + 
      machinesData.map(m => 
        `<option value="${m.id}" data-code="${esc(m.code)}">${esc(m.code)} – ${esc(m.name)}</option>`
      ).join('');
    
    // Restore selection if still exists
    if (currentValue && machinesData.find(m => m.id == currentValue)) {
      select.value = currentValue;
    }
    
    // Reinit Select2
    if (jq) jq(select).trigger('change');
  }
  
  function editMesin(id) {
    const mesin = machinesData.find(m => m.id == id);
    if (!mesin) return;
    
    qs('#mesin_id').value = mesin.id;
    
    // Split code into prefix and suffix
    const parts = mesin.code.split('-');
    if (parts.length > 1) {
      qs('#mesin_prefix').textContent = parts[0] + '-';
      qs('#mesin_code_suffix').value = parts[1];
    } else {
      qs('#mesin_code_suffix').value = mesin.code;
    }
    
    qs('#mesin_name').value = mesin.name;
    
    if (jq) {
      const typeSelect = jq('#mesin_type');
      if (typeSelect.find(`option[value="${mesin.type}"]`).length === 0) {
        const newOption = new Option(mesin.type, mesin.type, true, true);
        typeSelect.append(newOption).trigger('change');
      } else {
        typeSelect.val(mesin.type).trigger('change');
      }
    } else {
      qs('#mesin_type').value = mesin.type;
    }
    
    qs('#btnSaveMesin').innerHTML = '<span class="fas fa-save me-1"></span>Update';
  }
  
  function pilihMesin(id) {
    const select = qs('#machine_id');
    select.value = id;
    if (jq) jq(select).trigger('change');
    
    const modalEl = qs('#masterMesinModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    
    showToast('Mesin berhasil dipilih');
  }
  
  function getApiError(data) {
    if (data.messages && typeof data.messages === 'object') {
      return data.messages.error || Object.values(data.messages).join(', ');
    }
    return data.messages || data.error || 'Terjadi kesalahan';
  }

  function deleteMesin(id) {
    const mesin = machinesData.find(m => m.id == id);
    if (!mesin) return;
    
    showConfirm(`Hapus mesin ${mesin.code} - ${mesin.name}?`, () => {
      fetch(`<?= site_url("api/machines") ?>/${id}`, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          [csrfHeader]: csrfHash
        }
      })
      .then(async r => {
        const data = await r.json();
        if (!r.ok) throw new Error(getApiError(data));
        return data;
      })
      .then(data => {
        showToast('Mesin berhasil dihapus');
        loadMesinList();
      })
      .catch(err => {
        showToast('Gagal hapus mesin: ' + err.message, 'danger');
      });
    });
  }
  
  // Open Master Mesin Modal
  qs('#btnMasterMesin')?.addEventListener('click', () => {
    loadMesinList();
    new bootstrap.Modal(qs('#masterMesinModal')).show();
  });
  
  // Reset Form
  function resetMesinForm() {
    qs('#formMesin').reset();
    qs('#mesin_id').value = '';
    qs('#mesin_type').value = '<?= esc($moduleType) ?>';
    
    // Reset prefix
    qs('#mesin_prefix').textContent = '<?= esc($moduleType === 'dyeing' ? 'JD-' : 'ST-') ?>';
    qs('#mesin_code_suffix').value = '';
    
    qs('#btnSaveMesin').innerHTML = '<span class="fas fa-save me-1"></span>Simpan';
  }

  qs('#btnResetMesin')?.addEventListener('click', resetMesinForm);
  
  // Submit Form Mesin
  qs('#formMesin')?.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const id = qs('#mesin_id').value;
    const prefix = qs('#mesin_prefix').textContent.trim();
    const suffix = qs('#mesin_code_suffix').value.trim();
    
    const code = prefix + suffix;
    qs('#mesin_code').value = code;

    const data = {
      code: code.toUpperCase(),
      name: qs('#mesin_name').value.trim(),
      type: qs('#mesin_type').value
    };
    
    if (!suffix || !data.name || !data.type) {
      showToast('Semua field harus diisi', 'warning');
      return;
    }

    if (!/^[A-Z0-9]+-[0-9]{2,}$/.test(data.code)) {
      showToast('Format Nomor Mesin harus 2 digit angka (contoh: 01)', 'warning');
      return;
    }
    
    const url = id ? `<?= site_url("api/machines") ?>/${id}` : `<?= site_url("api/machines") ?>`;
    const method = 'POST';
    
    const reqHeaders = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      [csrfHeader]: csrfHash
    };
    
    fetch(url, {
      method: method,
      credentials: 'same-origin',
      headers: reqHeaders,
      body: JSON.stringify(data)
    })
    .then(async r => {
      const result = await r.json();
      if (!r.ok) throw new Error(getApiError(result));
      return result;
    })
    .then(result => {
      showToast(id ? 'Mesin berhasil diupdate' : 'Mesin berhasil ditambahkan');
      resetMesinForm();
      loadMesinList();
    })
    .catch(err => {
      showToast('Gagal simpan mesin: ' + err.message, 'danger');
    });
  });
})();
</script>
<?= $this->endSection() ?>

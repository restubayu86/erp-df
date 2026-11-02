# Schedule Form Redesign - Algorithm & GUI Specification

**Status:** IN DESIGN  
**Date:** 2026-11-02  
**Version:** 1.0

---

## 🎯 Requirements Analysis

### User Input:
1. **Dye Lot Format:** `JD{YYMMDD}{NOMC}{NOURUT}`
   - JD = prefix
   - YYMMDD = date
   - NOMC = machine code (2 digits)
   - NOURUT = sequence counter (2 digits)
   - Example: `JD2610260102` (2026-10-26, machine code 01, sequence 02)

2. **Auto-fill Capability:**
   - Kategori Perbaikan → load related data dari schedule (kategori Produksi)
   - Process → auto-fill Desain, Warna, Parameters default

3. **Jam Rencana Scope:**
   - Per-item level
   - Default dari header tanggal, but no duplicate in header

4. **Column Order:** Logical flow preferred

5. **Parameter Modal:** Shared modal untuk all items

6. **Shift:** Moved from header to item level
   - 1 machine = 1 day produksi
   - Multiple shifts/items possible per day

---

## 📊 New Form Structure

### HEADER (Simplified)
```
┌────────────────────────────────────────┐
│ JADWAL PRODUKSI                        │
├────────────────────────────────────────┤
│ Tanggal Produksi │ [date input]        │
│ Mesin            │ [machine select]    │
│ Kategori         │ [Produksi/Perbaikan]│
│ Catatan          │ [text input]        │
└────────────────────────────────────────┘
```

**REMOVED from Header:**
- Shift (moved to item level)
- Start/End Time (moved to item level)
- Priority (moved to item level)

### ITEM/ORDER TABLE (New Structure)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Item/Order | Status: Processing all items in shared modal                  │
├─────────────────────────────────────────────────────────────────────────────┤
│ # │ Dye Lot  │ Cat │ Process │ Shift │ Design │ Warna │ Lot │ MP │ Jam │Pri│Param│CM │
├─────────────────────────────────────────────────────────────────────────────┤
│1 │JD261026-1│Prod│ Dyeing │ Pagi  │Dye-A1 │Blue  │L001 │MP01│ .. │ ✓ │ 🎚│ ⚙️  │✓  │
│2 │JD261026-2│Prod│ Dyeing │ Siang│Dye-A2 │Red   │L002 │MP01│ .. │ ✓ │ 🎚│ ⚙️  │✗  │
└─────────────────────────────────────────────────────────────────────────────┘

Legend:
- Dye Lot: Auto-generate JD+date+machine+counter
- Cat: Kategori (Prod/Repair)
- Jam: Jam Rencana Start-End time
- Pri: Prioritas (Normal/High/Urgent)
- Param: Parameter button (opens shared modal)
- CM: Cuci Mesin (switch)
```

---

## 🔧 Algorithm Design

### A. Dye Lot Auto-Generation

```javascript
function generateDyeLotNo(date, machineCode, sequenceNum) {
  // Format: JD{YYMMDD}{NOMC}{NOURUT}
  // Example: JD2610260102
  
  const dateStr = formatDate(date, 'yymmdd');  // 261026
  const machineStr = String(machineCode).padStart(2, '0');  // 01
  const seqStr = String(sequenceNum).padStart(2, '0');  // 02
  
  return `JD${dateStr}${machineStr}${seqStr}`;
}

// Usage:
const dyeLotNo = generateDyeLotNo('2026-10-26', 1, 2);
// Result: JD2610260102
```

**Implementation Logic:**
```
1. Get current date from header (work_date)
2. Get machine ID from header (machine_id)
3. Extract machine code from machines master (2-digit code)
4. Count existing items for this date/machine
5. Increment counter by 1
6. Generate: JD + YYMMDD + machineCode + counter
7. Display in Dye Lot column (read-only)
```

---

### B. Process Auto-fill Logic

```javascript
function loadProcessDefaults(processId) {
  // Get process from PROCESSES master data
  // Return: { design_template, warna_template, parameters_defaults }
  
  const process = PROCESSES.find(p => p.id == processId);
  if (!process) return {};
  
  return {
    design: process.design_template || '',
    color: process.color_template || '',
    defaultParams: {
      BERAT: process.param_berat_default || '',
      PANJANG: process.param_panjang_default || '',
      JML_ROLL: process.param_jml_roll_default || '',
      LR: process.param_lr_default || '',
      SUHU: process.param_suhu_default || '',
      NO_PROGRAM: process.param_no_program_default || '',
      NO_NOZZLE: process.param_no_nozzle_default || ''
    }
  };
}

// Usage:
const dyeingDefaults = loadProcessDefaults(3);  // Process ID 3
// Result: {
//   design: 'Dye-Template-A',
//   color: 'Standard Color',
//   defaultParams: { BERAT: 50, PANJANG: 100, ... }
// }
```

**Implementation:**
```
1. On Process select change
2. Fetch process defaults from PROCESSES table
3. Auto-fill: Desain, Warna fields
4. Pre-populate parameter modal with defaults
5. User can override defaults per item
```

---

### C. Kategori Perbaikan - Load from Schedule

```javascript
async function loadRelatedProduction(kategori, machineId, date) {
  // If kategori = "Perbaikan", find related "Produksi" schedule
  
  if (kategori !== 'perbaikan') return null;
  
  try {
    const response = await fetch(
      `/api/schedule/related?` +
      `machine=${machineId}&` +
      `date=${date}&` +
      `kategori=produksi`,
      { credentials: 'same-origin' }
    );
    
    if (!response.ok) throw new Error('Load related production failed');
    
    const data = await response.json();
    
    return {
      relatedScheduleId: data.id,
      process: data.process,
      design: data.design_first,
      color: data.color_first,
      lot: data.lot_first,
      mp: data.mp_first,
      parameters: data.parameters
    };
  } catch (e) {
    console.error('Load related production:', e);
    return null;
  }
}

// Usage:
const related = await loadRelatedProduction('perbaikan', 1, '2026-10-26');
// Result: {
//   relatedScheduleId: 123,
//   process: 'Inspection',
//   design: 'Dye-A1',
//   ...
// }
```

**Implementation:**
```
1. On Kategori change to "Perbaikan"
2. Show loading indicator
3. Fetch related production schedule
4. Auto-fill: Process, Design, Warna, Lot, MP from related schedule
5. Pre-populate parameters from related production
6. Allow user to modify
```

---

### D. Jam Rencana Logic

```javascript
function initJamRencana(headerDate) {
  // Initialize start/end times for item
  // Default from header shift times
  
  const shiftSel = qs('#shift_id');
  const shiftOpt = shiftSel.selectedOptions[0];
  
  if (!shiftOpt || !shiftOpt.dataset.start) return {};
  
  return {
    start_at: shiftOpt.dataset.start,  // From shift master
    end_at: shiftOpt.dataset.end,      // From shift master (or calculated)
    duration: calculateDuration(shiftOpt.dataset.start, shiftOpt.dataset.end)
  };
}

function calculateDuration(startStr, endStr) {
  // Calculate duration in minutes
  const start = new Date(startStr);
  const end = new Date(endStr);
  return Math.round((end - start) / (1000 * 60));
}

// Usage:
const jamDefaults = initJamRencana('2026-10-26');
// Result: {
//   start_at: '2026-10-26 06:00:00',
//   end_at: '2026-10-26 14:00:00',
//   duration: 480  // 8 hours
// }
```

**Implementation:**
```
1. Per item, initialize from shift master data
2. User can edit per item (override shift default)
3. Calculate and display duration
4. Validate: end_at > start_at
5. Check overshift: end_at > shift.end_at
```

---

### E. Shift Handling

```javascript
function getShiftOptions(date) {
  // Get available shifts for date from kalender_shift
  
  // Current shift in header is template
  // Each item can have different shift
  // Shifts available from shift master + calendar
  
  const shifts = SHIFTS.map(s => ({
    id: s.id,
    name: s.name,  // Pagi, Siang, Malam
    start: s.start_at,
    end: s.end_at
  }));
  
  return shifts;
}

// Usage in addRow:
// Each item shows shift dropdown (all available shifts for date)
// Default: use header shift if selected, else empty
```

**Implementation:**
```
1. Build shift dropdown options from SHIFTS table
2. Show in each item row
3. Per item can select different shift
4. When shift changes:
   - Update jam_start/jam_end from shift master
   - Update parameter modal defaults if process-specific
5. Validate no conflicts (optional)
```

---

### F. Parameter Modal - Shared Logic

```javascript
function buildParameterModal() {
  // Build unified modal for all items' parameters
  
  const parameters = [
    { code: 'BERAT', label: 'Berat Kain (kg)', type: 'number', required: true },
    { code: 'PANJANG', label: 'Panjang Kain (m)', type: 'number', required: true },
    { code: 'JML_ROLL', label: 'Jumlah Roll', type: 'number', required: false },
    { code: 'LR', label: 'LR', type: 'text', required: false },
    { code: 'SUHU', label: 'Suhu (°C)', type: 'number', required: false },
    { code: 'NO_PROGRAM', label: 'No Program', type: 'text', required: false },
    { code: 'NO_NOZZLE', label: 'No Nozzle', type: 'text', required: false }
  ];
  
  const itemsCount = tbody.querySelectorAll('.item-main-row').length;
  
  const tabs = parameters.map((param, idx) => ({
    id: `param-tab-${param.code}`,
    label: param.label,
    code: param.code,
    icon: getParameterIcon(param.code)
  }));
  
  const rows = [];
  for (let i = 1; i <= itemsCount; i++) {
    rows.push({
      itemNo: i,
      dyeLotNo: getDyeLotNo(i),
      parameters: {}  // Will populate from form
    });
  }
  
  return {
    tabs: tabs,
    rows: rows,
    parameters: parameters
  };
}

// Usage:
const paramModal = buildParameterModal();
// Render tabs + grid with all items
```

**Implementation:**
```
1. Modal shows all items in rows
2. Each column = 1 parameter (Berat, Panjang, JML_ROLL, LR, Suhu, etc)
3. Tabs for quick navigation (optional)
4. Grid layout: Rows=Items, Columns=Parameters
5. Cells are input fields (editable)
6. Save button applies all changes to all items
7. Parameters with caption/unit display
```

---

### G. Item Row Creation Algorithm

```javascript
function addRow(itemData = {}) {
  const itemIndex = n++;
  const machineCode = getMachineCode(headerMachineId);
  const itemSequence = tbody.querySelectorAll('.item-main-row').length + 1;
  
  // Generate Dye Lot
  const dyeLotNo = generateDyeLotNo(
    qs('#work_date').value,
    machineCode,
    itemSequence
  );
  
  // Initialize from itemData or defaults
  const shift = itemData.shift || qs('#shift_id').value;
  const kategori = itemData.kategori || qs('#category').value;
  const jamDefaults = initJamRencana();
  
  // Create main row
  const tr = document.createElement('tr');
  tr.className = 'item-main-row';
  tr.dataset.itemIndex = itemIndex;
  tr.dataset.dyeLot = dyeLotNo;
  
  tr.innerHTML = `
    <!-- No -->
    <td class="text-600">${itemSequence}</td>
    
    <!-- Dye Lot No (read-only) -->
    <td class="text-primary fw-bold">${esc(dyeLotNo)}
      <input type="hidden" name="items[${itemIndex}][dye_lot_no]" value="${esc(dyeLotNo)}">
    </td>
    
    <!-- Kategori -->
    <td>
      <select class="form-select form-select-sm item-kategori" 
              name="items[${itemIndex}][kategori]" data-item-index="${itemIndex}">
        <option value="produksi" ${kategori === 'produksi' ? 'selected' : ''}>Produksi</option>
        <option value="perbaikan" ${kategori === 'perbaikan' ? 'selected' : ''}>Perbaikan</option>
      </select>
    </td>
    
    <!-- Process -->
    <td>
      <select class="form-select form-select-sm item-process" 
              name="items[${itemIndex}][process_id]" data-item-index="${itemIndex}">
        <option value="">Pilih Proses</option>
        ${PROCESSES.map(p => `
          <option value="${p.id}" 
                  data-design="${esc(p.design_template)}" 
                  data-warna="${esc(p.color_template)}"
                  ${itemData.process_id == p.id ? 'selected' : ''}>
            ${esc(p.name)}
          </option>
        `).join('')}
      </select>
    </td>
    
    <!-- Shift -->
    <td>
      <select class="form-select form-select-sm item-shift" 
              name="items[${itemIndex}][shift_id]" data-item-index="${itemIndex}">
        <option value="">Pilih Shift</option>
        ${SHIFTS.map(s => `
          <option value="${s.id}" 
                  data-start="${esc(s.start_at)}" 
                  data-end="${esc(s.end_at)}"
                  ${shift == s.id ? 'selected' : ''}>
            ${esc(s.name)}
          </option>
        `).join('')}
      </select>
    </td>
    
    <!-- Desain (auto-fill from process) -->
    <td>
      <input type="text" class="form-control form-control-sm item-design" 
             name="items[${itemIndex}][design_name]" 
             placeholder="Auto dari proses" 
             value="${esc(itemData.design_name || '')}">
    </td>
    
    <!-- Warna (auto-fill from process) -->
    <td>
      <input type="text" class="form-control form-control-sm item-color" 
             name="items[${itemIndex}][color_name]" 
             placeholder="Auto dari proses" 
             value="${esc(itemData.color_name || '')}">
    </td>
    
    <!-- Lot No -->
    <td>
      <input type="text" class="form-control form-control-sm" 
             name="items[${itemIndex}][lot_no]" 
             placeholder="Lot" 
             value="${esc(itemData.lot_no || '')}">
    </td>
    
    <!-- MP No -->
    <td>
      <input type="text" class="form-control form-control-sm" 
             name="items[${itemIndex}][mp_no]" 
             placeholder="MP" 
             value="${esc(itemData.mp_no || '')}">
    </td>
    
    <!-- Jam Rencana (Start - End) -->
    <td>
      <input type="datetime-local" class="form-control form-control-sm item-jam-start" 
             name="items[${itemIndex}][start_at]" 
             value="${esc(itemData.start_at || jamDefaults.start_at || '')}">
      <div style="font-size:0.75rem; color:#6c757d">
        <input type="datetime-local" class="form-control form-control-sm item-jam-end mt-1" 
               name="items[${itemIndex}][end_at]" 
               value="${esc(itemData.end_at || jamDefaults.end_at || '')}">
      </div>
    </td>
    
    <!-- Prioritas -->
    <td>
      <select class="form-select form-select-sm item-priority" 
              name="items[${itemIndex}][priority]">
        <option value="normal" ${itemData.priority === 'normal' ? 'selected' : ''}>Normal</option>
        <option value="high" ${itemData.priority === 'high' ? 'selected' : ''}>High</option>
        <option value="urgent" ${itemData.priority === 'urgent' ? 'selected' : ''}>Urgent</option>
      </select>
    </td>
    
    <!-- Parameter Button -->
    <td class="text-center">
      <button type="button" class="btn btn-falcon-default btn-sm px-2 py-1 btn-edit-params" 
              data-item-index="${itemIndex}" title="Edit Parameter">
        <span class="fas fa-sliders-h text-primary me-1"></span>
        <span class="badge badge-subtle-info">${PARAMETERS.length}</span>
      </button>
    </td>
    
    <!-- Cuci Mesin (Switch) -->
    <td class="text-center">
      <div class="form-check form-switch d-inline-block m-0">
        <input type="hidden" name="items[${itemIndex}][params][CUCI_MESIN]" value="0">
        <input class="form-check-input" type="checkbox" 
               name="items[${itemIndex}][params][CUCI_MESIN]" value="1"
               ${!falseValues.includes(itemData.params?.CUCI_MESIN) ? 'checked' : ''}>
      </div>
    </td>
    
    <!-- Delete Button -->
    <td class="text-center">
      <button type="button" class="btn btn-link text-danger p-0 btn-del" title="Hapus">
        <span class="fas fa-times"></span>
      </button>
    </td>
  `;
  
  tbody.appendChild(tr);
  initializeRowSelects(itemIndex);
  attachRowEventListeners(itemIndex);
}
```

---

### H. Event Listeners for Item Row

```javascript
function attachRowEventListeners(itemIndex) {
  const row = tbody.querySelector(`.item-main-row[data-item-index="${itemIndex}"]`);
  if (!row) return;
  
  // 1. Kategori change → load related production
  row.querySelector('.item-kategori').addEventListener('change', async (e) => {
    if (e.target.value === 'perbaikan') {
      const related = await loadRelatedProduction(
        'perbaikan',
        qs('#machine_id').value,
        qs('#work_date').value
      );
      
      if (related) {
        // Auto-fill from related production
        row.querySelector('.item-process').value = related.process;
        row.querySelector('.item-design').value = related.design;
        row.querySelector('.item-color').value = related.color;
        // ... fill other fields
      }
    }
  });
  
  // 2. Process change → auto-fill design/warna/parameters
  row.querySelector('.item-process').addEventListener('change', (e) => {
    const processOpt = e.target.selectedOptions[0];
    if (processOpt) {
      row.querySelector('.item-design').value = processOpt.dataset.design || '';
      row.querySelector('.item-color').value = processOpt.dataset.warna || '';
      // Mark parameter defaults in modal
    }
  });
  
  // 3. Shift change → update jam times
  row.querySelector('.item-shift').addEventListener('change', (e) => {
    const shiftOpt = e.target.selectedOptions[0];
    if (shiftOpt && shiftOpt.dataset.start) {
      row.querySelector('.item-jam-start').value = toLocal(shiftOpt.dataset.start);
      row.querySelector('.item-jam-end').value = toLocal(shiftOpt.dataset.end);
    }
  });
  
  // 4. Parameter button click → open shared modal
  row.querySelector('.btn-edit-params')?.addEventListener('click', () => {
    openParameterModal();  // Shows all items' parameters
  });
  
  // 5. Delete button
  row.querySelector('.btn-del').addEventListener('click', () => {
    if (tbody.querySelectorAll('.item-main-row').length > 1) {
      row.remove();
      renumberItems();
    } else {
      alert('Minimal 1 item');
    }
  });
}
```

---

## 🎨 GUI Layout - New Design

### Header Section (Simplified)
```html
<div class="card mb-3">
  <div class="card-header"><h6>Jadwal</h6></div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3">
        <label>Tanggal Produksi</label>
        <input type="date" class="form-control" id="work_date">
      </div>
      <div class="col-md-3">
        <label>Mesin</label>
        <select class="form-select" id="machine_id"></select>
      </div>
      <div class="col-md-3">
        <label>Kategori</label>
        <select class="form-select" id="category">
          <option value="produksi">Produksi</option>
          <option value="perbaikan">Perbaikan</option>
        </select>
      </div>
      <div class="col-md-3">
        <label>Catatan</label>
        <input type="text" class="form-control" id="notes">
      </div>
    </div>
  </div>
</div>
```

### Item/Order Table
```html
<div class="card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6>Item / Order</h6>
    <button type="button" class="btn btn-falcon-default btn-sm" id="btnAddItem">
      <span class="fas fa-plus me-1"></span>Tambah Item
    </button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive scrollbar">
      <table class="table table-sm mb-0" id="itemsTable">
        <thead class="bg-body-tertiary">
          <tr>
            <th style="width:2rem">#</th>
            <th style="min-width:130px">Dye Lot No</th>
            <th style="min-width:90px">Kategori</th>
            <th style="min-width:120px">Proses</th>
            <th style="min-width:90px">Shift</th>
            <th style="min-width:120px">Desain</th>
            <th style="min-width:100px">Warna</th>
            <th style="min-width:80px">Lot</th>
            <th style="min-width:80px">MP</th>
            <th style="min-width:140px">Jam Rencana</th>
            <th style="min-width:100px">Prioritas</th>
            <th style="width:80px">Parameter</th>
            <th style="width:80px">Cuci Mesin</th>
            <th style="width:3rem"></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
```

### Parameter Shared Modal
```html
<div class="modal fade" id="parameterModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5>Edit Parameter Semua Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Tabs for parameters -->
        <ul class="nav nav-tabs" role="tablist" id="parameterTabs">
          <!-- Generated dynamically -->
        </ul>
        
        <!-- Grid: Items x Parameters -->
        <div class="table-responsive scrollbar mt-3">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Dye Lot</th>
                <th id="param-header-BERAT">Berat Kain (kg)</th>
                <th id="param-header-PANJANG">Panjang Kain (m)</th>
                <th id="param-header-JML_ROLL">Jumlah Roll</th>
                <th id="param-header-LR">LR</th>
                <th id="param-header-SUHU">Suhu (°C)</th>
                <th id="param-header-NO_PROGRAM">No Program</th>
                <th id="param-header-NO_NOZZLE">No Nozzle</th>
              </tr>
            </thead>
            <tbody id="parameterGrid">
              <!-- Generated: Rows = Items, Cols = Parameters -->
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
```

---

## 🔄 Data Flow Diagram

```
User opens form
  ↓
Fills: Tanggal, Mesin, Kategori, Catatan
  ↓
Clicks "Tambah Item"
  ↓
addRow() function:
  1. Generate Dye Lot No (JD+date+machine+counter)
  2. Init from header or defaults
  3. Create row with dropdowns (Kategori, Process, Shift)
  4. Attach event listeners
  ↓
User selects Kategori = "Perbaikan"
  ↓
loadRelatedProduction() called
  ↓
Auto-fill: Process, Design, Color from related production
  ↓
User selects Process
  ↓
Auto-fill: Design, Color from process master
  ↓
User selects Shift
  ↓
Auto-fill: Jam Start/End from shift master
  ↓
User clicks Parameter button
  ↓
openParameterModal() shows all items + parameters grid
  ↓
User edits parameters in grid
  ↓
Clicks Save
  ↓
Parameters saved to form data
  ↓
User submits form
  ↓
All data (headers + items + parameters) sent to server
```

---

## ✅ Implementation Checklist

- [ ] Redesign header (remove Shift, Start/End, Priority)
- [ ] Build new item row with 13 columns
- [ ] Implement Dye Lot auto-generation
- [ ] Implement Process auto-fill logic
- [ ] Implement Kategori Perbaikan load logic
- [ ] Implement Shift selection per-item
- [ ] Implement Jam Rencana per-item
- [ ] Build shared parameter modal
- [ ] Attach all event listeners
- [ ] Test form submission
- [ ] Database verification

---

**Status:** 📋 **SPECIFICATION COMPLETE - READY FOR IMPLEMENTATION**


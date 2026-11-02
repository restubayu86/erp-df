# DataTable Tampilan & Styling Audit

**Date:** 2026-11-02  
**Component:** Schedule Index Page (index.php) - DataTable  
**Status:** Audit In Progress

---

## Current DataTable Implementation

### 1. CSS Styling ✅ GOOD

**Sticky Headers:**
```css
#tblSchedule thead th {
  position: sticky;
  top: 0;
  z-index: 10;
  background-color: #ffffff !important;
  box-shadow: inset 0 -1px 0 #dee2e6;
  font-weight: 600;
  font-size: 0.8rem;
  letter-spacing: 0.02em;
}
```
✅ **Status:** Proper - sticky headers fixed while scrolling

**Sticky Row Group Headers:**
```css
#tblSchedule tr.dtrg-group td {
  position: sticky;
  top: 33px;  /* Adjust based on thead height */
  z-index: 9;
  background-color: var(--bs-tertiary-bg) !important;
  font-weight: 600;
  cursor: pointer;
  user-select: none;
  font-size: 0.9rem;
}
```
✅ **Status:** Proper - row groups also sticky

**Table Cell Padding:**
```css
#tblSchedule th,
#tblSchedule td {
  padding: 0.6rem 0.75rem !important;
  white-space: nowrap;
}
```
✅ **Status:** Good - consistent spacing, no text wrapping

**Container Layout:**
```css
#scheduleCard {
  display: flex;
  flex-direction: column;
  height: 75vh;  /* 75% of viewport */
}

#scheduleCard .card-body {
  flex: 1;
  overflow: hidden !important;
  display: flex;
  flex-direction: column;
}

.table-responsive {
  overflow-x: auto !important;
  flex: 1;
  overflow-y: auto !important;
}
```
✅ **Status:** Good - proper flexbox layout with scroll containment

---

### 2. DataTable Columns & Rendering

#### Column 1: Aksi (Actions)
```javascript
{
  data: 'action',
  orderable: false,
  className: 'text-nowrap align-middle'
}
```

**Issue Found:** ⚠️ Column render tidak terlihat di code  
**Expected:** Should render Edit/Print/Delete buttons from server or JS

---

#### Column 2: Urutan (Sequence/Reorder)
```javascript
{
  data: 'sequence',
  className: 'reorder-handle align-middle',
  render: text
}
```

**Status:** ✅ Good - Shows drag handle (☰ icon dari CSS `::before`)

---

#### Column 3: Dye Lot No / Kategori
```javascript
render: (d, t, r) => {
  const cats = {
    'produksi': ['Produksi', 'primary', 'fa-cogs'],
    'perbaikan': ['Perbaikan', 'warning', 'fa-tools'],
    'litbang': ['Litbang', 'info', 'fa-flask'],
    'lain-lain': ['Lain-lain', 'secondary', 'fa-tag']
  };
  const c = cats[r.category] || [r.category, 'secondary', 'fa-tag'];
  const color = r.category === 'perbaikan' ? 'text-warning' : 'text-primary';
  
  return `<a href=".../${r.id}" class="fw-semi-bold ${color}">${esc(d)}</a>` +
    `<div class="mt-1"><span class="badge badge-subtle-${c[1]}">...${esc(c[0])}</span></div>`;
}
```

**Status:** ✅ Good - Multi-line cell with link + badge

---

#### Column 4: DOP No
```javascript
render: (d) => d ? `<a href="#!" class="btn-ifs-detail" data-dop="${esc(d)}">${esc(d)}</a>` : '–'
```

**Status:** ✅ Good - Clickable link with proper escaping

---

#### Column 5: Tanggal / Shift
```javascript
render: (d, t, r) => {
  const shiftColors = {
    'Pagi': 'primary',
    'Siang': 'warning',
    'Malam': 'dark'
  };
  const color = shiftColors[r.shift] || 'success';
  
  return `${esc(d)}<div class="mt-1"><span class="badge badge-subtle-${color}">
          <span class="fas fa-business-time me-1"></span>${esc(r.shift)}</span></div>`;
}
```

**Status:** ✅ Good - Multi-line with shift badge

---

#### Column 6: Desain / Segment + Parameter Button
```javascript
render: (d, t, r) => {
  const params = {
    'Berat Kain (kg)': r.param_BERAT,
    'Panjang Kain (m)': r.param_PANJANG,
    // ... more params
  };
  const pStr = btoa(JSON.stringify(params));
  const val = d || '–';
  const parts = val.split(' / ');
  const design = parts[0];
  const segment = parts.length > 1 ? ' / ' + parts.slice(1).join(' / ') : '';
  
  return `<span class="fs-10 text-emphasis">${esc(design)}</span>` +
    `<span class="text-600">${esc(segment)}</span>` +
    ` <button type="button" class="btn btn-falcon-default btn-sm ms-2 btn-param-detail px-2 py-1" ...>
      <span class="fas fa-sliders-h text-primary fs-10"></span></button>`;
}
```

**Status:** ✅ Good - Multi-line with inline button for parameters

---

#### Column 7: Warna (Color)
```javascript
render: (d) => esc(d || '–')
```

**Status:** ✅ Good - Simple text with null fallback

---

#### Column 8: No. MP
```javascript
render: (d) => esc(d || '–')
```

**Status:** ✅ Good

---

#### Column 9: Catatan (Notes)
```javascript
render: (d) => esc(d || '–')
```

**Status:** ✅ Good

---

#### Column 10: Proses (Process)
```javascript
render: (d) => esc(d || '–')
```

**Status:** ✅ Good

---

#### Column 11: Cuci Mesin (Machine Wash) ⚠️ ISSUE

```javascript
{
  data: 'param_CUCI_MESIN',
  orderable: false,
  className: 'align-middle text-center',
  render: (d, t, r) => {
    const checked = (d !== null && d !== undefined && d !== '' && d != 0 && d != '0' && d != 'tidak') ? 'checked' : '';
    return `<div class="form-check form-switch d-inline-block m-0">
              <input class="form-check-input" type="checkbox" role="switch" ${checked} disabled />
            </div>`;
  }
}
```

**Issues Found:**
1. ⚠️ **Loose Equality:** Uses `d != 0 && d != '0'` (should use strict `!==`)
2. ✅ Checkbox is disabled (read-only - correct)
3. ✅ Form-check-switch component correct
4. ✅ Multi-part condition matches form.php style (but should be fixed like form)

**Recommendation:** Apply same fix as form.php:
```javascript
const falseValues = [null, undefined, '', 0, '0', 'tidak', 'no', false];
const checked = !falseValues.includes(d) ? 'checked' : '';
```

---

#### Column 12: Jam Rencana (Scheduled Time)
```javascript
render: (d, t, r) => `<span class="text-primary fw-bold">${hm(d)}</span>–
                      <span class="text-danger fw-bold">${hm(r.end_at)}</span>
                      <div class="text-600">${dm(d)}
                      ${String(r.end_at).slice(0, 10) !== String(d).slice(0, 10) ? ' → ' + dm(r.end_at) : ''}</div>`
```

**Status:** ✅ Good
- Start time (primary color)
- End time (danger color) 
- Date below in gray
- Multi-line display
- Helper functions: `hm()` (hours:minutes), `dm()` (date month)

---

#### Column 13: Prioritas (Priority with Progress Gauge) ⚠️ ISSUE

```javascript
{
  data: 'priority',
  orderable: false,
  className: 'align-middle priority pe-4',
  render: (d, t, r) => {
    const prioColors = {
      'urgent': '#e63757',
      'high': '#f6ad55',
      'normal': '#2563eb'
    };
    const prioNames = {
      'urgent': 'Urgent',
      'high': 'High',
      'normal': 'Medium'
    };
    const color = prioColors[d] || '#2563eb';
    const name = prioNames[d] || 'Medium';
    
    // Calculate progress percentage
    let percent = 0;
    if (r.actual_start) {
      percent = parseInt(r.progress || 0, 10);
    }
    
    return `<div class="d-flex align-items-center gap-2">
              <div style="--falcon-circle-progress-bar:${percent}">
                <svg class="circle-progress-svg" width="26" height="26" viewBox="0 0 120 120">
                  <circle class="progress-bar-rail" cx="60" cy="60" r="54" fill="none" 
                          stroke-linecap="round" stroke-width="12"></circle>
                  <circle class="progress-bar-top" cx="60" cy="60" r="54" fill="none" 
                          stroke-linecap="round" stroke="${color}" stroke-width="12"></circle>
                </svg>
              </div>
              <h6 class="mb-0 text-700">${esc(name)}</h6>
            </div>`;
  }
}
```

**Issues Found:**
1. ⚠️ **SVG Progress Bar:** Has `stroke-dasharray` & `stroke-dashoffset` NOT visible in code
   - Only outer div with `style="--falcon-circle-progress-bar:${percent}"`
   - CSS should apply dasharray based on CSS variable
   - Need to verify CSS handles this

2. ✅ Color mapping correct (urgent=red, high=orange, normal=blue)
3. ✅ Percentage calculation from `progress` field

**Critical:** Check if CSS has:
```css
.circle-progress-svg .progress-bar-top {
  stroke-dasharray: 339.29;  /* 2*π*54 */
  stroke-dashoffset: calc(339.29 * (1 - var(--falcon-circle-progress-bar) / 100));
}
```

---

#### Column 14: Status
```javascript
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
  
  return `<span class="badge rounded-pill badge-subtle-${esc(l[1])}">
            <span class="fas ${icon} me-1"></span>${esc(l[0])}</span>` +
    (r.revision_no > 0 ? ` <span class="badge badge-subtle-info">Rev. ${r.revision_no}</span>` : '') +
    (r.overshift ? ' <span class="badge bg-danger"><span class="fas fa-hourglass-end me-1"></span>Overshift</span>' : '');
}
```

**Status:** ✅ Good
- Status badge with icon
- Revision number if applicable
- Overshift warning badge

---

#### Column 15: Operator
```javascript
render: (d) => esc(d || '–')
```

**Status:** ✅ Good

---

#### Hidden Columns
```javascript
{
  data: 'machine',
  visible: false,
  orderable: false
},
{
  data: 'code',
  visible: false,
  orderable: false
}
```

**Status:** ✅ Good - Used for row grouping (via rowGroup dataSrc)

---

## 3. Row Grouping (Group by Machine)

```javascript
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
    
    const statusIcons = {...};
    const chips = Object.entries(t.summary || {})
      .map(([st, n]) => {
        const l = LABELS[st] || [st, 'secondary'];
        const icon = statusIcons[st] || 'fa-circle';
        return `<span class="badge rounded-pill badge-subtle-${esc(l[1])} ms-1" ...>
                  <span class="fas ${icon} me-1"></span>${n}</span>`;
      }).join('');
    
    const over = t.overshift ? `<span class="badge rounded-pill bg-danger ms-1" ...>
                   <span class="fas fa-hourglass-end me-1"></span>${t.overshift}</span>` : '';
    
    return $('<tr/>')
      .attr('data-name', group)
      .toggleClass('is-collapsed', isC)
      .append($('<td/>')
        .attr('colspan', cols)
        .html(`<span class="fas fa-chevron-down group-icon text-600 me-1"></span>
               <span class="fas fa-industry text-primary me-2"></span>${esc(group)}
               <span class="badge rounded-pill badge-subtle-secondary ms-2" ...>
                 <span class="fas fa-list me-1"></span>${t.total}</span>${chips}${over}`));
  }
}
```

**Status:** ✅ Good
- Groups by machine name
- Shows total count
- Shows status breakdown badges
- Shows overshift warning
- Collapsible (click to expand/collapse)
- Persists state in localStorage

---

## 4. Reordering

```javascript
rowReorder: {
  dataSrc: 'sequence',
  selector: 'td.reorder-handle',
  update: false  // Read-only, no server update
}
```

**Status:** ✅ Good - Drag handle visible only when enabled

---

## 5. Pagination & Length Menu

```javascript
pageLength: 50,
lengthMenu: [25, 50, 100, 200],
language: {
  lengthMenu: 'Tampil _MENU_',
  info: '_START_–_END_ dari _TOTAL_',
  paginate: {
    previous: '‹',
    next: '›'
  }
}
```

**Status:** ✅ Good - Server-side pagination with options

---

## Summary of Issues Found

### ⚠️ ISSUES TO FIX

| # | Issue | Location | Severity | Fix |
|---|-------|----------|----------|-----|
| 1 | CUCI_MESIN loose equality | Column 11 render | 🟡 MEDIUM | Apply strict check like form.php |
| 2 | Progress gauge CSS | Need to verify | 🔴 CRITICAL | Check if CSS variables applied |
| 3 | Actions column render | Column 1 | 🟡 MEDIUM | Verify action buttons render |

---

### ✅ GOOD ELEMENTS

- Sticky headers (both table head and row groups)
- Proper cell padding and spacing
- Good use of badges, icons, colors
- Multi-line cell rendering
- Responsive text truncation (white-space: nowrap)
- Server-side pagination
- Row grouping with collapsible sections
- Proper escaping (esc function)
- Responsive container with flex layout

---

## Recommendations

### Priority 1: Verify Progress Gauge CSS
```css
/* Add to index.php styles if not present */
.circle-progress-svg .progress-bar-top {
  stroke-dasharray: 339.29;  /* circumference of circle: 2 * π * 54 */
  stroke-dashoffset: calc(339.29 * (1 - var(--falcon-circle-progress-bar, 0) / 100));
  transition: stroke-dashoffset 0.3s ease;
}
```

### Priority 2: Fix CUCI_MESIN Loose Equality
```javascript
// Before:
const checked = (d !== null && d !== undefined && d !== '' && d != 0 && d != '0' && d != 'tidak') ? 'checked' : '';

// After:
const falseValues = [null, undefined, '', 0, '0', 'tidak', 'no', false];
const checked = !falseValues.includes(d) ? 'checked' : '';
```

### Priority 3: Verify Actions Column
Need to see how action buttons (edit/print/delete) are rendered in the "Aksi" column

---

## Test Cases for DataTable Appearance

### Test 1: Sticky Headers
- [ ] Scroll table horizontally → headers stay visible
- [ ] Scroll table vertically → headers stick at top
- [ ] Row group headers also sticky (below table header)

### Test 2: Column Rendering
- [ ] CUCI_MESIN switches all display correctly (not broken HTML)
- [ ] Progress gauges show filled circle (not broken SVG)
- [ ] Status badges display with correct colors
- [ ] Action buttons clickable

### Test 3: Row Grouping
- [ ] Machines group properly
- [ ] Group headers collapse/expand on click
- [ ] Collapse state persists on refresh
- [ ] Totals and status badges show correct counts

### Test 4: Responsive
- [ ] On mobile: table scrolls horizontally
- [ ] Sticky headers don't break on narrow screens
- [ ] Collapse/expand buttons accessible
- [ ] Action buttons don't overflow

### Test 5: Pagination
- [ ] Each page shows correct rows (max 50/100/200)
- [ ] Pagination info shows "X-Y of Z"
- [ ] Can jump between pages
- [ ] Can change page size


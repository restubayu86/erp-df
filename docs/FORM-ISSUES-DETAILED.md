# Form.php Issues & Root Causes

**Document:** Detailed issue analysis with reproduction steps  
**Created:** 2026-11-02  
**Status:** Investigation Phase

---

## Critical Issues

### ISSUE #1: CUCI_MESIN Checkbox Boolean Serialization

**Severity:** 🔴 CRITICAL  
**File:** `app/Modules/Core/Views/schedule/form.php`, Lines 211-220  
**Symptom:** Unchecked CUCI_MESIN may serialize as missing value instead of `0`

#### Problem Analysis
```javascript
// Current code (lines 211-220)
const isChecked = (val !== null && val !== undefined && val !== '' && val != 0 && val != '0' && val != 'tidak');
paramsHtml += `<td class="text-center align-middle">
  <input type="hidden" name="items[${i}][params][${d.code}]" value="0">
  <div class="form-check form-switch d-inline-block m-0">
    <input class="form-check-input" type="checkbox" name="items[${i}][params][${d.code}]" value="1" ${isChecked ? 'checked' : ''}>
  </div>
</td>`;
```

**Issues:**
1. Hidden input `value="0"` placed BEFORE checkbox; browser form submit serializes in DOM order
2. If checkbox is BEFORE hidden input, checkbox value (1) overwrites hidden value (0)
3. CSS selector `.form-check-input` creates visual toggle, but HTML order wrong
4. Loose `!=` comparison fails when `val === '0'` (string zero)

#### Expected vs Actual
- **Expected:** Unchecked → `items[0][params][CUCI_MESIN]=0`
- **Actual:** Unchecked → `items[0][params][CUCI_MESIN]` (missing) OR `items[0][params][CUCI_MESIN]=1` if checkbox checked

#### Reproduction
1. Add item with CUCI_MESIN unchecked
2. Open DevTools Network tab
3. Submit form
4. Check form data in request payload
5. Verify: Should see `CUCI_MESIN=0`, but will likely see nothing

#### Fix Strategy
1. Move checkbox AFTER hidden input (DOM order matters)
2. Use strict equality (`!==` instead of `!=`)
3. Add console.log to verify values before submit

---

### ISSUE #2: Form Submission Without Items (No Validation)

**Severity:** 🔴 CRITICAL  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 151  
**Symptom:** Can submit empty item table; backend may fail silently or with vague error

#### Problem Analysis
```html
<!-- Line 151-156: No validation here -->
<div class="d-flex gap-2">
  <button class="btn btn-primary" type="submit"><?= $row ? 'Simpan perubahan' : 'Simpan sebagai draft' ?></button>
  <a class="btn btn-falcon-default" href="<?= site_url($moduleName . '/schedule') ?>">Batal</a>
</div>
```

**No client-side check for:**
- Minimum 1 item in table
- Required parameters filled
- Valid time range (start < end)

#### Expected vs Actual
- **Expected:** Form blocked with message "Minimal 1 item harus ditambahkan"
- **Actual:** Form submits to backend; backend may reject or process with 0 items

#### Reproduction
1. Fill schedule header (date, shift, machine, etc.)
2. DO NOT add any items
3. Click "Simpan sebagai draft"
4. Form should prevent submit but doesn't

#### Fix Strategy
1. Add `onsubmit` handler to form
2. Check `tbody.children.length > 0`
3. Check required parameters in each row
4. Show toast/alert if validation fails

---

### ISSUE #3: IFS Detail Fetch Error Not User-Visible

**Severity:** 🟠 HIGH  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 260-266  
**Symptom:** If IFS API fails, user sees generic "Gagal mengambil detail IFS" but doesn't know why

#### Problem Analysis
```javascript
// Lines 260-266
try {
  const d = await (await fetch(API_IFS + '/detail/' + dop, { credentials: 'same-origin' })).json();
  // ... add row ...
  ifsModal.hide();
} catch (e) { alert('Gagal mengambil detail IFS'); }
```

**Issues:**
1. Catches all errors (network, 404, 500, JSON parse) with same message
2. `alert()` is blocking; user can't see what went wrong
3. No retry mechanism
4. No logging for debugging

#### Reproduction
1. Open IFS modal
2. Disconnect network (DevTools → Offline mode)
3. Click "Pilih" button
4. Generic alert shows
5. No indication of network error vs API error

#### Fix Strategy
1. Parse `e.status` and show specific error message
2. Use toast notification instead of `alert()`
3. Provide "Retry" button
4. Log error to console for debugging

---

### ISSUE #4: Loose Equality in Boolean Checks

**Severity:** 🟡 MEDIUM  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 211  
**Symptom:** `val != 0 && val != '0'` fails to correctly identify false-like string values

#### Problem Analysis
```javascript
const isChecked = (val !== null && val !== undefined && val !== '' && val != 0 && val != '0' && val != 'tidak');
```

**Issue:** Uses `!=` (loose) instead of `!==` (strict); string `'0'` == number `0` in loose comparison

**Problematic cases:**
- `val = '0'` → `val != 0` is FALSE (unexpected!) → isChecked = FALSE ✓ (works by accident)
- `val = false` → `val != 0` is FALSE (unexpected!) → isChecked = FALSE ✓ (works)
- `val = 'no'` → `val != 0` is TRUE → isChecked = TRUE ✗ (wrong!)

#### Reproduction
1. Edit schedule with CUCI_MESIN already set to `'0'` (string)
2. Expected: Checkbox unchecked
3. May render as checked due to loose comparison

#### Fix Strategy
1. Use strict equality `!==`
2. Explicit list of false-like values: `['0', 'no', 'tidak', false, 0, null, undefined, '']`
3. Use Boolean() coercion if value is always 0/1

---

## High Priority Issues

### ISSUE #5: Parameter Autofill Hardcoded for IFS

**Severity:** 🟠 HIGH  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 267-268  
**Symptom:** IFS import always fills `BERAT` and `PANJANG`; fails if division doesn't use these parameters

#### Problem Analysis
```javascript
// Lines 267-268
params: { BERAT: d.qty, PANJANG: d.qty } // autofill qty ke parameter utama
```

**Issue:** Hardcoded assumption that primary parameter is always BERAT/PANJANG; fails for:
- Finishing division (may use different param names)
- Dyeing without PANJANG (only BERAT)
- Any division with custom parameter names

#### Expected vs Actual
- **Expected:** Auto-fill maps to actual primary parameter from DEFS
- **Actual:** Always fills BERAT/PANJANG regardless of division config

#### Reproduction
1. In Finishing division, try to import from IFS
2. Item row added with BERAT/PANJANG, but Finishing uses different params
3. Manual data entry required

#### Fix Strategy
1. Find first parameter with `is_primary = 1` from DEFS
2. Auto-fill only that parameter with `d.qty`
3. Pass `moduleName` to JavaScript to validate context

---

### ISSUE #6: Select2 Reinitialization May Leak Memory

**Severity:** 🟠 HIGH (potential)  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 242-248, 282-288  
**Symptom:** Calling `.select2({...})` on already-initialized element multiple times

#### Problem Analysis
```javascript
// Line 242-248: Initial setup
jq(function() {
  jq('select.form-select').select2({ ... });
});

// Line 282-288: Re-initialized on date change
if (jq) {
  jq(shiftSel).select2({ ... }).trigger('change');
}
```

**Issue:** Reinitializing Select2 without destroying previous instance may leak event listeners

#### Reproduction
1. Open form
2. Change date (triggers loadShifts)
3. Change date again
4. Open DevTools → Memory → Heap snapshot
5. Check for multiple Select2 event listeners

#### Fix Strategy
1. Check if Select2 already initialized before `.select2()`
2. If yes, call `.select2('destroy')` first
3. Or use `.off()` to detach old listeners

---

## Medium Priority Issues

### ISSUE #7: Missing Time Validation (start_at < end_at)

**Severity:** 🟡 MEDIUM  
**File:** `app/Modules/Core/Views/schedule/form.php` (missing implementation)  
**Symptom:** Can set start_at AFTER end_at; validation should prevent this

#### Expected Behavior
1. When end_at changes:
   - If end_at < start_at → Show warning: "Waktu selesai harus setelah waktu mulai"
   - If end_at > shift.end_at → Show warning: "Melampaui jam akhir shift"

#### Current Behavior
No validation shown; form accepts invalid times.

#### Fix Strategy
1. Add event listener to end_at input
2. Compare with start_at value
3. Show inline warning (not alert)

---

### ISSUE #8: No Duplicate Order_No Detection

**Severity:** 🟡 MEDIUM  
**File:** `app/Modules/Core/Views/schedule/form.php`, addRow() function  
**Symptom:** Can add same order_no multiple times in same schedule (data consistency issue)

#### Expected Behavior
1. When adding item with order_no already in table:
   - Show warning: "Order #{order_no} sudah ada di jadwal ini"
   - Allow continue or highlight existing row

#### Current Behavior
No check; duplicate order_no allowed.

#### Fix Strategy
1. Track order_no values in Set
2. Check new order_no against Set on input blur
3. Show Bootstrap alert if duplicate found

---

### ISSUE #9: Missing Minimum Item Validation

**Severity:** 🟡 MEDIUM  
**File:** `app/Modules/Core/Views/schedule/form.php`, form submit  
**Symptom:** Form submission accepted with 0 items (only header filled)

#### Fix Strategy
See ISSUE #2 above

---

## Low Priority Issues

### ISSUE #10: Unsafe DOM Traversal (No Null Checks)

**Severity:** ⚪ LOW  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 223  
**Symptom:** `e.target.closest('tr')` assumes button always nested in tr; could crash if not

#### Current Code
```javascript
if (!e.target.closest('.btn-del')) return;
if (tbody.children.length > 1) { e.target.closest('tr').remove(); renumber(); }
```

**Risk:** Very low since button is always in tr, but defensive programming preferred

#### Fix Strategy
1. Store tr reference: `const tr = e.target.closest('tr')`
2. Check: `if (!tr) return;` before remove

---

### ISSUE #11: Parameter Code Extraction Could Fail

**Severity:** ⚪ LOW  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 201  
**Symptom:** If DEFS[d.code] returns undefined, form still renders but with empty values

#### Current Code
```javascript
const val = (it.params || {})[d.code];
```

**Issue:** No error if `d.code` doesn't exist in it.params; renders empty input

#### Fix Strategy
1. Validate d.code exists in DEFS before accessing
2. Log warning if parameter missing

---

## Performance Issues

### ISSUE #12: DOM Creation Not Optimized (O(n) loop)

**Severity:** 🟡 MEDIUM (under heavy load)  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 219-234  
**Symptom:** Loading 100+ items causes 5-10 second lag

#### Current Code
```javascript
function addRow(it = {}) {
  const i = n++;
  // ... build HTML string ...
  const tr = document.createElement('tr');
  tr.innerHTML = `...`; // Entire row at once
  tbody.appendChild(tr); // DOM update after each row
}
ITEMS.forEach(addRow); // For each item: create → append
```

**Issue:** Each `appendChild()` triggers reflow/repaint; O(n) complexity

#### Reproduction
1. Create schedule with 100 items
2. Open form
3. Page takes 5+ seconds to render
4. Scroll is laggy while rendering

#### Fix Strategy
1. Use DocumentFragment for batch appends
2. Or build all HTML first, then single innerHTML update
3. Consider virtual scrolling if items > 50

---

### ISSUE #13: No Input Debounce on Date Change

**Severity:** ⚪ LOW  
**File:** `app/Modules/Core/Views/schedule/form.php`, Line 272  
**Symptom:** Rapid date changes trigger multiple loadShifts() API calls

#### Current Code
```javascript
qs('#work_date').addEventListener('change', () => { 
  shiftSel.dataset.selected = ''; 
  loadShifts(false); // No debounce
});
```

**Issue:** User typing date → multiple `change` events → multiple API calls

#### Fix Strategy
1. Add debounce wrapper:
```javascript
const debounce = (fn, ms) => { let tid; return (...a) => { clearTimeout(tid); tid = setTimeout(() => fn(...a), ms); }; };
qs('#work_date').addEventListener('change', debounce(() => loadShifts(false), 500));
```

---

## Summary by Priority

| Priority | Count | Issues |
|----------|-------|--------|
| 🔴 CRITICAL | 2 | #1 (CUCI_MESIN serialization), #2 (No min item validation) |
| 🟠 HIGH | 4 | #3 (IFS error handling), #4 (Loose equality), #5 (Hardcoded params), #6 (Select2 leak) |
| 🟡 MEDIUM | 5 | #7 (Time validation), #8 (Duplicate order_no), #9 (Min items), #12 (DOM performance), #13 (No debounce) |
| ⚪ LOW | 2 | #10 (DOM traversal), #11 (Param extraction) |

**Total Issues:** 13

---

## Fix Order Recommendation

1. **Phase 1 (Today):** Fix CRITICAL + HIGH issues
   - #1: CUCI_MESIN checkbox order fix
   - #2: Add form validation
   - #3: Better error handling for IFS
   - #4: Strict equality checks

2. **Phase 2 (Next session):** Optimize & refactor
   - #5: Flexible parameter autofill
   - #6: Select2 lifecycle management
   - #12: DOM optimization

3. **Phase 3 (Polish):** Defensive improvements
   - #7, #8, #9: Additional validations
   - #10, #11: Code hardening

---


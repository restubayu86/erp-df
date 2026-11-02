# Debug & Performance Testing Plan - Schedule Create Form

**Last Updated:** 2026-11-02  
**Status:** In Progress  
**Tester:** Copilot

---

## 1. Code Quality Issues Identified

### 1.1 JavaScript Logic Flaws

| Issue | Location | Severity | Description |
|-------|----------|----------|-------------|
| Loose Equality in Boolean Check | form.php:211 | MEDIUM | Uses loose `!=` instead of strict `!==` for value comparison. Fails for values like `'0'` (string) vs `0` (number) |
| Hidden Input Redundancy | form.php:213-215 | LOW | Hidden input value="0" may conflict if unchecked checkbox doesn't submit name attr |
| Missing Error Handling | form.php:254 | MEDIUM | IFS datatable render assumes `r.uom` exists; could throw if undefined |
| Parameter Autofill Assumption | form.php:267-268 | MEDIUM | Hardcoded BERAT/PANJANG params in IFS row; fails if params don't match division config |
| Unsafe DOM Traversal | form.php:247 | LOW | `e.target.closest('tr')` assumes button always has parent tr; no null check |

### 1.2 Performance Bottlenecks

| Issue | Impact | Severity | Notes |
|-------|--------|----------|-------|
| DOM Creation Loop (addRow) | O(n) for n items on page load | MEDIUM | `ITEMS.forEach(addRow)` creates all DOM at once; no virtual scrolling |
| No Input Debouncing | Multiple network calls on date change | LOW | Each date change calls `loadShifts()` immediately; no debounce |
| IFS DataTable Lazy Load | Good optimization | NONE | Only initializes when modal opens |
| Select2 Reinitialization | Potential memory leak | LOW | Calling `.select2({...})` on already-initialized element may leak event listeners |
| Multiple fetch() calls in parallel | Network congestion | LOW | IFS detail fetch doesn't batch requests |

### 1.3 Missing Validations

- No client-side validation for:
  - Minimum 1 item required before submit
  - Duplicate order_no in same schedule
  - Parameter constraints (min/max values)
  - Time range validation (start_at < end_at)
  - Shift boundary validation (times within shift hours)

---

## 2. Testing Scenarios

### 2.1 Form Submission & Data Serialization

**Test 2.1.1: Add Item with Boolean Parameters**
- [ ] Add 1 item with CUCI_MESIN checkbox CHECKED
  - Expected: `items[0][params][CUCI_MESIN]=1`
  - Verify in Network tab (form submission)
- [ ] Add 1 item with CUCI_MESIN checkbox UNCHECKED
  - Expected: `items[0][params][CUCI_MESIN]=0` (hidden input fallback)
  - Verify in Network tab

**Test 2.1.2: Multiple Items with Mixed Parameters**
- [ ] Add 3 items with:
  - Item 1: CUCI_MESIN=checked, BERAT=100, PANJANG=500
  - Item 2: CUCI_MESIN=unchecked, BERAT=200, PANJANG=600
  - Item 3: CUCI_MESIN=checked, BERAT=150, PANJANG=700
- [ ] Submit form and verify:
  - All items saved correctly
  - Parameter values preserved
  - Checkbox states correctly saved as 0/1

**Test 2.1.3: Required Parameter Validation**
- [ ] Try submit with missing required parameter (e.g., BERAT)
- [ ] Expected: Client-side validation prevents submit OR server returns error
- [ ] Verify error message displayed

**Test 2.1.4: Form State After Edit**
- [ ] Edit existing schedule with items
- [ ] Verify:
  - All items loaded correctly
  - Parameter values populated (including checkbox states)
  - Checkbox reflects database value (0=unchecked, 1=checked)

---

### 2.2 Select2 & Dynamic Field Updates

**Test 2.2.1: Shift Selection Updates Times**
- [ ] Select work_date
- [ ] Select shift → should auto-populate start_at with shift.start_at
- [ ] Verify: start_at populated; Select2 reinitialized; no console errors
- [ ] Change to different shift → start_at should NOT change (user edited)

**Test 2.2.2: Process Selection Auto-fill End Time**
- [ ] Select process with std_duration_min = 120
- [ ] Select start_at = 08:00
- [ ] Change end_at back to empty
- [ ] Verify: end_at auto-filled to 10:00 (start + 120min)
- [ ] Check for overshift warning if end_at > shift.end_at

**Test 2.2.3: Select2 Option Filtering**
- [ ] Machine list should filter by division (only Dyeing machines for Dyeing schedule)
- [ ] Shift list should filter by work_date (via API)
- [ ] Verify API returns correct data via Network tab

---

### 2.3 IFS Modal Integration

**Test 2.3.1: IFS DataTable Load & Search**
- [ ] Click "Ambil dari IFS" button
- [ ] Verify:
  - Modal opens
  - DataTable loads with data (server-side)
  - Search field functional (try "SO-" prefix filter)
  - Pagination works (load page 2+)

**Test 2.3.2: IFS Detail Fetch**
- [ ] Click any row's "Pilih" button in IFS table
- [ ] Verify:
  - Fetch to `/api/ifs/detail/{dop_no}` succeeds
  - Modal closes
  - New item row added with correct values:
    - order_no, customer_name, article, color_name, qty→BERAT/PANJANG
  - No console errors or duplicate rows

**Test 2.3.3: IFS Error Handling**
- [ ] Trigger API error (disconnect network, modify API URL in DevTools)
- [ ] Verify:
  - Error message displayed ("Gagal mengambil detail IFS")
  - Modal doesn't close on error
  - User can retry or cancel

---

### 2.4 Performance Testing

**Test 2.4.1: Form Render Time (Light Load)**
- [ ] Create schedule with 5 items
- [ ] Measure:
  - Form load time (Network tab - main document)
  - DOMContentLoaded event (Performance tab)
  - Time to interactive (until Select2 ready)
- [ ] Expected: <1 second total

**Test 2.4.2: Form Render Time (Heavy Load)**
- [ ] Create schedule with 100 items (via database seeder or direct DB edit for testing)
- [ ] Measure:
  - Form load time
  - Time to addRow completion (log in console)
  - Browser responsiveness (scroll, click items)
- [ ] Issue: May take 5-10 seconds if addRow is slow
- [ ] Check for DOM thrashing in DevTools

**Test 2.4.3: Add Item Performance**
- [ ] Click "Tambah item" button 20 times in rapid succession
- [ ] Measure:
  - Time per button click
  - Total time for 20 rows
  - Memory usage (DevTools Heap snapshot before/after)
- [ ] Expected: <100ms per click

**Test 2.4.4: IFS DataTable Search Performance**
- [ ] Open IFS modal with 1000+ rows available
- [ ] Type search query "SO-"
- [ ] Measure:
  - API response time (Network tab)
  - Table rerender time
  - No UI freezing

**Test 2.4.5: Form Submission Performance**
- [ ] Submit form with 50 items
- [ ] Measure:
  - Network request size (should be <500KB for form data)
  - Server processing time (check logs)
  - Response time (should be <3 seconds)

---

### 2.5 Edge Cases & Error Scenarios

**Test 2.5.1: Boundary Conditions**
- [ ] Empty item table → try submit (expected: validation error - need min 1 item)
- [ ] Very long order_no (>50 chars) → should truncate or error
- [ ] Special characters in fields: `<`, `>`, `"`, `'`, `&` → should be escaped/stored safely
- [ ] Timezone mismatch in datetime-local fields → verify correct interpretation

**Test 2.5.2: Concurrent Changes**
- [ ] Open same schedule in 2 tabs
- [ ] Edit in tab 1, submit
- [ ] Try edit in tab 2 (may show stale data or version conflict)
- [ ] Expected: Either merge gracefully or show "Schedule modified" warning

**Test 2.5.3: Network Failures**
- [ ] Disable network during:
  - Shift selection (loadShifts API fails)
  - IFS detail fetch (detail API fails)
  - Form submit (backend unavailable)
- [ ] Expected: User sees error, can retry, no silent failures

---

## 3. Browser DevTools Checklist

### Console Tab
- [ ] No JavaScript errors on page load
- [ ] No warnings about undefined variables
- [ ] All fetch() calls complete successfully
- [ ] No console logging in production code (only debug logs)

### Network Tab
- [ ] Main form page: <200KB
- [ ] Vendor JS (jQuery, DataTables, Select2): should be cached
- [ ] API calls (shift/effective, ifs/datatable, ifs/detail): <100ms each
- [ ] No redirect loops or failed requests (4xx/5xx)

### Performance Tab
- [ ] Form load FCP (First Contentful Paint): <2 seconds
- [ ] Form load LCP (Largest Contentful Paint): <3 seconds
- [ ] No long tasks (>50ms blocking the main thread)
- [ ] Memory: monitor heap growth (should stabilize)

### Storage Tab
- [ ] No localStorage pollution
- [ ] Session storage used only for temporary state (if any)
- [ ] No sensitive data in browser storage

---

## 4. Manual Testing Scenarios

### Scenario A: Create New Schedule (Happy Path)
1. [ ] Go to Create Schedule page
2. [ ] Fill basic info: date, shift, machine, process, times, priority
3. [ ] Click "Tambah item" → Add 3 items manually
4. [ ] For item 2: Check CUCI_MESIN checkbox
5. [ ] For item 3: Leave CUCI_MESIN unchecked
6. [ ] Submit form
7. [ ] Verify:
   - Schedule created with ID
   - All items saved
   - Parameters (including CUCI_MESIN) saved correctly
   - Redirect to index page shows new schedule

### Scenario B: Copy & Edit Schedule
1. [ ] Go to index page, find a schedule
2. [ ] Click "Salin" (copy) button
3. [ ] Form opens with pre-filled data + copyFrom hidden input
4. [ ] Edit 1 item's parameter
5. [ ] Change schedule date to tomorrow
6. [ ] Submit
7. [ ] Verify:
   - New schedule created (not updated old one)
   - Old schedule unchanged
   - new schedule in index page

### Scenario C: Import from IFS
1. [ ] Create new schedule
2. [ ] Click "Ambil dari IFS"
3. [ ] Wait for table to load
4. [ ] Search for order (e.g., "SO-2401")
5. [ ] Click row's "Pilih" button
6. [ ] Verify:
   - Item row added with IFS data
   - Modal closed
   - Item appears in table
7. [ ] Add 1 more IFS item
8. [ ] Add 1 manual item
9. [ ] Submit schedule
10. [ ] Verify: Mix of IFS and manual items saved

### Scenario D: Print Schedule
1. [ ] Create/edit schedule with 5 items
2. [ ] Save successfully
3. [ ] Go back to index page
4. [ ] Click "Print" button for that schedule
5. [ ] Print preview window opens
6. [ ] Verify:
   - All items display correctly
   - Parameters visible (if should be)
   - Layout fits F4 landscape
   - No "undefined" or blank values
7. [ ] Close print preview

---

## 5. Database Verification

**Pre-Testing Setup:**
```sql
-- Check if test schedule exists
SELECT COUNT(*) FROM schedules WHERE work_date >= CURDATE();

-- Check parameter definitions for Dyeing
SELECT code, name, data_type, is_required FROM parameter_divisions 
WHERE division_id = (SELECT id FROM divisions WHERE code='DYEING');

-- Verify CUCI_MESIN exists
SELECT * FROM parameter_divisions WHERE code='CUCI_MESIN';
```

**Post-Testing Verification:**
- [ ] New schedule saved with correct `work_date`, `shift_id`, `machine_id`
- [ ] Schedule items saved with correct `order_no`, `customer_name`, `params`
- [ ] Parameter values stored correctly (CUCI_MESIN should be 0 or 1, not NULL or '0'/'1')
- [ ] Foreign keys valid (shift_id exists in shifts table, etc.)

---

## 6. Issues to Document & Fix

### Priority 1 (Critical)
- [ ] CUCI_MESIN checkbox serialization (0/1 values may not submit correctly)
- [ ] Form validation (prevent empty item table submission)
- [ ] IFS API error handling (user not informed on failure)

### Priority 2 (High)
- [ ] Strict equality in boolean checks (change `!=` to `!==`)
- [ ] Large item list performance (100+ items lag)
- [ ] Select2 reinitialization memory leaks

### Priority 3 (Medium)
- [ ] Parameter autofill hardcoding (BERAT/PANJANG not flexible)
- [ ] Time validation (start_at < end_at)
- [ ] Duplicate order_no detection

### Priority 4 (Low)
- [ ] Shift pattern display (currently just text)
- [ ] Process std_duration conditional autofill logic clarification
- [ ] Console noise cleanup

---

## 7. Test Results Log

| Test ID | Scenario | Status | Duration | Notes |
|---------|----------|--------|----------|-------|
| 2.1.1 | Add Item Boolean Checked | [ ] | - | - |
| 2.1.2 | Add Item Boolean Unchecked | [ ] | - | - |
| 2.1.3 | Multiple Items Serialization | [ ] | - | - |
| 2.1.4 | Required Param Validation | [ ] | - | - |
| 2.2.1 | Shift Selection Auto-fill | [ ] | - | - |
| 2.2.2 | Process Duration Auto-fill | [ ] | - | - |
| 2.3.1 | IFS DataTable Load | [ ] | - | - |
| 2.3.2 | IFS Detail Fetch | [ ] | - | - |
| 2.4.1 | Form Render 5 Items | [ ] | <1s | - |
| 2.4.2 | Form Render 100 Items | [ ] | <10s | - |
| 2.4.3 | Add Item Rapid Click | [ ] | - | - |
| 2.4.4 | IFS Search Performance | [ ] | - | - |
| 2.4.5 | Form Submit 50 Items | [ ] | - | - |
| ScenarioA | Create New (Happy Path) | [ ] | - | - |
| ScenarioB | Copy & Edit | [ ] | - | - |
| ScenarioC | Import from IFS | [ ] | - | - |
| ScenarioD | Print Schedule | [ ] | - | - |

---

## 8. Cleanup Checklist

- [ ] Remove all debug `console.log()` statements
- [ ] Remove test data from database (if any)
- [ ] Verify no broken links in form
- [ ] Test in multiple browsers (Chrome, Firefox, Safari, Edge)
- [ ] Test on mobile responsive view (Form in mobile not fully tested yet)
- [ ] Document any remaining known issues in separate file

---

**Next Steps:**
1. Execute Test 2.1.1 - 2.1.4 (Form Serialization)
2. Execute Test 2.2.1 - 2.2.3 (Select2 & Dynamic Fields)
3. Execute Performance Tests (2.4.1 - 2.4.5)
4. Document findings in ISSUES.md
5. Fix Priority 1 issues before moving to edit form


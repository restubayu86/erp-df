# Schedule Form Testing Checklist

**Last Updated:** 2026-11-02  
**Tester:** [Your Name]  
**Status:** Ready for Testing

---

## Pre-Testing Setup

- [ ] Open application in browser
- [ ] Navigate to Schedule Create page (`/dyeing/schedule/create`)
- [ ] Open DevTools (F12)
- [ ] Open Console tab
- [ ] Close any browser extensions that might interfere

---

## Test Session 1: Form Submission & Serialization (20 minutes)

### Test 1.1: Add Item with CUCI_MESIN Checkbox Checked
**Steps:**
1. Click "Tambah item" button
2. Fill order_no: `SO-2401-001`
3. Fill customer: `PT. ABC Tekstil`
4. Fill design: `Motif A`
5. Fill color: `Red`
6. Fill lot: `LOT-001`
7. Find CUCI_MESIN column → **CHECK** the checkbox
8. Click Network tab in DevTools
9. Click "Simpan sebagai draft" button
10. In Network tab, find `POST` request to `/dyeing/schedule`
11. Click → Payload tab → Look for `items[0][params][CUCI_MESIN]=1`

**Expected Result:**
```
items[0][params][CUCI_MESIN]=1 ✓
```

**Actual Result:**
```
[ ] Correct (value = 1)
[ ] Wrong (value = 0)
[ ] Missing (parameter not submitted)
```

**Notes:** _______________________________________________

---

### Test 1.2: Add Item with CUCI_MESIN Checkbox Unchecked
**Steps:**
1. Cancel previous submission (or go to new form)
2. Fill same fields as 1.1
3. Find CUCI_MESIN column → **LEAVE UNCHECKED** (don't check)
4. Open Network tab
5. Submit form
6. Verify request payload has `items[0][params][CUCI_MESIN]=0`

**Expected Result:**
```
items[0][params][CUCI_MESIN]=0 ✓
```

**Actual Result:**
```
[ ] Correct (value = 0)
[ ] Wrong (value = 1)
[ ] Missing (parameter not submitted - BUG!)
```

**Notes:** _______________________________________________

---

### Test 1.3: Multiple Items with Mixed Parameters
**Steps:**
1. Fill form header (date, shift, machine, etc.)
2. Click "Tambah item" → Add Item 1:
   - order_no: `SO-2401-001`
   - CUCI_MESIN: **CHECKED**
   - BERAT: `100`
3. Click "Tambah item" → Add Item 2:
   - order_no: `SO-2401-002`
   - CUCI_MESIN: **UNCHECKED**
   - BERAT: `200`
4. Click "Tambah item" → Add Item 3:
   - order_no: `SO-2401-003`
   - CUCI_MESIN: **CHECKED**
   - BERAT: `150`
5. Submit form
6. Check Network payload for correct serialization

**Expected Serialization:**
```
items[0][params][CUCI_MESIN]=1
items[0][params][BERAT]=100
items[1][params][CUCI_MESIN]=0
items[1][params][BERAT]=200
items[2][params][CUCI_MESIN]=1
items[2][params][BERAT]=150
```

**Actual Result:**
```
[ ] All correct
[ ] Partially correct (specify): _______
[ ] Incorrect (specify): _______
```

**Notes:** _______________________________________________

---

### Test 1.4: Edit Existing Schedule (State Persistence)
**Steps:**
1. Find an existing schedule with CUCI_MESIN parameter
2. Click Edit button
3. Form loads with pre-filled data
4. Find CUCI_MESIN column for each item
5. Verify checkbox state matches saved value:
   - If saved value is `1` → checkbox should be **CHECKED**
   - If saved value is `0` → checkbox should be **UNCHECKED**

**Expected Result:**
```
All CUCI_MESIN checkboxes reflect saved state ✓
```

**Actual Result:**
```
[ ] All correct
[ ] Some wrong (specify which items): _______
```

**Database Verification:**
- Go to database, run query:
```sql
SELECT id, items, params FROM schedule_items WHERE schedule_id = [YOUR_SCHEDULE_ID];
```
- Verify `params` JSON contains `"CUCI_MESIN": 0` or `"CUCI_MESIN": 1`

**Result:** _______________________________________________

---

## Test Session 2: Form Validation (15 minutes)

### Test 2.1: Submit Empty Item Table
**Steps:**
1. Fill all header fields (date, shift, machine, start_at, end_at)
2. **DO NOT** add any items
3. Click "Simpan sebagai draft" button

**Expected Result:**
```
Alert dialog shows: "Minimal 1 item harus ditambahkan ke jadwal"
Form submission blocked ✓
```

**Actual Result:**
```
[ ] Correct (alert shown, submit blocked)
[ ] Wrong (form submitted even though empty)
[ ] Wrong (different error message): _______
```

**Notes:** _______________________________________________

---

### Test 2.2: Invalid Time Range (end_at < start_at)
**Steps:**
1. Fill header fields
2. Add 1 item
3. Set `start_at`: `2026-11-02 10:00`
4. Set `end_at`: `2026-11-02 08:00` (BEFORE start time)
5. Click "Simpan sebagai draft"

**Expected Result:**
```
Alert shows: "Waktu selesai harus setelah waktu mulai"
Form submission blocked ✓
```

**Actual Result:**
```
[ ] Correct (alert shown, submit blocked)
[ ] Wrong (form submitted despite invalid time)
[ ] Wrong (different error message): _______
```

**Notes:** _______________________________________________

---

### Test 2.3: Overshift Warning (end_at > shift.end_at)
**Steps:**
1. Select shift that ends at 14:00
2. Set `start_at`: `2026-11-02 12:00`
3. Set `end_at`: `2026-11-02 16:00` (AFTER shift end)
4. Click "Simpan sebagai draft"

**Expected Result:**
```
Confirmation dialog shows: "Jadwal melewati akhir shift. Apakah Anda yakin?"
User can choose "OK" (proceed) or "Cancel" (abort) ✓
```

**Actual Result:**
```
[ ] Correct (confirmation shown)
[ ] Wrong (no confirmation, submitted anyway)
[ ] Wrong (form blocked instead of asking)
```

**Notes:** _______________________________________________

---

## Test Session 3: IFS Modal & Integration (20 minutes)

### Test 3.1: IFS Modal Load & DataTable
**Steps:**
1. Open Create Schedule form
2. Click "Ambil dari IFS" button
3. Modal opens with DataTable
4. Wait 2-3 seconds for data to load

**Expected Result:**
```
Modal displays with data rows (DOP No, Order No, Customer, etc.)
Pagination shows (if more than 10 rows)
Search field functional ✓
```

**Actual Result:**
```
[ ] All working correctly
[ ] Modal opened but no data
[ ] Modal didn't open
[ ] Error in console: _______
```

**Console Check:**
- Any errors? ✓ Yes / ✗ No
- Errors: _______________________________________________

---

### Test 3.2: Search IFS Orders
**Steps:**
1. In IFS modal search field, type: `SO-`
2. DataTable filters to show only orders starting with "SO-"
3. Try different searches: `2401`, `ABC Tekstil`

**Expected Result:**
```
Search works in real-time (server-side filtering)
Results update on each keystroke ✓
```

**Actual Result:**
```
[ ] Search working correctly
[ ] Search not working
[ ] Slow response (>2 seconds)
```

**Notes:** _______________________________________________

---

### Test 3.3: Import from IFS (Success Case)
**Steps:**
1. In IFS modal, find any row
2. Click "Pilih" button in that row
3. Observe loading spinner on button
4. Wait for action to complete

**Expected Result:**
```
Loading spinner shows on button
Modal closes after 1-2 seconds
New item row appears in form table with IFS data ✓
```

**Actual Result:**
```
[ ] All correct (item imported successfully)
[ ] Spinner didn't show
[ ] Modal stayed open (item not added)
[ ] Error message shown: _______
```

**Verify Data:**
- order_no matches IFS data? ✓ Yes / ✗ No
- customer_name populated? ✓ Yes / ✗ No
- Primary parameter auto-filled? ✓ Yes / ✗ No

**Notes:** _______________________________________________

---

### Test 3.4: Import from IFS (Error Case)
**Steps:**
1. Open IFS modal
2. Disconnect network (DevTools → Network tab → Offline checkbox)
3. Click "Pilih" button on any row
4. Observe error handling

**Expected Result:**
```
Button shows loading spinner
Error message appears (toast or alert): "Network error" or "Data tidak ditemukan"
Modal stays open (allows retry or cancel) ✓
```

**Actual Result:**
```
[ ] Correct error handling
[ ] No error message shown (silent failure)
[ ] Modal closed unexpectedly
[ ] Error message: _______
```

**Re-enable Network:**
- Go back to Network tab, uncheck Offline
- Retry the same row → should now succeed

**Result:** ✓ Works / ✗ Still fails

**Notes:** _______________________________________________

---

## Test Session 4: Shift Selection & Time Updates (10 minutes)

### Test 4.1: Shift Auto-fills Start Time
**Steps:**
1. Select a `work_date`
2. In shift dropdown, select a shift with `start_at = 08:00`
3. Check `start_at` input field in header

**Expected Result:**
```
start_at auto-filled with shift start time: 08:00 ✓
```

**Actual Result:**
```
[ ] Correctly auto-filled
[ ] Not auto-filled (user must enter manually)
[ ] Wrong time filled: _______
```

**Notes:** _______________________________________________

---

### Test 4.2: Shift Change Doesn't Override User Input
**Steps:**
1. Select shift (auto-fills start_at = 08:00)
2. User manually changes start_at to 09:00
3. Change to different shift
4. Check start_at value

**Expected Result:**
```
start_at remains 09:00 (user's manual entry preserved) ✓
```

**Actual Result:**
```
[ ] Correctly preserved
[ ] Overwritten with new shift time (BUG)
[ ] Cleared to empty (BUG)
```

**Notes:** _______________________________________________

---

## Test Session 5: Performance (25 minutes)

### Test 5.1: Form Load Time
**Steps:**
1. Open Schedule Create form
2. Open DevTools → Performance tab
3. Measure page load time

**Expected Result:**
```
Page loads in < 3 seconds
DOM interactive < 3 seconds ✓
```

**Actual Result:**
```
Load time: __________ seconds
Interactive time: __________ seconds
Status: [ ] Pass / [ ] Fail (too slow)
```

**Notes:** _______________________________________________

---

### Test 5.2: Add Item Rapid Clicks
**Steps:**
1. Open DevTools → Performance tab
2. Click Record button
3. Click "Tambah item" button 20 times rapidly
4. Click Stop recording
5. Analyze:
   - Any long tasks (>50ms)?
   - Any UI jank/stuttering felt?

**Expected Result:**
```
Each click takes <100ms
No long tasks blocking main thread
No visible UI stuttering ✓
```

**Actual Result:**
```
[ ] Smooth (no issues)
[ ] Sluggish (takes 200+ms per click)
[ ] UI freezes temporarily
Average time per click: __________ ms
```

**Notes:** _______________________________________________

---

### Test 5.3: Memory Leak Check (Select2)
**Steps:**
1. Open DevTools → Performance/Memory tab
2. In form, repeatedly change `work_date` field (10-15 changes)
3. After changes, trigger garbage collection (if available)
4. Check memory growth

**Expected Result:**
```
Memory stable or minimal growth (<5 MB)
No growing list of Select2 event listeners ✓
```

**Actual Result:**
```
[ ] No memory leak detected
[ ] Significant growth (>5 MB)
Memory before: __________ MB
Memory after: __________ MB
Growth: __________ MB
```

**Notes:** _______________________________________________

---

### Test 5.4: Debounce Effectiveness
**Steps:**
1. Open DevTools → Network tab
2. In form, rapidly change `work_date` field (type date: 2026-11-01, 2026-11-02, 2026-11-03, etc.)
3. Do this 10 times within 2 seconds
4. Count API calls to `/api/shift/effective`

**Expected Result:**
```
10 date changes → 1-2 API calls (debounced)
NOT 10 API calls ✓
```

**Actual Result:**
```
[ ] Correctly debounced (1-2 calls)
[ ] No debounce (10 calls made) - BUG
API call count: __________
```

**Notes:** _______________________________________________

---

## Test Session 6: Browser Compatibility (10 minutes)

### Test 6.1: Chrome/Edge (Chromium)
**Browser:** Chrome / Edge  
**Version:** __________

- [ ] Form loads without errors
- [ ] All form fields work correctly
- [ ] Submit works and redirects properly
- [ ] DevTools shows no JavaScript errors

**Notes:** _______________________________________________

---

### Test 6.2: Firefox
**Browser:** Firefox  
**Version:** __________

- [ ] Form loads without errors
- [ ] All form fields work correctly
- [ ] Submit works and redirects properly
- [ ] Browser console shows no JavaScript errors

**Notes:** _______________________________________________

---

### Test 6.3: Safari
**Browser:** Safari  
**Version:** __________

- [ ] Form loads without errors
- [ ] All form fields work correctly
- [ ] Submit works and redirects properly
- [ ] Web Inspector shows no JavaScript errors

**Notes:** _______________________________________________

---

## Test Session 7: Database Verification (10 minutes)

### Test 7.1: Schedule Items Saved Correctly
```sql
-- Run this query after saving a schedule
SELECT 
  si.id,
  si.schedule_id,
  si.order_no,
  si.customer_name,
  si.params,
  JSON_EXTRACT(si.params, '$.CUCI_MESIN') as cuci_mesin_value
FROM schedule_items si
WHERE si.schedule_id = [LAST_CREATED_SCHEDULE_ID]
LIMIT 5;
```

**Expected Results:**
- [ ] All rows returned
- [ ] `params` column has valid JSON
- [ ] `CUCI_MESIN` values are exactly `0` or `1` (NOT `"0"`, `"1"`, NULL, or true/false)

**Actual Results:**
```
Row 1: params = __________, CUCI_MESIN = __________
Row 2: params = __________, CUCI_MESIN = __________
Row 3: params = __________, CUCI_MESIN = __________
```

**Status:** ✓ Pass / ✗ Fail

**Notes:** _______________________________________________

---

### Test 7.2: IFS-Imported Items Marked Correctly
```sql
SELECT 
  si.id,
  si.order_no,
  si.source_type,
  si.source_item_id,
  si.params
FROM schedule_items si
WHERE si.schedule_id = [LAST_CREATED_SCHEDULE_ID]
  AND si.source_type = 'ifs'
LIMIT 3;
```

**Expected:**
- [ ] `source_type` = `'ifs'`
- [ ] `source_item_id` populated with DOP number
- [ ] `params` contains quantity from IFS

**Actual Results:**
```
IFS items found: __________
source_type values: __________
source_item_id samples: __________
```

**Status:** ✓ Pass / ✗ Fail

**Notes:** _______________________________________________

---

## Issues Found During Testing

### Critical Issues
| # | Issue | Steps to Reproduce | Impact | Status |
|---|-------|-------------------|--------|--------|
| 1 | | | | [ ] Found / [ ] Not Found |
| 2 | | | | [ ] Found / [ ] Not Found |

---

### High Priority Issues
| # | Issue | Steps to Reproduce | Impact | Status |
|---|-------|-------------------|--------|--------|
| 1 | | | | [ ] Found / [ ] Not Found |
| 2 | | | | [ ] Found / [ ] Not Found |

---

### Medium Priority Issues
| # | Issue | Steps to Reproduce | Impact | Status |
|---|-------|-------------------|--------|--------|
| 1 | | | | [ ] Found / [ ] Not Found |
| 2 | | | | [ ] Found / [ ] Not Found |

---

## Summary

**Total Test Cases:** 25  
**Test Cases Passed:** __________ / 25  
**Test Cases Failed:** __________ / 25  
**Test Cases Blocked:** __________ / 25  

**Overall Status:** [ ] PASS / [ ] FAIL / [ ] BLOCKED

**Tester:** __________________________  
**Date:** __________________________  
**Time Spent:** __________________________  

**Recommendations:**
- ___________________________________________________________
- ___________________________________________________________
- ___________________________________________________________

**Sign-off:**
- Tester Signature: __________________________
- QA Lead Signature: __________________________
- Date: __________________________

---

## Appendix: Quick Reference

### Form URL
```
Create: /dyeing/schedule/create
Edit: /dyeing/schedule/edit/[ID]
```

### Database Queries
```sql
-- Check latest schedule
SELECT * FROM schedules ORDER BY created_at DESC LIMIT 1;

-- Check schedule items
SELECT * FROM schedule_items WHERE schedule_id = [ID];

-- Check parameter definitions
SELECT code, name, data_type FROM parameter_divisions WHERE division_id = 1;
```

### DevTools Tips
- **Network tab:** Filter by `xhr` to see API calls only
- **Console:** Type `perfTest.runAll()` to run performance suite
- **Memory:** Take heap snapshots before/after date changes to detect leaks

### Common API Endpoints
```
GET /api/shift/effective?date=2026-11-02
GET /api/ifs/datatable
GET /api/ifs/detail/{DOP_NO}
POST /dyeing/schedule (form submission)
```


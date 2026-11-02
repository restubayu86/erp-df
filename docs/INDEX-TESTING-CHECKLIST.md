# Schedule Index Page - Testing Checklist

**Date:** 2026-11-02  
**Tester:** [Your Name]  
**Browser:** [Chrome/Firefox/Safari/Edge]  
**Status:** Ready for Testing

---

## Pre-Testing Setup

- [ ] Schedule table accessible at `/dyeing/schedule`
- [ ] Database has test data (at least 10 schedules)
- [ ] DevTools available (F12)
- [ ] All vendor scripts loaded (jQuery, DataTables, Select2, Flatpickr)
- [ ] No browser extensions blocking content

---

## Section 1: Page Load & Basic Functionality (15 minutes)

### Test 1.1: Page Loads Successfully
**Steps:**
1. Navigate to `/dyeing/schedule`
2. Wait for page to fully load (all content visible)
3. Check browser console for errors

**Expected:**
- Page loads without errors
- All UI elements visible
- No red console errors

**Actual Result:**
- [ ] Loads cleanly, no errors
- [ ] Page shows content (insight cards, table)
- [ ] Console clean (no red errors)

**Issues Found:** _______________________________________________

---

### Test 1.2: Insight Cards Display
**Steps:**
1. Check top of page for 4 insight cards:
   - "Machines" - count of available machines
   - "Total Orders" - count of schedules/orders
   - "Total Qty" - sum of quantities
   - "Utilization %" - percentage calculation

2. Verify each card shows:
   - Title (bold text)
   - Large number (the metric)
   - Optional badge showing trend

**Expected:**
```
Machines: 5
Total Orders: 42
Total Qty: 1,250.50 kg
Utilization: 85.3%
```

**Actual Result:**
```
Card 1: Title = __________, Value = __________
Card 2: Title = __________, Value = __________
Card 3: Title = __________, Value = __________
Card 4: Title = __________, Value = __________
```

**Pass Criteria:**
- [ ] All 4 cards visible and populated
- [ ] Values are numbers (not "undefined", "null", or blank)
- [ ] No broken styling

**Issues Found:** _______________________________________________

---

### Test 1.3: DataTable Renders with Data
**Steps:**
1. Scroll down to find schedule table
2. Verify table headers visible:
   - Date, Machine, Process, Order No, Customer, Qty, Priority, CUCI_MESIN, Actions
3. Check that data rows populated (not empty)

**Expected:**
- Table visible with multiple columns
- At least 5-10 data rows visible
- Pagination info shows: "Showing 1 to 50 of XXX entries"

**Actual Result:**
- Table found: ✓ Yes / ✗ No
- Headers count: __________
- Data rows visible: __________
- Pagination shows: _______________________________________________

**Pass Criteria:**
- [ ] Table renders correctly
- [ ] Headers match expected
- [ ] Data rows populated
- [ ] No broken table layout

**Issues Found:** _______________________________________________

---

## Section 2: Insight Cards Data Verification (10 minutes)

### Test 2.1: Database Verification - Card Values
**Database Query:**
```sql
-- Check total schedules
SELECT COUNT(*) as total_schedules FROM schedules;

-- Check machines count
SELECT COUNT(DISTINCT machine_id) FROM schedules;

-- Check total items
SELECT COUNT(*) as total_items FROM schedule_items;

-- Check total quantity
SELECT SUM(CAST(JSON_EXTRACT(params, '$.BERAT') AS DECIMAL(10,2))) as total_qty
FROM schedule_items;
```

**Expected:**
- Database values match page cards
- No data mismatch

**Actual Result:**
```
DB Total Schedules: __________  →  Page Shows: __________  ✓/✗
DB Machines Count: __________  →  Page Shows: __________  ✓/✗
DB Total Items: __________  →  Page Shows: __________  ✓/✗
DB Total Qty: __________  →  Page Shows: __________  ✓/✗
```

**Pass Criteria:**
- [ ] Card values match database
- [ ] No hardcoded/dummy values
- [ ] Calculations correct

**Issues Found:** _______________________________________________

---

## Section 3: DataTable Columns & Rendering (20 minutes)

### Test 3.1: Column Visibility & Order
**Steps:**
1. Check table header columns (left to right):
   - [ ] Date
   - [ ] Machine
   - [ ] Process
   - [ ] Order No
   - [ ] Customer
   - [ ] Qty
   - [ ] Priority
   - [ ] CUCI_MESIN
   - [ ] Actions

2. Try scrolling right (table should be responsive)

**Actual Columns Found:**
1. _______________________________________________
2. _______________________________________________
3. _______________________________________________
(Continue for all columns)

**Pass Criteria:**
- [ ] All expected columns present
- [ ] Correct order
- [ ] Responsive scrolling works

**Issues Found:** _______________________________________________

---

### Test 3.2: CUCI_MESIN Switch Column
**Steps:**
1. Scroll right to find "CUCI_MESIN" column
2. Verify each cell shows a Bootstrap switch component:
   ```html
   <div class="form-check form-switch">
     <input class="form-check-input" type="checkbox" ...>
   </div>
   ```

3. Check switch states:
   - Some checked (blue toggle on right)
   - Some unchecked (toggle on left)

**Expected:**
- All switches render as interactive toggles
- States match database (0=unchecked, 1=checked)
- No HTML errors or broken elements

**Actual Result:**
- Switches visible: ✓ Yes / ✗ No / ⚠ Partially
- Switches working: ✓ Yes / ✗ No
- States accurate: ✓ Yes / ✗ No / ⚠ Partially

**Sample Check (3-5 rows):**
```
Row 1: Switch checked? ✓/✗  →  DB Value: __________
Row 2: Switch checked? ✓/✗  →  DB Value: __________
Row 3: Switch checked? ✓/✗  →  DB Value: __________
```

**Pass Criteria:**
- [ ] All switches display correctly (no broken HTML)
- [ ] States match database values
- [ ] Switches are not editable (read-only display)

**Issues Found:** _______________________________________________

---

### Test 3.3: Progress Gauge Column
**Steps:**
1. Find "Priority" column (should have circular progress bars)
2. Verify each row shows SVG circular gauge:
   ```
   Filled circle showing progress percentage
   With color based on priority (blue/orange/red)
   ```

3. Check gauge accuracy:
   - Filled percentage should match schedule progress
   - If schedule not started: gauge empty
   - If schedule in progress: gauge partially filled
   - If schedule done: gauge full (100%)

**Expected:**
- All gauges render as circles (SVG)
- Percentages correct
- Colors match priority levels:
  - Blue = Normal
  - Orange/Yellow = High
  - Red = Urgent

**Actual Result:**
- Gauges visible: ✓ Yes / ✗ No
- SVG renders: ✓ Yes / ✗ No / ⚠ Broken
- Colors correct: ✓ Yes / ✗ No

**Sample Check (3 rows):**
```
Row 1 (Normal Priority):
  - Gauge fills: __________% 
  - Color: Blue ✓/✗
  
Row 2 (High Priority):
  - Gauge fills: __________% 
  - Color: Orange ✓/✗
  
Row 3 (Urgent Priority):
  - Gauge fills: __________% 
  - Color: Red ✓/✗
```

**Pass Criteria:**
- [ ] All gauges display (no broken SVG)
- [ ] Percentages accurate
- [ ] Colors match priority
- [ ] No console errors

**Issues Found:** _______________________________________________

---

### Test 3.4: Data Column Accuracy
**Steps:**
1. Pick 3 random rows
2. For each row, verify data matches database:
   - Date format (YYYY-MM-DD)
   - Machine name
   - Order number
   - Customer name
   - Quantity value

**Database Verification:**
```sql
SELECT id, work_date, machine_id, order_no, customer_name, 
       JSON_EXTRACT(params, '$.BERAT') as qty
FROM schedule_items si
JOIN schedules s ON si.schedule_id = s.id
WHERE s.id = [SCHEDULE_ID]
LIMIT 3;
```

**Comparison:**
```
Row 1: 
  - Date (Page): __________ vs (DB): __________  ✓/✗
  - Machine (Page): __________ vs (DB): __________  ✓/✗
  - Order (Page): __________ vs (DB): __________  ✓/✗
  - Qty (Page): __________ vs (DB): __________  ✓/✗

Row 2:
  - Date (Page): __________ vs (DB): __________  ✓/✗
  - ... (continue for all fields)
```

**Pass Criteria:**
- [ ] All data matches database
- [ ] No typos or formatting issues
- [ ] Date format consistent (YYYY-MM-DD)

**Issues Found:** _______________________________________________

---

## Section 4: Pagination & Table Navigation (10 minutes)

### Test 4.1: Pagination Controls
**Steps:**
1. Find pagination controls at bottom of table:
   - Previous button
   - Page numbers
   - Next button
   - Page size selector (if present)

2. Click "Next" button → verify new data loads
3. Click a page number → jump to that page
4. Click "Previous" → go back

**Expected:**
- Pagination info updates: "Showing X to Y of Z entries"
- Data changes when navigating
- Page loads in <1 second

**Actual Result:**
- Pagination visible: ✓ Yes / ✗ No
- "Next" works: ✓ Yes / ✗ No
- Page numbers work: ✓ Yes / ✗ No
- "Previous" works: ✓ Yes / ✗ No
- Load time per page: __________ seconds

**Pass Criteria:**
- [ ] All pagination buttons functional
- [ ] Data updates correctly
- [ ] No console errors
- [ ] Page load <1 second

**Issues Found:** _______________________________________________

---

### Test 4.2: Page Size Selection
**Steps:**
1. Find page size dropdown (usually shows "10", "25", "50", "100")
2. Change from default (usually 50) to 10
3. Verify table shows only 10 rows
4. Change to 100
5. Verify table shows up to 100 rows

**Actual Result:**
- Page size dropdown found: ✓ Yes / ✗ No
- Size 10 works: ✓ Yes / ✗ No  → Rows shown: __________
- Size 50 works: ✓ Yes / ✗ No  → Rows shown: __________
- Size 100 works: ✓ Yes / ✗ No  → Rows shown: __________

**Pass Criteria:**
- [ ] Page size selector functional
- [ ] Correct row count after change
- [ ] No data loss or duplication

**Issues Found:** _______________________________________________

---

## Section 5: Filters & Search (15 minutes)

### Test 5.1: Date Range Filter
**Steps:**
1. Find date range filter (two date inputs or calendar icon)
2. Click first date input → calendar opens
3. Select start date: 2026-11-01
4. Select end date: 2026-11-05
5. Verify table filters to show only schedules in that date range
6. Check pagination updates

**Expected:**
```
Before filter: "Showing 1 to 50 of 150 entries"
After filter (2026-11-01 to 2026-11-05): 
  "Showing 1 to 30 of 30 entries" (example)
```

**Actual Result:**
- Calendar opens: ✓ Yes / ✗ No
- Date range applies: ✓ Yes / ✗ No
- Entries before: __________ 
- Entries after filter: __________
- All rows in range: ✓ Yes / ✗ No / ⚠ Some outside range

**Pass Criteria:**
- [ ] Calendar functional
- [ ] Filter applies correctly
- [ ] Only rows in date range shown
- [ ] Pagination updated

**Issues Found:** _______________________________________________

---

### Test 5.2: Search/Global Filter
**Steps:**
1. Find search input (usually says "Search..." or "Filter...")
2. Type: "SO-2401"
3. Verify table filters to show only rows containing "SO-2401"
4. Clear search
5. Try different searches: machine name, customer name

**Expected:**
```
Search "SO-2401" → Shows only schedules with that order number
Search "Machine A" → Shows only schedules using Machine A
Search "ABC Company" → Shows only that customer's schedules
```

**Actual Result:**
- Search input found: ✓ Yes / ✗ No
- Search "SO-2401" works: ✓ Yes / ✗ No
- Results accurate: ✓ Yes / ✗ No
- Search delay: __________ ms (target: <500ms)

**Comparison (before vs after search):**
```
Before: __________ rows
After search "SO-2401": __________ rows
All results contain search term: ✓ Yes / ✗ No
```

**Pass Criteria:**
- [ ] Search functional
- [ ] Results accurate
- [ ] Response fast (<500ms)
- [ ] Can clear search and restore data

**Issues Found:** _______________________________________________

---

## Section 6: Action Buttons (15 minutes)

### Test 6.1: Edit Button
**Steps:**
1. Find a schedule row
2. Look for "Edit" or pencil icon button
3. Click Edit button
4. Verify edit form opens with pre-filled data:
   - Date matches
   - Machine matches
   - All items/parameters populate
   - Switches show correct state

**Expected:**
- Edit form opens (not blank)
- Data matches schedule being edited
- CUCI_MESIN switches show correct state
- Form ready for editing

**Actual Result:**
- Edit button found: ✓ Yes / ✗ No
- Form opens: ✓ Yes / ✗ No
- Data pre-filled: ✓ Yes / ✗ No / ⚠ Partially
- Switches accurate: ✓ Yes / ✗ No
- No errors loading: ✓ Yes / ✗ No

**Sample Verification:**
```
Schedule Being Edited:
  - Date (Table): __________  vs  (Form): __________  ✓/✗
  - Machine (Table): __________  vs  (Form): __________  ✓/✗
  - CUCI_MESIN Switch: (Table) checked? ✓/✗  vs  (Form) checked? ✓/✗
```

**Pass Criteria:**
- [ ] Edit button functional
- [ ] Form pre-populates with correct data
- [ ] Can modify and save
- [ ] No console errors

**Issues Found:** _______________________________________________

---

### Test 6.2: Print Button
**Steps:**
1. Find a schedule row
2. Look for "Print" or printer icon button
3. Click Print button
4. Print preview window opens
5. Verify preview shows:
   - Schedule header (company name, date, etc.)
   - All items in table format
   - Layout is F4 landscape
   - All data readable

**Expected:**
```
Print window opens with:
- Company name (PT. Sinar Continental)
- Schedule date
- Machine info
- All items with parameters
- Proper F4 landscape formatting
```

**Actual Result:**
- Print button found: ✓ Yes / ✗ No
- Print window opens: ✓ Yes / ✗ No
- Content visible: ✓ Yes / ✗ No / ⚠ Partially
- Layout correct (F4 landscape): ✓ Yes / ✗ No

**Content Check:**
- [ ] Company name visible
- [ ] Schedule date shown
- [ ] All items listed
- [ ] Parameters displayed
- [ ] CUCI_MESIN parameter shown
- [ ] No "undefined" or broken values
- [ ] Can print to PDF successfully

**Pass Criteria:**
- [ ] Print button functional
- [ ] Preview window opens
- [ ] All data visible and readable
- [ ] Can print/save as PDF
- [ ] F4 landscape format correct

**Issues Found:** _______________________________________________

---

### Test 6.3: Delete Button (Destructive - Test Last)
**Steps:**
1. Find a test schedule row (one you can safely delete)
2. Look for "Delete" or trash icon button
3. Click Delete button
4. Verify confirmation dialog appears
5. Click "Cancel" → nothing deleted
6. Click Delete again
7. Click "Confirm/Yes" → schedule deleted
8. Verify it's removed from table and database

**Warning:** Use a test schedule created specifically for testing!

**Expected:**
```
Delete click → Confirmation dialog: "Sure you want to delete this schedule?"
Click No/Cancel → Schedule still in table
Click Delete again → Same dialog
Click Yes/Confirm → Schedule deleted
Refresh page → Schedule gone
```

**Actual Result:**
- Delete button found: ✓ Yes / ✗ No
- Confirmation dialog shows: ✓ Yes / ✗ No / ⚠ No confirmation (BUG!)
- Cancel works: ✓ Yes / ✗ No
- Confirm deletes: ✓ Yes / ✗ No
- Schedule removed from table: ✓ Yes / ✗ No
- Database updated: ✓ Yes / ✗ No

**Pass Criteria:**
- [ ] Delete button functional
- [ ] Confirmation required (prevents accidents)
- [ ] Cancel doesn't delete
- [ ] Confirm actually deletes
- [ ] Database updated
- [ ] No orphaned data

**Issues Found:** _______________________________________________

---

## Section 7: Performance Testing (20 minutes)

### Test 7.1: Page Load Performance
**Steps:**
1. Open DevTools → Performance tab
2. Hard refresh (Ctrl+Shift+R)
3. Wait for page to fully load
4. Check Network tab:
   - Total requests
   - Total size
   - Largest file

**Expected:**
- Page load: <3 seconds
- DOM Interactive: <2 seconds
- Total size: <1 MB
- Network requests: <50 total

**Actual Result:**
- Page load time: __________ seconds (target: <3s)
- DOM Interactive: __________ seconds
- Total network size: __________ KB (target: <1000 KB)
- Total requests: __________ (target: <50)

**Network Breakdown:**
- JS files: __________ KB
- CSS files: __________ KB
- Images: __________ KB
- API calls: __________ KB

**Pass Criteria:**
- [ ] Page load <3 seconds
- [ ] Interactive <2 seconds
- [ ] Total <1 MB
- [ ] No failed requests (404/500)

**Issues Found:** _______________________________________________

---

### Test 7.2: DataTable Pagination Performance
**Steps:**
1. Open Network tab in DevTools
2. Note current entry count
3. Click Next page button
4. Measure response time

**Expected:**
- Page loads in <1 second
- Network request size <500 KB
- No UI freezing

**Actual Result:**
```
Page 1 → Page 2: __________ seconds
Page 2 → Page 3: __________ seconds
Page 3 → Page 1: __________ seconds
Average response time: __________ seconds (target: <1s)
```

**Pass Criteria:**
- [ ] Each pagination <1 second
- [ ] No UI lag/stuttering
- [ ] Smooth transitions

**Issues Found:** _______________________________________________

---

### Test 7.3: Search Performance
**Steps:**
1. Click in search box
2. Type a search term slowly: "s-o-2-4"
3. Measure time from last keystroke to results update

**Expected:**
- Search results update within 500ms
- No UI freezing while typing
- No duplicate API calls for each keystroke (should debounce)

**Actual Result:**
- Initial search term: "__________"
- Time to filter: __________ ms (target: <500ms)
- Results accurate: ✓ Yes / ✗ No
- Debounce working: ✓ Yes / ✗ No

**Pass Criteria:**
- [ ] Search fast (<500ms)
- [ ] No UI lag
- [ ] Debounce prevents excess API calls

**Issues Found:** _______________________________________________

---

### Test 7.4: Memory Usage
**Steps:**
1. Open DevTools → Memory tab
2. Take initial heap snapshot
3. Paginate through 5 pages (click Next 5 times)
4. Take heap snapshot after pagination
5. Compare memory growth

**Expected:**
- Memory stable or minimal growth (<10 MB)
- No signs of memory leak

**Actual Result:**
- Initial heap: __________ MB
- After pagination: __________ MB
- Growth: __________ MB
- Status: [ ] Normal / [ ] Possible leak

**Pass Criteria:**
- [ ] Memory growth <10 MB
- [ ] No signs of leak (stable over time)

**Issues Found:** _______________________________________________

---

## Section 8: Browser Compatibility (15 minutes)

### Test 8.1: Chrome/Edge (Chromium)
**Browser:** Chrome / Edge  
**Version:** __________

**Checklist:**
- [ ] Page loads without errors
- [ ] All elements render correctly
- [ ] DataTable works smoothly
- [ ] Filters/Search functional
- [ ] No console errors
- [ ] Actions work (Edit/Print/Delete)

**Performance:**
- Load time: __________ seconds
- Search speed: __________ ms
- Overall: [ ] Excellent / [ ] Good / [ ] Acceptable / [ ] Poor

**Issues:** _______________________________________________

---

### Test 8.2: Firefox
**Browser:** Firefox  
**Version:** __________

**Checklist:**
- [ ] Page loads without errors
- [ ] All elements render correctly
- [ ] DataTable works smoothly
- [ ] Filters/Search functional
- [ ] No console errors
- [ ] Actions work (Edit/Print/Delete)

**Performance:**
- Load time: __________ seconds
- Search speed: __________ ms
- Overall: [ ] Excellent / [ ] Good / [ ] Acceptable / [ ] Poor

**Issues:** _______________________________________________

---

### Test 8.3: Safari
**Browser:** Safari  
**Version:** __________

**Checklist:**
- [ ] Page loads without errors
- [ ] All elements render correctly
- [ ] DataTable works smoothly
- [ ] Filters/Search functional
- [ ] No console errors
- [ ] Actions work (Edit/Print/Delete)

**Performance:**
- Load time: __________ seconds
- Search speed: __________ ms
- Overall: [ ] Excellent / [ ] Good / [ ] Acceptable / [ ] Poor

**Issues:** _______________________________________________

---

### Test 8.4: Mobile Responsive (375px - iPhone width)
**Device:** Mobile / Tablet  
**Resolution:** 375px width

**Checklist:**
- [ ] Page loads and renders
- [ ] Table scrolls horizontally (horizontally scrollable)
- [ ] Search/filters accessible
- [ ] Action buttons visible and clickable
- [ ] Insight cards stack vertically
- [ ] No horizontal overflow
- [ ] Text readable (no tiny font)

**Usability:**
- [ ] Easy to navigate on mobile
- [ ] All features work on mobile
- [ ] No broken layout

**Issues:** _______________________________________________

---

## Section 9: Database Data Integrity (10 minutes)

### Test 9.1: Real Data Verification
**Database Queries:**

**Query 1: Count Verification**
```sql
SELECT COUNT(*) as total_schedules FROM schedules;
SELECT COUNT(*) as total_items FROM schedule_items;
```

**Query 2: Data Matching**
```sql
-- Pick a schedule_id from the page, replace [ID]
SELECT s.id, s.work_date, m.name as machine, COUNT(si.id) as item_count,
       SUM(CAST(JSON_EXTRACT(si.params, '$.BERAT') AS DECIMAL(10,2))) as total_qty
FROM schedules s
JOIN machines m ON s.machine_id = m.id
LEFT JOIN schedule_items si ON s.id = si.schedule_id
WHERE s.id = [SCHEDULE_ID]
GROUP BY s.id;
```

**Comparison:**
```
Page Shows:
  - Total Schedules: __________
  - Total Items: __________
  - Sample Schedule Info: __________

Database Shows:
  - Total Schedules: __________
  - Total Items: __________
  - Sample Schedule Info: __________

Match: [ ] Yes / [ ] No
```

**Pass Criteria:**
- [ ] Page totals match database
- [ ] Sample data verified
- [ ] No hardcoded/dummy values
- [ ] 100% real database data

**Issues Found:** _______________________________________________

---

## Section 10: Issues Summary

### Critical Issues Found
| # | Issue | Severity | Status |
|---|-------|----------|--------|
| 1 | | 🔴 | [ ] Found / [ ] Not Found |
| 2 | | 🔴 | [ ] Found / [ ] Not Found |

---

### High Priority Issues
| # | Issue | Severity | Status |
|---|-------|----------|--------|
| 1 | | 🟠 | [ ] Found / [ ] Not Found |
| 2 | | 🟠 | [ ] Found / [ ] Not Found |

---

### Medium Priority Issues
| # | Issue | Severity | Status |
|---|-------|----------|--------|
| 1 | | 🟡 | [ ] Found / [ ] Not Found |
| 2 | | 🟡 | [ ] Found / [ ] Not Found |

---

## Final Summary

**Total Tests:** 50+  
**Tests Passed:** __________ / __________  
**Tests Failed:** __________ / __________  
**Tests Blocked:** __________ / __________  

**Overall Status:**
- [ ] PASS - Ready for Production
- [ ] FAIL - Issues need fixing
- [ ] BLOCKED - Cannot complete testing

**Tester Signature:** __________________________  
**Date:** __________________________  
**Time Spent:** __________________________  

**QA Lead Review:** __________________________  
**Date:** __________________________  

---

## Recommendations

1. _______________________________________________________________
2. _______________________________________________________________
3. _______________________________________________________________

---

## Sign-Off

- [ ] Developer verified fixes
- [ ] QA Lead approved testing
- [ ] Ready for production deployment

**Approved By:** __________________________  
**Date:** __________________________


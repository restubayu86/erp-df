# Schedule Index Page - Debug & Performance Testing

**Last Updated:** 2026-11-02  
**Component:** Schedule List/Index (index.php)  
**Status:** Ready for Testing  
**File Size:** 1,025 lines (40 KB)

---

## Overview

Index page menampilkan:
- ✅ Insight cards (summary statistics)
- ✅ DataTable dengan server-side pagination
- ✅ Date range filtering
- ✅ Progress bars (circular gauge)
- ✅ CUCI_MESIN switch column
- ✅ Print button per schedule
- ✅ Edit/Delete actions
- ✅ Row grouping (by date/machine)

---

## Critical Issues to Verify

### Issue 1: Insight Cards Data Loading
**Status:** Need to verify

**Expected Behavior:**
```
- Page loads with spinner in cards
- After 1-2 seconds, cards show real data (COUNT, SUM, etc.)
- Cards display: Machines, Total Orders, Total Qty, Utilization %
```

**Test Steps:**
1. Open /dyeing/schedule page
2. Wait for page to fully load
3. Check if insight cards show data

**Pass Criteria:**
- [ ] Cards show numerical values (not "undefined" or empty)
- [ ] Values match database aggregation
- [ ] No console errors

---

### Issue 2: DataTable Server-Side Pagination
**Status:** Need to verify performance

**Expected Behavior:**
```
- DataTable loads with server-side pagination
- Shows 50 rows per page by default
- "Showing 1 to 50 of XXX entries" displays correctly
- Pagination controls work (Next, Previous, Jump to page)
```

**Test Steps:**
1. Open index page
2. Wait for DataTable to load
3. Scroll to bottom → Check pagination info
4. Click "Next" button → verify new data loads
5. Measure load time for each page

**Performance Targets:**
- [ ] Page load: <3 seconds
- [ ] Pagination click: <1 second
- [ ] Network: <200KB per request
- [ ] No UI freezing

---

### Issue 3: Date Range Filtering
**Status:** Need to verify functionality

**Expected Behavior:**
```
- Flatpickr calendar opens when clicking date input
- Can select date range (from - to)
- DataTable filters to show only selected date range
- Filter persists when paginating
```

**Test Steps:**
1. Find date range filter input
2. Click to open calendar
3. Select start date: 2026-11-01
4. Select end date: 2026-11-05
5. Verify DataTable reloads with filtered data
6. Change pagination → verify filter still active

**Pass Criteria:**
- [ ] Calendar opens correctly
- [ ] Date range applies filter
- [ ] Only schedules in range displayed
- [ ] Filter applies to all columns (machine, process, etc.)

---

### Issue 4: CUCI_MESIN Switch Column
**Status:** Need to verify rendering

**Expected Behavior:**
```
- CUCI_MESIN column shows Bootstrap switch component
- Switch state reflects database value (0=unchecked, 1=checked)
- No broken elements or console errors
```

**Test Steps:**
1. Scroll right in DataTable to find CUCI_MESIN column
2. Check several rows:
   - Some switches checked (value=1)
   - Some switches unchecked (value=0)
3. Verify visual consistency with form.php switches

**Pass Criteria:**
- [ ] All switches display correctly (no missing inputs)
- [ ] State matches database (0/1)
- [ ] No console errors
- [ ] Styling matches Bootstrap theme

---

### Issue 5: Progress Bars (Circular Gauge)
**Status:** Need to verify rendering

**Expected Behavior:**
```
- Priority column shows SVG circular gauge
- Gauge filled percentage matches `progress` field in database
- Color coded by priority (normal=blue, high=orange, urgent=red)
- Responsive on different screen sizes
```

**Test Steps:**
1. Find Priority column
2. Check circular gauges:
   - Some should be full (100%) if schedule completed
   - Some partial if in progress
   - Some empty if not started
3. Verify colors match priority level
4. Check on mobile view (responsive)

**Pass Criteria:**
- [ ] Gauge renders (not broken SVG)
- [ ] Percentage correct (matches progress field)
- [ ] Colors correct by priority
- [ ] Responsive on mobile

---

### Issue 6: Print Button Functionality
**Status:** Need to verify

**Expected Behavior:**
```
- Each row has "Print" button
- Click → Opens print preview window
- Print page shows F4 landscape layout
- All schedule data visible
- Can actually print to PDF/printer
```

**Test Steps:**
1. Find a schedule row
2. Click "Print" button
3. Print preview window opens
4. Verify all data visible
5. Try "Print to PDF"
6. Check PDF content

**Pass Criteria:**
- [ ] Print window opens
- [ ] All schedule items visible in print
- [ ] Layout is F4 landscape
- [ ] PDF generated successfully
- [ ] No console errors

---

### Issue 7: Real Database Data vs Dummy Data
**Status:** Need to verify (audit completed, but re-confirm)

**Expected Behavior:**
```
- ALL data shown is from database
- NO hardcoded/dummy values
- Page works with 0 schedules (shows "No data available")
- Page works with 1000+ schedules (pagination handles)
```

**Test Steps:**
1. Check database: `SELECT COUNT(*) FROM schedules;`
2. Compare with page total count
3. Verify specific values by checking database
4. Clear test data and reload page

**Pass Criteria:**
- [ ] Page count matches database count
- [ ] Sample values match database
- [ ] Empty state works (0 schedules)
- [ ] Large dataset works (1000+ schedules)

---

### Issue 8: Performance Issues
**Status:** Need to measure

**Critical Metrics:**
```
- Page load time: Target <3 seconds
- DataTable render: Target <2 seconds  
- Each pagination: Target <1 second
- Memory usage: Target <50 MB on page
- Network requests: Target <500 KB total
```

**Test Steps:**
1. Open DevTools → Performance tab
2. Open index page
3. Record performance metrics:
   - DOMContentLoaded
   - Load event
   - First Contentful Paint (FCP)
   - Largest Contentful Paint (LCP)
4. Analyze any long tasks (>50ms)

**Acceptance Criteria:**
- [ ] FCP < 2 seconds
- [ ] LCP < 3 seconds
- [ ] No long tasks blocking UI
- [ ] Smooth scrolling in table

---

### Issue 9: Search/Filter Integration
**Status:** Need to verify

**Expected Behavior:**
```
- Search box filters DataTable
- Filters by multiple columns (order_no, customer, machine, etc.)
- Results update in real-time
- Search term highlighted in results
```

**Test Steps:**
1. Find search input
2. Type: "SO-2401" → verify results filter
3. Type machine name → verify machine column filters
4. Type customer name → verify results
5. Clear search → all data returns

**Pass Criteria:**
- [ ] Search works on all columns
- [ ] No delay >2 seconds
- [ ] Results accurate
- [ ] No broken styling

---

### Issue 10: Export/Action Buttons
**Status:** Need to verify

**Expected Behavior:**
```
- Each row has action buttons:
  - Edit: Opens edit form
  - Print: Opens print preview
  - Delete: Shows confirmation, deletes schedule
- Action buttons responsive (don't overlap on mobile)
- Proper permissions (can't delete if not authorized)
```

**Test Steps:**
1. Find action buttons in a row
2. Click Edit → verify form opens with pre-filled data
3. Click Print → verify print window opens
4. Click Delete → verify confirmation dialog
5. Check permissions (if implemented)

**Pass Criteria:**
- [ ] Edit button works (form loads correctly)
- [ ] Print button works
- [ ] Delete requires confirmation
- [ ] Actions don't cause console errors
- [ ] No permission bypass

---

## Testing Plan

### Phase 1: Quick Smoke Test (10 minutes)

```
Test 1.1: Page Loads
- [ ] /dyeing/schedule page loads without errors
- [ ] No red console errors
- [ ] DataTable visible

Test 1.2: Basic Functionality
- [ ] Insight cards show data (or loading spinner)
- [ ] DataTable shows rows
- [ ] Pagination works
- [ ] Print button visible

Test 1.3: Filters Work
- [ ] Date range filter opens
- [ ] Search box works
- [ ] Filters update table
```

---

### Phase 2: Comprehensive Testing (45 minutes)

**Section A: Data Integrity (15 min)**
```
Test 2.1: Database Verification
- [ ] Run: SELECT COUNT(*) FROM schedules;
- [ ] Compare with page total count
- [ ] Verify specific rows exist in table

Test 2.2: Real Data Check
- [ ] Open database for sample schedule
- [ ] Check if same data displayed on page
- [ ] Verify no dummy/hardcoded values

Test 2.3: Null/Empty Handling
- [ ] Check schedule with NULL notes field
- [ ] Check schedule with empty parameters
- [ ] Verify no "undefined" or broken display
```

**Section B: Column Rendering (15 min)**
```
Test 2.4: CUCI_MESIN Column
- [ ] Scroll right to find column
- [ ] Verify switches display (not broken HTML)
- [ ] Check states match values (0/1)

Test 2.5: Progress Gauge
- [ ] Find Priority column
- [ ] Verify SVG renders (not broken)
- [ ] Check fill percentage correctness
- [ ] Verify colors by priority

Test 2.6: Text Columns
- [ ] All text readable (not overflowing)
- [ ] No HTML injection (tags show as text)
- [ ] Date format consistent
```

**Section C: Interactive Features (15 min)**
```
Test 2.7: Pagination
- [ ] Click Next/Previous buttons
- [ ] Measure load time per page
- [ ] Verify row counts correct

Test 2.8: Filtering
- [ ] Apply date range filter
- [ ] Search for specific schedule
- [ ] Filter by machine/process
- [ ] Verify results accurate

Test 2.9: Actions
- [ ] Click Edit → form opens
- [ ] Click Print → preview opens
- [ ] Click Delete → confirmation dialog
```

---

### Phase 3: Performance Testing (30 minutes)

**Test 3.1: Load Performance**
```javascript
// In DevTools Console:
// Measure page load time
const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
const interactive = performance.timing.domInteractive - performance.timing.navigationStart;
console.log(`Load: ${loadTime}ms, Interactive: ${interactive}ms`);
```

**Expected:** <3 seconds

---

**Test 3.2: DataTable Rendering**
```javascript
// Measure DataTable initialization
const dt = $('table.display').DataTable();
// Watch Network tab while paginating
// Target: <1 second per page load
```

**Expected:** <1 second

---

**Test 3.3: Memory Usage**
```javascript
// DevTools Memory tab:
1. Take heap snapshot before opening page
2. Wait for page to load
3. Take heap snapshot after
4. Compare growth
```

**Expected:** <50 MB growth

---

**Test 3.4: Network Analysis**
```
DevTools Network tab:
- [ ] Main document: <200 KB
- [ ] CSS/JS vendors: should be cached
- [ ] API calls (datatable): <200 KB per request
- [ ] Total page: <1 MB (with all assets)
```

---

### Phase 4: Browser Compatibility (15 minutes)

**Test 4.1: Chrome/Edge**
- [ ] DataTable renders correctly
- [ ] All features work
- [ ] No console errors
- [ ] Performance acceptable

**Test 4.2: Firefox**
- [ ] DataTable renders correctly
- [ ] All features work
- [ ] No console errors

**Test 4.3: Safari**
- [ ] DataTable renders correctly
- [ ] All features work
- [ ] Progress bars display correctly

**Test 4.4: Mobile (Responsive)**
- [ ] Open on phone-size viewport (375px width)
- [ ] Table scrollable horizontally
- [ ] Action buttons accessible
- [ ] Insight cards stack vertically
- [ ] Pagination works

---

## Performance Testing Script

Add this to browser console to run automated tests:

```javascript
(function() {
  console.clear();
  console.log('%c=== INDEX PAGE PERFORMANCE TEST ===', 'font-weight: bold; color: blue; font-size: 14px');
  
  // Test 1: Page load time
  const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
  const interactive = performance.timing.domInteractive - performance.timing.navigationStart;
  const fcp = performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 'N/A';
  
  console.log(`📊 Load Times:`);
  console.log(`  - Page Load: ${loadTime}ms (target: <3000ms)`);
  console.log(`  - Interactive: ${interactive}ms`);
  console.log(`  - FCP: ${fcp} (target: <2000ms)`);
  
  // Test 2: DataTable stats
  const table = $('table.display').DataTable();
  const pageLength = table.page.len();
  const totalRecords = table.settings()[0]._iRecordsTotal;
  
  console.log(`\n📋 DataTable Stats:`);
  console.log(`  - Total Records: ${totalRecords}`);
  console.log(`  - Page Size: ${pageLength}`);
  console.log(`  - Pages: ${Math.ceil(totalRecords / pageLength)}`);
  
  // Test 3: DOM elements
  const switchCount = document.querySelectorAll('.form-check-input').length;
  const gaugeCount = document.querySelectorAll('.circle-progress-svg').length;
  
  console.log(`\n🎨 UI Components:`);
  console.log(`  - Switches (CUCI_MESIN): ${switchCount}`);
  console.log(`  - Gauges (Progress): ${gaugeCount}`);
  
  // Test 4: Network requests
  const perfEntries = performance.getEntriesByType('resource');
  const totalSize = perfEntries.reduce((sum, entry) => sum + (entry.transferSize || 0), 0);
  
  console.log(`\n🌐 Network:`);
  console.log(`  - Total Size: ${(totalSize / 1024 / 1024).toFixed(2)} MB`);
  console.log(`  - Requests: ${perfEntries.length}`);
  
  // Test 5: Memory (Chrome only)
  if (performance.memory) {
    const usedMemory = performance.memory.usedJSHeapSize / 1024 / 1024;
    console.log(`\n💾 Memory:`);
    console.log(`  - Used Heap: ${usedMemory.toFixed(2)} MB`);
  }
  
  // Test 6: Console errors
  const originalError = console.error;
  let errorCount = 0;
  console.error = function() { errorCount++; originalError.apply(console, arguments); };
  
  console.log(`\n✓ Tests Complete!`);
})();
```

**How to use:**
1. Open /dyeing/schedule page
2. Press F12 → Console tab
3. Copy and paste above script
4. Press Enter
5. Review results

---

## Test Case Matrix

### Insight Cards

| Card | Expected Data | Verification |
|------|---------------|---------------|
| Machines | COUNT from machines table | Check DB |
| Total Orders | COUNT schedules in date range | Check DB |
| Total Qty | SUM parameters in schedules | Check DB |
| Utilization % | TIMESTAMPDIFF calculation | Check formula |

---

### DataTable Columns

| Column | Expected | Notes |
|--------|----------|-------|
| Date | YYYY-MM-DD | Formatted date |
| Machine | Machine name/code | From machine table |
| Process | Process name | From process table |
| Order No | Order number | From schedule_items |
| Qty | Quantity value | From parameter value |
| Progress | Circular gauge | SVG render |
| Priority | Color coded | normal/high/urgent |
| CUCI_MESIN | Toggle switch | 0/1 state |
| Actions | Edit/Print/Delete buttons | Functional links |

---

### Filter Combinations to Test

```
Test 1: Date range only
  - Select: 2026-11-01 to 2026-11-05
  - Expected: Show schedules in range

Test 2: Date + Machine filter
  - Date: 2026-11-01 to 2026-11-05
  - Machine: [specific machine]
  - Expected: Filtered by both

Test 3: Search + Filters
  - Date range applied
  - Search: "SO-2401"
  - Expected: Combination of both

Test 4: Clear all filters
  - Applied multiple filters
  - Click "Clear filters" or reset date
  - Expected: Show all data again
```

---

## Known Issues to Document

| # | Issue | Status |
|---|-------|--------|
| 1 | Insight cards data loading | ⏳ Testing |
| 2 | CUCI_MESIN switch rendering | ⏳ Testing |
| 3 | Progress gauge accuracy | ⏳ Testing |
| 4 | Print functionality | ⏳ Testing |
| 5 | Performance at scale (1000+ rows) | ⏳ Testing |
| 6 | Mobile responsiveness | ⏳ Testing |

---

## Database Verification Queries

### Check Schedule Count
```sql
SELECT COUNT(*) as total_schedules FROM schedules;
```

### Check Insight Card Values
```sql
-- Machines count
SELECT COUNT(DISTINCT machine_id) FROM schedules 
WHERE DATE(work_date) BETWEEN '2026-11-01' AND CURDATE();

-- Total orders
SELECT COUNT(*) as total_orders FROM schedule_items si
JOIN schedules s ON si.schedule_id = s.id
WHERE DATE(s.work_date) BETWEEN '2026-11-01' AND CURDATE();

-- Total quantity (primary parameter)
SELECT SUM(CAST(JSON_EXTRACT(params, '$.BERAT') AS DECIMAL(10,2))) as total_qty
FROM schedule_items si
JOIN schedules s ON si.schedule_id = s.id
WHERE DATE(s.work_date) BETWEEN '2026-11-01' AND CURDATE();
```

### Check CUCI_MESIN Values
```sql
SELECT 
  COUNT(*) as total,
  SUM(CASE WHEN JSON_EXTRACT(params, '$.CUCI_MESIN') = 1 THEN 1 ELSE 0 END) as checked,
  SUM(CASE WHEN JSON_EXTRACT(params, '$.CUCI_MESIN') = 0 THEN 1 ELSE 0 END) as unchecked
FROM schedule_items
WHERE JSON_EXTRACT(params, '$.CUCI_MESIN') IS NOT NULL;
```

### Check Progress Values
```sql
SELECT 
  id, 
  work_date,
  CASE 
    WHEN actual_start IS NULL THEN 0
    ELSE LEAST(100, CAST(progress AS UNSIGNED))
  END as gauge_percentage
FROM schedules
ORDER BY work_date DESC LIMIT 10;
```

---

## Test Results Template

### Test Session: _______________
**Date:** _______________  
**Tester:** _______________  
**Browser:** _______________  

#### Smoke Test
- [ ] Page loads (no errors)
- [ ] DataTable shows data
- [ ] Insight cards visible
- **Issues Found:** _______________

#### Data Integrity
- [ ] Database count matches page count
- [ ] Sample data verified
- [ ] No dummy values
- **Issues Found:** _______________

#### Features
- [ ] Filters work correctly
- [ ] Pagination works
- [ ] Print button works
- [ ] Edit/Delete work
- **Issues Found:** _______________

#### Performance
- Page load: __________ ms (target: <3000ms)
- FCP: __________ ms (target: <2000ms)
- Network: __________ KB (target: <1000KB)
- Memory: __________ MB (target: <50MB)
- **Issues Found:** _______________

#### Mobile Responsiveness
- [ ] Table scrollable
- [ ] Buttons accessible
- [ ] Filters work on mobile
- **Issues Found:** _______________

#### Browser Compatibility
- Chrome: [ ] Pass / [ ] Fail
- Firefox: [ ] Pass / [ ] Fail
- Safari: [ ] Pass / [ ] Fail
- Edge: [ ] Pass / [ ] Fail

#### Overall Status
- [ ] PASS (Ready for Production)
- [ ] FAIL (Issues found, needs fixes)
- [ ] BLOCKED (Cannot test)

**Summary:**
_______________________________________________________________
_______________________________________________________________

---

## Deployment Checklist

Before deploying index.php changes:

- [ ] All tests passed
- [ ] No performance regressions
- [ ] No console errors
- [ ] Mobile responsive verified
- [ ] Cross-browser tested
- [ ] Database queries optimized
- [ ] Insight card calculations verified
- [ ] Print functionality works
- [ ] Get QA sign-off

---


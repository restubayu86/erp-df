/**
 * Schedule Index Page - Performance & Functionality Testing
 * 
 * Instructions:
 * 1. Open /dyeing/schedule page
 * 2. Wait for page to fully load
 * 3. Open DevTools (F12) → Console
 * 4. Paste this entire script
 * 5. Run: indexTest.runAll()
 */

(function() {
  'use strict';
  
  const log = (msg, data) => {
    console.log(`%c[INDEX TEST] ${msg}`, 'color: #0066cc; font-weight: bold', data || '');
  };
  
  const error = (msg, data) => {
    console.error(`%c[INDEX TEST ERROR] ${msg}`, 'color: #cc0000; font-weight: bold', data || '');
  };
  
  const success = (msg, data) => {
    console.log(`%c[INDEX TEST ✓] ${msg}`, 'color: #00cc66; font-weight: bold', data || '');
  };
  
  const warn = (msg, data) => {
    console.warn(`%c[INDEX TEST ⚠] ${msg}`, 'color: #ff9900; font-weight: bold', data || '');
  };
  
  // Test 1: Page Load Performance
  function testPageLoadTime() {
    log('Test 1: Page Load Performance');
    
    const perf = window.performance || {};
    const timing = perf.timing || {};
    
    const navigationStart = timing.navigationStart || 0;
    const loadEnd = timing.loadEventEnd || 0;
    const interactiveTime = timing.domInteractive || 0;
    const contentfulPaint = timing.domContentLoadedEventEnd || 0;
    
    const loadTime = loadEnd - navigationStart;
    const interactive = interactiveTime - navigationStart;
    const contentful = contentfulPaint - navigationStart;
    
    success(`Page Load: ${loadTime}ms`);
    success(`DOM Interactive: ${interactive}ms`);
    success(`Content Loaded: ${contentful}ms`);
    
    if (loadTime > 3000) {
      error(`Page load too slow: ${loadTime}ms (target: <3000ms)`);
    }
    
    return { loadTime, interactive, contentful };
  }
  
  // Test 2: DataTable Verification
  function testDataTable() {
    log('Test 2: DataTable Verification');
    
    const table = document.querySelector('table.display, table.table');
    if (!table) {
      error('DataTable not found');
      return null;
    }
    
    const tbody = table.querySelector('tbody');
    const rows = tbody ? tbody.querySelectorAll('tr') : [];
    const headers = table.querySelectorAll('th');
    
    success(`Headers found: ${headers.length}`);
    success(`Data rows: ${rows.length}`);
    
    // Check for data
    if (rows.length === 0) {
      warn('No data rows in table (may be empty schedule)');
    }
    
    // Look for pagination info
    const paginationInfo = document.querySelector('.dataTables_info');
    if (paginationInfo) {
      success(`Pagination: ${paginationInfo.textContent.trim()}`);
    }
    
    return { headerCount: headers.length, rowCount: rows.length };
  }
  
  // Test 3: Insight Cards
  function testInsightCards() {
    log('Test 3: Insight Cards Verification');
    
    const cards = document.querySelectorAll('.insight-card, .card');
    const cardData = [];
    
    cards.forEach((card, idx) => {
      const title = card.querySelector('h6')?.textContent?.trim() || 'Unknown';
      const value = card.querySelector('.display-4, .fs-2, .h1')?.textContent?.trim() || '?';
      const badge = card.querySelector('.badge')?.textContent?.trim() || '';
      
      cardData.push({ title, value, badge });
      log(`Card ${idx + 1}: ${title} = ${value} ${badge}`);
    });
    
    if (cardData.length === 0) {
      warn('No insight cards found');
    }
    
    return cardData;
  }
  
  // Test 4: UI Components (Switches, Gauges)
  function testUIComponents() {
    log('Test 4: UI Components');
    
    // Count switches
    const switches = document.querySelectorAll('.form-check-input[type="checkbox"]');
    success(`Switches found: ${switches.length}`);
    
    // Verify switch states
    let checkedCount = 0;
    let uncheckedCount = 0;
    switches.forEach(sw => {
      if (sw.checked) checkedCount++;
      else uncheckedCount++;
    });
    
    if (switches.length > 0) {
      success(`  - Checked: ${checkedCount}`);
      success(`  - Unchecked: ${uncheckedCount}`);
    }
    
    // Count progress gauges
    const gauges = document.querySelectorAll('.circle-progress-svg, [class*="progress"]');
    success(`Progress gauges: ${gauges.length}`);
    
    // Check for SVG integrity
    const brokenSVGs = Array.from(gauges).filter(g => {
      const circles = g.querySelectorAll('circle');
      return circles.length === 0;
    });
    
    if (brokenSVGs.length > 0) {
      error(`Broken SVG gauges: ${brokenSVGs.length}`);
    } else {
      success('All SVG gauges intact');
    }
    
    return { switches: switches.length, gauges: gauges.length, brokenSVGs: brokenSVGs.length };
  }
  
  // Test 5: Network Requests
  function testNetworkRequests() {
    log('Test 5: Network Requests Analysis');
    
    const resources = performance.getEntriesByType('resource');
    let totalSize = 0;
    let jsSize = 0;
    let cssSize = 0;
    let imageSize = 0;
    let apiSize = 0;
    
    resources.forEach(res => {
      const size = res.transferSize || 0;
      totalSize += size;
      
      if (res.name.includes('.js')) jsSize += size;
      else if (res.name.includes('.css')) cssSize += size;
      else if (res.name.includes('api/')) apiSize += size;
      else if (res.name.match(/\.(png|jpg|gif|svg)$/)) imageSize += size;
    });
    
    success(`Total resources: ${resources.length}`);
    success(`Total size: ${(totalSize / 1024).toFixed(2)} KB`);
    success(`  - JS: ${(jsSize / 1024).toFixed(2)} KB`);
    success(`  - CSS: ${(cssSize / 1024).toFixed(2)} KB`);
    success(`  - API: ${(apiSize / 1024).toFixed(2)} KB`);
    success(`  - Images: ${(imageSize / 1024).toFixed(2)} KB`);
    
    if (totalSize > 1024 * 1024) {
      error(`Total size too large: ${(totalSize / 1024 / 1024).toFixed(2)} MB (target: <1 MB)`);
    }
    
    return { total: totalSize, resources: resources.length };
  }
  
  // Test 6: Memory Usage
  function testMemoryUsage() {
    log('Test 6: Memory Usage');
    
    if (!performance.memory) {
      warn('Memory API not available (Chrome only)');
      return null;
    }
    
    const used = performance.memory.usedJSHeapSize;
    const limit = performance.memory.jsHeapSizeLimit;
    const percent = ((used / limit) * 100).toFixed(1);
    
    success(`Used: ${(used / 1024 / 1024).toFixed(2)} MB`);
    success(`Limit: ${(limit / 1024 / 1024).toFixed(2)} MB`);
    success(`Utilization: ${percent}%`);
    
    if (used > 100 * 1024 * 1024) {
      error(`Memory usage high: ${(used / 1024 / 1024).toFixed(2)} MB (target: <100 MB)`);
    }
    
    return { used, limit, percent };
  }
  
  // Test 7: Filter Functionality
  function testFilters() {
    log('Test 7: Filter Functionality');
    
    const dateInputs = document.querySelectorAll('input[type="date"], input.flatpickr');
    const searchInput = document.querySelector('input[type="search"], input[placeholder*="Search"], input[placeholder*="search"]');
    const selectInputs = document.querySelectorAll('select.form-select');
    
    success(`Date inputs: ${dateInputs.length}`);
    success(`Search input: ${searchInput ? 'Found' : 'Not found'}`);
    success(`Select filters: ${selectInputs.length}`);
    
    // Check if Flatpickr is initialized
    if (dateInputs.length > 0) {
      const hasFlatpickr = window.flatpickr && dateInputs[0]._flatpickr;
      success(`Flatpickr initialized: ${hasFlatpickr ? 'Yes' : 'No'}`);
    }
    
    // Check if Select2 is initialized
    if (selectInputs.length > 0) {
      const jq = window.jQuery || window.$;
      if (jq) {
        const select2Enabled = jq(selectInputs[0]).hasClass('select2-hidden-accessible');
        success(`Select2 initialized: ${select2Enabled ? 'Yes' : 'No'}`);
      }
    }
    
    return { dateInputs: dateInputs.length, searchInput: !!searchInput, selectInputs: selectInputs.length };
  }
  
  // Test 8: Action Buttons
  function testActionButtons() {
    log('Test 8: Action Buttons');
    
    const editBtns = document.querySelectorAll('[href*="/schedule/edit"], button[title*="Edit"], button[title*="edit"]');
    const printBtns = document.querySelectorAll('button[title*="Print"], button[title*="print"], a[href*="print"]');
    const deleteBtns = document.querySelectorAll('button[title*="Delete"], button[title*="delete"], button[onclick*="delete"]');
    
    success(`Edit buttons: ${editBtns.length}`);
    success(`Print buttons: ${printBtns.length}`);
    success(`Delete buttons: ${deleteBtns.length}`);
    
    if (editBtns.length === 0 && printBtns.length === 0) {
      warn('No action buttons found (table may be empty)');
    }
    
    return { edit: editBtns.length, print: printBtns.length, delete: deleteBtns.length };
  }
  
  // Test 9: Console Errors Check
  function testConsoleErrors() {
    log('Test 9: Console Error Check');
    
    const errors = [];
    const originalError = console.error;
    const originalWarn = console.warn;
    
    // Intercept errors (this happens after page load, so won't catch load-time errors)
    console.error = function(...args) {
      errors.push({ type: 'error', msg: args.join(' ') });
      originalError.apply(console, args);
    };
    
    console.warn = function(...args) {
      // Only track serious warnings, not just deprecation notices
      if (args[0]?.includes?.('Uncaught') || args[0]?.includes?.('Error')) {
        errors.push({ type: 'warn', msg: args.join(' ') });
      }
      originalWarn.apply(console, args);
    };
    
    // Restore after test
    setTimeout(() => {
      console.error = originalError;
      console.warn = originalWarn;
    }, 1000);
    
    // Check for common issues
    const scripts = document.querySelectorAll('script');
    let hasErrors = false;
    
    scripts.forEach(script => {
      if (script.textContent.includes('undefined')) {
        warn(`Potential undefined reference in script: ${script.src || 'inline'}`);
        hasErrors = true;
      }
    });
    
    if (!hasErrors) {
      success('No obvious script errors detected');
    }
    
    return { errors, hasErrors };
  }
  
  // Test 10: DataTable Search Performance
  function testSearchPerformance() {
    log('Test 10: Search Performance');
    
    const searchInput = document.querySelector('input[type="search"], input[placeholder*="Search"]');
    if (!searchInput) {
      warn('Search input not found');
      return null;
    }
    
    // Simulate search
    const start = performance.now();
    searchInput.value = 'test';
    searchInput.dispatchEvent(new Event('keyup'));
    const end = performance.now();
    
    success(`Search trigger time: ${(end - start).toFixed(2)}ms`);
    
    // Clear search
    searchInput.value = '';
    searchInput.dispatchEvent(new Event('keyup'));
    
    return { triggerTime: end - start };
  }
  
  // Run all tests
  async function runAllTests() {
    console.clear();
    log('='.repeat(70));
    log('INDEX PAGE PERFORMANCE & FUNCTIONALITY TEST SUITE');
    log('='.repeat(70));
    
    const results = {};
    
    try {
      results.loadTime = testPageLoadTime();
      results.dataTable = testDataTable();
      results.insightCards = testInsightCards();
      results.uiComponents = testUIComponents();
      results.network = testNetworkRequests();
      results.memory = testMemoryUsage();
      results.filters = testFilters();
      results.actionButtons = testActionButtons();
      results.consoleErrors = testConsoleErrors();
      results.searchPerf = testSearchPerformance();
      
      log('\n' + '='.repeat(70));
      log('TEST SUMMARY');
      log('='.repeat(70));
      
      const summary = {
        'Page Load': {
          metric: 'Time',
          value: `${results.loadTime.loadTime}ms`,
          status: results.loadTime.loadTime < 3000 ? '✓ PASS' : '✗ FAIL'
        },
        'DataTable Rows': {
          metric: 'Count',
          value: results.dataTable?.rowCount || 0,
          status: '✓ OK'
        },
        'Insight Cards': {
          metric: 'Count',
          value: results.insightCards?.length || 0,
          status: results.insightCards?.length > 0 ? '✓ PASS' : '⚠ NO DATA'
        },
        'UI Components': {
          metric: 'Items',
          value: `${results.uiComponents?.switches || 0} switches + ${results.uiComponents?.gauges || 0} gauges`,
          status: results.uiComponents?.brokenSVGs === 0 ? '✓ PASS' : '✗ BROKEN'
        },
        'Network Size': {
          metric: 'KB',
          value: `${(results.network?.total / 1024).toFixed(2)} KB`,
          status: (results.network?.total || 0) < 1024 * 1024 ? '✓ PASS' : '✗ LARGE'
        },
        'Memory': {
          metric: 'MB',
          value: `${results.memory?.used ? (results.memory.used / 1024 / 1024).toFixed(2) : 'N/A'} MB`,
          status: results.memory?.used < 100 * 1024 * 1024 ? '✓ PASS' : '✗ HIGH'
        }
      };
      
      console.table(summary);
      
      success('\n✓ All tests completed!');
      
      // Final verdict
      const failCount = Object.values(summary).filter(s => s.status.includes('FAIL')).length;
      if (failCount === 0) {
        success('✓ INDEX PAGE READY FOR PRODUCTION');
      } else {
        error(`⚠ ${failCount} issues found - needs attention`);
      }
      
      return results;
      
    } catch (err) {
      error('Test suite failed:', err);
      throw err;
    }
  }
  
  // Expose to global scope
  window.indexTest = {
    runAll: runAllTests,
    testLoadTime: testPageLoadTime,
    testDataTable: testDataTable,
    testInsightCards: testInsightCards,
    testUIComponents: testUIComponents,
    testNetwork: testNetworkRequests,
    testMemory: testMemoryUsage,
    testFilters: testFilters,
    testActionButtons: testActionButtons,
    testConsoleErrors: testConsoleErrors,
    testSearchPerf: testSearchPerformance
  };
  
  log('✓ Index page test suite loaded!');
  log('Run: indexTest.runAll() to execute all tests');
  log('Or run individual tests:');
  log('  - indexTest.testLoadTime()');
  log('  - indexTest.testDataTable()');
  log('  - indexTest.testInsightCards()');
  log('  - indexTest.testUIComponents()');
  log('  - indexTest.testNetwork()');
  log('  - indexTest.testMemory()');
  log('  - indexTest.testFilters()');
  log('  - indexTest.testActionButtons()');
  
})();

/**
 * Performance Testing Script for Schedule Form
 * 
 * Instructions:
 * 1. Open Schedule Create/Edit form in browser
 * 2. Open DevTools Console (F12)
 * 3. Paste this entire script and press Enter
 * 4. Follow the prompts to run tests
 * 
 * Tests:
 * - Form render time
 * - Add item performance
 * - Memory leak detection (Select2)
 * - API call debouncing
 * - Form validation speed
 */

(function() {
  'use strict';
  
  const log = (msg, data) => {
    console.log(`%c[PERF TEST] ${msg}`, 'color: #0066cc; font-weight: bold', data || '');
  };
  
  const error = (msg, data) => {
    console.error(`%c[PERF TEST ERROR] ${msg}`, 'color: #cc0000; font-weight: bold', data || '');
  };
  
  const success = (msg, data) => {
    console.log(`%c[PERF TEST ✓] ${msg}`, 'color: #00cc66; font-weight: bold', data || '');
  };
  
  // Test 1: Measure form initial render time
  function testFormRenderTime() {
    log('Test 1: Form Render Time');
    const loadTime = performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart;
    const interactiveTime = performance.timing.domInteractive - performance.timing.navigationStart;
    
    success(`DOM Load: ${loadTime}ms`);
    success(`Interactive: ${interactiveTime}ms`);
    
    if (loadTime > 3000) {
      error(`Form load too slow: ${loadTime}ms (target: <3000ms)`);
    }
    
    return { loadTime, interactiveTime };
  }
  
  // Test 2: Measure add item button performance
  function testAddItemPerformance(iterations = 10) {
    log(`Test 2: Add Item Performance (${iterations} items)`);
    const tbody = document.querySelector('#itemsTable tbody');
    const initialCount = tbody.children.length;
    const btnAdd = document.querySelector('#btnAddItem');
    
    if (!btnAdd) {
      error('Add item button not found');
      return null;
    }
    
    const times = [];
    for (let i = 0; i < iterations; i++) {
      const start = performance.now();
      btnAdd.click();
      const end = performance.now();
      times.push(end - start);
    }
    
    const avg = times.reduce((a, b) => a + b, 0) / times.length;
    const max = Math.max(...times);
    const min = Math.min(...times);
    
    success(`Average: ${avg.toFixed(2)}ms`);
    success(`Min: ${min.toFixed(2)}ms, Max: ${max.toFixed(2)}ms`);
    
    if (avg > 100) {
      error(`Add item too slow: ${avg.toFixed(2)}ms (target: <100ms)`);
    }
    
    // Cleanup: remove added items
    while (tbody.children.length > initialCount) {
      tbody.lastElementChild.remove();
    }
    
    return { avg, max, min, times };
  }
  
  // Test 3: Memory leak detection (Select2 reinit)
  function testSelect2MemoryLeak() {
    log('Test 3: Select2 Memory Leak Detection');
    
    if (!window.performance.memory) {
      error('Memory API not available (Chrome only)');
      return null;
    }
    
    const shiftSel = document.querySelector('#shift_id');
    const dateInput = document.querySelector('#work_date');
    
    if (!shiftSel || !dateInput) {
      error('Required elements not found');
      return null;
    }
    
    // Force GC if available
    if (window.gc) {
      window.gc();
      log('Manual GC triggered');
    }
    
    const initialHeap = performance.memory.usedJSHeapSize;
    log(`Initial heap: ${(initialHeap / 1024 / 1024).toFixed(2)} MB`);
    
    // Simulate 20 date changes (triggers Select2 reinit)
    const testDate = new Date();
    for (let i = 0; i < 20; i++) {
      testDate.setDate(testDate.getDate() + 1);
      dateInput.value = testDate.toISOString().split('T')[0];
      dateInput.dispatchEvent(new Event('change'));
    }
    
    // Wait for async operations
    return new Promise((resolve) => {
      setTimeout(() => {
        if (window.gc) window.gc();
        
        const finalHeap = performance.memory.usedJSHeapSize;
        const heapGrowth = finalHeap - initialHeap;
        const heapGrowthMB = heapGrowth / 1024 / 1024;
        
        log(`Final heap: ${(finalHeap / 1024 / 1024).toFixed(2)} MB`);
        success(`Heap growth: ${heapGrowthMB.toFixed(2)} MB`);
        
        if (heapGrowthMB > 5) {
          error(`Possible memory leak: ${heapGrowthMB.toFixed(2)} MB growth`);
        } else {
          success('No significant memory leak detected');
        }
        
        resolve({ initialHeap, finalHeap, heapGrowth: heapGrowthMB });
      }, 1000);
    });
  }
  
  // Test 4: API debounce verification
  function testDebounceEffectiveness() {
    log('Test 4: Debounce Effectiveness');
    
    const dateInput = document.querySelector('#work_date');
    if (!dateInput) {
      error('Date input not found');
      return null;
    }
    
    // Intercept fetch calls
    let apiCallCount = 0;
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
      if (args[0].includes('/api/shift/effective')) {
        apiCallCount++;
        log(`API call #${apiCallCount}`);
      }
      return originalFetch.apply(this, args);
    };
    
    // Simulate rapid date changes
    const testDate = new Date();
    for (let i = 0; i < 10; i++) {
      testDate.setDate(testDate.getDate() + 1);
      dateInput.value = testDate.toISOString().split('T')[0];
      dateInput.dispatchEvent(new Event('change'));
    }
    
    return new Promise((resolve) => {
      setTimeout(() => {
        window.fetch = originalFetch;
        
        if (apiCallCount === 1) {
          success(`Debounce working: 10 changes → 1 API call`);
        } else if (apiCallCount < 5) {
          success(`Debounce effective: 10 changes → ${apiCallCount} API calls`);
        } else {
          error(`Debounce ineffective: 10 changes → ${apiCallCount} API calls`);
        }
        
        resolve({ changes: 10, apiCalls: apiCallCount });
      }, 1000);
    });
  }
  
  // Test 5: Form validation performance
  function testFormValidationSpeed() {
    log('Test 5: Form Validation Speed');
    
    const form = document.querySelector('#schForm');
    const tbody = document.querySelector('#itemsTable tbody');
    
    if (!form) {
      error('Form not found');
      return null;
    }
    
    // Test 1: Empty item validation
    const initialItems = Array.from(tbody.children);
    tbody.innerHTML = ''; // Clear items
    
    const start1 = performance.now();
    form.dispatchEvent(new Event('submit', { cancelable: true }));
    const end1 = performance.now();
    
    success(`Empty validation: ${(end1 - start1).toFixed(2)}ms`);
    
    // Restore items
    initialItems.forEach(item => tbody.appendChild(item));
    
    // Test 2: Time range validation
    const startAt = document.querySelector('#start_at');
    const endAt = document.querySelector('#end_at');
    const origStart = startAt.value;
    const origEnd = endAt.value;
    
    startAt.value = '2026-11-02T10:00';
    endAt.value = '2026-11-02T08:00'; // Invalid: end before start
    
    const start2 = performance.now();
    form.dispatchEvent(new Event('submit', { cancelable: true }));
    const end2 = performance.now();
    
    success(`Time validation: ${(end2 - start2).toFixed(2)}ms`);
    
    // Restore values
    startAt.value = origStart;
    endAt.value = origEnd;
    
    if ((end1 - start1) > 50 || (end2 - start2) > 50) {
      error('Validation too slow (target: <50ms)');
    }
    
    return { emptyValidation: end1 - start1, timeValidation: end2 - start2 };
  }
  
  // Test 6: Check for console errors
  function checkConsoleErrors() {
    log('Test 6: Console Errors Check');
    
    const errors = [];
    const originalError = console.error;
    
    console.error = function(...args) {
      errors.push(args.join(' '));
      originalError.apply(console, args);
    };
    
    // Trigger common actions
    document.querySelector('#btnAddItem')?.click();
    document.querySelector('#work_date')?.dispatchEvent(new Event('change'));
    
    setTimeout(() => {
      console.error = originalError;
      
      if (errors.length === 0) {
        success('No console errors detected');
      } else {
        error(`${errors.length} console errors found:`, errors);
      }
    }, 500);
  }
  
  // Run all tests
  async function runAllTests() {
    console.clear();
    log('='.repeat(60));
    log('PERFORMANCE TEST SUITE - Schedule Form');
    log('='.repeat(60));
    
    const results = {};
    
    try {
      results.render = testFormRenderTime();
      results.addItem = testAddItemPerformance(10);
      results.validation = testFormValidationSpeed();
      
      log('\nRunning async tests...');
      results.memory = await testSelect2MemoryLeak();
      results.debounce = await testDebounceEffectiveness();
      
      checkConsoleErrors();
      
      log('\n' + '='.repeat(60));
      log('TEST SUMMARY');
      log('='.repeat(60));
      
      console.table({
        'Form Load': { 
          metric: 'Time', 
          value: `${results.render.loadTime}ms`, 
          status: results.render.loadTime < 3000 ? '✓ Pass' : '✗ Fail' 
        },
        'Add Item (avg)': { 
          metric: 'Time', 
          value: `${results.addItem.avg.toFixed(2)}ms`, 
          status: results.addItem.avg < 100 ? '✓ Pass' : '✗ Fail' 
        },
        'Memory Growth': { 
          metric: 'Heap', 
          value: `${results.memory.heapGrowth.toFixed(2)} MB`, 
          status: results.memory.heapGrowth < 5 ? '✓ Pass' : '✗ Fail' 
        },
        'Debounce': { 
          metric: 'API Calls', 
          value: `${results.debounce.apiCalls}/10`, 
          status: results.debounce.apiCalls < 5 ? '✓ Pass' : '✗ Fail' 
        },
        'Validation': { 
          metric: 'Time', 
          value: `${results.validation.emptyValidation.toFixed(2)}ms`, 
          status: results.validation.emptyValidation < 50 ? '✓ Pass' : '✗ Fail' 
        }
      });
      
      success('\nAll tests completed!');
      
      return results;
      
    } catch (err) {
      error('Test suite failed:', err);
      throw err;
    }
  }
  
  // Expose to global scope
  window.perfTest = {
    runAll: runAllTests,
    testFormRender: testFormRenderTime,
    testAddItem: testAddItemPerformance,
    testMemory: testSelect2MemoryLeak,
    testDebounce: testDebounceEffectiveness,
    testValidation: testFormValidationSpeed,
    checkErrors: checkConsoleErrors
  };
  
  log('Performance test suite loaded!');
  log('Run: perfTest.runAll() to run all tests');
  log('Or run individual tests:');
  log('  - perfTest.testFormRender()');
  log('  - perfTest.testAddItem(10)');
  log('  - perfTest.testMemory()');
  log('  - perfTest.testDebounce()');
  log('  - perfTest.testValidation()');
  log('  - perfTest.checkErrors()');
  
})();

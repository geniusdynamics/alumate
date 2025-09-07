// ABOUTME: Diagnostic script to capture screenshots and check application state for blank screen issue
// ABOUTME: This script uses Playwright to take screenshots and analyze the current application state

import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

async function diagnoseApplication() {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();
    
    const diagnosticResults = {
        timestamp: new Date().toISOString(),
        tests: [],
        screenshots: [],
        consoleLogs: [],
        networkErrors: [],
        pageErrors: []
    };
    
    // Capture console logs
    page.on('console', msg => {
        diagnosticResults.consoleLogs.push({
            type: msg.type(),
            text: msg.text(),
            timestamp: new Date().toISOString()
        });
    });
    
    // Capture page errors
    page.on('pageerror', error => {
        diagnosticResults.pageErrors.push({
            message: error.message,
            stack: error.stack,
            timestamp: new Date().toISOString()
        });
    });
    
    // Capture network failures
    page.on('requestfailed', request => {
        diagnosticResults.networkErrors.push({
            url: request.url(),
            failure: request.failure()?.errorText,
            timestamp: new Date().toISOString()
        });
    });
    
    const urls = [
        { name: 'Homepage', url: 'http://127.0.0.1:8081' },
        { name: 'Design_System', url: 'http://127.0.0.1:8081/design-system' }
    ];
    
    for (const { name, url } of urls) {
        try {
            console.log(`\n=== Testing ${name} at ${url} ===`);
            
            // Navigate to page
            const response = await page.goto(url, { 
                waitUntil: 'networkidle',
                timeout: 30000 
            });
            
            // Wait a bit for any dynamic content
            await page.waitForTimeout(3000);
            
            // Take screenshot
            const screenshotPath = path.join(__dirname, 'diagnostic-screenshots', `${name}_diagnostic.png`);
            await fs.promises.mkdir(path.dirname(screenshotPath), { recursive: true });
            await page.screenshot({ path: screenshotPath, fullPage: true });
            
            // Get page title and content
            const title = await page.title();
            const bodyText = await page.evaluate(() => document.body.innerText);
            const bodyHTML = await page.evaluate(() => document.body.innerHTML);
            
            // Check for specific elements
            const hasContent = bodyText.trim().length > 0;
            const hasVisibleElements = await page.evaluate(() => {
                const elements = document.querySelectorAll('*');
                let visibleCount = 0;
                for (let el of elements) {
                    const style = window.getComputedStyle(el);
                    if (style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0') {
                        visibleCount++;
                    }
                }
                return visibleCount;
            });
            
            // Check for Vue app mounting
            const vueAppMounted = await page.evaluate(() => {
                return !!document.querySelector('[data-v-app]') || !!document.querySelector('#app');
            });
            
            // Check for CSS loading
            const cssLoaded = await page.evaluate(() => {
                const links = document.querySelectorAll('link[rel="stylesheet"]');
                return links.length > 0;
            });
            
            // Check for JavaScript errors in console
            const jsErrors = diagnosticResults.consoleLogs.filter(log => log.type === 'error');
            
            const testResult = {
                name,
                url,
                status: response?.status() || 'unknown',
                title,
                hasContent,
                contentLength: bodyText.length,
                visibleElements: hasVisibleElements,
                vueAppMounted,
                cssLoaded,
                jsErrorCount: jsErrors.length,
                screenshot: screenshotPath,
                timestamp: new Date().toISOString()
            };
            
            diagnosticResults.tests.push(testResult);
            diagnosticResults.screenshots.push(screenshotPath);
            
            console.log(`✓ ${name}: Status ${testResult.status}, Title: "${title}", Content: ${hasContent ? 'Yes' : 'No'} (${bodyText.length} chars)`);
            console.log(`  Visible elements: ${hasVisibleElements}, Vue mounted: ${vueAppMounted}, CSS loaded: ${cssLoaded}`);
            console.log(`  JS errors: ${jsErrors.length}, Screenshot: ${screenshotPath}`);
            
            if (!hasContent || bodyText.length < 50) {
                console.log(`⚠️  WARNING: ${name} appears to have minimal content!`);
                console.log(`  Body text preview: "${bodyText.substring(0, 200)}..."`);
            }
            
        } catch (error) {
            console.error(`❌ Error testing ${name}:`, error.message);
            diagnosticResults.tests.push({
                name,
                url,
                error: error.message,
                timestamp: new Date().toISOString()
            });
        }
    }
    
    // Save diagnostic report
    const reportPath = path.join(__dirname, 'diagnostic-screenshots', 'diagnostic_report.json');
    await fs.promises.writeFile(reportPath, JSON.stringify(diagnosticResults, null, 2));
    
    console.log(`\n=== DIAGNOSTIC SUMMARY ===`);
    console.log(`Total tests: ${diagnosticResults.tests.length}`);
    console.log(`Console logs: ${diagnosticResults.consoleLogs.length}`);
    console.log(`Page errors: ${diagnosticResults.pageErrors.length}`);
    console.log(`Network errors: ${diagnosticResults.networkErrors.length}`);
    console.log(`Report saved: ${reportPath}`);
    
    if (diagnosticResults.pageErrors.length > 0) {
        console.log(`\n=== PAGE ERRORS ===`);
        diagnosticResults.pageErrors.forEach(error => {
            console.log(`❌ ${error.message}`);
        });
    }
    
    if (diagnosticResults.networkErrors.length > 0) {
        console.log(`\n=== NETWORK ERRORS ===`);
        diagnosticResults.networkErrors.forEach(error => {
            console.log(`🌐 Failed: ${error.url} - ${error.failure}`);
        });
    }
    
    await browser.close();
    return diagnosticResults;
}

// Run the diagnostic
diagnoseApplication().catch(console.error);
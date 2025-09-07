import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

async function monitorWebpages() {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext();
    const page = await context.newPage();
    
    const results = {
        timestamp: new Date().toISOString(),
        baseUrl: 'http://127.0.0.1:8080',
        tests: []
    };
    
    console.log('🔍 Starting webpage monitoring...');
    
    // Test Homepage
    try {
        console.log('Testing Homepage...');
        const startTime = Date.now();
        await page.goto('http://127.0.0.1:8080', { waitUntil: 'networkidle' });
        const loadTime = Date.now() - startTime;
        
        const title = await page.title();
        const hasContent = await page.locator('body').count() > 0;
        const buttons = await page.locator('button').count();
        const links = await page.locator('a').count();
        const forms = await page.locator('form').count();
        
        // Check for any console errors
        const errors = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });
        
        await page.screenshot({ path: 'test-screenshots/homepage_monitor.png', fullPage: true });
        
        results.tests.push({
            page: 'Homepage',
            url: 'http://127.0.0.1:8080',
            status: 'passed',
            loadTime: loadTime,
            title: title,
            hasContent: hasContent,
            interactiveElements: {
                buttons: buttons,
                links: links,
                forms: forms
            },
            errors: errors
        });
        
        console.log(`✅ Homepage loaded in ${loadTime}ms`);
        console.log(`   Title: ${title}`);
        console.log(`   Interactive elements: ${buttons} buttons, ${links} links, ${forms} forms`);
        
    } catch (error) {
        results.tests.push({
            page: 'Homepage',
            url: 'http://127.0.0.1:8080',
            status: 'failed',
            error: error.message
        });
        console.log(`❌ Homepage test failed: ${error.message}`);
    }
    
    // Test Design System Page
    try {
        console.log('Testing Design System page...');
        const startTime = Date.now();
        await page.goto('http://127.0.0.1:8080/design-system', { waitUntil: 'networkidle' });
        const loadTime = Date.now() - startTime;
        
        const title = await page.title();
        const hasContent = await page.locator('body').count() > 0;
        const buttons = await page.locator('button').count();
        const links = await page.locator('a').count();
        const components = await page.locator('[class*="component"], [data-component]').count();
        
        await page.screenshot({ path: 'test-screenshots/design_system_monitor.png', fullPage: true });
        
        results.tests.push({
            page: 'Design System',
            url: 'http://127.0.0.1:8080/design-system',
            status: 'passed',
            loadTime: loadTime,
            title: title,
            hasContent: hasContent,
            interactiveElements: {
                buttons: buttons,
                links: links,
                components: components
            }
        });
        
        console.log(`✅ Design System page loaded in ${loadTime}ms`);
        console.log(`   Title: ${title}`);
        console.log(`   Interactive elements: ${buttons} buttons, ${links} links, ${components} components`);
        
    } catch (error) {
        results.tests.push({
            page: 'Design System',
            url: 'http://127.0.0.1:8080/design-system',
            status: 'failed',
            error: error.message
        });
        console.log(`❌ Design System test failed: ${error.message}`);
    }
    
    // Save results
    const reportPath = 'test-screenshots/monitor_report.json';
    fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));
    
    console.log('\n📊 Monitoring Summary:');
    console.log(`   Total tests: ${results.tests.length}`);
    console.log(`   Passed: ${results.tests.filter(t => t.status === 'passed').length}`);
    console.log(`   Failed: ${results.tests.filter(t => t.status === 'failed').length}`);
    console.log(`   Report saved: ${reportPath}`);
    
    await browser.close();
}

monitorWebpages().catch(console.error);
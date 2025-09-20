// ABOUTME: Playwright end-to-end testing script for comprehensive web application testing
// ABOUTME: Tests interactive elements, navigation, forms, and captures screenshots for documentation

import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

// Test configuration
const BASE_URL = 'http://127.0.0.1:8080';
const SCREENSHOT_DIR = './test-screenshots';
const TEST_TIMEOUT = 30000;

// Ensure screenshot directory exists
if (!fs.existsSync(SCREENSHOT_DIR)) {
    fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

class WebTester {
    constructor() {
        this.browser = null;
        this.page = null;
        this.testResults = [];
    }

    async init() {
        console.log('🚀 Initializing Playwright browser...');
        this.browser = await chromium.launch({ 
            headless: false,
            slowMo: 500 // Slow down for better visibility
        });
        
        const context = await this.browser.newContext({
            viewport: { width: 1920, height: 1080 },
            userAgent: 'AlumateTestBot/1.0 (Playwright)'
        });
        
        this.page = await context.newPage();
        
        // Set longer timeout for all operations
        this.page.setDefaultTimeout(TEST_TIMEOUT);
        
        console.log('✅ Browser initialized successfully\n');
    }

    async takeScreenshot(name, description) {
        const filename = `${name.replace(/[^a-zA-Z0-9]/g, '_')}_${Date.now()}.png`;
        const filepath = path.join(SCREENSHOT_DIR, filename);
        
        await this.page.screenshot({ 
            path: filepath, 
            fullPage: true 
        });
        
        console.log(`📸 Screenshot saved: ${filename} - ${description}`);
        return filepath;
    }

    async testPageLoad(url, pageName) {
        console.log(`\n=== TESTING PAGE: ${pageName} ===`);
        console.log(`URL: ${url}`);
        
        try {
            const startTime = Date.now();
            await this.page.goto(url, { waitUntil: 'networkidle' });
            const loadTime = Date.now() - startTime;
            
            // Take initial screenshot
            await this.takeScreenshot(`${pageName}_initial`, `Initial load of ${pageName}`);
            
            // Check for basic page elements
            const title = await this.page.title();
            const hasContent = await this.page.locator('body').count() > 0;
            
            const result = {
                page: pageName,
                url: url,
                status: 'PASS',
                loadTime: `${loadTime}ms`,
                title: title,
                hasContent: hasContent
            };
            
            console.log(`✅ PASS - ${pageName} loaded in ${loadTime}ms`);
            console.log(`   Title: ${title}`);
            
            this.testResults.push(result);
            return true;
            
        } catch (error) {
            const result = {
                page: pageName,
                url: url,
                status: 'FAIL',
                error: error.message
            };
            
            console.log(`❌ FAIL - ${pageName}: ${error.message}`);
            this.testResults.push(result);
            return false;
        }
    }

    async testInteractiveElements() {
        console.log('\n=== TESTING INTERACTIVE ELEMENTS ===');
        
        try {
            // Test buttons
            const buttons = await this.page.locator('button, .btn, [role="button"]').all();
            console.log(`Found ${buttons.length} interactive buttons`);
            
            for (let i = 0; i < Math.min(buttons.length, 5); i++) {
                const button = buttons[i];
                const buttonText = await button.textContent() || `Button ${i + 1}`;
                
                try {
                    await button.scrollIntoViewIfNeeded();
                    await this.takeScreenshot(`button_${i + 1}_before`, `Before clicking: ${buttonText}`);
                    
                    await button.click();
                    await this.page.waitForTimeout(1000); // Wait for any animations
                    
                    await this.takeScreenshot(`button_${i + 1}_after`, `After clicking: ${buttonText}`);
                    console.log(`✅ Button clicked successfully: ${buttonText}`);
                    
                } catch (error) {
                    console.log(`⚠️  Button click failed: ${buttonText} - ${error.message}`);
                }
            }
            
            // Test links
            const links = await this.page.locator('a[href]').all();
            console.log(`Found ${links.length} links`);
            
            for (let i = 0; i < Math.min(links.length, 10); i++) {
                const link = links[i];
                const href = await link.getAttribute('href');
                const linkText = await link.textContent() || `Link ${i + 1}`;
                
                if (href && !href.startsWith('mailto:') && !href.startsWith('tel:') && !href.startsWith('#')) {
                    try {
                        await link.scrollIntoViewIfNeeded();
                        console.log(`🔗 Testing link: ${linkText} -> ${href}`);
                        
                        // Check if it's an external link
                        if (href.startsWith('http') && !href.includes('127.0.0.1') && !href.includes('localhost')) {
                            console.log(`   ⚠️  External link detected, skipping navigation test`);
                            continue;
                        }
                        
                        // For internal links, test navigation
                        if (href.startsWith('/') || href.includes('127.0.0.1') || href.includes('localhost')) {
                            await link.click();
                            await this.page.waitForTimeout(2000);
                            
                            const currentUrl = this.page.url();
                            await this.takeScreenshot(`link_${i + 1}_navigation`, `Navigation to: ${currentUrl}`);
                            console.log(`   ✅ Navigation successful to: ${currentUrl}`);
                            
                            // Go back to original page
                            await this.page.goBack();
                            await this.page.waitForTimeout(1000);
                        }
                        
                    } catch (error) {
                        console.log(`   ❌ Link test failed: ${linkText} - ${error.message}`);
                    }
                }
            }
            
        } catch (error) {
            console.log(`❌ Interactive elements test failed: ${error.message}`);
        }
    }

    async testForms() {
        console.log('\n=== TESTING FORMS ===');
        
        try {
            const forms = await this.page.locator('form').all();
            console.log(`Found ${forms.length} forms`);
            
            for (let i = 0; i < forms.length; i++) {
                const form = forms[i];
                
                try {
                    await form.scrollIntoViewIfNeeded();
                    await this.takeScreenshot(`form_${i + 1}_initial`, `Form ${i + 1} initial state`);
                    
                    // Find input fields
                    const inputs = await form.locator('input, textarea, select').all();
                    console.log(`Form ${i + 1} has ${inputs.length} input fields`);
                    
                    // Fill out form fields with test data
                    for (let j = 0; j < inputs.length; j++) {
                        const input = inputs[j];
                        const inputType = await input.getAttribute('type') || 'text';
                        const inputName = await input.getAttribute('name') || `field_${j}`;
                        
                        try {
                            switch (inputType.toLowerCase()) {
                                case 'email':
                                    await input.fill('test@example.com');
                                    break;
                                case 'text':
                                case 'search':
                                    await input.fill('Test Input');
                                    break;
                                case 'number':
                                    await input.fill('123');
                                    break;
                                case 'tel':
                                    await input.fill('555-1234');
                                    break;
                                case 'url':
                                    await input.fill('https://example.com');
                                    break;
                                default:
                                    if (await input.getAttribute('tagName') === 'TEXTAREA') {
                                        await input.fill('Test message content');
                                    }
                            }
                            
                            console.log(`   ✅ Filled ${inputName} (${inputType})`);
                            
                        } catch (error) {
                            console.log(`   ⚠️  Could not fill ${inputName}: ${error.message}`);
                        }
                    }
                    
                    await this.takeScreenshot(`form_${i + 1}_filled`, `Form ${i + 1} filled with test data`);
                    
                    // Look for submit button
                    const submitButton = await form.locator('button[type="submit"], input[type="submit"], .submit-btn').first();
                    
                    if (await submitButton.count() > 0) {
                        console.log(`   🔄 Testing form submission...`);
                        await submitButton.click();
                        await this.page.waitForTimeout(2000);
                        
                        await this.takeScreenshot(`form_${i + 1}_submitted`, `Form ${i + 1} after submission`);
                        console.log(`   ✅ Form submission test completed`);
                    }
                    
                } catch (error) {
                    console.log(`❌ Form ${i + 1} test failed: ${error.message}`);
                }
            }
            
        } catch (error) {
            console.log(`❌ Forms test failed: ${error.message}`);
        }
    }

    async testResponsiveDesign() {
        console.log('\n=== TESTING RESPONSIVE DESIGN ===');
        
        const viewports = [
            { name: 'Desktop', width: 1920, height: 1080 },
            { name: 'Tablet', width: 768, height: 1024 },
            { name: 'Mobile', width: 375, height: 667 }
        ];
        
        for (const viewport of viewports) {
            try {
                console.log(`📱 Testing ${viewport.name} viewport (${viewport.width}x${viewport.height})`);
                
                await this.page.setViewportSize({ 
                    width: viewport.width, 
                    height: viewport.height 
                });
                
                await this.page.waitForTimeout(1000); // Wait for responsive changes
                
                await this.takeScreenshot(
                    `responsive_${viewport.name.toLowerCase()}`, 
                    `${viewport.name} responsive view`
                );
                
                console.log(`   ✅ ${viewport.name} viewport test completed`);
                
            } catch (error) {
                console.log(`   ❌ ${viewport.name} viewport test failed: ${error.message}`);
            }
        }
        
        // Reset to desktop viewport
        await this.page.setViewportSize({ width: 1920, height: 1080 });
    }

    async generateReport() {
        console.log('\n=== GENERATING TEST REPORT ===');
        
        const report = {
            timestamp: new Date().toISOString(),
            baseUrl: BASE_URL,
            totalTests: this.testResults.length,
            passedTests: this.testResults.filter(r => r.status === 'PASS').length,
            failedTests: this.testResults.filter(r => r.status === 'FAIL').length,
            results: this.testResults
        };
        
        const reportPath = path.join(SCREENSHOT_DIR, 'test_report.json');
        fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
        
        console.log(`📊 Test report saved: ${reportPath}`);
        console.log(`\n=== FINAL SUMMARY ===`);
        console.log(`Total Tests: ${report.totalTests}`);
        console.log(`Passed: ${report.passedTests}`);
        console.log(`Failed: ${report.failedTests}`);
        console.log(`Success Rate: ${((report.passedTests / report.totalTests) * 100).toFixed(2)}%`);
        
        return report;
    }

    async cleanup() {
        if (this.browser) {
            await this.browser.close();
            console.log('🧹 Browser closed');
        }
    }
}

// Main test execution
async function runTests() {
    const tester = new WebTester();
    
    try {
        await tester.init();
        
        // Test main pages
        await tester.testPageLoad(BASE_URL, 'Homepage');
        await tester.testPageLoad(`${BASE_URL}/design-system`, 'Design System');
        
        // Test interactive elements on homepage
        await tester.page.goto(BASE_URL);
        await tester.testInteractiveElements();
        
        // Test forms
        await tester.testForms();
        
        // Test responsive design
        await tester.testResponsiveDesign();
        
        // Generate final report
        await tester.generateReport();
        
    } catch (error) {
        console.error('❌ Test execution failed:', error);
    } finally {
        await tester.cleanup();
    }
}

// Run the tests
runTests().catch(console.error);
// ABOUTME: Comprehensive test runner for schema-based tenant migration system
// ABOUTME: Validates all components including scripts, documentation, and configuration files

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Test configuration
const config = {
    projectRoot: process.cwd(),
    testResults: [],
    verbose: process.argv.includes('--verbose'),
    dryRun: process.argv.includes('--dry-run')
};

// ANSI color codes for console output
const colors = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    magenta: '\x1b[35m',
    cyan: '\x1b[36m'
};

// Utility functions
function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

function logSuccess(message) {
    log(`✓ ${message}`, 'green');
}

function logError(message) {
    log(`✗ ${message}`, 'red');
}

function logWarning(message) {
    log(`⚠ ${message}`, 'yellow');
}

function logInfo(message) {
    log(`ℹ ${message}`, 'blue');
}

function addTestResult(category, test, status, message = '', details = '') {
    config.testResults.push({
        category,
        test,
        status,
        message,
        details,
        timestamp: new Date().toISOString()
    });
}

function fileExists(filePath) {
    try {
        return fs.existsSync(path.join(config.projectRoot, filePath));
    } catch (error) {
        return false;
    }
}

function readFile(filePath) {
    try {
        return fs.readFileSync(path.join(config.projectRoot, filePath), 'utf8');
    } catch (error) {
        return null;
    }
}

function validateFileContent(filePath, requiredContent) {
    const content = readFile(filePath);
    if (!content) return false;
    
    if (Array.isArray(requiredContent)) {
        return requiredContent.every(item => content.includes(item));
    }
    return content.includes(requiredContent);
}

// Test categories
class MigrationSystemTester {
    constructor() {
        this.startTime = Date.now();
    }

    async runAllTests() {
        log('\n🚀 Starting Schema-Based Tenant Migration System Tests', 'cyan');
        log('=' .repeat(60), 'cyan');
        
        try {
            await this.testFileStructure();
            await this.testMigrationScripts();
            await this.testDocumentation();
            await this.testConfiguration();
            await this.testModels();
            await this.testServices();
            await this.testMiddleware();
            await this.testTestSuite();
            await this.testMonitoringSystem();
            await this.testRollbackProcedures();
            
            this.generateReport();
        } catch (error) {
            logError(`Test execution failed: ${error.message}`);
            process.exit(1);
        }
    }

    async testFileStructure() {
        log('\n📁 Testing File Structure...', 'yellow');
        
        const requiredFiles = [
            // Migration files
            'database/migrations/2024_01_01_000001_migrate_to_schema_based_tenancy.php',
            
            // Model files
            'app/Models/User.php',
            'app/Models/Lead.php',
            'app/Models/LandingPage.php',
            'app/Models/Campaign.php',
            'app/Models/EmailTemplate.php',
            'app/Models/FormSubmission.php',
            'app/Models/Analytics.php',
            'app/Models/BrandCustomization.php',
            
            // Service files
            'app/Services/TenantContextService.php',
            'app/Services/LeadService.php',
            'app/Services/LandingPageService.php',
            'app/Services/CampaignService.php',
            'app/Services/EmailService.php',
            'app/Services/AnalyticsService.php',
            'app/Services/BrandCustomizerService.php',
            'app/Services/LeadScoringService.php',
            
            // Middleware
            'app/Http/Middleware/TenantMiddleware.php',
            
            // Configuration
            'config/tenant_monitoring.php',
            
            // Scripts
            'scripts/rollback_migration.php',
            'scripts/monitor_tenant_health.php',
            
            // Documentation
            'docs/SCHEMA_BASED_TENANCY.md',
            'docs/DISASTER_RECOVERY_PLAN.md',
            
            // Tests
            'tests/TestCase.php',
            'tests/run_tenant_tests.php',
            'tests/Helpers/TenantTestHelper.php',
            'phpunit.xml'
        ];

        const requiredDirectories = [
            'tests/Unit/Models',
            'tests/Unit/Services',
            'tests/Integration',
            'tests/Performance',
            'tests/Migration',
            'tests/Helpers'
        ];

        // Test required files
        let missingFiles = 0;
        for (const file of requiredFiles) {
            if (fileExists(file)) {
                logSuccess(`File exists: ${file}`);
                addTestResult('File Structure', `File: ${file}`, 'PASS');
            } else {
                logError(`Missing file: ${file}`);
                addTestResult('File Structure', `File: ${file}`, 'FAIL', 'File not found');
                missingFiles++;
            }
        }

        // Test required directories
        let missingDirs = 0;
        for (const dir of requiredDirectories) {
            if (fileExists(dir)) {
                logSuccess(`Directory exists: ${dir}`);
                addTestResult('File Structure', `Directory: ${dir}`, 'PASS');
            } else {
                logError(`Missing directory: ${dir}`);
                addTestResult('File Structure', `Directory: ${dir}`, 'FAIL', 'Directory not found');
                missingDirs++;
            }
        }

        if (missingFiles === 0 && missingDirs === 0) {
            logSuccess('All required files and directories are present');
        } else {
            logWarning(`Found ${missingFiles} missing files and ${missingDirs} missing directories`);
        }
    }

    async testMigrationScripts() {
        log('\n🔄 Testing Migration Scripts...', 'yellow');
        
        // Test main migration file
        const migrationFile = 'database/migrations/2024_01_01_000001_migrate_to_schema_based_tenancy.php';
        if (fileExists(migrationFile)) {
            const requiredMethods = ['up', 'down', 'createTenantSchema', 'migrateTenantData'];
            const content = readFile(migrationFile);
            
            for (const method of requiredMethods) {
                if (content && content.includes(`function ${method}`)) {
                    logSuccess(`Migration method found: ${method}`);
                    addTestResult('Migration Scripts', `Method: ${method}`, 'PASS');
                } else {
                    logError(`Missing migration method: ${method}`);
                    addTestResult('Migration Scripts', `Method: ${method}`, 'FAIL', 'Method not found');
                }
            }
        }

        // Test rollback script
        const rollbackScript = 'scripts/rollback_migration.php';
        if (fileExists(rollbackScript)) {
            const requiredFunctions = [
                'validatePreRollback',
                'createEmergencyBackup',
                'collectTenantData',
                'recreateTenantIdTables',
                'migrateTenantData'
            ];
            const content = readFile(rollbackScript);
            
            for (const func of requiredFunctions) {
                if (content && content.includes(func)) {
                    logSuccess(`Rollback function found: ${func}`);
                    addTestResult('Migration Scripts', `Rollback Function: ${func}`, 'PASS');
                } else {
                    logError(`Missing rollback function: ${func}`);
                    addTestResult('Migration Scripts', `Rollback Function: ${func}`, 'FAIL', 'Function not found');
                }
            }
        }
    }

    async testDocumentation() {
        log('\n📚 Testing Documentation...', 'yellow');
        
        const docs = [
            {
                file: 'docs/SCHEMA_BASED_TENANCY.md',
                requiredSections: [
                    '# Schema-Based Tenancy Architecture',
                    '## Overview',
                    '## Architecture Changes',
                    '## Benefits',
                    '## Implementation Details',
                    '## Migration Process',
                    '## Testing Strategy'
                ]
            },
            {
                file: 'docs/DISASTER_RECOVERY_PLAN.md',
                requiredSections: [
                    '# Disaster Recovery Plan',
                    '## Risk Assessment',
                    '## Recovery Scenarios',
                    '## Backup Strategies',
                    '## Monitoring and Detection',
                    '## Emergency Procedures'
                ]
            }
        ];

        for (const doc of docs) {
            if (fileExists(doc.file)) {
                const content = readFile(doc.file);
                logSuccess(`Documentation file exists: ${doc.file}`);
                
                for (const section of doc.requiredSections) {
                    if (content && content.includes(section)) {
                        logSuccess(`Section found: ${section}`);
                        addTestResult('Documentation', `${doc.file}: ${section}`, 'PASS');
                    } else {
                        logError(`Missing section: ${section}`);
                        addTestResult('Documentation', `${doc.file}: ${section}`, 'FAIL', 'Section not found');
                    }
                }
            } else {
                logError(`Missing documentation: ${doc.file}`);
                addTestResult('Documentation', doc.file, 'FAIL', 'File not found');
            }
        }
    }

    async testConfiguration() {
        log('\n⚙️ Testing Configuration...', 'yellow');
        
        // Test monitoring configuration
        const monitoringConfig = 'config/tenant_monitoring.php';
        if (fileExists(monitoringConfig)) {
            const requiredKeys = [
                'alert_thresholds',
                'notification_channels',
                'check_intervals',
                'alert_policies',
                'monitoring_scope'
            ];
            const content = readFile(monitoringConfig);
            
            for (const key of requiredKeys) {
                if (content && content.includes(`'${key}'`)) {
                    logSuccess(`Configuration key found: ${key}`);
                    addTestResult('Configuration', `Monitoring Config: ${key}`, 'PASS');
                } else {
                    logError(`Missing configuration key: ${key}`);
                    addTestResult('Configuration', `Monitoring Config: ${key}`, 'FAIL', 'Key not found');
                }
            }
        }

        // Test PHPUnit configuration
        const phpunitConfig = 'phpunit.xml';
        if (fileExists(phpunitConfig)) {
            const content = readFile(phpunitConfig);
            const requiredElements = ['<testsuites>', '<env name="DB_CONNECTION"', '<env name="TENANT_SCHEMA"'];
            
            for (const element of requiredElements) {
                if (content && content.includes(element)) {
                    logSuccess(`PHPUnit config element found: ${element}`);
                    addTestResult('Configuration', `PHPUnit: ${element}`, 'PASS');
                } else {
                    logError(`Missing PHPUnit config element: ${element}`);
                    addTestResult('Configuration', `PHPUnit: ${element}`, 'FAIL', 'Element not found');
                }
            }
        }
    }

    async testModels() {
        log('\n🏗️ Testing Models...', 'yellow');
        
        const models = [
            'app/Models/User.php',
            'app/Models/Lead.php',
            'app/Models/LandingPage.php',
            'app/Models/Campaign.php',
            'app/Models/EmailTemplate.php'
        ];

        for (const model of models) {
            if (fileExists(model)) {
                const content = readFile(model);
                
                // Check that tenant_id is removed
                if (content && !content.includes('tenant_id')) {
                    logSuccess(`Model cleaned of tenant_id: ${model}`);
                    addTestResult('Models', `${model}: tenant_id removed`, 'PASS');
                } else {
                    logWarning(`Model may still contain tenant_id: ${model}`);
                    addTestResult('Models', `${model}: tenant_id removed`, 'WARNING', 'May still contain tenant_id');
                }
                
                // Check for proper model structure
                if (content && content.includes('class ') && content.includes('extends Model')) {
                    logSuccess(`Model structure valid: ${model}`);
                    addTestResult('Models', `${model}: structure`, 'PASS');
                } else {
                    logError(`Invalid model structure: ${model}`);
                    addTestResult('Models', `${model}: structure`, 'FAIL', 'Invalid structure');
                }
            } else {
                logError(`Model not found: ${model}`);
                addTestResult('Models', model, 'FAIL', 'File not found');
            }
        }
    }

    async testServices() {
        log('\n🔧 Testing Services...', 'yellow');
        
        // Test TenantContextService
        const tenantService = 'app/Services/TenantContextService.php';
        if (fileExists(tenantService)) {
            const content = readFile(tenantService);
            const requiredMethods = [
                'setTenantContext',
                'getTenantContext',
                'switchSchema',
                'getCurrentSchema'
            ];
            
            for (const method of requiredMethods) {
                if (content && content.includes(`function ${method}`)) {
                    logSuccess(`TenantContextService method found: ${method}`);
                    addTestResult('Services', `TenantContextService: ${method}`, 'PASS');
                } else {
                    logError(`Missing TenantContextService method: ${method}`);
                    addTestResult('Services', `TenantContextService: ${method}`, 'FAIL', 'Method not found');
                }
            }
        }

        // Test other services
        const services = [
            'app/Services/LeadService.php',
            'app/Services/LandingPageService.php',
            'app/Services/CampaignService.php'
        ];

        for (const service of services) {
            if (fileExists(service)) {
                const content = readFile(service);
                
                // Check for TenantContextService injection
                if (content && content.includes('TenantContextService')) {
                    logSuccess(`Service uses TenantContextService: ${service}`);
                    addTestResult('Services', `${service}: TenantContextService`, 'PASS');
                } else {
                    logWarning(`Service may not use TenantContextService: ${service}`);
                    addTestResult('Services', `${service}: TenantContextService`, 'WARNING', 'May not use TenantContextService');
                }
            }
        }
    }

    async testMiddleware() {
        log('\n🛡️ Testing Middleware...', 'yellow');
        
        const middleware = 'app/Http/Middleware/TenantMiddleware.php';
        if (fileExists(middleware)) {
            const content = readFile(middleware);
            const requiredMethods = ['handle'];
            
            for (const method of requiredMethods) {
                if (content && content.includes(`function ${method}`)) {
                    logSuccess(`TenantMiddleware method found: ${method}`);
                    addTestResult('Middleware', `TenantMiddleware: ${method}`, 'PASS');
                } else {
                    logError(`Missing TenantMiddleware method: ${method}`);
                    addTestResult('Middleware', `TenantMiddleware: ${method}`, 'FAIL', 'Method not found');
                }
            }
            
            // Check for TenantContextService usage
            if (content && content.includes('TenantContextService')) {
                logSuccess('TenantMiddleware uses TenantContextService');
                addTestResult('Middleware', 'TenantMiddleware: TenantContextService', 'PASS');
            } else {
                logError('TenantMiddleware does not use TenantContextService');
                addTestResult('Middleware', 'TenantMiddleware: TenantContextService', 'FAIL', 'Does not use TenantContextService');
            }
        } else {
            logError('TenantMiddleware not found');
            addTestResult('Middleware', 'TenantMiddleware', 'FAIL', 'File not found');
        }
    }

    async testTestSuite() {
        log('\n🧪 Testing Test Suite...', 'yellow');
        
        const testFiles = [
            'tests/TestCase.php',
            'tests/run_tenant_tests.php',
            'tests/Helpers/TenantTestHelper.php'
        ];

        for (const testFile of testFiles) {
            if (fileExists(testFile)) {
                logSuccess(`Test file exists: ${testFile}`);
                addTestResult('Test Suite', testFile, 'PASS');
            } else {
                logError(`Missing test file: ${testFile}`);
                addTestResult('Test Suite', testFile, 'FAIL', 'File not found');
            }
        }

        // Check test directories
        const testDirs = [
            'tests/Unit/Models',
            'tests/Unit/Services',
            'tests/Integration',
            'tests/Performance',
            'tests/Migration'
        ];

        for (const testDir of testDirs) {
            if (fileExists(testDir)) {
                logSuccess(`Test directory exists: ${testDir}`);
                addTestResult('Test Suite', `Directory: ${testDir}`, 'PASS');
            } else {
                logError(`Missing test directory: ${testDir}`);
                addTestResult('Test Suite', `Directory: ${testDir}`, 'FAIL', 'Directory not found');
            }
        }
    }

    async testMonitoringSystem() {
        log('\n📊 Testing Monitoring System...', 'yellow');
        
        const monitoringScript = 'scripts/monitor_tenant_health.php';
        if (fileExists(monitoringScript)) {
            const content = readFile(monitoringScript);
            const requiredFunctions = [
                'checkDatabaseHealth',
                'checkSchemaHealth',
                'checkPerformance',
                'checkBackups',
                'generateReport'
            ];
            
            for (const func of requiredFunctions) {
                if (content && content.includes(func)) {
                    logSuccess(`Monitoring function found: ${func}`);
                    addTestResult('Monitoring', `Function: ${func}`, 'PASS');
                } else {
                    logError(`Missing monitoring function: ${func}`);
                    addTestResult('Monitoring', `Function: ${func}`, 'FAIL', 'Function not found');
                }
            }
        } else {
            logError('Monitoring script not found');
            addTestResult('Monitoring', 'monitor_tenant_health.php', 'FAIL', 'File not found');
        }
    }

    async testRollbackProcedures() {
        log('\n🔄 Testing Rollback Procedures...', 'yellow');
        
        const rollbackScript = 'scripts/rollback_migration.php';
        if (fileExists(rollbackScript)) {
            const content = readFile(rollbackScript);
            
            // Test for dry-run capability
            if (content && content.includes('dry-run')) {
                logSuccess('Rollback script supports dry-run mode');
                addTestResult('Rollback', 'Dry-run support', 'PASS');
            } else {
                logWarning('Rollback script may not support dry-run mode');
                addTestResult('Rollback', 'Dry-run support', 'WARNING', 'May not support dry-run');
            }
            
            // Test for backup creation
            if (content && content.includes('backup')) {
                logSuccess('Rollback script includes backup functionality');
                addTestResult('Rollback', 'Backup functionality', 'PASS');
            } else {
                logError('Rollback script missing backup functionality');
                addTestResult('Rollback', 'Backup functionality', 'FAIL', 'Missing backup functionality');
            }
        } else {
            logError('Rollback script not found');
            addTestResult('Rollback', 'rollback_migration.php', 'FAIL', 'File not found');
        }
    }

    generateReport() {
        const endTime = Date.now();
        const duration = (endTime - this.startTime) / 1000;
        
        log('\n📋 Test Report', 'cyan');
        log('=' .repeat(60), 'cyan');
        
        // Summary statistics
        const totalTests = config.testResults.length;
        const passedTests = config.testResults.filter(r => r.status === 'PASS').length;
        const failedTests = config.testResults.filter(r => r.status === 'FAIL').length;
        const warningTests = config.testResults.filter(r => r.status === 'WARNING').length;
        
        log(`\nTest Summary:`, 'bright');
        log(`Total Tests: ${totalTests}`);
        logSuccess(`Passed: ${passedTests}`);
        logError(`Failed: ${failedTests}`);
        logWarning(`Warnings: ${warningTests}`);
        log(`Duration: ${duration.toFixed(2)} seconds`);
        
        // Category breakdown
        const categories = [...new Set(config.testResults.map(r => r.category))];
        log('\nResults by Category:', 'bright');
        
        for (const category of categories) {
            const categoryTests = config.testResults.filter(r => r.category === category);
            const categoryPassed = categoryTests.filter(r => r.status === 'PASS').length;
            const categoryFailed = categoryTests.filter(r => r.status === 'FAIL').length;
            const categoryWarnings = categoryTests.filter(r => r.status === 'WARNING').length;
            
            log(`\n${category}:`);
            log(`  ✓ Passed: ${categoryPassed}`, 'green');
            if (categoryFailed > 0) log(`  ✗ Failed: ${categoryFailed}`, 'red');
            if (categoryWarnings > 0) log(`  ⚠ Warnings: ${categoryWarnings}`, 'yellow');
        }
        
        // Failed tests details
        const failedTestsDetails = config.testResults.filter(r => r.status === 'FAIL');
        if (failedTestsDetails.length > 0) {
            log('\nFailed Tests:', 'red');
            for (const test of failedTestsDetails) {
                log(`  ✗ ${test.category}: ${test.test} - ${test.message}`, 'red');
            }
        }
        
        // Warning tests details
        const warningTestsDetails = config.testResults.filter(r => r.status === 'WARNING');
        if (warningTestsDetails.length > 0) {
            log('\nWarning Tests:', 'yellow');
            for (const test of warningTestsDetails) {
                log(`  ⚠ ${test.category}: ${test.test} - ${test.message}`, 'yellow');
            }
        }
        
        // Overall status
        log('\nOverall Status:', 'bright');
        if (failedTests === 0) {
            if (warningTests === 0) {
                logSuccess('🎉 All tests passed! Schema-based tenant migration system is ready.');
            } else {
                logWarning(`⚠️ Tests passed with ${warningTests} warnings. Review warnings before deployment.`);
            }
        } else {
            logError(`❌ ${failedTests} tests failed. Address failures before deployment.`);
        }
        
        // Save detailed report
        this.saveDetailedReport(duration);
        
        log('\n📄 Detailed report saved to: test_migration_report.json', 'blue');
        
        // Exit with appropriate code
        process.exit(failedTests > 0 ? 1 : 0);
    }
    
    saveDetailedReport(duration) {
        const report = {
            summary: {
                timestamp: new Date().toISOString(),
                duration_seconds: duration,
                total_tests: config.testResults.length,
                passed: config.testResults.filter(r => r.status === 'PASS').length,
                failed: config.testResults.filter(r => r.status === 'FAIL').length,
                warnings: config.testResults.filter(r => r.status === 'WARNING').length
            },
            test_results: config.testResults,
            system_info: {
                node_version: process.version,
                platform: process.platform,
                working_directory: process.cwd(),
                command_line_args: process.argv
            }
        };
        
        try {
            fs.writeFileSync(
                path.join(config.projectRoot, 'test_migration_report.json'),
                JSON.stringify(report, null, 2)
            );
        } catch (error) {
            logError(`Failed to save detailed report: ${error.message}`);
        }
    }
}

// Main execution
if (require.main === module) {
    const tester = new MigrationSystemTester();
    tester.runAllTests().catch(error => {
        logError(`Test execution failed: ${error.message}`);
        console.error(error.stack);
        process.exit(1);
    });
}

module.exports = MigrationSystemTester;
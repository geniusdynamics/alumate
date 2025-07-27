# User Acceptance Testing (UAT) System

This directory contains the comprehensive User Acceptance Testing system for the Graduate Tracking System. The UAT system provides tools for creating test data, running automated tests, collecting user feedback, and generating reports.

## Overview

The UAT system consists of several key components:

1. **Test Data Management** - Create and manage comprehensive test datasets
2. **Test Execution** - Run automated test scenarios across all user roles
3. **Feedback Collection** - Collect user feedback, bug reports, and usability data
4. **Reporting** - Generate detailed test reports and analytics

## Components

### 1. TestDataSets.php
Creates comprehensive test data for all system components:
- Institutions (tenants) with proper domain mapping
- Users for all roles (Super Admin, Institution Admin, Employer, Graduate)
- Courses with skills and career path mappings
- Graduates with complete profiles and employment status
- Employers with verification status
- Jobs with detailed requirements and benefits
- Job applications with various statuses
- Announcements and notifications
- Performance testing data (large datasets)

### 2. TestRunner.php
Executes automated test scenarios:
- Super Admin tests (institution management, analytics, employer verification)
- Institution Admin tests (graduate management, bulk import, course management)
- Employer tests (registration, job posting, application management)
- Graduate tests (profile management, job search, career tracking)
- Integration tests (job matching workflow, data flow verification)
- Performance tests (load testing, data volume testing)
- Security tests (authentication, data protection)

### 3. FeedbackCollector.php
Collects and manages user feedback:
- General feedback collection
- Bug report management with severity and priority
- Usability feedback with ratings and suggestions
- Performance feedback with response time metrics
- Comprehensive reporting and analytics
- CSV export for external analysis

### 4. TestScenarios.md
Detailed test scenarios documentation covering:
- All user roles and their specific workflows
- Cross-role integration scenarios
- Performance and security testing requirements
- Accessibility and browser compatibility testing
- Data migration and backup/recovery scenarios

## Usage

### Running Tests

#### Command Line Interface
Use the Artisan command to run tests:

```bash
# Run full test suite
php artisan test:uat

# Setup test data only
php artisan test:uat --setup

# Run specific category tests
php artisan test:uat --category=super-admin
php artisan test:uat --category=institution-admin
php artisan test:uat --category=employer
php artisan test:uat --category=graduate
php artisan test:uat --category=integration
php artisan test:uat --category=performance
php artisan test:uat --category=security

# Run performance tests with large datasets
php artisan test:uat --performance

# Generate detailed report
php artisan test:uat --report

# Cleanup test data
php artisan test:uat --cleanup
```

#### Programmatic Usage
```php
use Tests\UserAcceptance\TestRunner;
use Tests\UserAcceptance\TestDataSets;

// Create test data
$testData = TestDataSets::createAllTestData();

// Run tests
$testRunner = new TestRunner();
$results = $testRunner->runAllTests();

// Cleanup
TestDataSets::cleanupTestData();
```

### Collecting User Feedback

#### Web Interface
Access the feedback form at `/testing/feedback` to collect:
- General feedback from testers
- Bug reports with detailed information
- Usability feedback with ratings
- Performance feedback and issues

#### Programmatic Collection
```php
use Tests\UserAcceptance\FeedbackCollector;

$collector = new FeedbackCollector();

// Collect general feedback
$collector->collectTestFeedback('SA-001', 'Institution Management', 'super_admin', 'The interface is intuitive...');

// Collect bug report
$collector->collectBugReport('IA-002', [
    'title' => 'Import fails with large files',
    'description' => 'When importing files larger than 10MB...',
    'severity' => 'high',
    'steps_to_reproduce' => '1. Go to import page...',
]);

// Generate report
$report = $collector->generateFeedbackReport();
```

## Test Categories

### Super Admin Tests (SA-*)
- SA-001: Institution Management
- SA-002: System-Wide Analytics
- SA-003: Employer Verification

### Institution Admin Tests (IA-*)
- IA-001: Graduate Management
- IA-002: Bulk Graduate Import
- IA-003: Course Management
- IA-004: Institution Analytics

### Employer Tests (E-*)
- E-001: Employer Registration
- E-002: Job Posting
- E-003: Application Management
- E-004: Graduate Search

### Graduate Tests (G-*)
- G-001: Profile Management
- G-002: Job Search and Application
- G-003: Classmate Connections
- G-004: Career Tracking

### Integration Tests (CR-*)
- CR-001: Job Matching Workflow
- CR-002: Data Flow Verification

### Performance Tests (P-*)
- P-001: Load Testing
- P-002: Data Volume Testing

### Security Tests (S-*)
- S-001: Authentication Testing
- S-002: Data Protection Testing

## Test Data Structure

### Institutions
- Test University (test-university.localhost)
- Tech College (tech-college.localhost)

### Users
- Super Admin (superadmin@system.com)
- Institution Admins for each institution
- Employers (verified and pending)
- Graduates with varying profile completion levels

### Test Scenarios
Each test includes:
- Preconditions
- Step-by-step test procedures
- Expected results
- Acceptance criteria
- Error handling verification

## Reporting

### Test Reports
Generated reports include:
- Test execution summary
- Pass/fail statistics
- Performance metrics
- Error details
- Recommendations

### Feedback Reports
Feedback reports contain:
- Bug severity distribution
- Usability ratings analysis
- Performance issue summary
- Improvement recommendations
- Detailed feedback entries

## Best Practices

### Test Data Management
1. Always cleanup test data after testing
2. Use realistic data that represents actual usage
3. Include edge cases and boundary conditions
4. Test with various data volumes

### Test Execution
1. Run tests in isolated environments
2. Verify preconditions before each test
3. Document any deviations from expected results
4. Include performance measurements

### Feedback Collection
1. Provide clear instructions to testers
2. Collect feedback from all user roles
3. Categorize and prioritize issues
4. Follow up on critical issues immediately

## Troubleshooting

### Common Issues

#### Database Connection Errors
- Ensure database is running and accessible
- Check database credentials in .env file
- Verify tenant database configuration

#### Memory Issues with Large Datasets
- Increase PHP memory limit
- Use chunked processing for large imports
- Monitor system resources during testing

#### Permission Errors
- Verify file system permissions
- Check storage directory access
- Ensure proper role assignments

### Getting Help

For issues with the UAT system:
1. Check the Laravel logs in `storage/logs/`
2. Review test output for specific error messages
3. Verify test data integrity
4. Check system requirements and dependencies

## Contributing

When adding new test scenarios:
1. Follow the existing naming convention
2. Include comprehensive documentation
3. Add both positive and negative test cases
4. Update the TestScenarios.md file
5. Ensure proper cleanup procedures

## Files Structure

```
tests/UserAcceptance/
├── README.md                 # This documentation
├── TestDataSets.php         # Test data creation and management
├── TestRunner.php           # Test execution engine
├── FeedbackCollector.php    # User feedback collection
└── TestScenarios.md         # Detailed test scenarios

app/Console/Commands/
└── RunUserAcceptanceTests.php  # Artisan command for running tests

app/Http/Controllers/
└── TestingController.php       # Web interface for feedback collection

resources/views/testing/
└── feedback-form.blade.php     # Feedback collection form

routes/
└── testing.php                 # Testing-related routes
```

This UAT system provides comprehensive testing capabilities to ensure the Graduate Tracking System meets all requirements and provides an excellent user experience across all roles and scenarios.
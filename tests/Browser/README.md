# End-to-End Testing Suite

This directory contains comprehensive end-to-end (E2E) tests for the Alumni Management System, built using Laravel Dusk to simulate real user interactions in a browser environment.

## Test Structure

### Test Categories

1. **Graduate Registration & Profile Setup** (`GraduateRegistrationBrowserTest.php`)
   - Complete graduate onboarding journey
   - Profile creation and verification
   - Job search and application workflows
   - Networking and mentorship discovery

2. **Employer Job Posting Workflow** (`EmployerJobPostingWorkflowTest.php`)
   - Complete job posting process
   - Company profile management
   - Bulk job posting capabilities
   - Employer analytics and reporting

3. **Job Application Journey** (planned)
   - Full job application lifecycle
   - Application tracking and communication
   - Interview scheduling and management

4. **Mentorship System** (planned)
   - Mentorship request and matching
   - Session scheduling and management
   - Progress tracking and feedback

5. **Events & Networking** (planned)
   - Event creation and management
   - Attendee registration
   - Virtual and in-person event handling

6. **Admin Dashboard** (planned)
   - System administration workflows
   - User and content management
   - Analytics and reporting

7. **Tenant Isolation** (planned)
   - Multi-tenant data isolation verification
   - Cross-tenant security validation

## Setup Instructions

### Prerequisites

1. **Laravel Dusk Dependencies**: Ensure laravel/dusk is installed
2. **Chrome/Chromium Browser**: Required for headless testing
3. **ChromeDriver**: Auto-managed by Dusk (version 114+)
4. **Test Database**: Dedicated testing database for E2E tests

### Configuration

Add to `.env.dusk.local`:

```env
APP_URL=http://127.0.0.1:8000
APP_ENV=testing

# Test database configuration
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Mail configuration for testing
MAIL_MAILER=log
MAIL_DEFAULT_TO=dev@example.com

# Queue configuration
QUEUE_CONNECTION=sync
```

### Running Tests

#### Single Test File
```bash
# Run specific test file
php artisan dusk tests/Browser/GraduateRegistrationBrowserTest.php

# Run with verbose output
php artisan dusk tests/Browser/GraduateRegistrationBrowserTest.php --verbose
```

#### Entire Test Suite
```bash
# Run all E2E tests
php artisan dusk

# Run with specific browser
php artisan dusk --browse

# Run in headful mode (visible Chrome window)
php artisan dusk --browse --disable-headless
```

#### Specific Test Methods
```bash
# Run specific test method
php artisan dusk tests/Browser/GraduateRegistrationBrowserTest.php::test_complete_graduate_registration_and_profile_setup_journey
```

#### Parallel Testing
```bash
# Run tests in parallel for faster execution
php artisan dusk --processes=4
```

### Browser Options

#### Common Options
- `--browse`: Open browser window (non-headless)
- `--disable-headless`: Run in headful mode
- `--window-size`: Set browser window size
- `--timeout`: Set test timeout
- `--processes`: Number of parallel processes

#### Configuration Examples
```bash
# Run with visible browser for debugging
php artisan dusk --browse --disable-headless --window-size=1920,1080

# Run with longer timeout for slow operations
php artisan dusk --timeout=120

# Run with custom Chrome options
php artisan dusk --arguments="--disable-gpu,--no-sandbox"
```

## Test Data Management

### Factory Setup
```php
// Test data is created using Laravel factories
protected function setUp(): void
{
    parent::setUp();

    // Create test users, institutions, companies
    $this->institution = Institution::factory()->create();
    $this->testUser = User::factory()->create([
        'institution_id' => $this->institution->id
    ]);
}
```

### Database Cleanup
- Tests use `DatabaseMigrations` trait for clean database state
- Each test method gets a fresh database
- No cross-test contamination

## Writing E2E Tests

### Basic Test Structure

```php
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleBrowserTest extends DuskTestCase
{
    public function test_example_workflow()
    {
        $this->browse(function (Browser $browser) {
            // Navigation
            $browser->visit('/route')
                   ->assertPathIs('/route')
                   ->assertSee('Expected Content');

            // Interaction
            $browser->click('.button-selector')
                   ->waitFor('.result-element')
                   ->assertSee('Success Message');

            // Form filling
            $browser->type('input[name="email"]', 'test@example.com')
                   ->type('input[name="password"]', 'password123')
                   ->click('.submit-button');

            // Validation
            $browser->assertPathIs('/dashboard')
                   ->assertSee('Welcome back!');
        });
    }
}
```

### Common Browser Methods

#### Navigation
```php
$browser->visit('/path')           // Navigate to path
       ->back()                    // Go back
       ->forward()                 // Go forward
       ->refresh()                 // Refresh page
```

#### Assertions
```php
$browser->assertPathIs('/expected/path')
       ->assertTitle('Expected Title')
       ->assertSee('Expected Text')
       ->assertDontSee('Unexpected Text')
       ->assertPresent('.css-selector')
       ->assertMissing('.css-selector')
```

#### Form Interactions
```php
$browser->type('input[name]', 'value')      // Type text
       ->select('select[name]', 'option')   // Select dropdown
       ->check('input[name]')               // Check checkbox
       ->uncheck('input[name]')             // Uncheck checkbox
       ->radio('input[name]', 'value')      // Radio button
       ->attach('input[name]', '/path/to/file') // File upload
```

#### Waiting
```php
$browser->waitFor('.selector')              // Wait for element
       ->waitUntil('condition()')           // Wait for JavaScript condition
       ->pause(1000)                        // Pause for milliseconds
       ->whenAvailable('.selector', function ($browser) {
           // Execute when element is available
       })
```

#### JavaScript Execution
```php
$browser->script('JavaScript code here')
       ->driver->executeScript('code', ['param1', 'param2'])
```

### Best Practices

#### Element Selectors
- Use CSS selectors over XPath when possible
- Prefer semantic classes over generic selectors
- Use data attributes for test-specific selectors

```html
<!-- Good -->
<button class="btn btn-primary" data-test="submit">Submit</button>

<!-- Avoid -->
<button class="btn btn-primary">Submit</button>
```

#### Test Organization
- Group related actions in logical steps
- Use descriptive method names
- Keep tests focused on single workflows
- Use factories for consistent test data

#### Error Handling
- Use explicit waits to avoid race conditions
- Assert early and often
- Provide descriptive error messages
- Handle dynamic content appropriately

#### Performance
- Minimize unnecessary page refreshes
- Use appropriate wait times
- Group related operations
- Consider parallel execution for large test suites

## Continuous Integration

### CI Configuration Example (.github/workflows/e2e.yml)

```yaml
name: E2E Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  e2e-tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Install Chrome
        run: |
          wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
          sudo sh -c 'echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google.list'
          sudo apt-get update && sudo apt-get install -y google-chrome-stable

      - name: Install PHP dependencies
        run: composer install --no-interaction

      - name: Install Node.js dependencies
        run: npm install

      - name: Setup environment
        run: cp .env.ci .env

      - name: Generate application key
        run: php artisan key:generate

      - name: Run database migrations
        run: php artisan migrate

      - name: Seed database
        run: php artisan db:seed

      - name: Run E2E tests
        run: php artisan dusk --processes=2 --verbose

      - name: Upload test artifacts
        uses: actions/upload-artifact@v3
        if: failure()
        with:
          name: dusk-screenshots
          path: tests/Browser/screenshots/
```

### Docker Configuration

```dockerfile
# docker-compose.yml for E2E testing
version: '3.8'
services:
  app:
    image: your-laravel-app
    environment:
      - APP_URL=http://app:8000
      - DB_CONNECTION=mysql
      - DB_HOST=db

  chrome:
    image: selenium/standalone-chrome:114.0
    ports:
      - "4444:4444"
    environment:
      - SE_OPTS="--port 4444"

  tests:
    image: your-test-runner
    depends_on:
      - app
      - chrome
    environment:
      - DUSK_DRIVER_URL=http://chrome:4444/wd/hub
```

## Troubleshooting

### Common Issues

#### Chrome/ChromeDriver Compatibility
```bash
# Check Chrome version
google-chrome --version

# Download compatible ChromeDriver
# Visit: https://chromedriver.chromium.org/downloads
```

#### Element Not Found Errors
- Use appropriate waits: `$browser->waitFor('.selector')`
- Check element visibility: `$browser->waitFor('.selector', 10)`
- Use explicit paths: `$browser->within('.container', function($browser) { ... })`

#### Database Connection Issues
- Ensure test database is properly configured
- Use in-memory SQLite for faster tests
- Clear cache before running tests: `php artisan config:clear`

#### Timeout Issues
- Increase global timeout: `--timeout=120`
- Use explicit waits for AJAX requests
- Check network connectivity for external dependencies

#### Flaky Tests
- Add retry logic for unstable operations
- Use more specific selectors
- Implement proper waiting strategies
- Consider environment-specific conditions

### Debugging Tips

#### Visual Debugging
```bash
# Run tests with visible browser
php artisan dusk --browse --disable-headless
```

#### Screenshot Capture
```php
// Take screenshot on failure
$browser->screenshot('debug-filename');

// Or implement globally in DuskTestCase
public function tearDown(): void
{
    if ($this->hasFailed()) {
        $this->captureFailureScreenshot();
    }
    parent::tearDown();
}
```

#### Console Logging
```php
// Check browser console for JavaScript errors
$browser->driver->manage()->getLog('browser');
// Check network requests
$browser->driver->manage()->getLog('performance');
```

## Contributing

### Adding New Tests

1. Create new test file in `tests/Browser/`
2. Extend `DuskTestCase`
3. Follow naming convention: `{Feature}BrowserTest.php`
4. Add comprehensive documentation

### Test File Structure
```
tests/Browser/
├── ExampleFeatureBrowserTest.php    # Feature-specific tests
├── GraduateRegistrationBrowserTest.php
├── EmployerJobPostingWorkflowTest.php
├── README.md                        # This documentation
└── screenshots/                     # Failure screenshots
    └── failures/
```

### Code Style
- Follow PSR-12 standards
- Use descriptive method names
- Add PHPDoc comments for complex logic
- Group related test methods together
- Keep individual tests focused and concise

## Performance Considerations

### Test Execution Speed
- Use `DatabaseMigrations` over `DatabaseTransactions` for E2E tests
- Consider in-memory SQLite for faster execution
- Group related tests to avoid redundant setup
- Use parallel execution for large test suites

### Resource Usage
- Close unnecessary browser instances
- Clean up test data after execution
- Monitor memory usage for large test suites
- Consider test prioritization for CI environments

This E2E testing suite provides comprehensive coverage of critical user workflows, ensuring the Alumni Management System functions correctly from end-to-end under real browser conditions.
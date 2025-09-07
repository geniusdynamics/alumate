# Testing Guide

This guide covers the comprehensive testing strategy for the Alumni Platform, including unit tests, feature tests, integration tests, and end-to-end testing.

## Testing Overview

The platform uses a multi-layer testing approach:

- **Unit Tests**: Test individual functions and methods
- **Feature Tests**: Test HTTP endpoints and user interactions
- **Integration Tests**: Test interactions between components
- **E2E Tests**: Test complete user workflows
- **Performance Tests**: Test system performance under load
- **Accessibility Tests**: Test WCAG compliance

## Testing Stack

### PHP Testing
- **Framework**: PHPUnit
- **Coverage**: PHPUnit Code Coverage
- **Mocking**: Mockery (built into PHPUnit)
- **Database**: Laravel Test Database
- **Browser**: Laravel Dusk (for E2E)

### JavaScript/TypeScript Testing
- **Unit Tests**: Jest + Vue Test Utils
- **E2E Tests**: Playwright
- **Coverage**: Istanbul via Jest

## Directory Structure

```
tests/
├── Accessibility/           # WCAG compliance tests
├── Browser/                # Laravel Dusk E2E tests
├── EndToEnd/              # Playwright E2E tests
├── Feature/                # HTTP endpoint tests
│   ├── Admin/             # Admin feature tests
│   └── Api/               # API endpoint tests
├── Integration/           # Cross-component tests
├── Js/                    # JavaScript tests
│   ├── Accessibility/     # A11y component tests
│   ├── Components/        # Vue component tests
│   ├── Composables/       # Vue composable tests
│   ├── Performance/       # Performance tests
│   └── Services/          # Service layer tests
├── Notifications/         # Notification testing
├── Performance/           # Load testing
├── Security/              # Security tests
├── Unit/                  # PHP unit tests
│   ├── ComponentLibrary/  # Component tests
│   ├── Jobs/              # Queue job tests
│   └── Models/            # Eloquent model tests
└── UserAcceptance/        # UAT tests
```

## Running Tests

### Quick Commands

```bash
# Run all tests
php artisan test

# Run all tests with coverage
php artisan test --coverage

# Run specific test group
php artisan test --testsuite=Unit

# Run specific test class
php artisan test tests/Unit/Models/UserTest

# Run with verbose output
php artisan test -v

# Run tests in parallel
php artisan test --parallel

# Generate HTML coverage report
php artisan test --coverage-html=reports/coverage
```

### JavaScript Tests

```bash
# Run Jest unit tests
npm test

# Run with coverage
npm run test:coverage

# Run specific test file
npm test UserService.test.ts

# Run tests in watch mode
npm run test:watch
```

### Browser Tests (Laravel Dusk)

```bash
# Install ChromeDriver
php artisan dusk:install

# Run Dusk tests
php artisan dusk

# Run specific browser test
php artisan dusk tests/Browser/LoginTest.php
```

### Playwright E2E Tests

```bash
# Install browsers for Playwright
npx playwright install

# Run all E2E tests
npx playwright test

# Run specific test
npx playwright test tests/e2e/user-onboarding.spec.ts

# Run with UI mode
npx playwright test --ui

# Generate test results
npx playwright show-report
```

## Writing Tests

### PHP Unit Tests

```php
<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_user_email_must_be_unique()
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'name' => 'Jane Doe',
            'email' => 'john@example.com', // Duplicate
            'password' => 'password456'
        ]);
    }
}
```

### API Feature Tests

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_alumni_directory()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
                        ->getJson('/api/alumni');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id', 'name', 'email', 'graduation_year'
                         ]
                     ],
                     'meta' => [
                         'total', 'per_page', 'current_page'
                     ]
                 ]);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/alumni');

        $response->assertStatus(401);
    }
}
```

### Vue Component Tests

```typescript
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createApp } from 'vue'
import AlumniCard from '@/components/AlumniCard.vue'

describe('AlumniCard', () => {
  it('displays alumni information', () => {
    const alumni = {
      id: 1,
      name: 'John Doe',
      graduation_year: 2023,
      company: 'Tech Corp'
    }

    const wrapper = mount(AlumniCard, {
      props: { alumni }
    })

    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('2023')
    expect(wrapper.text()).toContain('Tech Corp')
  })

  it('emits connect event when connect button is clicked', async () => {
    const alumni = { id: 1, name: 'John Doe' }
    const wrapper = mount(AlumniCard, {
      props: { alumni }
    })

    await wrapper.find('button.connect-btn').trigger('click')

    expect(wrapper.emitted()).toHaveProperty('connect')
    expect(wrapper.emitted().connect[0]).toEqual([1]) // alumni ID
  })

  it('shows loading state during connection request', async () => {
    const alumni = { id: 1, name: 'John Doe' }
    const wrapper = mount(AlumniCard, {
      props: { alumni },
      data: () => ({ isConnecting: true })
    })

    const button = wrapper.find('button.connect-btn')
    expect(button.text()).toContain('Connecting...')
    expect(button.attributes().disabled).toBeDefined()
  })
})
```

### Playwright E2E Tests

```typescript
import { test, expect } from '@playwright/test'

test.describe('User Onboarding', () => {
  test('successful user registration and first login', async ({ page }) => {
    // Navigate to registration page
    await page.goto('/register')

    // Fill registration form
    await page.fill('input[name="name"]', 'Test User')
    await page.fill('input[name="email"]', 'test@example.com')
    await page.fill('input[name="password"]', 'password123')
    await page.fill('input[name="graduation_year"]', '2023')

    // Submit form
    await page.click('button[type="submit"]')

    // Should redirect to dashboard
    await page.waitForURL('**/dashboard')

    // Verify welcome elements
    await expect(page.locator('h1')).toContainText('Welcome to Alumni Network')
    await expect(page.locator('.onboarding-modal')).toBeVisible()

    // Test navigation
    await page.click('nav a[href="/alumni"]')
    await page.waitForURL('**/alumni')
    await expect(page.locator('.alumni-directory')).toBeVisible()
  })

  test('alumni directory search and connection', async ({ page }) => {
    // Assume user is logged in
    await page.goto('/alumni')

    // Search for alumni
    await page.fill('input[placeholder*="Search alumni"]', 'John')
    await page.keyboard.press('Enter')

    // Wait for results
    await page.waitForSelector('.alumni-result')

    // Verify search results
    const results = page.locator('.alumni-result')
    await expect(results).toHaveCount(await results.count())

    // Click connect button
    await page.click('.alumni-result:first-child .connect-btn')
    await expect(page.locator('.connection-request-modal')).toBeVisible()

    // Send connection request
    await page.fill('.connection-message', 'I would like to connect!')
    await page.click('.send-request-btn')

    // Verify success message
    await expect(page.locator('.success-message')).toContainText('Connection request sent')
  })
})
```

## Test Data Management

### Factories

```php
<?php

namespace Database\Factories;

use App\Models\Alumni;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniFactory extends Factory
{
    protected $model = Alumni::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'graduation_year' => $this->faker->year(),
            'degree' => $this->faker->randomElement([
                'Bachelor of Science',
                'Master of Science',
                'MBA',
                'PhD'
            ]),
            'major' => $this->faker->randomElement([
                'Computer Science', 'Business', 'Engineering', 'Arts'
            ]),
            'gpa' => $this->faker->randomFloat(2, 2.0, 4.0),
            'honors' => $this->faker->boolean(20), // 20% chance
            'internship_experience' => $this->faker->paragraph(),
            'research_projects' => $this->faker->boolean(30),
        ];
    }

    public function withHonors(): static
    {
        return $this->state(fn (array $attributes) => [
            'honors' => true,
            'gpa' => $this->faker->randomFloat(2, 3.5, 4.0),
        ]);
    }

    public function recentGraduate(): static
    {
        return $this->state(fn (array $attributes) => [
            'graduation_year' => now()->year,
        ]);
    }
}
```

### Seeders

```php
<?php

namespace Database\Seeders;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAlumniSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo alumni with profiles
        User::factory()
            ->alumniProfile([
                'graduation_year' => 2023,
                'degree' => 'Bachelor of Science in Computer Science',
                'current_position' => 'Software Engineer',
                'company' => 'Tech Innovations',
                'industry' => 'Technology'
            ])
            ->create([
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@demo.alumnate.edu'
            ]);

        // Bulk create various alumni profiles
        Alumni::factory(100)->create();

        // Create alumni with achievements
        Alumni::factory(10)
            ->withHonors()
            ->hasAchievements(2)
            ->create();
    }
}
```

## Continuous Integration

### GitHub Actions Example

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  php-tests:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:13
        env:
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: test
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
    - uses: actions/checkout@v4
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.3
        extensions: dom, curl, libxml, mbstring, zip, pdo, sqlite, pdo_sqlite, pgsql
        ini-values: error_reporting=E_ALL

    - name: Install Composer dependencies
      run: composer install --prefer-dist --no-progress --no-suggest

    - name: Copy environment file
      run: cp .env.ci .env

    - name: Generate application key
      run: php artisan key:generate

    - name: Run migrations
      run: php artisan migrate

    - name: Run PHP tests
      run: php artisan test --coverage --coverage-clover=coverage.xml

    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: coverage.xml

  js-tests:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v4
    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: 18
        cache: 'npm'

    - name: Install dependencies
      run: npm ci

    - name: Run JavaScript tests
      run: npm test -- --coverage

    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: coverage/coverage-final.json

  e2e-tests:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v4
    - name: Setup Node.js
      uses: shivammathur/setup-node@v4
      with:
        node-version: 18

    - name: Install Playwright
      run: npx playwright install --with-deps

    - name: Run E2E tests
      run: npx playwright test

    - name: Upload test results
      uses: actions/upload-artifact@v3
      if: always()
      with:
        name: playwright-report
        path: test-results/
```

## Test Performance Monitoring

### Coverage Goals
- **Unit Tests**: 90%+ coverage
- **Feature Tests**: 85%+ coverage
- **Critical Paths**: 100% coverage (login, payments, user creation)

### Performance Benchmarks
```bash
# Measure test execution time
php artisan test --profile

# Load testing for API endpoints
php artisan test --testsuite=Performance
```

## Accessibility Testing

### Automated Accessibility Tests

```javascript
import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'

test.describe('Accessibility', () => {
  test('homepage should pass accessibility checks', async ({ page }) => {
    await page.goto('/')

    const accessibilityScanResults = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'best-practice'])
      .analyze()

    expect(accessibilityScanResults.violations).toEqual([])
  })

  test('alumni search form should be keyboard accessible', async ({ page }) => {
    await page.goto('/alumni')

    const searchInput = page.locator('input[role="search"]')

    // Focus on search input
    await page.keyboard.press('Tab')
    await expect(searchInput).toBeFocused()

    // Type search query
    await page.keyboard.type('john doe')
    await page.keyboard.press('Enter')

    // Verify results are keyboard accessible
    await page.keyboard.press('Tab')
    await expect(page.locator('.search-result:focus')).toBeVisible()
  })
})
```

## Best Practices

### Test Structure
1. **Arrange**: Set up test data and conditions
2. **Act**: Execute the code being tested
3. **Assert**: Verify expected outcomes
4. **Clean up**: Reset state if needed

### Test Naming
- **Unit tests**: `test_user_can_be_created`
- **Feature tests**: `test_can_view_alumni_directory`
- **E2E tests**: `test_successful_user_registration_and_login`

### Mocking External Dependencies
```php
public function test_sends_notification_when_post_liked()
{
    Notification::shouldReceive('send')
        ->once()
        ->with(Mockery::on(function ($user) {
            return $user->id === 1;
        }), 'post_liked');

    // Test logic here
}

public function test_handles_payment_webhook()
{
    // Mock Stripe webhook payload
    $payload = [
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_test']]
    ];

    $this->postJson('/webhooks/stripe', $payload)
         ->assertStatus(200);
}
```

## Debugging Test Failures

### Common Issues
1. **Database state**: Use `RefreshDatabase` trait
2. **Authentication**: Use `actingAs()` for authenticated requests
3. **Authorization**: Test both allowed and denied actions
4. **Race conditions**: Use `DatabaseTransactions` when needed
5. **External APIs**: Mock all external service calls

### Debugging Tools
```bash
# Get detailed error output
php artisan test -v --stop-on-failure

# Debug specific test
php artisan test --filter=test_user_can_create_post

# Run with debugger
php -dxdebug.mode=debug artisan test
```

## Test Documentation

### Test Case Specifications

| Test Type | Description | Covered Components | Success Criteria |
|-----------|-------------|-------------------|------------------|
| Unit | Individual function/method testing | Classes, methods, utilities | Function returns expected result |
| Feature | HTTP endpoint testing | Controllers, routes, middleware | Correct response format |
| Integration | Component interaction | Multiple classes/services | Components work together |
| E2E | Full user workflow | Complete application | User success scenario |
| Accessibility | WCAG compliance | UI components | Passes accessibility checks |

---

## Quick Start

```bash
# Run tests in development
composer test:all

# Run tests with coverage report
composer test:coverage

# Run JavaScript tests
npm test

# Run E2E tests
npm run test:e2e
```

For more detailed information, see specific test files in the `tests/` directory and the [Laravel Testing Documentation](https://laravel.com/docs/testing).
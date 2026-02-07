# Pre-Launch Readiness Assessment

## 04 - Developer Onboarding Guide

**Project:** Alumate - Alumni Platform MVP  
**Target Audience:** New developers joining the project  
**Last Updated:** 2026-02-06

---

## 🚀 Quick Start (5 Minutes)

### Prerequisites

- PHP 8.3+
- Node.js 22+
- PostgreSQL 17+
- Composer 2.x
- Git

### One-Command Setup

```bash
# Clone repository
git clone https://github.com/your-org/alumate.git
cd alumate

# Install all dependencies and setup
cp .env.example .env
composer install
npm install
php artisan key:generate

# Database setup
touch database/database.sqlite  # For quick testing
php artisan migrate
php artisan tenants:migrate
php artisan db:seed

# Start development servers
composer run dev
```

**Access the app at:** http://localhost:8080

---

## 💻 Development Environment Setup

### Windows (XAMPP/WAMP)

1. **Install PHP 8.3** via XAMPP or standalone
    - Path: `D:\DevCenter\xampp\php-8.3.23\php.exe`

2. **Install Node.js 22**

    ```powershell
    # Using nvm-windows
    nvm install 22
    nvm use 22
    ```

3. **Install PostgreSQL 17**
    - Download from https://www.postgresql.org/download/windows/
    - Create database: `alumate`
    - Create user: `postgres` / `password`

4. **Install Composer**

    ```powershell
    # Download from https://getcomposer.org/download/
    # Add to PATH
    ```

5. **Setup Project**

    ```powershell
    # Clone and navigate to project
    cd D:\DevCenter\abuilds\alumate

    # Install PHP dependencies
    composer install

    # Install Node dependencies
    npm install

    # Environment file
    copy .env.example .env
    php artisan key:generate

    # Database (PostgreSQL)
    # Update .env with your DB credentials
    php artisan migrate
    php artisan tenants:migrate

    # Optional: Seed sample data
    php artisan db:seed --class=DemoDataSeeder
    ```

### macOS/Linux

```bash
# Install dependencies (macOS with Homebrew)
brew install php@8.3
brew install node
brew install postgresql@17
brew install composer

# Enable PHP extensions
# Edit /usr/local/etc/php/8.3/php.ini
# Uncomment: extension=pdo_pgsql, extension=redis, etc.

# PostgreSQL setup
brew services start postgresql@17
createdb alumate

# Project setup
git clone git@github.com:your-org/alumate.git
cd alumate
composer install
npm install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan tenants:migrate

# Start servers
composer run dev
```

---

## 🔧 Configuration

### Environment Variables (.env)

```bash
# Application
APP_NAME="Alumate"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=alumate
DB_USERNAME=postgres
DB_PASSWORD=password

# Testing (SQLite for speed)
DB_TESTING_CONNECTION=sqlite
DB_TESTING_DATABASE=:memory:

# Cache & Queue (Redis)
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (Mailtrap for development)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
```

### IDE Setup

#### PHPStorm / IntelliJ

1. **Install Plugins:**
    - Laravel Plugin
    - PHP Pest (for testing)
    - .env files support
    - Vue.js plugin

2. **Configure PHP Interpreter:**
    - Settings → PHP → CLI Interpreter → Add
    - Select PHP 8.3 executable

3. **Configure Code Style:**
    - Import `pint.json` for Laravel Pint
    - Settings → Editor → Code Style → PHP → Import Scheme

#### VS Code

1. **Recommended Extensions:**

    ```json
    {
        "recommendations": [
            "bmewburn.vscode-intelephense-client",
            "shufo.vscode-blade-formatter",
            "vue.volar",
            "dbaeumer.vscode-eslint",
            "esbenp.prettier-vscode",
            "ms-vscode.vscode-typescript-next"
        ]
    }
    ```

2. **Settings:**
    ```json
    {
        "php.validate.executablePath": "D:\\DevCenter\\xampp\\php-8.3.23\\php.exe",
        "editor.formatOnSave": true,
        "editor.defaultFormatter": "esbenp.prettier-vscode"
    }
    ```

---

## 📁 Project Structure Guide

### Where to Find Things

| What            | Where                       | Example                    |
| --------------- | --------------------------- | -------------------------- |
| **Controllers** | `app/Http/Controllers/`     | `UserController.php`       |
| **Models**      | `app/Models/`               | `User.php`                 |
| **Services**    | `app/Services/`             | `EmailService.php`         |
| **Routes**      | `routes/`                   | `web.php`, `api.php`       |
| **Views/Pages** | `resources/js/Pages/`       | `Dashboard/Index.vue`      |
| **Components**  | `resources/js/Components/`  | `UserCard.vue`             |
| **Composables** | `resources/js/composables/` | `useAuth.js`               |
| **Stores**      | `resources/js/stores/`      | `auth.js`                  |
| **Tests**       | `tests/`                    | `Unit/Models/UserTest.php` |
| **Migrations**  | `database/migrations/`      | `create_users_table.php`   |

### Multi-Tenant Code Structure

```php
// Tenant context is automatically set by middleware
// In controllers, just use models normally:
$user = User::find(1); // Automatically queries tenant database

// For central (non-tenant) data:
$tenant = \App\Models\Tenant::find(1); // Central database
```

---

## 🧪 Development Workflow

### Daily Development Cycle

```bash
# 1. Pull latest changes
git pull origin develop

# 2. Install new dependencies
composer install
npm install

# 3. Run migrations (if any)
php artisan migrate
php artisan tenants:migrate

# 4. Start development servers
composer run dev
# OR separately:
php artisan serve --port=8080
npm run dev

# 5. Make changes...

# 6. Run tests
vendor/bin/pest
npm test

# 7. Code quality checks
vendor/bin/pint
npm run lint

# 8. Type checking
npx vue-tsc --noEmit

# 9. Commit
git add .
git commit -m "feat: add new feature"
git push origin feature/your-feature
```

### Branching Strategy

```
main (production)
  ↑
develop (integration)
  ↑
feature/* (feature branches)
hotfix/* (emergency fixes)
```

**Naming Conventions:**

- Features: `feature/user-profile-redesign`
- Bug fixes: `fix/login-validation`
- Hotfixes: `hotfix/security-patch`

---

## 📝 Coding Standards

### PHP Standards

1. **Use Strict Types**

    ```php
    <?php
    declare(strict_types=1);

    namespace App\Services;

    class UserService
    {
        public function findById(int $id): ?User
        {
            // ...
        }
    }
    ```

2. **Follow PSR-12**

    ```bash
    # Auto-fix with Pint
    vendor/bin/pint

    # Check without fixing
    vendor/bin/pint --test
    ```

3. **Document Public APIs**
    ```php
    /**
     * Find a user by their ID.
     *
     * @param int $id The user ID
     * @return User|null The user or null if not found
     * @throws UserNotFoundException When user is deleted
     */
    public function findById(int $id): ?User
    ```

### Vue/TypeScript Standards

1. **Use Composition API**

    ```vue
    <script setup lang="ts">
    import { ref, computed } from 'vue';
    import type { User } from '@/types';

    const props = defineProps<{
        user: User;
    }>();

    const isActive = computed(() => props.user.status === 'active');

    const handleClick = () => {
        // ...
    };
    </script>
    ```

2. **No `any` Types**

    ```typescript
    // ❌ Bad
    const data: any = response.data;

    // ✅ Good
    const data: User[] = response.data as User[];
    ```

3. **Use Composables for Logic**
    ```typescript
    // composables/useAuth.ts
    export function useAuth() {
        const user = computed(() => authStore.user);
        const isAuthenticated = computed(() => !!user.value);

        return { user, isAuthenticated };
    }
    ```

---

## 🧪 Testing Guide

### Running Tests

```bash
# All tests
php artisan test

# Specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Specific file
php artisan test tests/Unit/Models/UserTest.php

# With coverage
vendor/bin/pest --coverage

# Frontend tests
npm test
npm run test:ui  # Interactive mode

# Specific test file
npm test -- tests/Js/components/Button.test.ts
```

### Writing Tests

#### PHP Unit Test

```php
<?php
uses()->group('unit');

it('can create a user', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    expect($user)
        ->email->toBe('test@example.com')
        ->id->toBeInt();
});
```

#### Vue Component Test

```typescript
import { mount } from '@vue/test-utils';
import Button from '@/Components/Button.vue';

describe('Button', () => {
    it('renders correctly', () => {
        const wrapper = mount(Button, {
            props: { label: 'Click me' },
        });

        expect(wrapper.text()).toBe('Click me');
    });
});
```

---

## 🐛 Troubleshooting

### Common Issues

#### Issue: "No code coverage driver available"

**Solution:**

```bash
# Windows (XAMPP)
# Edit php.ini and uncomment:
extension=xdebug

# macOS/Linux
pecl install xdebug
# Add to php.ini: zend_extension="xdebug.so"
```

#### Issue: "Tenant not found" error

**Solution:**

```bash
# Ensure migrations are run for tenants
php artisan tenants:migrate

# Check tenant exists in central database
php artisan tinker
\App\Models\Tenant::all();
```

#### Issue: "Vite connection refused"

**Solution:**

```bash
# Restart Vite dev server
npm run dev

# Or check firewall settings
# Vite runs on port 5173 by default
```

#### Issue: PostgreSQL connection refused

**Solution:**

```bash
# Windows - Start PostgreSQL service
# Services → PostgreSQL 17 → Start

# macOS/Linux
brew services start postgresql@17

# Verify connection
pg_isready -h localhost -p 5432
```

### Getting Help

1. **Check Documentation:**
    - `AGENTS.md` - AI agent guidelines
    - `README.md` - Project overview
    - `plans/readiness/` - Architecture & deployment

2. **Team Channels:**
    - Slack: #dev-help
    - GitHub Issues: Label with `question`

3. **Run Diagnostics:**
    ```bash
    php artisan about
    php artisan route:list
    php artisan migrate:status
    ```

---

## 📚 Learning Resources

### Laravel

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Laravel Bootcamp](https://bootcamp.laravel.com/)
- [Laracasts](https://laracasts.com/)

### Vue.js

- [Vue.js Guide](https://vuejs.org/guide/introduction.html)
- [Vue Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Inertia.js](https://inertiajs.com/)

### Testing

- [Pest Documentation](https://pestphp.com/)
- [Vitest](https://vitest.dev/)

### Multi-Tenancy

- [Tenancy for Laravel](https://tenancyforlaravel.com/)

---

## ✅ Onboarding Checklist

### Week 1

- [ ] Environment setup completed
- [ ] Can run application locally
- [ ] Read architecture overview
- [ ] Complete first PR (documentation fix)
- [ ] Attend team intro meeting

### Week 2

- [ ] Understand multi-tenant architecture
- [ ] Write first unit test
- [ ] Review coding standards document
- [ ] Shadow feature development
- [ ] Set up IDE with recommended plugins

### Week 3

- [ ] Complete first feature independently
- [ ] Understand CI/CD pipeline
- [ ] Participate in code review
- [ ] Deploy to staging environment

### Month 1

- [ ] Own a feature from design to deployment
- [ ] Mentor next new hire
- [ ] Contribute to technical documentation
- [ ] Suggest process improvements

---

**Welcome to the team! 🎉**

If you find issues with this guide, please submit a PR to improve it.

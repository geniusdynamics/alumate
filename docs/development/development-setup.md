# Development Environment Setup

This guide provides step-by-step instructions for setting up a complete development environment for the Alumni Platform.

## Prerequisites

### System Requirements

**Minimum Requirements:**
- **Operating System**: Windows 10/11, macOS 12+, or Ubuntu 18.04+
- **CPU**: 2.4 GHz dual-core processor or higher
- **RAM**: 8 GB minimum (16 GB recommended)
- **Storage**: 20 GB free disk space
- **Network**: Stable internet connection

### Required Software

| Software | Version | Download Link | Purpose |
|----------|---------|----------------|---------|
| **PHP** | 8.3.23+ | [php.net](https://php.net/downloads) | Backend runtime |
| **Composer** | 2.6.0+ | [getcomposer.org](https://getcomposer.org) | PHP dependency management |
| **Node.js** | 18.0+ | [nodejs.org](https://nodejs.org) | JavaScript runtime |
| **npm** | 8.0+ (included with Node.js) | - | Node.js package management |
| **PostgreSQL** | 13.0+ | [postgresql.org](https://postgresql.org) | Primary database |
| **Redis** | 6.0+ (optional) | [redis.io](https://redis.io) | Caching and sessions |
| **Git** | 2.30+ | [git-scm.com](https://git-scm.com) | Version control |
| **Visual Studio Code** | Latest | [code.visualstudio.com](https://code.visualstudio.com) | IDE (recommended) |

### PostgreSQL Setup

1. **Windows:**
   ```bash
   # Download and install PostgreSQL from official website
   # Or use Chocolatey:
   choco install postgresql13
   ```

2. **macOS** (using Homebrew):
   ```bash
   brew install postgresql@13
   brew services start postgresql@13
   ```

3. **Linux:**
   ```bash
   sudo apt update
   sudo apt install postgresql postgresql-contrib
   sudo systemctl start postgresql
   sudo systemctl enable postgresql
   ```

4. **Create Database:**
   ```sql
   -- Connect to PostgreSQL as superuser
   psql -U postgres

   -- Create database user
   CREATE USER alumnate_user WITH PASSWORD 'your_secure_password';
   CREATE DATABASE alumnate_db OWNER alumnate_user;
   CREATE DATABASE alumnate_testing_db OWNER alumnate_user;

   -- Grant privileges
   GRANT ALL PRIVILEGES ON DATABASE alumnate_db TO alumnate_user;
   GRANT ALL PRIVILEGES ON DATABASE alumnate_testing_db TO alumnate_user;

   -- For multi-tenant setup
   ALTER USER alumnate_user CREATEDB;
   ```

### Redis Setup (Optional but recommended)

1. **Windows:**
   ```bash
   # Download from GitHub releases or use Chocolatey
   choco install redis-64
   redis-server
   ```

2. **macOS:**
   ```bash
   brew install redis
   brew services start redis
   ```

3. **Linux:**
   ```bash
   sudo apt install redis-server
   sudo systemctl start redis-server
   sudo systemctl enable redis-server
   ```

## Initial Project Setup

### 1. Clone Repository

```bash
# Clone the repository
git clone https://github.com/your-org/alumnate-platform.git
cd alumnate-platform
```

### 2. Environment Configuration

```bash
# Copy environment template
cp .env.example .env

# Edit .env file with your configuration
nano .env  # or use your preferred editor
```

**Critical .env Configuration:**

```bash
# Application
APP_NAME=AlumniPlatform
APP_ENV=local
APP_KEY=base64:generate-32-char-random-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=alumnate_db
DB_USERNAME=alumnate_user
DB_PASSWORD=your_secure_password

# Cache (Redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=redis

# Mail (for development)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

# API
API_BASE_URL=http://localhost:8000/api/v1
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

### 3. Generate Application Key

```bash
# Generate 32-character application key
php artisan key:generate
```

### 4. Install PHP Dependencies

```bash
# Install Composer dependencies
composer install

# Install specific versions if needed
composer require laravel/framework:12.26.4
composer require inertiajs/inertia-laravel:2.0.6
```

### 5. Install Node.js Dependencies

```bash
# Install npm dependencies
npm install

# For production builds
npm ci
```

### 6. Database Setup

```bash
# Run database migrations
php artisan migrate

# For multi-tenant setup
php artisan tenants:migrate

# Seed database with demo data
php artisan db:seed

# Seed with specific seeder
php artisan db:seed --class=DemoDataSeeder
```

### 7. Create Sample Data (Development)

```bash
# Run custom data creation scripts
php scripts/data/create_sample_data.php
php scripts/data/create_tenant_sample_data.php

# Or create specific tenant
php scripts/data/create_tenant.php --domain=test.institution.edu --name="Test Institution"
```

## Development Workflow Setup

### 1. Laravel Development Server

```bash
# Standard development server
php artisan serve --host=127.0.0.1 --port=8000

# Development server with forced HTTPS
php artisan serve --host=127.0.0.1 --port=8000 --scheme=https
```

### 2. Frontend Development Server (Vite)

```bash
# Hot module replacement development server
npm run dev

# Development server on specific port
npm run dev -- --port=3000

# With verbose logging
npm run dev -- --verbose
```

### 3. Start Queue Worker (Optional)

```bash
# Start queue worker for background processing
php artisan queue:work

# Start with specific connection
php artisan queue:work redis

# With retry failed jobs
php artisan queue:work --tries=3
```

### 4. Start WebSocket Server (Laravel Echo)

```bash
# If using real-time features
php artisan websockets:serve
```

## VS Code Development Setup

### Recommended Extensions

```json
{
  "recommendations": [
    "ms-vscode.vscode-typescript-next",
    "bradlc.vscode-tailwindcss",
    "ms-vscode.vscode-json",
    "formulahendry.auto-rename-tag",
    "christian-kohler.path-intellisense",
    "ms-vscode-remote.remote-ssh",
    "bmewburn.vscode-intelephense-client",
    "bradlc.vscode-tailwindcss"
  ]
}
```

### Workspace Settings

**.vscode/settings.json:**
```json
{
  "php.validate.executablePath": "C:/path-to-your/php-8.3.23/php.exe",
  "php.format.codeStyle": "PSR-2",
  "emmet.triggerExpansionOnTab": true,
  "css.validate": false,
  "less.validate": false,
  "scss.validate": false,
  "typescript.preferences.importModuleSpecifier": "relative",
  "editor.codeActionsOnSave": {
    "source.fixAll": "explicit"
  },
  "tailwindCSS.includeLanguages": {
    "plaintext": "html"
  },
  "files.associations": {
    "*.blade.php": "blade",
    "*.ts": "typescript",
    "*.vue": "vue"
  }
}
```

### Debug Configuration

**.vscode/launch.json:**
```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceRoot}"
      }
    },
    {
      "name": "Launch PHP Server",
      "type": "php",
      "request": "launch",
      "program": "${workspaceRoot}/artisan",
      "args": ["serve", "--host=127.0.0.1", "--port=8000"],
      "cwd": "${workspaceRoot}"
    }
  ]
}
```

## Testing Environment Setup

### PHP Testing Setup

```bash
# Install PHPUnit globally (optional)
composer global require phpunit/phpunit

# Run basic test
php artisan test

# Run with coverage
php artisan test --coverage
```

### JavaScript Testing Setup

```bash
# Install testing dependencies
npm install --save-dev @typescript-eslint/eslint-plugin @typescript-eslint/parser
npm install --save-dev jest vue-jest @vue/test-utils

# Run JavaScript tests
npm test

# Run with coverage
npm run test:coverage
```

## Advanced Development Setup

### Docker Development (Alternative)

```dockerfile
# Dockerfile for development
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-client \
    redis-tools

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install PHP dependencies
RUN composer install

# Install Node.js dependencies
RUN npm install && npm run build

# Expose port 8000
EXPOSE 8000

# Start Laravel server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

### Multi-Tenant Development

```bash
# Seed tenant databases
php scripts/data/create_tenant_sample_data.php

# Switch to specific tenant for development
php artisan tenant:switch your-tenant-domain.com

# Migrate tenant database
php artisan tenants:migrate --tenants=your-tenant-domain.com
```

## Troubleshooting Setup Issues

### Common Problems and Solutions

1. **PHP Extensions Missing:**
   ```bash
   # Ubuntu/Debian
   sudo apt install php8.3-cli php8.3-fpm php8.3-pgsql php8.3-redis php8.3-mbstring

   # Windows with XAMPP
   # Check php.ini for commented extensions
   ```

2. **Composer Memory Issues:**
   ```bash
   # Increase memory limit
   COMPOSER_MEMORY_LIMIT=2G composer install

   # Or temporarily
   php -d memory_limit=2G composer install
   ```

3. **Database Connection Issues:**
   ```bash
   # Test PostgreSQL connection
   psql -h localhost -p 5432 -U alumnate_user -d alumnate_db

   # Check PostgreSQL service status
   sudo systemctl status postgresql
   ```

4. **Node.js Module Issues:**
   ```bash
   # Clear node_modules and reinstall
   rm -rf node_modules package-lock.json
   npm cache clean --force
   npm install

   # Use specific npm version for compatibility
   npm install --legacy-peer-deps
   ```

5. **Vite Hot Reload Issues:**
   ```bash
   # Clear Vite cache
   rm -rf node_modules/.vite

   # Restart development server
   npm run dev
   ```

### Health Checks

```bash
# Check PHP version and extensions
php -v
php -m | grep -E "(pgsql|redis|mbstring)"

# Test Composer
composer diagnose

# Test Node.js and npm
node --version
npm --version

# Test database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection OK';"

# Test Redis connection (if used)
php artisan tinker --execute="Redis::ping(); echo 'Redis connection OK';"
```

## Performance Optimization

### Development Optimizations

```bash
# Enable query logging for debugging
php artisan config:clear && php artisan config:cache

# Enable debugbar in development
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

### Production-like Development

```bash
# Enable production-like settings
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear caches for development
php artisan optimize:clear
```

## Contributing and Workflow

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/your-feature-name

# Make changes...

# Run tests
php artisan test
npm test

# Code quality checks
composer run quality
npm run lint

# Commit changes
git commit -m "feat: brief description of changes"

# Push to remote
git push origin feature/your-feature-name
```

### Pre-commit Hooks

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash

# Run PHP tests
echo "Running PHP tests..."
php artisan test --no-coverage
if [ $? -ne 0 ]; then
    echo "PHP tests failed. Please fix before committing."
    exit 1
fi

# Run JavaScript tests
echo "Running JavaScript tests..."
npm test
if [ $? -ne 0 ]; then
    echo "JavaScript tests failed. Please fix before committing."
    exit 1
fi

echo "All tests passed!"
```

## Support

For setup issues that can't be resolved:

1. **Check logs**: `storage/logs/laravel.log`
2. **Debug mode**: Set `APP_DEBUG=true` in .env
3. **Community**: Check [GitHub Issues](https://github.com/your-org/alumnate-platform/issues)
4. **Documentation**: See troubleshooting guides at `/docs/development/troubleshooting/`
5. **Support**: Contact developer-support@alumnate.edu

---

## Quick Start Summary

For experienced developers:

```bash
# One-line setup (after prerequisites)
git clone <repo> && cd alumnate-platform
cp .env.example .env
# Edit .env with your database settings
composer install && npm install
php artisan key:generate
php artisan migrate && php artisan db:seed
php artisan serve &
npm run dev &
```

Your development environment will be running at:
- **Backend**: http://localhost:8000
- **Frontend**: http://localhost:3000 (via Vite)
- **API**: http://localhost:8000/api/v1
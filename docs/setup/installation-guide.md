# Setup and Installation Guide

## System Requirements

### Minimum Hardware Requirements

- **CPU**: Dual-core processor (Intel i5 or equivalent)
- **RAM**: 8 GB minimum, 16 GB recommended
- **Storage**: 20 GB available disk space
- **Network**: Reliable internet connection (min. 10 Mbps)

### Recommended Hardware Requirements

- **CPU**: Quad-core processor (Intel i7 or equivalent)
- **RAM**: 16 GB or more
- **Storage**: 50 GB SSD with high I/O performance
- **Network**: High-speed internet (100 Mbps or better)

## Software Prerequisites

### Required Software

#### PHP 8.3+
```bash
# Download and install PHP 8.3+ from php.net
# On Windows, use XAMPP/WAMP or install directly
# Verify installation
php --version
php 8.3.0 (cli) (built: Nov 24 2023 23:20:59) (NTS)
```

#### PostgreSQL 13+
```bash
# Install PostgreSQL server
# Create database user and databases
createdb alumni_platform
createuser alumni_user --password --superuser --createdb
```

#### Node.js 18+
```bash
# Install Node.js LTS version
# Verify installation
node --version
v18.19.0
npm --version
9.4.0
```

#### Composer 2.x
```bash
# Install Composer globally
# Verify installation
composer --version
Composer version 2.5.7
```

### Optional Components

#### Redis Server
```bash
# Install Redis for caching and session management
redis-server --version
Redis server v=7.0.8
```

#### Git
```bash
# Required for version control
git --version
git version 2.39.2
```

## Installation Process

### 1. Download Project Files

#### Option A: Using Git
```bash
git clone https://github.com/your-org/alumni-platform.git
cd alumni-platform
```

#### Option B: Direct Download
- Download the latest release archive from GitHub
- Extract files to your web server directory
- Navigate to the extracted directory

### 2. Configure Environment

#### Copy Environment File
```bash
cp .env.example .env
```

#### Edit .env File
```bash
# Open .env file with your preferred editor
nano .env  # or code .env on Windows
```

**Critical .env Settings:**

```env
# Application Settings
APP_NAME="Alumni Tracking Platform"
APP_ENV=production
APP_KEY=base64:your-application-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Settings
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=alumni_platform
DB_USERNAME=alumni_user
DB_PASSWORD=your-secure-password

# Cache & Session Settings
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail Settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com

# File Storage Settings
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

This will set the `APP_KEY` value in your .env file.

### 4. Install PHP Dependencies

```bash
# Install Composer packages
composer install --optimize-autoloader --no-dev

# Generate autoloader
composer dump-autoload --optimize
```

### 5. Install Frontend Dependencies

```bash
# Install Node.js packages
npm install

# For production builds
npm install --production
```

### 6. Database Setup

#### Run Migrations
```bash
# Run database migrations
php artisan migrate

# Run tenant-specific migrations
php artisan tenants:migrate
```

#### Seed Database (Optional)
```bash
# Seed with sample data
php artisan db:seed --class=DemoDataSeeder
php scripts/data/create_sample_data.php
php scripts/data/create_tenant_sample_data.php
```

### 7. Build Frontend Assets

```bash
# For development
npm run dev

# For production
npm run build
```

### 8. Set File Permissions

#### Linux/Unix
```bash
# Set proper permissions
sudo chown -R www-data:www-data /path/to/alumni-platform
sudo chmod -R 755 /path/to/alumni-platform
sudo chmod -R 775 /path/to/alumni-platform/storage
sudo chmod -R 775 /path/to/alumni-platform/bootstrap/cache
```

#### Windows
```cmd
# Right-click folder -> Properties -> Security -> Full Control for IIS_IUSRS
```

### 9. Configure Web Server

#### Nginx Configuration
Create `/etc/nginx/sites-available/alumni-platform.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;

    root /path/to/alumni-platform/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
}

# SSL Configuration (recommended)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;

    # SSL Certificate settings
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    # ... same configuration as HTTP server block
}
```

#### Apache Configuration
Create `/etc/apache2/sites-available/alumni-platform.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/alumni-platform/public
    ErrorLog ${APACHE_LOG_DIR}/alumni-platform_error.log
    CustomLog ${APACHE_LOG_DIR}/alumni-platform_access.log combined

    <Directory /path/to/alumni-platform/public>
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### 10. SSL Certificate Setup

```bash
# Install Certbot for Let's Encrypt
sudo apt install certbot python3-certbot-nginx  # Ubuntu/Debian
sudo certbot --nginx -d your-domain.com

# For Apache
sudo certbot --apache -d your-domain.com
```

### 11. Final Steps

#### Create Super Admin User
```bash
php artisan tinker

# In the tinker shell:
$user = \App\Models\User::factory()->create([
    'name' => 'Super Admin',
    'email' => 'admin@your-domain.com',
    'password' => bcrypt('temporary-password'),
    'role' => 'super_admin'
]);
```

#### Verify Installation
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Generate optimized classes
php artisan optimize
```

#### Test Application
1. Visit `https://your-domain.com`
2. Verify all pages load correctly
3. Test user registration functionality
4. Check database connections

## Post-Installation Configuration

### Email Configuration
- Configure SMTP settings
- Set up email templates
- Test email sending functionality

### Backup Scheduling
```bash
# Set up cron job for automated backups
0 2 * * * pg_dump alumni_platform > /path/to/backup/$(date +\%Y\%m\%d)_alumni_backup.sql
```

### Security Hardening
- Change default admin passwords
- Disable debug mode in production
- Set up firewall rules
- Enable log monitoring

### Performance Optimization
- Enable opcode caching (OPcache)
- Configure database connection pooling
- Set up CDN for static assets
- Implement caching strategies

## Troubleshooting Installation Issues

### Common Installation Problems

#### Database Connection Errors
```bash
# Check PostgreSQL service
sudo systemctl status postgresql

# Verify database credentials
psql -U alumni_user -d alumni_platform -h localhost
```

#### Permission Errors
```bash
# Check file permissions
ls -la storage/
ls -la bootstrap/cache/

# Fix permissions if needed
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Dependency Installations
```bash
# Clear Composer cache
composer clear-cache

# Install dependencies with verbose output
composer install -vvv

# For NPM issues
npm cache clean --force
rm -rf node_modules
npm install
```

### Support Resources

- **Installation Logs**: Check `storage/logs/laravel.log`
- **PHP Errors**: Review PHP-FPM logs
- **Database Logs**: Check PostgreSQL logs
- **Support Portal**: Contact support team for assistance

## Next Steps

After successful installation:

1. **Configure Institution Settings** - Set up your school's branding and information
2. **Import Graduate Data** - Bulk upload existing alumni data
3. **Create User Roles** - Set up departments and user permissions
4. **Set Up Email Campaigns** - Configure communication automation
5. **Test Key Features** - Validate core functionality before announcing to users
6. **Training** - Train staff and early users on platform capabilities
7. **Go-Live** - Schedule announcement and soft launch

For additional support during installation, contact our technical support team at setup.support@alumni-platform.com.
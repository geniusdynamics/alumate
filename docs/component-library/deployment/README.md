# Deployment Guide

## Overview

This guide covers deploying the Component Library System across different environments, from development to production. It includes configuration management, scaling strategies, monitoring setup, and best practices for reliable deployments.

## Environment Configuration

### Development Environment

#### Local Setup

```bash
# Clone repository
git clone https://github.com/your-org/component-library-system.git
cd component-library-system

# Install dependencies
composer install
npm install

# Environment configuration
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate:fresh --seed

# Build assets
npm run dev

# Start development server
php artisan serve
```

#### Docker Development Setup

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.dev
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
      - /var/www/html/vendor
      - /var/www/html/node_modules
    environment:
      - APP_ENV=local
      - DB_HOST=database
      - REDIS_HOST=redis
    depends_on:
      - database
      - redis

  database:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: component_library
      MYSQL_USER: laravel
      MYSQL_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  mailhog:
    image: mailhog/mailhog
    ports:
      - "1025:1025"
      - "8025:8025"

volumes:
  mysql_data:
  redis_data:
```

```dockerfile
# Dockerfile.dev
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
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
```

### Staging Environment

#### Configuration

```env
# .env.staging
APP_NAME="Component Library - Staging"
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging.componentlibrary.com

DB_CONNECTION=mysql
DB_HOST=staging-db.internal
DB_PORT=3306
DB_DATABASE=component_library_staging
DB_USERNAME=laravel_staging
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=staging-redis.internal
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379

# CDN Configuration
CDN_URL=https://cdn-staging.componentlibrary.com
AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=component-library-staging

# CRM Integrations (Sandbox)
SALESFORCE_CLIENT_ID=${SALESFORCE_STAGING_CLIENT_ID}
SALESFORCE_CLIENT_SECRET=${SALESFORCE_STAGING_CLIENT_SECRET}
SALESFORCE_SANDBOX=true

HUBSPOT_API_KEY=${HUBSPOT_STAGING_API_KEY}

# Monitoring
SENTRY_LARAVEL_DSN=${SENTRY_STAGING_DSN}
LOG_CHANNEL=stack
LOG_LEVEL=info

# Performance
OCTANE_SERVER=swoole
OCTANE_HTTPS=true
```

#### Deployment Script

```bash
#!/bin/bash
# deploy-staging.sh

set -e

echo "🚀 Deploying to Staging Environment"

# Pull latest code
git fetch origin
git reset --hard origin/main

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --production

# Build assets
npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
php artisan queue:restart
php artisan octane:reload

# Run health checks
php artisan health:check

echo "✅ Staging deployment completed successfully"
```

### Production Environment

#### Infrastructure Requirements

**Minimum Requirements:**
- **Web Servers**: 2x 4 CPU, 8GB RAM
- **Database**: MySQL 8.0, 4 CPU, 16GB RAM, SSD storage
- **Cache**: Redis 7.0, 2 CPU, 4GB RAM
- **Load Balancer**: Application Load Balancer with SSL termination
- **CDN**: CloudFront or similar for static assets
- **Storage**: S3 or compatible object storage

**Recommended Production Setup:**
- **Web Servers**: 4x 8 CPU, 16GB RAM (auto-scaling)
- **Database**: RDS MySQL 8.0, Multi-AZ, 8 CPU, 32GB RAM
- **Cache**: ElastiCache Redis, 4 CPU, 8GB RAM
- **Queue Workers**: 2x 4 CPU, 8GB RAM (dedicated)
- **Monitoring**: CloudWatch, Sentry, New Relic

#### Production Configuration

```env
# .env.production
APP_NAME="Component Library"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://componentlibrary.com

# Database
DB_CONNECTION=mysql
DB_HOST=prod-db-cluster.cluster-xyz.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=component_library_prod
DB_USERNAME=laravel_prod
DB_PASSWORD=${DB_PASSWORD}

# Cache & Sessions
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis Cluster
REDIS_HOST=prod-redis-cluster.xyz.cache.amazonaws.com
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379
REDIS_PREFIX=component_library_

# File Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=component-library-prod
AWS_USE_PATH_STYLE_ENDPOINT=false

# CDN
CDN_URL=https://cdn.componentlibrary.com
ASSET_URL=https://cdn.componentlibrary.com

# Performance
OCTANE_SERVER=swoole
OCTANE_WORKERS=4
OCTANE_TASK_WORKERS=2
OCTANE_HTTPS=true

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=componentlibrary.com,www.componentlibrary.com

# Monitoring & Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning
SENTRY_LARAVEL_DSN=${SENTRY_PROD_DSN}

# CRM Integrations (Production)
SALESFORCE_CLIENT_ID=${SALESFORCE_PROD_CLIENT_ID}
SALESFORCE_CLIENT_SECRET=${SALESFORCE_PROD_CLIENT_SECRET}
SALESFORCE_SANDBOX=false

HUBSPOT_API_KEY=${HUBSPOT_PROD_API_KEY}
MAILCHIMP_API_KEY=${MAILCHIMP_PROD_API_KEY}

# Rate Limiting
RATE_LIMIT_API=1000
RATE_LIMIT_UPLOADS=100
RATE_LIMIT_ANALYTICS=500
```

## Deployment Strategies

### Blue-Green Deployment

```yaml
# .github/workflows/deploy-production.yml
name: Production Deployment

on:
  push:
    branches: [main]
    tags: ['v*']

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, iconv, json, mbstring, pdo
          
      - name: Install dependencies
        run: |
          composer install --no-dev --optimize-autoloader
          npm ci
          
      - name: Run tests
        run: |
          php artisan test --parallel
          npm run test
          
      - name: Security scan
        run: |
          composer audit
          npm audit

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Configure AWS credentials
        uses: aws-actions/configure-aws-credentials@v2
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: us-east-1
          
      - name: Deploy to Blue Environment
        run: |
          # Deploy to blue environment
          aws ecs update-service \
            --cluster component-library-prod \
            --service component-library-blue \
            --task-definition component-library:${{ github.sha }}
            
      - name: Health Check Blue Environment
        run: |
          # Wait for deployment and run health checks
          ./scripts/health-check.sh blue
          
      - name: Switch Traffic to Blue
        run: |
          # Update load balancer to point to blue environment
          aws elbv2 modify-listener \
            --listener-arn ${{ secrets.ALB_LISTENER_ARN }} \
            --default-actions Type=forward,TargetGroupArn=${{ secrets.BLUE_TARGET_GROUP_ARN }}
            
      - name: Verify Production Traffic
        run: |
          # Verify production is working correctly
          ./scripts/production-verification.sh
          
      - name: Cleanup Green Environment
        run: |
          # Scale down green environment
          aws ecs update-service \
            --cluster component-library-prod \
            --service component-library-green \
            --desired-count 0
```

### Rolling Deployment

```bash
#!/bin/bash
# rolling-deploy.sh

set -e

SERVERS=("web1.prod.internal" "web2.prod.internal" "web3.prod.internal" "web4.prod.internal")
DEPLOY_USER="deploy"
APP_PATH="/var/www/component-library"

echo "🚀 Starting rolling deployment"

for server in "${SERVERS[@]}"; do
    echo "📦 Deploying to $server"
    
    # Remove server from load balancer
    aws elbv2 deregister-targets \
        --target-group-arn $TARGET_GROUP_ARN \
        --targets Id=$server
    
    # Wait for connections to drain
    sleep 30
    
    # Deploy to server
    ssh $DEPLOY_USER@$server << 'EOF'
        cd /var/www/component-library
        
        # Backup current version
        cp -r . ../component-library-backup-$(date +%Y%m%d-%H%M%S)
        
        # Pull latest code
        git fetch origin
        git reset --hard origin/main
        
        # Install dependencies
        composer install --no-dev --optimize-autoloader
        npm ci --production
        npm run build
        
        # Run migrations (only on first server)
        if [ "$HOSTNAME" = "web1" ]; then
            php artisan migrate --force
        fi
        
        # Clear and cache
        php artisan config:clear
        php artisan cache:clear
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        
        # Restart services
        sudo systemctl reload php8.3-fpm
        sudo systemctl reload nginx
        
        # Health check
        curl -f http://localhost/health || exit 1
EOF
    
    # Re-register server with load balancer
    aws elbv2 register-targets \
        --target-group-arn $TARGET_GROUP_ARN \
        --targets Id=$server
    
    # Wait for server to be healthy
    sleep 15
    
    echo "✅ $server deployed successfully"
done

echo "🎉 Rolling deployment completed"
```

## Scaling Strategies

### Horizontal Scaling

#### Auto Scaling Configuration

```yaml
# infrastructure/autoscaling.yml
Resources:
  WebServerAutoScalingGroup:
    Type: AWS::AutoScaling::AutoScalingGroup
    Properties:
      VPCZoneIdentifier:
        - !Ref PrivateSubnet1
        - !Ref PrivateSubnet2
      LaunchTemplate:
        LaunchTemplateId: !Ref WebServerLaunchTemplate
        Version: !GetAtt WebServerLaunchTemplate.LatestVersionNumber
      MinSize: 2
      MaxSize: 10
      DesiredCapacity: 4
      TargetGroupARNs:
        - !Ref WebServerTargetGroup
      HealthCheckType: ELB
      HealthCheckGracePeriod: 300
      Tags:
        - Key: Name
          Value: ComponentLibrary-WebServer
          PropagateAtLaunch: true

  WebServerScaleUpPolicy:
    Type: AWS::AutoScaling::ScalingPolicy
    Properties:
      AdjustmentType: ChangeInCapacity
      AutoScalingGroupName: !Ref WebServerAutoScalingGroup
      Cooldown: 300
      ScalingAdjustment: 2

  WebServerScaleDownPolicy:
    Type: AWS::AutoScaling::ScalingPolicy
    Properties:
      AdjustmentType: ChangeInCapacity
      AutoScalingGroupName: !Ref WebServerAutoScalingGroup
      Cooldown: 300
      ScalingAdjustment: -1

  CPUAlarmHigh:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmDescription: Scale up on high CPU
      MetricName: CPUUtilization
      Namespace: AWS/EC2
      Statistic: Average
      Period: 300
      EvaluationPeriods: 2
      Threshold: 70
      ComparisonOperator: GreaterThanThreshold
      Dimensions:
        - Name: AutoScalingGroupName
          Value: !Ref WebServerAutoScalingGroup
      AlarmActions:
        - !Ref WebServerScaleUpPolicy

  CPUAlarmLow:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmDescription: Scale down on low CPU
      MetricName: CPUUtilization
      Namespace: AWS/EC2
      Statistic: Average
      Period: 300
      EvaluationPeriods: 2
      Threshold: 30
      ComparisonOperator: LessThanThreshold
      Dimensions:
        - Name: AutoScalingGroupName
          Value: !Ref WebServerAutoScalingGroup
      AlarmActions:
        - !Ref WebServerScaleDownPolicy
```

#### Queue Worker Scaling

```php
<?php
// app/Console/Commands/ScaleQueueWorkers.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ScaleQueueWorkers extends Command
{
    protected $signature = 'queue:scale';
    protected $description = 'Scale queue workers based on queue size';
    
    public function handle(): void
    {
        $queueSize = Redis::llen('queues:default');
        $currentWorkers = $this->getCurrentWorkerCount();
        
        $targetWorkers = $this->calculateTargetWorkers($queueSize);
        
        if ($targetWorkers > $currentWorkers) {
            $this->scaleUp($targetWorkers - $currentWorkers);
        } elseif ($targetWorkers < $currentWorkers) {
            $this->scaleDown($currentWorkers - $targetWorkers);
        }
        
        $this->info("Queue size: {$queueSize}, Workers: {$currentWorkers} -> {$targetWorkers}");
    }
    
    private function calculateTargetWorkers(int $queueSize): int
    {
        // Scale workers based on queue size
        if ($queueSize > 1000) return 10;
        if ($queueSize > 500) return 6;
        if ($queueSize > 100) return 4;
        if ($queueSize > 10) return 2;
        return 1;
    }
    
    private function getCurrentWorkerCount(): int
    {
        $output = shell_exec('supervisorctl status | grep queue-worker | wc -l');
        return (int) trim($output);
    }
    
    private function scaleUp(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            shell_exec('supervisorctl start queue-worker:*');
        }
    }
    
    private function scaleDown(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            shell_exec('supervisorctl stop queue-worker:*');
        }
    }
}
```

### Database Scaling

#### Read Replicas Configuration

```php
<?php
// config/database.php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        
        'mysql_read' => [
            'driver' => 'mysql',
            'host' => env('DB_READ_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_READ_PORT', env('DB_PORT', '3306')),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_READ_USERNAME', env('DB_USERNAME', 'forge')),
            'password' => env('DB_READ_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
    ],
];
```

```php
<?php
// app/Models/Component.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    // Use read replica for analytics queries
    public function scopeAnalytics($query)
    {
        return $query->setConnection('mysql_read');
    }
    
    // Use read replica for search operations
    public function scopeSearch($query, $term)
    {
        return $query->setConnection('mysql_read')
            ->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }
}
```

## Monitoring and Observability

### Application Performance Monitoring

#### Laravel Telescope (Development/Staging)

```php
<?php
// config/telescope.php

return [
    'enabled' => env('TELESCOPE_ENABLED', true),
    
    'domain' => env('TELESCOPE_DOMAIN'),
    
    'path' => env('TELESCOPE_PATH', 'telescope'),
    
    'driver' => env('TELESCOPE_DRIVER', 'database'),
    
    'storage' => [
        'database' => [
            'connection' => env('TELESCOPE_DB_CONNECTION', null),
            'chunk' => 1000,
        ],
    ],
    
    'watchers' => [
        Watchers\CacheWatcher::class => env('TELESCOPE_CACHE_WATCHER', true),
        Watchers\CommandWatcher::class => env('TELESCOPE_COMMAND_WATCHER', true),
        Watchers\DumpWatcher::class => env('TELESCOPE_DUMP_WATCHER', true),
        Watchers\EventWatcher::class => env('TELESCOPE_EVENT_WATCHER', true),
        Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),
        Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),
        Watchers\LogWatcher::class => env('TELESCOPE_LOG_WATCHER', true),
        Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),
        Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'hydrations' => true,
        ],
        Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),
        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'slow' => 100, // milliseconds
        ],
        Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),
        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
        ],
        Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
        Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],
];
```

#### Sentry Error Tracking

```php
<?php
// config/sentry.php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    
    'release' => env('SENTRY_RELEASE'),
    
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),
    
    'sample_rate' => env('SENTRY_SAMPLE_RATE', 1.0),
    
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),
    
    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'livewire' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'sql_transactions' => true,
        'http_client_requests' => true,
    ],
    
    'tracing' => [
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', false),
        'queue_jobs' => true,
        'sql_queries' => true,
        'requests' => true,
        'redis_commands' => env('SENTRY_TRACE_REDIS_COMMANDS', false),
        'http_client_requests' => true,
    ],
    
    'integrations' => [
        'breadcrumbs' => true,
        'environment_variables' => true,
    ],
];
```

#### Custom Health Checks

```php
<?php
// app/Console/Commands/HealthCheck.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheck extends Command
{
    protected $signature = 'health:check';
    protected $description = 'Perform comprehensive health check';
    
    public function handle(): int
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'integrations' => $this->checkIntegrations(),
        ];
        
        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');
        
        foreach ($checks as $service => $result) {
            $status = $result['status'] === 'healthy' ? '✅' : '❌';
            $this->line("{$status} {$service}: {$result['message']}");
        }
        
        if (!$allHealthy) {
            $this->error('❌ Health check failed');
            return 1;
        }
        
        $this->info('✅ All systems healthy');
        return 0;
    }
    
    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            $connectionTime = $this->measureTime(fn() => DB::select('SELECT 1'));
            
            return [
                'status' => $connectionTime < 100 ? 'healthy' : 'degraded',
                'message' => "Database responding in {$connectionTime}ms",
                'response_time' => $connectionTime
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => "Database connection failed: {$e->getMessage()}"
            ];
        }
    }
    
    private function checkRedis(): array
    {
        try {
            Redis::ping();
            $responseTime = $this->measureTime(fn() => Redis::ping());
            
            return [
                'status' => $responseTime < 50 ? 'healthy' : 'degraded',
                'message' => "Redis responding in {$responseTime}ms",
                'response_time' => $responseTime
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => "Redis connection failed: {$e->getMessage()}"
            ];
        }
    }
    
    private function checkStorage(): array
    {
        try {
            $testFile = 'health-check-' . time() . '.txt';
            Storage::put($testFile, 'health check');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);
            
            return [
                'status' => $exists ? 'healthy' : 'unhealthy',
                'message' => $exists ? 'Storage read/write successful' : 'Storage read/write failed'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => "Storage check failed: {$e->getMessage()}"
            ];
        }
    }
    
    private function checkQueue(): array
    {
        try {
            $queueSize = Redis::llen('queues:default');
            $failedJobs = DB::table('failed_jobs')->count();
            
            $status = 'healthy';
            if ($queueSize > 1000) $status = 'degraded';
            if ($failedJobs > 100) $status = 'degraded';
            if ($queueSize > 5000 || $failedJobs > 500) $status = 'unhealthy';
            
            return [
                'status' => $status,
                'message' => "Queue: {$queueSize} pending, {$failedJobs} failed",
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => "Queue check failed: {$e->getMessage()}"
            ];
        }
    }
    
    private function checkIntegrations(): array
    {
        $integrationHealth = cache('integration_health_check', []);
        
        if (empty($integrationHealth)) {
            return [
                'status' => 'unknown',
                'message' => 'Integration health data not available'
            ];
        }
        
        $unhealthy = collect($integrationHealth)
            ->filter(fn($health) => $health['status'] !== 'healthy')
            ->count();
            
        if ($unhealthy === 0) {
            return [
                'status' => 'healthy',
                'message' => 'All integrations healthy'
            ];
        }
        
        return [
            'status' => $unhealthy > 2 ? 'unhealthy' : 'degraded',
            'message' => "{$unhealthy} integrations unhealthy"
        ];
    }
    
    private function measureTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
```

### Infrastructure Monitoring

#### CloudWatch Dashboards

```json
{
  "widgets": [
    {
      "type": "metric",
      "properties": {
        "metrics": [
          ["AWS/ApplicationELB", "RequestCount", "LoadBalancer", "component-library-prod"],
          [".", "TargetResponseTime", ".", "."],
          [".", "HTTPCode_Target_2XX_Count", ".", "."],
          [".", "HTTPCode_Target_4XX_Count", ".", "."],
          [".", "HTTPCode_Target_5XX_Count", ".", "."]
        ],
        "period": 300,
        "stat": "Sum",
        "region": "us-east-1",
        "title": "Application Load Balancer Metrics"
      }
    },
    {
      "type": "metric",
      "properties": {
        "metrics": [
          ["AWS/RDS", "CPUUtilization", "DBInstanceIdentifier", "component-library-prod"],
          [".", "DatabaseConnections", ".", "."],
          [".", "ReadLatency", ".", "."],
          [".", "WriteLatency", ".", "."]
        ],
        "period": 300,
        "stat": "Average",
        "region": "us-east-1",
        "title": "RDS Database Metrics"
      }
    },
    {
      "type": "metric",
      "properties": {
        "metrics": [
          ["AWS/ElastiCache", "CPUUtilization", "CacheClusterId", "component-library-redis"],
          [".", "CacheHits", ".", "."],
          [".", "CacheMisses", ".", "."],
          [".", "NetworkBytesIn", ".", "."],
          [".", "NetworkBytesOut", ".", "."]
        ],
        "period": 300,
        "stat": "Average",
        "region": "us-east-1",
        "title": "Redis Cache Metrics"
      }
    }
  ]
}
```

## Security Considerations

### SSL/TLS Configuration

```nginx
# /etc/nginx/sites-available/component-library
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name componentlibrary.com www.componentlibrary.com;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/componentlibrary.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/componentlibrary.com/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/componentlibrary.com/chain.pem;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.componentlibrary.com;" always;
    
    root /var/www/component-library/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    # Static asset caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
    }
    
    # Security
    location ~ /\. {
        deny all;
    }
    
    location ~ ^/(storage|bootstrap/cache) {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name componentlibrary.com www.componentlibrary.com;
    return 301 https://$server_name$request_uri;
}
```

### Backup and Disaster Recovery

```bash
#!/bin/bash
# backup-production.sh

set -e

BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/component-library"
S3_BUCKET="component-library-backups"

echo "🔄 Starting production backup - $BACKUP_DATE"

# Database backup
echo "📊 Backing up database..."
mysqldump \
    --host=$DB_HOST \
    --user=$DB_USERNAME \
    --password=$DB_PASSWORD \
    --single-transaction \
    --routines \
    --triggers \
    $DB_DATABASE | gzip > "$BACKUP_DIR/database_$BACKUP_DATE.sql.gz"

# Application files backup
echo "📁 Backing up application files..."
tar -czf "$BACKUP_DIR/application_$BACKUP_DATE.tar.gz" \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    /var/www/component-library

# Storage backup (if not using S3)
if [ "$FILESYSTEM_DISK" != "s3" ]; then
    echo "💾 Backing up storage..."
    tar -czf "$BACKUP_DIR/storage_$BACKUP_DATE.tar.gz" \
        /var/www/component-library/storage/app
fi

# Upload to S3
echo "☁️ Uploading to S3..."
aws s3 sync "$BACKUP_DIR" "s3://$S3_BUCKET/daily/$BACKUP_DATE/"

# Cleanup old local backups (keep 7 days)
find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete

# Cleanup old S3 backups (keep 30 days)
aws s3 ls "s3://$S3_BUCKET/daily/" | while read -r line; do
    backup_date=$(echo $line | awk '{print $2}' | cut -d'/' -f1)
    if [[ $(date -d "$backup_date" +%s) -lt $(date -d "30 days ago" +%s) ]]; then
        aws s3 rm "s3://$S3_BUCKET/daily/$backup_date/" --recursive
    fi
done

echo "✅ Backup completed successfully"
```

This comprehensive deployment guide covers all aspects of deploying and scaling the Component Library System from development to production environments.
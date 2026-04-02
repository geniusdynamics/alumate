# Deployment Pipeline Documentation

This document describes the complete CI/CD deployment pipeline for the Alumate Project.

## Overview

The deployment pipeline is designed to ensure code quality, security, and reliable deployments across multiple environments. It includes:

- **Continuous Integration (CI)**: Automated testing and quality checks
- **Continuous Deployment (CD)**: Automated deployment to staging and production
- **Rollback Procedures**: Safe rollback capabilities for failed deployments
- **Notifications**: Real-time notifications via Slack

## Pipeline Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Development   │───▶│     Staging     │───▶│   Production    │
│   (develop)     │    │   (staging)     │    │     (main)      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                     │                     │
         ▼                     ▼                     ▼
   CI Pipeline          CI + Staging CD        CI + Prod CD
```

## Workflow Files

### 1. CI Pipeline (`.github/workflows/ci.yml`)

The main CI pipeline runs on every push and pull request:

**Triggers:**
- Push to `develop`, `main`, or `db` branches
- Pull requests to `develop`, `main`, or `db` branches

**Jobs:**
1. **lint-and-format**: Code quality checks (PHP Pint, ESLint)
2. **unit-tests**: Unit tests with SQLite database
3. **unit-tests-pgsql**: Unit tests with PostgreSQL
4. **integration-tests**: Integration tests with PostgreSQL
5. **feature-tests**: Feature tests with PostgreSQL
6. **full-tests-pgsql**: Full test suite with tenant migrations
7. **frontend-tests**: Frontend tests (Vitest) and build

### 2. Enhanced CI Pipeline (`.github/workflows/ci-enhanced.yml`)

Enhanced CI with additional security and quality checks:

**Additional Jobs:**
1. **code-quality**: PHPStan static analysis
2. **security-checks**: Dependency vulnerability scanning
3. **db-consistency**: Database schema validation

### 3. Staging Deployment (`.github/workflows/staging-deployment.yml`)

Automated deployment to staging and development environments:

**Triggers:**
- Push to `develop` or `staging` branches
- Manual workflow dispatch

**Jobs:**
1. **security-scan**: Pre-deployment security checks
2. **build-and-test**: Build and test application
3. **deploy-staging**: Deploy to staging environment
4. **deploy-development**: Deploy to development environment

### 4. Production Deployment (`.github/workflows/production-deployment.yml`)

Production deployment with approval gate:

**Triggers:**
- Push to `main` branch
- Manual workflow dispatch with environment selection

**Jobs:**
1. **security-scan**: Comprehensive security scanning
2. **build-and-test**: Build and test application
3. **deploy-staging**: Optional staging deployment
4. **deploy-production**: Production deployment with approval
5. **health-check**: Post-deployment health verification
6. **rollback**: Automatic rollback on failure

### 5. Rollback Workflow (`.github/workflows/rollback.yml`)

Manual rollback workflow for emergencies:

**Triggers:**
- Manual workflow dispatch

**Parameters:**
- `environment`: Target environment (production/staging/development)
- `reason`: Reason for rollback
- `target_release`: Optional specific release to rollback to

## Deployment Process

### Development Environment

1. **Code push** to `develop` branch
2. **CI Pipeline** runs automatically:
   - Code quality checks
   - Security scanning
   - Unit tests
   - Integration tests
   - Frontend tests
3. **Staging deployment** triggers automatically:
   - Security scan
   - Build and test
   - Deploy to staging

### Staging Environment

1. **Merge** from `develop` to `staging`
2. **Staging deployment** runs:
   - Full CI pipeline
   - Deployment to staging
3. **Testing** on staging environment
4. **Approval** for production deployment

### Production Environment

1. **Merge** from `staging` to `main`
2. **Production deployment** workflow:
   - Security scan
   - Full build and test
   - Database backup
   - Zero-downtime deployment
   - Health checks
   - Automated rollback on failure

## Rollback Procedures

### Automatic Rollback

The production deployment includes automatic rollback if:
- Health checks fail after deployment
- Application errors are detected
- Database migrations fail

### Manual Rollback

To perform a manual rollback:

1. Go to **Actions** tab in GitHub
2. Select **Rollback Deployment** workflow
3. Click **Run workflow**
4. Select environment (production/staging/development)
5. Enter reason for rollback
6. Optionally specify target release
7. Click **Run workflow**

### Rollback Commands

```bash
# Rollback to previous release
./deploy.production.sh rollback

# Rollback to specific release
DEPLOY_TAG=v2.0.0 ./deploy.production.sh

# Rollback via GitHub Actions
# Use the rollback.yml workflow with manual dispatch
```

## Environment Configuration

### Required Secrets

Configure the following secrets in GitHub repository settings:

#### Staging Environment
| Secret | Description |
|--------|-------------|
| `STAGING_HOST` | Staging server hostname |
| `STAGING_USER` | SSH user for staging server |
| `STAGING_SSH_KEY` | SSH private key |
| `STAGING_PATH` | Deployment path on server |
| `STAGING_DB_USER` | Database username |
| `STAGING_DB_PASSWORD` | Database password |
| `STAGING_REDIS_PASSWORD` | Redis password |
| `STAGING_APP_KEY` | Laravel application key |
| `STAGING_DOMAIN` | Staging domain name |

#### Production Environment
| Secret | Description |
|--------|-------------|
| `PROD_HOST` | Production server hostname |
| `PROD_USER` | SSH user for production server |
| `PROD_SSH_KEY` | SSH private key |
| `PROD_PATH` | Deployment path on server |
| `PROD_DB_USER` | Database username |
| `PROD_DB_PASSWORD` | Database password |
| `PROD_DB_NAME` | Database name |
| `PROD_REDIS_PASSWORD` | Redis password |
| `PROD_APP_KEY` | Laravel application key |
| `PROD_DOMAIN` | Production domain name |
| `PROD_DIRECT_SERVER` | Direct server access for fallback |

#### Notification Channels
| Secret | Description |
|--------|-------------|
| `SLACK_WEBHOOK_URL` | Slack webhook URL for notifications |
| `DISCORD_WEBHOOK_URL` | Discord webhook URL (optional) |
| `DEPLOY_WEBHOOK_URL` | Custom deployment webhook URL |

## Deployment Commands

### Manual Deployment

```bash
# Deploy to staging
DEPLOY_ENV=staging ./infrastructure/production/deploy.production.sh

# Deploy to production
DEPLOY_ENV=production ./infrastructure/production/deploy.production.sh

# Deploy specific tag
DEPLOY_TAG=v2.0.0 ./infrastructure/production/deploy.production.sh

# Skip database backup
BACKUP_DATABASE=false ./infrastructure/production/deploy.production.sh

# Disable rollback
ENABLE_ROLLBACK=false ./infrastructure/production/deploy.production.sh
```

### Deployment Script Options

| Option | Description |
|--------|-------------|
| `--rollback` | Perform rollback to previous release |
| `--env=ENV` | Set deployment environment |
| `--no-backup` | Skip backup before deployment |
| `--skip-tests` | Skip running tests |

## Health Checks

### Health Check Endpoints

| Endpoint | Description |
|----------|-------------|
| `/health` | Basic health check |
| `/health/detailed` | Detailed system status |
| `/health-check` | Deployment verification |

### Health Check Configuration

Health checks are configured in [`infrastructure/production/health-checks.php`](infrastructure/production/health-checks.php):

```php
// Health check includes:
// - Database connectivity
// - Redis connection
// - Queue worker status
// - Storage accessibility
// - SSL certificate validity
```

## Notifications

### Slack Notifications

Deployments send notifications to configured Slack channels:

- **#deployments**: General deployment notifications
- **#production-alerts**: Production alerts and rollbacks
- **#staging**: Staging environment updates
- **#ci-notifications**: CI pipeline results

### Notification Events

| Event | Channel | Message |
|-------|---------|---------|
| CI Pass | #ci-notifications | ✅ CI Pipeline PASSED |
| CI Fail | #ci-notifications | ❌ CI Pipeline FAILED |
| Deploy Start | #deployments | 🚀 Deployment started |
| Deploy Success | #deployments | ✅ Deployment successful |
| Deploy Fail | #production-alerts | ❌ Deployment failed |
| Rollback Start | #production-alerts | 🚨 Rollback initiated |
| Rollback Complete | #production-alerts | ✅ Rollback completed |

## Monitoring Integration

### Prometheus Metrics

The deployment pipeline integrates with Prometheus for monitoring:

```yaml
# Metrics endpoint: /metrics
# Includes:
# - Deployment duration
# - Health check status
# - Database migration status
# - Error rates
```

### Monitoring Dashboard

Access Grafana dashboards at:
- Staging: `http://staging-monitoring.your-domain.com`
- Production: `http://monitoring.your-domain.com`

## Troubleshooting

### Common Issues

#### Deployment Fails at Health Check

1. Check application logs: `tail -f storage/logs/deploy.log`
2. Verify database connectivity
3. Check Redis connection
4. Review Nginx error logs

```bash
# Check deployment logs
ssh user@host "cd /var/www/html && tail -f storage/logs/deploy.log"

# Check application health
curl https://your-domain.com/health/detailed

# Verify services
docker-compose ps
```

#### Rollback Fails

1. Check if previous release exists
2. Verify database backup is available
3. Manual database restore if needed

```bash
# List available releases
ls -la /var/www/html/releases/

# Manual database restore
pg_restore -h localhost -U postgres -d database backup_file.dump
```

### Debug Mode

Enable debug mode for detailed logging:

```bash
# Set environment variable
APP_DEBUG=true

# Check debug logs
tail -f storage/logs/laravel.log
```

## Security Considerations

### Pre-deployment Checks

1. **Dependency audit**: Scan for vulnerable packages
2. **Code analysis**: Static analysis for security issues
3. **Secret scanning**: Detect hardcoded credentials
4. **Access control**: Verify deployment permissions

### Post-deployment Verification

1. **Security headers**: Verify CSP, HSTS, etc.
2. **SSL certificate**: Check certificate validity
3. **Firewall rules**: Verify network access
4. **Audit logging**: Enable comprehensive logging

## Performance Optimization

### Build Optimization

- **Dependency caching**: Composer and npm caches
- **Parallel testing**: Run tests in parallel
- **Artifact reuse**: Reuse build artifacts
- **Incremental builds**: Use build caches

### Deployment Optimization

- **Zero-downtime**: Symlink-based switching
- **Database backup**: Pre-deployment backups
- **Health checks**: Automated verification
- **Rollback capability**: Fast recovery

## Best Practices

### Code Review

1. All changes must pass CI before merge
2. Require at least 1 approval for production
3. Use feature flags for risky changes
4. Test in staging before production

### Deployment Schedule

- **Staging**: Automatic on push to `develop`
- **Production**: Manual approval required
- **Hotfixes**: Fast-track with approver notification

### Rollback Criteria

Rollback is automatic if:
- Health check fails within 5 minutes
- Error rate exceeds 5%
- Database migration fails

Manual rollback if:
- Critical bugs discovered post-deployment
- Performance degradation
- Security vulnerabilities found

## Support

### Documentation

- [Infrastructure README](infrastructure/README.md)
- [Production Configuration](docs/PRODUCTION-CONFIGURATION.md)
- [Monitoring Guide](docs/MONITORING.md)
- [Backup Documentation](docs/BACKUP.md)

### Emergency Contacts

- **DevOps Team**: #devops-support
- **On-call**: [PagerDuty link]
- **Escalation**: [Escalation policy link]

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2024-01-01 | Initial deployment pipeline |
| 1.1.0 | 2024-02-15 | Added rollback workflow |
| 1.2.0 | 2024-03-01 | Enhanced security scanning |
| 1.3.0 | 2024-04-01 | Added staging deployment |
| 1.4.0 | 2024-05-01 | Comprehensive documentation |

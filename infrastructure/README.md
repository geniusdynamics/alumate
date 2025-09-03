# Production Deployment Infrastructure

This directory contains the complete production deployment infrastructure for the Alumni Platform multi-tenant application.

## Overview

The production infrastructure is designed for:
- **Multi-tenant Laravel application** with Vue.js frontend
- **Zero-downtime deployments** with automated rollback
- **Docker containerization** for reliable environment consistency
- **Complete CI/CD pipeline** with testing and monitoring
- **Security hardening** and performance optimization
- **Automated monitoring** and alerting

## Directory Structure

```
infrastructure/
├── production/
│   ├── docker-compose.prod.yml    # Production Docker containers
│   ├── Dockerfile.php             # PHP/Laravel application container
│   ├── Dockerfile.nginx           # Nginx web server container
│   ├── .env.production            # Production environment configuration
│   ├── .env.staging              # Staging environment configuration
│   ├── deploy.production.sh      # Advanced deployment script
│   ├── scripts/
│   │   └── start.sh              # Container startup script
│   └── config/                   # Service configurations
│       ├── nginx/                 # Nginx reverse proxy configs
│       ├── php/                  # PHP-FPM optimization
│       └── supervisor/           # Process manager configs
├── staging/                      # Staging-specific configurations
└── development/                  # Development overrides
```

## Quick Start

### Prerequisites
- Docker and Docker Compose
- PostgreSQL database server
- Redis server
- SSL certificates (Let's Encrypt recommended)
- Production domain configuration

### Basic Deployment

1. **Configure Environment**
   ```bash
   cp infrastructure/production/.env.production .env.production
   # Edit with your production values
   ```

2. **Start Production Stack**
   ```bash
   cd infrastructure/production
   docker-compose -f docker-compose.prod.yml up -d
   ```

3. **Initial Deployment**
   ```bash
   ./deploy.production.sh
   ```

### Advanced Usage

#### Deploy via CI/CD
The application includes a complete GitHub Actions pipeline:
- Automatic testing on push/PR
- Security scanning
- Database backup before deployment
- Zero-downtime deployment with health checks
- Automated rollback on failure

#### Multi-Tenant Configuration
The infrastructure is designed for multiple tenant isolation:
- Separate database schemas per tenant
- Dedicated file storage per tenant
- Domain-based tenant routing
- Shared application resources

## Configuration Files

### Environment Variables
See `.env.production` for complete configuration options including:
- Database connections and credentials
- Redis cache configuration
- SSL/TLS certificates
- Security headers and CSP policies
- CDN and storage settings
- Monitoring and alerting

### Docker Configuration
- **Web Server**: Nginx with SSL termination
- **Application Server**: PHP-FPM with performance tuning
- **Database**: PostgreSQL with backups and monitoring
- **Cache**: Redis with persistent storage
- **Queue Worker**: Separate container for job processing
- **Monitoring**: Prometheus metrics and exporters

### Deployment Features

#### ✓ Zero-Downtime Deployment
- Symlink-based deployment strategy
- Health checks after deployment
- Automatic rollback on failure
- Database backup integration

#### ✓ Security
- SSL/TLS termination at load balancer
- Security headers (HSTS, CSP, X-Frame-Options)
- PHP-FPM chroot environment
- Container security hardening

#### ✓ Monitoring & Alerting
- Health check endpoints
- Error tracking integration
- Performance monitoring
- Log aggregation

#### ✓ Backup & Recovery
- Automated database backups
- File system snapshots
- Point-in-time recovery
- Backup verification

## Admin Commands

### Container Management
```bash
# View container status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f app

# Restart specific service
docker-compose -f docker-compose.prod.yml restart nginx

# Scale services
docker-compose -f docker-compose.prod.yml up -d --scale queue-worker=3
```

### Laravel Commands
```bash
# Execute artisan commands
docker-compose -f docker-compose.prod.yml exec app php artisan migrate

# Run tests
docker-compose -f docker-compose.prod.yml exec app php artisan test

# Clear caches
docker-compose -f docker-compose.prod.yml exec app php artisan optimize:clear
```

### Monitoring
```bash
# Check health
curl https://your-domain.com/health-check

# Check metrics
curl http://localhost:9090  # Prometheus

# Check queue status
docker-compose -f docker-compose.prod.yml exec app php artisan queue:status
```

## Troubleshooting

### Common Issues

**Container Startup Failures**
```bash
# Check container logs
docker-compose -f docker-compose.prod.yml logs

# Validate configuration
docker-compose -f docker-compose.prod.yml config

# Restart with verbose logging
docker-compose -f docker-compose.prod.yml up --verbose
```

**Health Check Failures**
- Verify database connectivity
- Check Redis connection
- Validate SSL certificates
- Review application logs

**Deployment Failures**
- Check deployment script permissions
- Verify SSH keys and connectivity
- Ensure sufficient disk space
- Validate backup integrity

### Logs Location
- **Application Logs**: `/var/www/html/storage/logs/`
- **Nginx Logs**: `/var/log/nginx/`
- **Database Logs**: `/var/log/postgresql/postgresql.log`
- **Deployment Logs**: `~/deployment.log`

## Security Checklist

- [ ] SSL certificates installed and valid
- [ ] Password policies enforced
- [ ] Two-factor authentication enabled
- [ ] Security headers configured
- [ ] File permissions verified
- [ ] Database connections encrypted
- [ ] Backup encryption enabled
- [ ] Monitoring alerts configured
- [ ] Access controls validated

## Performance Tuning

### Database
- Connection pooling configuration
- Query optimization and indexing
- Backup window scheduling
- Cache invalidation strategy

### Application
- OPcache configuration
- Session storage optimization
- Queue worker scaling
- CDN integration

### Infrastructure
- Load balancer configuration
- SSL termination optimization
- Network security groups
- Monitoring thresholds

---

## Support

For deployment issues:
1. Review container logs
2. Check health check endpoints
3. Verify configuration files
4. Review monitoring dashboards
5. Consult deployment runbooks

**Note**: Always test deployments in staging environment before production deployment.
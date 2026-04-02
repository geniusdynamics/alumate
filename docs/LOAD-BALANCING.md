# Load Balancing Configuration

This document describes the load balancing configuration for the Alumate Platform.

## Overview

The Alumate Platform uses Nginx as a load balancer to distribute traffic across multiple application servers. This provides:

- **High Availability**: Multiple servers ensure service continuity
- **Scalability**: Easy horizontal scaling by adding servers
- **Performance**: Distributes load to prevent single points of failure
- **Session Persistence**: Ensures users stay connected to the same server
- **Failover**: Automatic switching to backup servers

## Architecture

```
                    Internet
                        |
                        |
                    Nginx LB
                        |
        +---------------+---------------+
        |               |               |
    app_primary     app_backup     websocket_backend
        |               |
    +---+---+---+   +---+---+
    |app01|app02|app03|backup01|backup02
    +---+---+---+   +---+---+
```

## Configuration Files

| File | Purpose |
|------|---------|
| `infrastructure/production/config/nginx/nginx.conf` | Main Nginx configuration with upstream definitions |
| `infrastructure/production/config/nginx/conf.d/load-balancer.conf` | Load balancer server blocks |
| `infrastructure/production/config/nginx/conf.d/health-checks.conf` | Health check endpoints |
| `infrastructure/production/config/nginx/conf.d/failover.conf` | Failover configuration |

## Upstream Definitions

### Primary Application Load Balancer

```nginx
upstream app_primary {
    server app01:9000 weight=3 max_fails=3 fail_timeout=30s;
    server app02:9000 weight=3 max_fails=3 fail_timeout=30s;
    server app03:9000 weight=2 max_fails=3 fail_timeout=30s;
    keepalive 64;
}
```

- **Weight**: Traffic distribution ratio (higher = more traffic)
- **max_fails**: Failed requests before marking server as down
- **fail_timeout**: Time to wait before retrying failed server
- **keepalive**: Number of persistent connections to maintain

### Session-Aware Load Balancer

```nginx
upstream app_sessions {
    ip_hash;
    server app01:9000 weight=3;
    server app02:9000 weight=3;
    server app03:9000 weight=2;
    keepalive 64;
}
```

Uses IP hash algorithm for session persistence.

### WebSocket Load Balancer

```nginx
upstream websocket_backend {
    ip_hash;
    server websocket01:8080 weight=2;
    server websocket02:8080 weight=2;
    server websocket03:8080 weight=1;
    keepalive 32;
}
```

Sticky sessions for WebSocket connections.

### Backup Load Balancer

```nginx
upstream app_backup {
    server backup01:9000 weight=1 backup;
    server backup02:9000 weight=1 backup;
    keepalive 16;
}
```

Backup servers are only used when all primary servers are unavailable.

## Health Check Endpoints

| Endpoint | Purpose |
|----------|---------|
| `/health/simple` | Basic health check (200 OK) |
| `/health/detailed` | Detailed health information (JSON) |
| `/health/database` | Database connectivity check |
| `/health/cache` | Cache availability check |
| `/health/upstream/app-primary` | Primary upstream status |
| `/health/upstream/websocket` | WebSocket upstream status |
| `/health/upstream/analytics` | Analytics upstream status |
| `/lb/status` | Load balancer status |
| `/nginx/status` | Nginx metrics (stub_status) |

## Failover Configuration

### Automatic Failover

Failover is automatic when:

1. All primary servers return errors (500, 502, 503, 504)
2. Connections to primary servers timeout
3. Primary servers are marked as down due to max_fails

```nginx
proxy_next_upstream error timeout invalid_header http_500 http_502 http_503 http_504;
proxy_next_upstream_tries 3;
proxy_next_upstream_timeout 10s;
```

### Manual Failover

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/failover/trigger` | GET | Trigger manual failover |
| `/failover/recover` | GET | Recover to primary servers |
| `/failover/status` | GET | Check failover status |

### Management API

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/status` | GET | Get current failover status |
| `/api/failover` | GET | Trigger failover |
| `/api/recover` | GET | Recover to primary |
| `/api/upstreams` | GET | Get upstream server status |

## Session Persistence

### IP-Based Persistence

For authenticated routes, use `app_sessions` upstream:

```nginx
location /auth/ {
    proxy_pass http://app_sessions;
    # IP hash ensures same client reaches same server
}
```

### Cookie-Based Persistence

For application-level session persistence, configure PHP sessions:

```php
// config/session.php
'driver' => 'redis',
'store' => 'sessions',
```

## SSL Termination

SSL termination is handled at the Nginx level:

```nginx
ssl_certificate /etc/letsencrypt/live/alumni-platform.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/alumni-platform.com/privkey.pem;

include /etc/nginx/snippets/ssl-params.conf;
```

See [SSL-TLS.md](SSL-TLS.md) for detailed SSL configuration.

## Rate Limiting

| Zone | Rate | Purpose |
|------|------|---------|
| `php` | 10r/s | PHP request limiting |
| `api` | 30r/s | API request limiting |
| `general` | 100r/s | General request limiting |
| `conn_limit` | - | Connection limit per IP |

```nginx
limit_req_zone $binary_remote_addr zone=php:10m rate=10r/s;
limit_req zone=php burst=20 nodelay;
```

## Monitoring

### Log Format

The load balancer logs include upstream information:

```
log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                '$status $body_bytes_sent "$http_referer" '
                '"$http_user_agent" "$http_x_forwarded_for" '
                'upstream_addr=$upstream_addr '
                'upstream_status=$upstream_status '
                'upstream_response_time=$upstream_response_time';
```

### Metrics Endpoints

- `/metrics`: Prometheus metrics (requires exporter)
- `/nginx/status`: Nginx stub_status
- `/health/detailed`: Application health metrics

## Troubleshooting

### Check Upstream Status

```bash
# Check if servers are responding
curl http://app01:9000/health
curl http://app02:9000/health

# Check load balancer status
curl https://alumni-platform.com/lb/status
```

### View Error Logs

```bash
# Nginx error log
tail -f /var/log/nginx/error.log

# Failover events
tail -f /var/log/nginx/failover.log
```

### Test Failover

```bash
# Trigger manual failover
curl https://lb-management.alumni-platform.com/api/failover

# Check failover status
curl https://alumni-platform.com/failover/status
```

## Adding New Servers

To add a new application server:

1. Update `upstream` block in `nginx.conf`:
   ```nginx
   upstream app_primary {
       server app01:9000 weight=3 max_fails=3 fail_timeout=30s;
       server app02:9000 weight=3 max_fails=3 fail_timeout=30s;
       server app03:9000 weight=2 max_fails=3 fail_timeout=30s;
       server app04:9000 weight=1 max_fails=3 fail_timeout=30s;  # New server
       keepalive 64;
   }
   ```

2. Reload Nginx configuration:
   ```bash
   nginx -t && nginx -s reload
   ```

3. Verify the new server:
   ```bash
   curl http://app04:9000/health
   ```

## Performance Tuning

### Connection Settings

```nginx
# Worker connections
worker_connections 1024;

# Keepalive connections
keepalive_timeout 65;

# Upstream keepalive
upstream_keepalive_requests 1000;
upstream_keepalive_timeout 60s;
```

### Buffer Settings

```nginx
proxy_buffering on;
proxy_buffer_size 4k;
proxy_buffers 8 32k;
proxy_busy_buffers_size 64k;
```

## Security Considerations

1. **Restrict Management Access**: The management API should only be accessible from internal networks
2. **Enable SSL**: Always use HTTPS for production
3. **Rate Limiting**: Protect against DDoS attacks
4. **Security Headers**: Included via `security-headers.conf`
5. **Firewall**: Configure firewall to allow only necessary ports

## Disaster Recovery

### Backup Servers

Backup servers are automatically used when all primary servers fail. Ensure backup servers are:

- In a different availability zone
- Have adequate capacity to handle primary traffic
- Are regularly tested

### Recovery Procedures

1. **Automatic Recovery**: Primary servers return to rotation once healthy
2. **Manual Recovery**: Use management API to force recovery:
   ```bash
   curl https://lb-management.alumni-platform.com/api/recover
   ```

## References

- [Nginx Load Balancing](https://docs.nginx.com/nginx/admin-guide/load-balancer/)
- [Nginx Health Checks](https://docs.nginx.com/nginx/admin-guide/load-balancer/health-checks/)
- [Nginx Keepalive](https://docs.nginx.com/nginx/admin-guide/load-balancer/tcp-keepalives/)

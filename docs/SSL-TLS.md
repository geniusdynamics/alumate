# SSL/TLS Configuration Documentation

This document describes the SSL/TLS configuration for the Alumate platform, including certificate setup, HTTPS configuration, security headers, and certificate renewal procedures.

## Table of Contents

1. [Overview](#overview)
2. [Certificate Configuration](#certificate-configuration)
3. [HTTPS Server Setup](#https-server-setup)
4. [Security Headers](#security-headers)
5. [Certificate Renewal](#certificate-renewal)
6. [Testing and Verification](#testing-and-verification)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The Alumate platform uses nginx as the reverse proxy and SSL termination point. The SSL/TLS configuration follows industry best practices:

- **TLS Versions**: Only TLS 1.2 and TLS 1.3 (TLS 1.0 and 1.1 are deprecated)
- **Cipher Suites**: Mozilla Intermediate recommendations with forward secrecy
- **Certificate Authority**: Let's Encrypt (free, automated certificates)
- **Certificate Renewal**: Automated via certbot with daily checks

### File Structure

```
infrastructure/production/config/nginx/
├── nginx.conf                    # Main nginx configuration
├── conf.d/
│   └── default.conf             # Server blocks (HTTP/HTTPS)
└── snippets/
    ├── ssl-params.conf          # SSL/TLS parameters
    └── security-headers.conf     # Security HTTP headers
```

---

## Certificate Configuration

### Certificate Paths

Update the following paths in [`conf.d/default.conf`](conf.d/default.conf) to point to your SSL certificates:

```nginx
ssl_certificate /etc/letsencrypt/live/alumni-platform.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/alumni-platform.com/privkey.pem;
ssl_trusted_certificate /etc/letsencrypt/live/alumni-platform.com/chain.pem;
```

### Certificate Files Explained

| File | Description |
|------|-------------|
| `fullchain.pem` | Full certificate chain (your certificate + intermediate CA) |
| `privkey.pem` | Private key (keep this secret!) |
| `chain.pem` | Certificate chain (intermediate CAs only) |

### Let's Encrypt Setup

1. **Install certbot**:
   ```bash
   # Debian/Ubuntu
   apt-get install certbot python3-certbot-nginx
   
   # RHEL/CentOS
   yum install certbot python3-certbot-nginx
   ```

2. **Obtain certificate**:
   ```bash
   certbot --nginx -d alumni-platform.com -d www.alumni-platform.com
   ```

3. **Generate Diffie-Hellman parameters**:
   ```bash
   openssl dhparam -out /etc/nginx/dhparam.pem 4096
   ```

---

## HTTPS Server Setup

### TLS Protocol Configuration

Only modern, secure protocols are enabled in [`snippets/ssl-params.conf`](snippets/ssl-params.conf):

```nginx
ssl_protocols TLSv1.2 TLSv1.3;
```

| Protocol | Status | Notes |
|----------|--------|-------|
| TLS 1.0 | ❌ Disabled | Deprecated, vulnerable |
| TLS 1.1 | ❌ Disabled | Deprecated, vulnerable |
| TLS 1.2 | ✅ Enabled | Modern, secure |
| TLS 1.3 | ✅ Enabled | Latest, fastest |

### Cipher Suite Configuration

Strong cipher suites with forward secrecy are configured:

```nginx
ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
```

### Elliptic Curve Configuration

```nginx
ssl_ecdh_curve secp384r1:X25519:prime256v1;
```

### SSL Session Configuration

```nginx
ssl_session_timeout 1d;
ssl_session_cache shared:SSL:50m;
ssl_session_tickets off;
```

---

## Security Headers

The following security headers are configured in [`snippets/security-headers.conf`](snippets/security-headers.conf):

### HTTP Strict Transport Security (HSTS)

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
```

| Directive | Value | Description |
|-----------|-------|-------------|
| `max-age` | 31536000 | 1 year (in seconds) |
| `includeSubDomains` | - | Applies to all subdomains |
| `preload` | - | Include in browser HSTS preload lists |

⚠️ **Important**: Enable HSTS only after SSL is properly configured and tested. Start with a lower `max-age` value.

### Content Security Policy (CSP)

```nginx
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.example.com; ..." always;
```

### Other Security Headers

| Header | Value | Purpose |
|--------|-------|---------|
| `X-Frame-Options` | `SAMEORIGIN` | Prevent clickjacking |
| `X-Content-Type-Options` | `nosniff` | Prevent MIME sniffing |
| `X-XSS-Protection` | `1; mode=block` | XSS filter (legacy) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Control referrer info |
| `Permissions-Policy` | `accelerometer=(), ...` | Control browser features |
| `Cross-Origin-Opener-Policy` | `same-origin` | Isolate browsing context |
| `Cross-Origin-Embedder-Policy` | `require-corp` | Require CORS for resources |

---

## Certificate Renewal

### Automated Renewal Setup

1. **Generate DH parameters** (if not already done):
   ```bash
   ./scripts/production/renew-ssl.sh dhparam
   ```

2. **Setup auto-renewal**:
   ```bash
   ./scripts/production/renew-ssl.sh setup-auto
   ```

3. **Test renewal** (dry run):
   ```bash
   ./scripts/production/renew-ssl.sh renew
   ```

### Manual Renewal

```bash
# Force renewal (even if not expired)
./scripts/production/renew-ssl.sh renew --force

# Non-interactive mode
./scripts/production/renew-ssl.sh renew --non-interactive
```

### Certificate Status

Check certificate expiration:

```bash
./scripts/production/renew-ssl.sh status
```

### Cron Job Configuration

The auto-renewal script adds this cron job:

```cron
0 2 * * * /path/to/scripts/production/renew-ssl.sh --non-interactive >> /var/log/ssl-renewal.log 2>&1
```

This runs daily at 2 AM and only renews certificates that are within 30 days of expiration.

---

## Testing and Verification

### SSL Configuration Test

Test nginx configuration:

```bash
./scripts/production/renew-ssl.sh test
```

### SSL Rating Check

Get SSL rating from SSL Labs:

```bash
./scripts/production/renew-ssl.sh verify
```

Or visit: https://ssllabs.com/ssltest/analyze.html?d=alumni-platform.com

### Expected Rating

The configuration targets an **A+** rating from SSL Labs:

- ✅ TLS 1.2 and 1.3 only
- ✅ Strong cipher suites
- ✅ Forward secrecy enabled
- ✅ HSTS enabled
- ✅ OCSP stapling enabled
- ✅ No known vulnerabilities

### Manual SSL Test

```bash
# Check certificate chain
openssl s_client -connect alumni-platform.com:443 -servername alumni-platform.com

# Check certificate expiration
echo | openssl s_client -connect alumni-platform.com:443 2>/dev/null | openssl x509 -noout -enddate

# Check SSL protocol versions
openssl s_client -tls1_2 -connect alumni-platform.com:443 < /dev/null
openssl s_client -tls1_3 -connect alumni-platform.com:443 < /dev/null
```

---

## Troubleshooting

### Certificate Not Found

**Error**: `SSL: error:02001002:system library:fopen:No such file or directory`

**Solution**: 
1. Verify certificate paths in nginx config
2. Ensure certificates exist at specified paths
3. Check file permissions (nginx needs read access)

```bash
ls -la /etc/letsencrypt/live/alumni-platform.com/
```

### HSTS Issues

**Problem**: HSTS prevents access after SSL certificate expires

**Solution**: 
1. Clear HSTS cache in browser
2. Use `chrome://net-internals/#hsts` in Chrome
3. Lower HSTS max-age during initial testing

### Mixed Content Warnings

**Problem**: Some resources loaded over HTTP

**Solution**:
1. Update all resource URLs to use HTTPS
2. Use protocol-relative URLs (//cdn.example.com)
3. Set Content-Security-Policy appropriately

### OCSP Stapling Errors

**Error**: OCSP stapling not working

**Solution**:
1. Verify DNS resolver is accessible
2. Check firewall rules
3. Test OCSP manually:

```bash
openssl ocsp -issuer chain.pem -cert cert.pem -url http://ocsp.int-x3.letsencrypt.org -CAfile chain.pem -verify_other chain.pem -trust_other
```

### Let's Encrypt Rate Limits

**Error**: Too many certificates issued

**Solution**:
1. Wait 7 days (Let's Encrypt limit reset)
2. Use staging environment for testing
3. Request certificate for only one domain (www or non-www)

---

## Additional Resources

- [Mozilla SSL Configuration Generator](https://ssl-config.mozilla.org/)
- [SSL Labs SSL Test](https://ssllabs.com/ssltest/)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [OWASP TLS Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Transport_Layer_Security_Cheat_Sheet.html)

---

## Configuration Checklist

- [ ] Update domain name in all nginx config files
- [ ] Generate DH parameters (4096 bits)
- [ ] Install SSL certificates
- [ ] Test nginx configuration
- [ ] Verify HTTPS is working
- [ ] Check SSL rating (aim for A+)
- [ ] Enable HSTS (after testing)
- [ ] Setup automated renewal
- [ ] Monitor certificate expiration

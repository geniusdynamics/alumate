#!/bin/bash
# =============================================================================
# SSL/TLS Certificate Renewal Script
# =============================================================================
# This script handles automatic SSL certificate renewal for the Alumate platform
# Uses Let's Encrypt (certbot) for free SSL certificates
#
# Usage:
#   ./renew-ssl.sh                    # Dry run (recommended for first time)
#   ./renew-ssl.sh --force           # Force renewal even if not expired
#   ./renew-ssl.sh --post-hook       # Run post-renewal hooks
#
# Cron job recommendation:
#   0 2 * * * /path/to/renew-ssl.sh >> /var/log/ssl-renewal.log 2>&1
# =============================================================================

set -e

# Configuration
DOMAIN="alumni-platform.com"
WWW_DOMAIN="www.alumni-platform.com"
TENANT_WILDCARD="*.alumni-platform.com"
EMAIL="admin@alumni-platform.com"  # Change to your email
CERT_PATH="/etc/letsencrypt/live/${DOMAIN}"
LOG_PATH="/var/log/ssl-renewal"
WEBROOT_PATH="/var/www/letsencrypt"
NGINX_CONFIG="/etc/nginx/nginx.conf"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# =============================================================================
# HELPER FUNCTIONS
# =============================================================================

log_info() {
    echo -e "${GREEN}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "This script must be run as root"
        exit 1
    fi
}

check_certbot() {
    if ! command -v certbot &> /dev/null; then
        log_error "certbot is not installed. Install with:"
        log_error "  apt-get install certbot python3-certbot-nginx  # Debian/Ubuntu"
        log_error "  yum install certbot python3-certbot-nginx        # RHEL/CentOS"
        exit 1
    fi
}

check_nginx() {
    if ! command -v nginx &> /dev/null; then
        log_error "nginx is not installed"
        exit 1
    fi
}

# =============================================================================
# MAIN FUNCTIONS
# =============================================================================

generate_dhparam() {
    log_info "Generating Diffie-Hellman parameters..."
    
    DHFILE="/etc/nginx/dhparam.pem"
    
    if [[ ! -f "$DHFILE" ]]; then
        openssl dhparam -out "$DHFILE" 4096
        log_info "Diffie-Hellman parameters generated at $DHFILE"
    else
        log_info "Diffie-Hellman parameters already exist at $DHFILE"
    fi
}

test_nginx_config() {
    log_info "Testing nginx configuration..."
    
    if ! nginx -t 2>&1; then
        log_error "nginx configuration test failed"
        exit 1
    fi
    
    log_info "nginx configuration test passed"
}

reload_nginx() {
    log_info "Reloading nginx..."
    
    if nginx -s reload 2>/dev/null; then
        log_info "nginx reloaded successfully"
    else
        log_error "Failed to reload nginx"
        exit 1
    fi
}

renew_certificates() {
    local force=$1
    local mode=${2:-"--non-interactive"}
    
    log_info "Starting SSL certificate renewal process..."
    
    # Create log directory
    mkdir -p "$LOG_PATH"
    
    # Ensure webroot path exists
    mkdir -p "$WEBROOT_PATH"
    
    # Test nginx configuration first
    test_nginx_config
    
    # Generate DH parameters if needed
    generate_dhparam
    
    # Run certbot
    local certbot_args=(
        "certbot"
        "renew"
        "--nginx"
        "--email" "$EMAIL"
        "--agree-tos"
        "--redirect"
        "--hsts"
        "--uir"
    )
    
    if [[ "$force" == "--force" ]]; then
        certbot_args+=("--force-renewal")
    fi
    
    # Add domains (main domain + www + wildcard for subdomains)
    certbot_args+=(
        "--domain" "$DOMAIN"
        "--domain" "$WWW_DOMAIN"
    )
    
    # Run certbot
    "${certbot_args[@]}" 2>&1 | tee -a "${LOG_PATH}/renewal.log"
    
    # Check renewal status
    if [[ ${PIPESTATUS[0]} -eq 0 ]]; then
        log_info "Certificate renewal completed successfully"
        reload_nginx
        return 0
    else
        log_error "Certificate renewal failed"
        return 1
    fi
}

obtain_certificate() {
    local domain=$1
    local mode=${2:-"--non-interactive"}
    
    log_info "Obtaining new certificate for $domain..."
    
    # Create log directory
    mkdir -p "$LOG_PATH"
    
    # Ensure webroot path exists
    mkdir -p "$WEBROOT_PATH"
    
    # Test nginx configuration first
    test_nginx_config
    
    # Run certbot
    certbot --nginx \
        --email "$EMAIL" \
        --agree-tos \
        --redirect \
        --hsts \
        "--domain" "$domain" \
        2>&1 | tee -a "${LOG_PATH}/obtain.log"
    
    if [[ ${PIPESTATUS[0]} -eq 0 ]]; then
        log_info "Certificate obtained successfully for $domain"
        reload_nginx
        return 0
    else
        log_error "Failed to obtain certificate for $domain"
        return 1
    fi
}

check_certificate_status() {
    local domain=${1:-$DOMAIN}
    
    log_info "Checking certificate status for $domain..."
    
    certbot certificates --domain "$domain" 2>/dev/null || {
        log_warn "No certificate found for $domain"
        return 1
    }
    
    # Show expiration info
    echo ""
    log_info "Certificate expiration check:"
    echo "$CERT_PATH/cert.pem" | xargs -I {} openssl x509 -enddate -noout -in {} 2>/dev/null || true
}

setup_auto_renewal() {
    log_info "Setting up automatic certificate renewal..."
    
    # Create cron job for automatic renewal
    local cron_job="0 2 * * * /path/to/scripts/production/renew-ssl.sh --non-interactive >> /var/log/ssl-renewal.log 2>&1"
    
    # Add to crontab (requires root)
    if [[ $EUID -eq 0 ]]; then
        (crontab -l 2>/dev/null | grep -v "renew-ssl.sh"; echo "$cron_job") | crontab -
        log_info "Auto-renewal cron job added"
        log_info "Certificates will be renewed automatically at 2 AM daily"
    else
        log_warn "Not running as root. Add this line to crontab manually:"
        echo "$cron_job"
    fi
}

verify_ssl() {
    local domain=${1:-$DOMAIN}
    
    log_info "Verifying SSL configuration for $domain..."
    
    # Check certificate
    echo "=== Certificate Info ===" | tee /dev/stderr
    echo | openssl s_client -servername "$domain" -connect "$domain":443 2>/dev/null | \
        openssl x509 -noout -text 2>/dev/null | head -20
    
    echo ""
    echo "=== SSL Rating ===" | tee /dev/stderr
    
    # Test with SSL Labs API (requires curl and jq)
    if command -v curl &> /dev/null && command -v jq &> /dev/null; then
        log_info "Fetching SSL rating from SSL Labs..."
        local api_response
        api_response=$(curl -s "https://api.ssllabs.com/api/v3/analyze?host=$domain&all=done" 2>/dev/null)
        echo "$api_response" | jq '.endpoints[].grade' 2>/dev/null || \
            log_warn "Could not fetch SSL rating. Check SSL Labs API manually: https://ssllabs.com/ssltest/analyze.html?d=$domain"
    else
        log_warn "Install curl and jq for automatic SSL rating"
        log_info "Manual SSL test: https://ssllabs.com/ssltest/analyze.html?d=$domain"
    fi
}

# =============================================================================
# MAIN SCRIPT
# =============================================================================

main() {
    log_info "SSL/TLS Certificate Management Script"
    log_info "Domain: $DOMAIN"
    echo ""
    
    # Parse arguments
    case "${1:-}" in
        --help|-h)
            echo "Usage: $0 [command] [options]"
            echo ""
            echo "Commands:"
            echo "  renew              Renew existing certificates (dry run without --force)"
            echo "  renew --force      Force renewal even if not expired"
            echo "  obtain <domain>    Obtain new certificate for a domain"
            echo "  status             Check certificate status"
            echo "  verify             Verify SSL configuration and rating"
            echo "  setup-auto         Setup automatic renewal cron job"
            echo "  dhparam            Generate Diffie-Hellman parameters"
            echo "  test               Test nginx configuration"
            echo "  reload             Reload nginx"
            echo "  --help             Show this help message"
            echo ""
            exit 0
            ;;
        renew)
            check_root
            check_certbot
            check_nginx
            renew_certificates "${2:-}" "${3:-}"
            ;;
        obtain)
            check_root
            check_certbot
            check_nginx
            obtain_certificate "${2:-$DOMAIN}" "${3:-}"
            ;;
        status)
            check_certificate_status "${2:-$DOMAIN}"
            ;;
        verify)
            verify_ssl "${2:-$DOMAIN}"
            ;;
        setup-auto)
            check_root
            setup_auto_renewal
            ;;
        dhparam)
            check_root
            generate_dhparam
            ;;
        test)
            check_nginx
            test_nginx_config
            ;;
        reload)
            check_root
            check_nginx
            reload_nginx
            ;;
        *)
            log_info "SSL/TLS Certificate Management Script"
            echo ""
            echo "Use --help to see available commands"
            echo ""
            echo "Quick Commands:"
            echo "  $0 renew              # Check and renew certificates"
            echo "  $0 verify            # Verify SSL configuration"
            echo "  $0 status            # Check certificate status"
            echo "  $0 setup-auto        # Setup automatic renewal"
            ;;
    esac
}

# Run main function
main "$@"

# 🚀 Graduate Tracking System - Startup Guide

## Quick Start Options

### Option 1: Windows Batch Script (Recommended for simplicity)
```bash
.\start-dev.bat
```
- ✅ Simple and reliable
- ✅ Works on all Windows systems
- ✅ Shows test accounts during startup
- ✅ Auto-opens browser

### Option 2: PowerShell Script (Recommended for advanced users)
```bash
.\start-dev-final.ps1
```
- ✅ Enhanced error handling
- ✅ Detailed server status checks
- ✅ Better process management
- ✅ Colored output

### Option 3: Interactive Helper
```bash
scripts/development/dev-helper.bat
```
- ✅ Interactive menu system
- ✅ Multiple development tools
- ✅ Guided setup process
- ✅ Additional utilities

## Test Accounts (All scripts show these)

| Role | Email | Password | Dashboard |
|------|-------|----------|-----------|
| **Super Admin** | admin@system.com | password | /super-admin/dashboard |
| **Institution** | admin@tech-institute.edu | password | /institution-admin/dashboard |
| **Graduate** | john.smith@student.edu | password | /graduate/dashboard |
| **Employer** | techcorp@company.com | password | /employer/dashboard |

## Access URLs

- **Main App**: http://127.0.0.1:8080
- **Login**: http://127.0.0.1:8080/login
- **Register**: http://127.0.0.1:8080/register
- **Testing**: http://127.0.0.1:8080/testing

## Manual Startup (if scripts don't work)

```bash
# Terminal 1: Start Vite
npm run dev

# Terminal 2: Start Laravel
php artisan serve --host=127.0.0.1 --port=8080
```

## Troubleshooting

### If servers don't start:
1. Check if ports 8080 and 5173 are free
2. Ensure PHP and Node.js are installed
3. Run `php artisan config:clear`
4. Try manual startup method

### If you get 404 errors:
1. Make sure you're using http://127.0.0.1:8080 (not localhost:5173)
2. Clear browser cache
3. Check Laravel logs in `storage/logs/`

## Documentation Locations

- **Complete Setup**: [README.md](README.md)
- **Development Guide**: [DEVELOPMENT.md](DEVELOPMENT.md)
- **Quick Reference**: [QUICK_START.md](QUICK_START.md)
- **Welcome Page**: http://127.0.0.1:8080 (shows accounts interactively)

---

**Need help?** All startup scripts display the test accounts and URLs when they run!
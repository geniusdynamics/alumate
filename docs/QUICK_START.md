# 🚀 Graduate Tracking System - Quick Start

## ⚡ Start Development

**Choose your preferred method**:

```bash
# Windows Batch (simple & reliable)
.\start-dev.bat

# PowerShell (enhanced features)
.\start-dev-final.ps1

# Interactive helper
scripts/development/dev-helper.bat
```

## 🌐 Access URLs

- **Main App**: http://127.0.0.1:8080
- **Login**: http://127.0.0.1:8080/login
- **Testing**: http://127.0.0.1:8080/testing

## 🔑 Test Accounts

| Role | Email | Password |
|------|-------|----------|
| **Super Admin** | admin@system.com | password |
| **Institution** | admin@tech-institute.edu | password |
| **Graduate** | john.smith@student.edu | password |
| **Employer** | techcorp@company.com | password |

## 🎯 Quick Test Flow

1. **Start servers**: `.\start-dev-final.ps1`
2. **Open**: http://127.0.0.1:8080/login
3. **Login**: Use any account above with password `password`
4. **Explore**: Each role has different dashboard and features

## 🛠️ Development Commands

```bash
# Check users and roles
php scripts/data/check_user_roles.php

# Fix user roles if needed
php scripts/utilities/fix_user_roles.php

# Create sample data
php scripts/data/create_sample_data.php

# Test all user access
php scripts/testing/test_all_user_access.php

# Clear caches
php artisan config:clear && php artisan cache:clear
```

## 📚 Full Documentation

- **Complete Setup**: [README.md](README.md)
- **Development Guide**: [DEVELOPMENT.md](DEVELOPMENT.md)
- **Scripts Documentation**: [scripts/README.md](scripts/README.md)

---

**Need help?** Run `scripts/development/dev-helper.bat` for interactive assistance.
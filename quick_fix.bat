@echo off
echo 🔧 Quick Frontend Fix
echo ====================

echo.
echo 1. Installing dependencies...
call npm install

echo.
echo 2. Building assets...
call npm run build

echo.
echo 3. Clearing Laravel caches...
php artisan config:clear
php artisan route:clear  
php artisan view:clear

echo.
echo 4. Testing frontend...
php fix_frontend.php

echo.
echo ✅ Frontend fix complete!
echo.
echo Try visiting:
echo - http://localhost:8000/
echo - http://localhost:8000/login
echo - http://localhost:8000/analytics/dashboard
echo.
pause
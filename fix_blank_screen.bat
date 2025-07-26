@echo off
echo 🔧 Fixing Blank Screen Issue
echo ============================

echo.
echo Checking current setup...
php fix_frontend.php

echo.
echo Step 1: Installing Node.js dependencies...
echo (This may take a few minutes)
call npm install

echo.
echo Step 2: Building frontend assets...
call npm run build

echo.
echo Step 3: Clearing Laravel caches...
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo.
echo Step 4: Optimizing Laravel...
php artisan config:cache
php artisan route:cache

echo.
echo Step 5: Setting up sample data (if needed)...
php create_sample_data.php

echo.
echo ✅ Fix complete!
echo.
echo Now try visiting:
echo - http://localhost:8000/
echo - http://localhost:8000/login
echo - http://localhost:8000/analytics/dashboard
echo.
echo If you still see a blank screen:
echo 1. Check browser console for JavaScript errors (F12)
echo 2. Try running: npm run dev (for development mode)
echo 3. Check if the server is running on the correct port
echo.
pause
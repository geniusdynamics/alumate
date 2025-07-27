@echo off
echo Setting up Analytics System...

cd ..\..

echo.
echo 1. Running migrations...
php artisan migrate --force

echo.
echo 2. Creating tenant...
php scripts\utilities\create_tenant.php

echo.
echo 3. Creating sample data...
php scripts\data\create_sample_data.php

echo.
echo 4. Building frontend assets...
npm run build

echo.
echo Setup complete! You can now test the analytics system.
echo Visit: http://localhost:8080/analytics/dashboard
echo Login: admin@test.com / password

pause
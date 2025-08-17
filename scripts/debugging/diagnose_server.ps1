# Server Diagnostics Script
# Helps diagnose common Laravel server issues

Write-Host "Server Diagnostics" -ForegroundColor Cyan
Write-Host "==================" -ForegroundColor Cyan
Write-Host ""

$phpPath = "D:\DevCenter\xampp\php-8.3.23\php.exe"

# Check PHP version and extensions
Write-Host "1. PHP Configuration:" -ForegroundColor Yellow
Write-Host "   PHP Path: $phpPath" -ForegroundColor Gray
if (Test-Path $phpPath) {
    $phpVersion = & $phpPath --version | Select-Object -First 1
    Write-Host "   OK $phpVersion" -ForegroundColor Green
    
    # Check required extensions
    $extensions = @("openssl", "pdo", "mbstring", "tokenizer", "xml", "ctype", "json", "bcmath")
    foreach ($ext in $extensions) {
        $result = & $phpPath -m | Select-String $ext
        if ($result) {
            Write-Host "   OK $ext extension loaded" -ForegroundColor Green
        } else {
            Write-Host "   ERROR $ext extension missing" -ForegroundColor Red
        }
    }
} else {
    Write-Host "   ERROR PHP not found at specified path" -ForegroundColor
}

Write-Host ""

# Check Laravel configuration
Write-Host "2. Laravel Configuration:" -ForegroundColor Yellow
if (Test-Path ".env") {
    Write-Host "   OK .env file exists" -ForegroundColor Green
    
    # Check APP_KEY
    $appKey = Select-String -Path ".env" -Pattern "APP_KEY="
    if ($appKey -and $appKey.Line -notmatch "APP_KEY=$") {
        Write-Host "   OK APP_KEY is set" -ForegroundColor Green
    } else {
        Write-Host "   ERROR APP_KEY is missing or empty" -ForegroundColor Red
        Write-Host "   Run: php artisan key:generate" -ForegroundColor Yellow
    }
    
    # Check APP_URL
    $appUrl = Select-String -Path ".env" -Pattern "APP_URL="
    if ($appUrl) {
        Write-Host "   OK APP_URL: $($appUrl.Line)" -ForegroundColor Green
    }
} else {
    Write-Host "   ERROR .env file missing" -ForegroundColor
    Write-Host "   Copy .env.example to .env" -ForegroundColor Yellow
}

Write-Host ""

# Check database connection
Write-Host "3. Database Connection:" -ForegroundColor Yellow
try {
    $dbTest = & $phpPath artisan migrate:status 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   OK Database connection successful" -ForegroundColor Green
    } else {
        Write-Host "   ERROR Database connection failed" -ForegroundColored
        Write-Host "   Error: $dbTest" -ForegroundColor Red
    }
} catch {
    Write-Host "   ERROR Could not test database connection" -ForegroundColor
}

Write-Host ""

# Check routes
Write-Host "4. Route Configuration:" -ForegroundColor Yellow
try {
    $routeList = & $phpPath artisan route:list --compact 2>&1
    if ($LASTEXITCODE -eq 0) {
        $routeCount = ($routeList | Measure-Object -Line).Lines
        Write-Host "   OK Routes loaded successfully ($routeCount routes)" -ForegroundColor Green
        
        # Check for specific routes
        $homeRoute = $routeList | Select-String "GET.*/"
        if ($homeRoute) {
            Write-Host "   OK Home route (/) exists" -ForegroundColor Gree
        } else {
            Write-Host "   ERROR Home route (/) missing" -ForegroundColor
        }
    } else {
        Write-Host "   ERROR Route loading failed" -ForegroundColor
        Write-Host "   Error: $routeList" -ForegroundColor Red
    }
} catch {
    Write-Host "   ERROR Could not check routes" -ForegroundColor
}

Write-Host ""

# Check storage permissions
Write-Host "5. Storage Permissions:" -ForegroundColor Yellow
$storageDirs = @("storage/logs", "storage/framework/cache", "storage/framework/sessions", "storage/framework/views", "bootstrap/cache")
foreach ($dir in $storageDirs) {
    if (Test-Path $dir) {
        try {
            $testFile = Join-Path $dir "test_write.tmp"
            "test" | Out-File -FilePath $testFile -ErrorAction Stop
            Remove-Item $testFile -ErrorAction SilentlyContinue
            Write-Host "   OK $dir is writable" -ForegroundColor Green
        } catch {
            Write-Host "   ERROR $dir is not writable" -ForegroundColord
        }
    } else {
        Write-Host "   ERROR $dir does not exist" -ForegroundColor
    }
}

Write-Host ""

# Check frontend assets
Write-Host "6. Frontend Assets:" -ForegroundColor Yellow
if (Test-Path "public/build/manifest.json") {
    Write-Host "   OK Vite manifest exists" -ForegroundColor Gree
} else {
    Write-Host "   ERROR Vite manifest missing" -ForegroundColorRed
    Write-Host "   Run: npm run build" -ForegroundColor Yellow
}

if (Test-Path "node_modules") {
    Write-Host "   OK Node modules installed" -ForegroundColor Green
} else {
    Write-Host "   ERROR Node modules missing" -ForegroundColor
    Write-Host "   Run: npm install" -ForegroundColor Yellow
}

Write-Host ""

# Test server connectivity
Write-Host "7. Server Connectivity:" -ForegroundColor Yellow
$ports = @(8080, 5100)
foreach ($port in $ports) {
    try {
        $connection = New-Object System.Net.Sockets.TcpClient
        $connection.Connect("127.0.0.1", $port)
        $connection.Close()
        Write-Host "   OK Port $port is accessible" -ForegroundColor Green
    } catch {
        Write-Host "   ERROR Port $port is not accessible" -ForegroundColor
    }
}

Write-Host ""

# Recommendations
Write-Host "Recommendations:" -ForegroundColor Cyan
Write-Host "===============" -ForegroundColor Cyan
Write-Host "1. If APP_KEY is missing: php artisan key:generate" -ForegroundColor Yellow
Write-Host "2. If database issues: Check .env database settings" -ForegroundColor Yellow
Write-Host "3. If assets missing: npm install; npm run build" -ForegroundColor Yellow
Write-Host "4. If routes missing: php artisan route:cache" -ForegroundColor Yellow
Write-Host "5. Clear all caches: php artisan optimize:clear" -ForegroundColor Yellow
Write-Host ""
Write-Host "Quick Fix Command:" -ForegroundColor Cyan
Write-Host "php artisan key:generate; php artisan optimize:clear; npm install; npm run build" -ForegroundColor Green
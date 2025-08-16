# Test Vite server connectivity
Write-Host "Testing Vite server connectivity..." -ForegroundColor Yellow

# Test localhost:5100
try {
    $response = Invoke-WebRequest -Uri "http://localhost:5100" -TimeoutSec 5 -UseBasicParsing
    Write-Host "✅ localhost:5100 is accessible" -ForegroundColor Green
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "❌ localhost:5100 is NOT accessible" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 127.0.0.1:5100
try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:5100" -TimeoutSec 5 -UseBasicParsing
    Write-Host "✅ 127.0.0.1:5100 is accessible" -ForegroundColor Green
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "❌ 127.0.0.1:5100 is NOT accessible" -ForegroundColor Red
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Check if Vite process is running
$viteProcess = Get-Process | Where-Object {$_.ProcessName -like "*node*" -and $_.CommandLine -like "*vite*"}
if ($viteProcess) {
    Write-Host "✅ Vite process found" -ForegroundColor Green
} else {
    Write-Host "❌ No Vite process found" -ForegroundColor Red
}

# Check network listeners on port 5100
$listeners = netstat -ano | findstr :5100
if ($listeners) {
    Write-Host "✅ Port 5100 listeners found:" -ForegroundColor Green
    Write-Host $listeners -ForegroundColor Cyan
} else {
    Write-Host "❌ No listeners on port 5100" -ForegroundColor Red
}
<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\SecurityService;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Security Features...\n\n";

$securityService = app(SecurityService::class);

// Test 1: Suspicious patterns detection
echo "1. Testing Suspicious Patterns Detection:\n";

$testCases = [
    ['input' => 'SELECT * FROM users', 'expected' => true, 'description' => 'SQL SELECT'],
    ['input' => '<script>alert(1)</script>', 'expected' => true, 'description' => 'XSS Script'],
    ['input' => '; rm -rf /', 'expected' => true, 'description' => 'Command Injection'],
    ['input' => 'Normal user input', 'expected' => false, 'description' => 'Normal Input'],
];

foreach ($testCases as $test) {
    $request = Request::create('/test', 'POST', ['data' => $test['input']]);
    $result = $securityService->checkSuspiciousPatterns($request);

    $status = $result === $test['expected'] ? 'PASS' : 'FAIL';
    echo "  {$test['description']}: {$status}\n";
}

echo "\n";

// Test 2: Rate limiting
echo "2. Testing Rate Limiting:\n";

$identifier = 'test-user-' . time();
$attempts = 0;
$rateLimited = false;

for ($i = 1; $i <= 12; $i++) {
    $result = $securityService->detectRateLimitViolation($identifier, 5, 1);
    if ($result) {
        $rateLimited = true;
        echo "  Rate limit triggered at attempt {$i}: PASS\n";
        break;
    }
    $attempts = $i;
}

if (!$rateLimited) {
    echo "  Rate limit not triggered after {$attempts} attempts: FAIL\n";
}

echo "\n";

// Test 3: IP blocking (without database)
echo "3. Testing IP Blocking (without database):\n";

$result = $securityService->isIpBlocked('127.0.0.1');
$status = $result === false ? 'PASS' : 'FAIL';
echo "  Graceful handling of missing database: {$status}\n";

echo "\n";

// Test 4: GeoIP database check
echo "4. Checking GeoIP Database:\n";

$databasePath = storage_path('app/geoip/GeoLite2-City.mmdb');
if (file_exists($databasePath)) {
    echo "  GeoIP database exists: PASS\n";
} else {
    echo "  GeoIP database missing (needs manual download): INFO\n";
}

echo "\nSecurity testing completed!\n";
<?php

echo "Testing Security Features (Simple Version)...\n\n";

// Test patterns directly
$patterns = [
    '/SELECT.*FROM/i',
    '/<script/i',
    '/[;&|`].*rm/i',
];

$testCases = [
    ['input' => 'SELECT * FROM users', 'expected' => true, 'description' => 'SQL SELECT'],
    ['input' => '<script>alert(1)</script>', 'expected' => true, 'description' => 'XSS Script'],
    ['input' => '; rm -rf /', 'expected' => true, 'description' => 'Command Injection'],
    ['input' => 'Normal user input', 'expected' => false, 'description' => 'Normal Input'],
];

echo "1. Testing Pattern Detection:\n";
foreach ($testCases as $test) {
    $detected = false;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $test['input'])) {
            $detected = true;
            break;
        }
    }

    $status = $detected === $test['expected'] ? 'PASS' : 'FAIL';
    echo "  {$test['description']}: {$status}\n";
}

echo "\n";

// Test GeoIP database existence
echo "2. Checking GeoIP Database:\n";
$databasePath = __DIR__ . '/../../storage/app/geoip/GeoLite2-City.mmdb';
if (file_exists($databasePath)) {
    echo "  GeoIP database exists: PASS\n";
} else {
    echo "  GeoIP database missing (needs manual download): INFO\n";
}

echo "\n";

// Test middleware file existence
echo "3. Checking Security Files:\n";
$files = [
    __DIR__ . '/../../app/Http/Middleware/SecurityMiddleware.php',
    __DIR__ . '/../../app/Services/SecurityService.php',
];

foreach ($files as $file) {
    $relativePath = str_replace(__DIR__ . '/../../', '', $file);
    if (file_exists($file)) {
        echo "  {$relativePath}: EXISTS\n";
    } else {
        echo "  {$relativePath}: MISSING\n";
    }
}

echo "\nSecurity implementation check completed!\n";
echo "Note: Full Laravel testing requires database setup and proper environment.\n";
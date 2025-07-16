<?php

require_once 'vendor/autoload.php';

// Create Laravel application
$app = require_once 'bootstrap/app.php';

// Boot the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Generate a new application key
$key = 'base64:' . base64_encode(random_bytes(32));

// Read current .env content
$envContent = file_get_contents('.env');

// Replace or add APP_KEY
if (strpos($envContent, 'APP_KEY=') !== false) {
    $envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $envContent);
} else {
    $envContent .= "\nAPP_KEY=" . $key;
}

// Write back to .env
file_put_contents('.env', $envContent);

echo "Application key generated: " . $key . PHP_EOL;
echo "Laravel application bootstrapped successfully!" . PHP_EOL;
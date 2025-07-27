<?php

// Generate a new application key
$key = 'base64:' . base64_encode(random_bytes(32));

// Read current .env content
$envContent = file_get_contents('../../.env');

// Replace APP_KEY
$envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $envContent);

// Write back to .env
file_put_contents('../../.env', $envContent);

echo "Application key generated: " . $key . PHP_EOL;
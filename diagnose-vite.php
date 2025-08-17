<?php

echo "=== Vite Development Server Diagnostic ===\n\n";

// Check if we're in development mode
$isDev = env('APP_ENV') === 'local';
echo "Development mode: " . ($isDev ? 'YES' : 'NO') . "\n";

// Check Vite dev server URL
$viteUrl = env('VITE_DEV_SERVER_URL', 'http://localhost:5100');
echo "Vite dev server URL: $viteUrl\n";

// Test connection to Vite server
echo "\nTesting connection to Vite server...\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$result = @file_get_contents($viteUrl, false, $context);

if ($result !== false) {
    echo "✅ Successfully connected to Vite server\n";
    echo "Response length: " . strlen($result) . " bytes\n";
} else {
    echo "❌ Failed to connect to Vite server\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
}

// Check if hot file exists
$hotFile = public_path('hot');
echo "\nHot file status:\n";
echo "Path: $hotFile\n";
echo "Exists: " . (file_exists($hotFile) ? 'YES' : 'NO') . "\n";

if (file_exists($hotFile)) {
    $hotContent = file_get_contents($hotFile);
    echo "Content: $hotContent\n";
}

// Check manifest file
$manifestFile = public_path('build/manifest.json');
echo "\nManifest file status:\n";
echo "Path: $manifestFile\n";
echo "Exists: " . (file_exists($manifestFile) ? 'YES' : 'NO') . "\n";

echo "\n=== End Diagnostic ===\n";
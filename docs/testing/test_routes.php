<?php

// Simple route testing script
echo "Testing Alumni Platform Routes...\n\n";

$routes_to_test = [
    'http://127.0.0.1:8080/' => 'Homepage',
    'http://127.0.0.1:8080/jobs' => 'Jobs Portal',
    'http://127.0.0.1:8080/alumni' => 'Alumni Directory',
    'http://127.0.0.1:8080/alumni/map' => 'Alumni Map',
    'http://127.0.0.1:8080/stories' => 'Success Stories',
    'http://127.0.0.1:8080/contact' => 'Contact Page',
    'http://127.0.0.1:8080/login' => 'Login Page',
    'http://127.0.0.1:8080/register' => 'Register Page',
];

foreach ($routes_to_test as $url => $name) {
    echo "Testing {$name} ({$url})... ";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ ERROR: {$error}\n";
    } elseif ($httpCode == 200) {
        echo "✅ OK (HTTP {$httpCode})\n";
    } elseif ($httpCode == 302 || $httpCode == 301) {
        echo "🔄 REDIRECT (HTTP {$httpCode})\n";
    } else {
        echo "❌ FAILED (HTTP {$httpCode})\n";
    }
}

echo "\n✅ Route testing completed!\n";
echo "\nDemo Credentials:\n";
echo "- Super Admin: admin@system.com / password\n";
echo "- Institution Admin: admin@tech-institute.edu / password\n";
echo "- Graduate: john.smith@student.edu / password\n";
echo "- Employer: techcorp@company.com / password\n";

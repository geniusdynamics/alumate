<?php
// ABOUTME: Web functionality testing script for comprehensive end-to-end testing
// ABOUTME: Tests key routes, forms, and interactive elements of the Laravel application

// Test configuration
$baseUrl = 'http://127.0.0.1:8080';
$testResults = [];

function testRoute($url, $description) {
    global $testResults;
    
    echo "Testing: $description\n";
    echo "URL: $url\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => "User-Agent: AlumateTestBot/1.0\r\n"
        ]
    ]);
    
    $startTime = microtime(true);
    $response = @file_get_contents($url, false, $context);
    $endTime = microtime(true);
    
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    if ($response !== false) {
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = intval($matches[1]);
                    break;
                }
            }
        }
        
        $result = [
            'url' => $url,
            'description' => $description,
            'status' => 'PASS',
            'http_code' => $httpCode,
            'response_time' => $responseTime . 'ms',
            'content_length' => strlen($response)
        ];
        
        echo "✅ PASS - HTTP $httpCode - {$responseTime}ms - " . strlen($response) . " bytes\n";
    } else {
        $result = [
            'url' => $url,
            'description' => $description,
            'status' => 'FAIL',
            'http_code' => 'N/A',
            'response_time' => $responseTime . 'ms',
            'error' => error_get_last()['message'] ?? 'Unknown error'
        ];
        
        echo "❌ FAIL - {$responseTime}ms - " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
    $testResults[] = $result;
    echo "\n";
    
    return $response !== false;
}

function testApiEndpoint($url, $description, $method = 'GET', $data = null) {
    global $testResults;
    
    echo "Testing API: $description\n";
    echo "URL: $url\n";
    
    $options = [
        'http' => [
            'timeout' => 10,
            'method' => $method,
            'header' => "Content-Type: application/json\r\nUser-Agent: AlumateTestBot/1.0\r\n"
        ]
    ];
    
    if ($data && $method === 'POST') {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    
    $startTime = microtime(true);
    $response = @file_get_contents($url, false, $context);
    $endTime = microtime(true);
    
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    if ($response !== false) {
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = intval($matches[1]);
                    break;
                }
            }
        }
        
        $jsonData = json_decode($response, true);
        $isValidJson = json_last_error() === JSON_ERROR_NONE;
        
        $result = [
            'url' => $url,
            'description' => $description,
            'status' => 'PASS',
            'http_code' => $httpCode,
            'response_time' => $responseTime . 'ms',
            'is_json' => $isValidJson,
            'content_length' => strlen($response)
        ];
        
        echo "✅ PASS - HTTP $httpCode - {$responseTime}ms - " . ($isValidJson ? 'Valid JSON' : 'Not JSON') . "\n";
    } else {
        $result = [
            'url' => $url,
            'description' => $description,
            'status' => 'FAIL',
            'http_code' => 'N/A',
            'response_time' => $responseTime . 'ms',
            'error' => error_get_last()['message'] ?? 'Unknown error'
        ];
        
        echo "❌ FAIL - {$responseTime}ms - " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
    $testResults[] = $result;
    echo "\n";
    
    return $response !== false;
}

echo "=== ALUMATE WEB FUNCTIONALITY TEST ===\n";
echo "Base URL: $baseUrl\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Test core pages
echo "=== TESTING CORE PAGES ===\n";
testRoute($baseUrl . '/', 'Homepage');
testRoute($baseUrl . '/homepage', 'Homepage Alternative Route');
testRoute($baseUrl . '/homepage/institutional', 'Institutional Homepage');
testRoute($baseUrl . '/design-system', 'Design System Showcase');
testRoute($baseUrl . '/health-check/homepage', 'Health Check');

// Test API endpoints
echo "=== TESTING API ENDPOINTS ===\n";
testApiEndpoint($baseUrl . '/api/homepage/statistics', 'Homepage Statistics API');
testApiEndpoint($baseUrl . '/api/homepage/testimonials', 'Testimonials API');
testApiEndpoint($baseUrl . '/api/homepage/features', 'Features API');
testApiEndpoint($baseUrl . '/api/homepage/trust-badges', 'Trust Badges API');
testApiEndpoint($baseUrl . '/api/homepage/success-stories', 'Success Stories API');
testApiEndpoint($baseUrl . '/api/homepage/platform-preview', 'Platform Preview API');
testApiEndpoint($baseUrl . '/api/homepage/branded-apps', 'Branded Apps API');
testApiEndpoint($baseUrl . '/api/homepage/pricing/plans', 'Pricing Plans API');
testApiEndpoint($baseUrl . '/api/homepage/detect-audience', 'Audience Detection API');
testApiEndpoint($baseUrl . '/api/homepage/active-ab-tests', 'Active A/B Tests API');

// Test POST endpoints with sample data
echo "=== TESTING POST ENDPOINTS ===\n";
testApiEndpoint($baseUrl . '/api/homepage/calculator/calculate', 'Career Calculator', 'POST', [
    'experience_years' => 5,
    'education_level' => 'bachelor',
    'industry' => 'technology'
]);

testApiEndpoint($baseUrl . '/api/homepage/track-cta', 'CTA Tracking', 'POST', [
    'cta_id' => 'test-cta',
    'page' => 'homepage'
]);

// Generate summary report
echo "=== TEST SUMMARY ===\n";
$totalTests = count($testResults);
$passedTests = array_filter($testResults, function($result) { return $result['status'] === 'PASS'; });
$failedTests = array_filter($testResults, function($result) { return $result['status'] === 'FAIL'; });

echo "Total Tests: $totalTests\n";
echo "Passed: " . count($passedTests) . "\n";
echo "Failed: " . count($failedTests) . "\n";
echo "Success Rate: " . round((count($passedTests) / $totalTests) * 100, 2) . "%\n\n";

if (!empty($failedTests)) {
    echo "=== FAILED TESTS ===\n";
    foreach ($failedTests as $test) {
        echo "❌ {$test['description']} - {$test['url']}\n";
        if (isset($test['error'])) {
            echo "   Error: {$test['error']}\n";
        }
    }
    echo "\n";
}

echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>
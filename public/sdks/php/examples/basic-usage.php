<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AlumniPlatform\ApiClient\Client;
use AlumniPlatform\ApiClient\AlumniPlatformException;

// Initialize the client
$client = new Client([
    'base_uri' => 'https://your-domain.com/api',
    'token' => 'your-access-token'
]);

try {
    // Test API connectivity
    echo "Testing API connectivity...\n";
    $ping = $client->ping();
    echo "✓ API is responding: " . json_encode($ping) . "\n\n";

    // Get user profile
    echo "Getting user profile...\n";
    $user = $client->getUser();
    echo "✓ User: {$user['name']} ({$user['email']})\n\n";

    // Get timeline
    echo "Getting timeline...\n";
    $timeline = $client->getTimeline(1, 5);
    echo "✓ Timeline loaded with " . count($timeline['data']) . " posts\n\n";

    // Search alumni
    echo "Searching alumni...\n";
    $alumni = $client->searchAlumni([
        'industry' => 'technology',
        'limit' => 5
    ]);
    echo "✓ Found " . count($alumni['data']) . " alumni in technology\n\n";

    // Get job recommendations
    echo "Getting job recommendations...\n";
    $jobs = $client->getJobRecommendations(['limit' => 3]);
    echo "✓ Found " . count($jobs) . " job recommendations\n\n";

    // Get events
    echo "Getting events...\n";
    $events = $client->getEvents(['upcoming' => true, 'limit' => 3]);
    echo "✓ Found " . count($events['data']) . " upcoming events\n\n";

    // Get notifications
    echo "Getting notifications...\n";
    $notifications = $client->getNotifications();
    echo "✓ Found " . count($notifications['data']) . " notifications\n\n";

    echo "All API calls completed successfully!\n";

} catch (AlumniPlatformException $e) {
    echo "❌ Alumni Platform API Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
}
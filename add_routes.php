<?php

// Temporary script to add new routes to api.php
$content = file_get_contents('routes/api.php');

// Find the line with custom-events/{definition}/analytics and add new routes after it
$search = "Route::get('custom-events/{definition}/analytics', [App\Http\Controllers\Analytics\CustomEventController::class, 'analytics']);";
$replace = "Route::get('custom-events/{definition}/analytics', [App\Http\Controllers\Analytics\CustomEventController::class, 'analytics']);
        Route::get('custom-events/{definition}/behavior-flow', [App\Http\Controllers\Analytics\CustomEventController::class, 'behaviorFlow']);
        Route::post('custom-events/funnel', [App\Http\Controllers\Analytics\CustomEventController::class, 'funnel']);";

$content = str_replace($search, $replace, $content);

file_put_contents('routes/api.php', $content);

echo 'Routes added successfully!';

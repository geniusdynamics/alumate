<?php

test('timeline service class exists and has correct methods', function () {
    expect(class_exists(\App\Services\TimelineService::class))->toBeTrue();

    $reflection = new ReflectionClass(\App\Services\TimelineService::class);

    expect($reflection->hasMethod('generateTimelineForUser'))->toBeTrue();
    expect($reflection->hasMethod('getCirclePosts'))->toBeTrue();
    expect($reflection->hasMethod('getGroupPosts'))->toBeTrue();
    expect($reflection->hasMethod('scorePost'))->toBeTrue();
    expect($reflection->hasMethod('cacheTimeline'))->toBeTrue();
    expect($reflection->hasMethod('invalidateTimelineCache'))->toBeTrue();
});

test('timeline controller class exists and has correct methods', function () {
    expect(class_exists(\App\Http\Controllers\Api\TimelineController::class))->toBeTrue();

    $reflection = new ReflectionClass(\App\Http\Controllers\Api\TimelineController::class);

    expect($reflection->hasMethod('index'))->toBeTrue();
    expect($reflection->hasMethod('refresh'))->toBeTrue();
    expect($reflection->hasMethod('loadMore'))->toBeTrue();
    expect($reflection->hasMethod('circles'))->toBeTrue();
    expect($reflection->hasMethod('groups'))->toBeTrue();
});

test('refresh timelines job class exists and has correct methods', function () {
    expect(class_exists(\App\Jobs\RefreshTimelinesJob::class))->toBeTrue();

    $reflection = new ReflectionClass(\App\Jobs\RefreshTimelinesJob::class);

    expect($reflection->hasMethod('handle'))->toBeTrue();
    expect($reflection->hasMethod('forNewPost'))->toBeTrue();
    expect($reflection->hasMethod('forUsers'))->toBeTrue();
    expect($reflection->hasMethod('forAllActiveUsers'))->toBeTrue();
});

test('timeline vue component exists', function () {
    $componentPath = __DIR__.'/../../resources/js/Components/Timeline.vue';
    expect(file_exists($componentPath))->toBeTrue();

    $content = file_get_contents($componentPath);
    expect($content)->toContain('<template>');
    expect($content)->toContain('<script setup>');
    expect($content)->toContain('timeline-container');
});

test('post skeleton vue component exists', function () {
    $componentPath = __DIR__.'/../../resources/js/Components/PostSkeleton.vue';
    expect(file_exists($componentPath))->toBeTrue();

    $content = file_get_contents($componentPath);
    expect($content)->toContain('<template>');
    expect($content)->toContain('animate-pulse');
});

test('api routes are registered', function () {
    $routesPath = __DIR__.'/../../routes/api.php';
    $content = file_get_contents($routesPath);

    expect($content)->toContain('TimelineController');
    expect($content)->toContain('timeline');
    expect($content)->toContain('timeline/refresh');
    expect($content)->toContain('timeline/circles');
    expect($content)->toContain('timeline/groups');
});

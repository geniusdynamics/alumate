<?php

namespace Tests\Feature;

use Tests\TestCase;

class PWATest extends TestCase
{
    public function test_manifest_is_accessible()
    {
        $response = $this->get('/manifest.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $manifest = $response->json();
        $this->assertEquals('Modern Alumni Platform', $manifest['name']);
        $this->assertEquals('Alumni', $manifest['short_name']);
        $this->assertEquals('standalone', $manifest['display']);
    }

    public function test_service_worker_is_accessible()
    {
        $response = $this->get('/sw.js');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');

        $content = $response->getContent();
        $this->assertStringContains('alumni-platform-v1', $content);
        $this->assertStringContains('STATIC_CACHE', $content);
        $this->assertStringContains('DYNAMIC_CACHE', $content);
    }

    public function test_offline_page_is_accessible()
    {
        $response = $this->get('/offline');

        $response->assertStatus(200);
        $response->assertSee('You\'re Offline');
        $response->assertSee('Alumni Platform');
        $response->assertSee('Try Again');
    }

    public function test_pwa_meta_tags_are_present()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta name="theme-color" content="#3b82f6">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false);
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
    }

    public function test_push_notification_vapid_key_endpoint()
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/push/vapid-key');

        $response->assertStatus(200);
        $response->assertJsonStructure(['publicKey']);
    }

    public function test_push_notification_subscribe_endpoint()
    {
        $user = \App\Models\User::factory()->create();

        $subscriptionData = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
            'keys' => [
                'p256dh' => 'test-key',
                'auth' => 'test-auth',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/push/subscribe', $subscriptionData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_push_notification_unsubscribe_endpoint()
    {
        $user = \App\Models\User::factory()->create();

        $unsubscribeData = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/push/unsubscribe', $unsubscribeData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_required_icon_files_exist()
    {
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertFileExists(public_path('android-chrome-192x192.png'));
        $this->assertFileExists(public_path('android-chrome-512x512.png'));
    }
}

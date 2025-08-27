<?php

namespace Tests\Feature;

use Tests\TestCase;

class CareerAnalyticsRoutesTest extends TestCase
{
    public function test_career_analytics_routes_exist()
    {
        $user = $this->createUserWithRole('graduate');

        // Test that routes are registered (they should return some response, not 404)
        $routes = [
            '/api/career-analytics/filter-options',
            '/api/career-analytics/overview',
            '/api/career-analytics/salary-analysis',
            '/api/career-analytics/program-effectiveness',
            '/api/career-analytics/industry-placement',
            '/api/career-analytics/demographic-outcomes',
            '/api/career-analytics/career-path-analysis',
            '/api/career-analytics/trend-analysis',
            '/api/career-analytics/snapshots',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->getJson($route);

            // Should not be 404 (route not found)
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$route} should exist (got 404)");
        }
    }

    public function test_career_analytics_requires_authentication()
    {
        $response = $this->getJson('/api/career-analytics/overview');
        $response->assertStatus(401);
    }

    public function test_career_analytics_export_route_exists()
    {
        $user = $this->createUserWithRole('graduate');

        $response = $this->actingAs($user)
            ->postJson('/api/career-analytics/export', [
                'format' => 'csv',
                'data_type' => 'overview',
                'filters' => [],
            ]);

        // Should not be 404 (route not found)
        $this->assertNotEquals(404, $response->getStatusCode(),
            'Export route should exist (got 404)');
    }
}

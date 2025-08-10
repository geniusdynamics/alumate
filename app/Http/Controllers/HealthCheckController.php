<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    /**
     * Homepage health check endpoint.
     */
    public function homepage(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'homepage_assets' => $this->checkHomepageAssets(),
            'homepage_routes' => $this->checkHomepageRoutes(),
        ];
        
        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');
        
        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
        ], $allHealthy ? 200 : 503);
    }
    
    /**
     * Check database connectivity.
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            
            // Test a simple query
            $result = DB::select('SELECT 1 as test');
            
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'response_time' => $this->measureResponseTime(fn() => DB::select('SELECT 1')),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'response_time' => null,
            ];
        }
    }
    
    /**
     * Check cache system.
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache system working',
                    'driver' => config('cache.default'),
                ];
            }
            
            return [
                'status' => 'unhealthy',
                'message' => 'Cache value mismatch',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache system failed: ' . $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }
    
    /**
     * Check storage system.
     */
    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test';
            
            Storage::put($testFile, $testContent);
            $retrieved = Storage::get($testFile);
            Storage::delete($testFile);
            
            if ($retrieved === $testContent) {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage system working',
                    'driver' => config('filesystems.default'),
                ];
            }
            
            return [
                'status' => 'unhealthy',
                'message' => 'Storage content mismatch',
                'driver' => config('filesystems.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage system failed: ' . $e->getMessage(),
                'driver' => config('filesystems.default'),
            ];
        }
    }
    
    /**
     * Check homepage assets.
     */
    private function checkHomepageAssets(): array
    {
        try {
            $manifestPath = public_path('build/manifest.json');
            
            if (!file_exists($manifestPath)) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Build manifest not found',
                ];
            }
            
            $manifest = json_decode(file_get_contents($manifestPath), true);
            
            if (!$manifest) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Invalid build manifest',
                ];
            }
            
            // Check for key homepage assets
            $requiredAssets = [
                'resources/js/Pages/Homepage/Index.vue',
                'resources/js/Components/Homepage/HeroSection.vue',
                'resources/css/app.css',
            ];
            
            $missingAssets = [];
            foreach ($requiredAssets as $asset) {
                if (!isset($manifest[$asset])) {
                    $missingAssets[] = $asset;
                }
            }
            
            if (!empty($missingAssets)) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Missing homepage assets: ' . implode(', ', $missingAssets),
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'Homepage assets available',
                'assets_count' => count($manifest),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Asset check failed: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Check homepage routes.
     */
    private function checkHomepageRoutes(): array
    {
        try {
            $routes = app('router')->getRoutes();
            $homepageRoutes = [];
            
            foreach ($routes as $route) {
                if (str_contains($route->uri(), 'homepage') || $route->uri() === '/') {
                    $homepageRoutes[] = $route->uri();
                }
            }
            
            if (empty($homepageRoutes)) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'No homepage routes found',
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'Homepage routes available',
                'routes_count' => count($homepageRoutes),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Route check failed: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Measure response time for a callback.
     */
    private function measureResponseTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);
        
        return round(($end - $start) * 1000, 2); // Convert to milliseconds
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ABTestController extends Controller
{
    /**
     * Get active A/B tests for the current audience
     */
    public function getActiveTests(Request $request): JsonResponse
    {
        $audience = $request->header('X-Audience', 'individual');
        
        try {
            $cacheKey = "active_ab_tests_{$audience}";
            
            $tests = Cache::remember($cacheKey, 300, function () use ($audience) {
                return DB::table('ab_tests')
                    ->where('status', 'running')
                    ->where(function ($query) use ($audience) {
                        $query->where('audience', $audience)
                              ->orWhere('audience', 'both');
                    })
                    ->where('start_date', '<=', now())
                    ->where(function ($query) {
                        $query->whereNull('end_date')
                              ->orWhere('end_date', '>', now());
                    })
                    ->select([
                        'id',
                        'name',
                        'audience',
                        'variants',
                        'traffic_allocation',
                        'conversion_goals',
                        'start_date',
                        'end_date',
                        'status'
                    ])
                    ->get()
                    ->map(function ($test) {
                        $test->variants = json_decode($test->variants, true);
                        $test->conversion_goals = json_decode($test->conversion_goals, true);
                        return $test;
                    });
            });

            return response()->json($tests);

        } catch (\Exception $e) {
            Log::error('Failed to get active A/B tests', [
                'error' => $e->getMessage(),
                'audience' => $audience
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve active tests'
            ], 500);
        }
    }

    /**
     * Store A/B test assignment
     */
    public function storeAssignment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'testId' => 'required|string|max:100',
            'variantId' => 'required|string|max:100',
            'userId' => 'nullable|string|max:100',
            'sessionId' => 'required|string|max:100',
            'audience' => 'required|in:individual,institutional',
            'timestamp' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $assignmentData = [
                'test_id' => $request->input('testId'),
                'variant_id' => $request->input('variantId'),
                'user_id' => $request->input('userId'),
                'session_id' => $request->input('sessionId'),
                'audience' => $request->input('audience'),
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'assigned_at' => $request->input('timestamp'),
                'created_at' => now()
            ];

            // Use INSERT IGNORE to handle duplicate assignments gracefully
            DB::table('ab_test_assignments')->insertOrIgnore($assignmentData);

            // Update assignment cache for real-time metrics
            $this->updateAssignmentCache($assignmentData);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to store A/B test assignment', [
                'error' => $e->getMessage(),
                'test_id' => $request->input('testId'),
                'session_id' => $request->input('sessionId')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to store assignment'
            ], 500);
        }
    }

    /**
     * Store A/B test conversion
     */
    public function storeConversion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'testId' => 'required|string|max:100',
            'variantId' => 'required|string|max:100',
            'goalId' => 'required|string|max:100',
            'value' => 'required|numeric|min:0',
            'userId' => 'nullable|string|max:100',
            'sessionId' => 'required|string|max:100',
            'audience' => 'required|in:individual,institutional',
            'timestamp' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conversionData = [
                'test_id' => $request->input('testId'),
                'variant_id' => $request->input('variantId'),
                'goal_id' => $request->input('goalId'),
                'value' => $request->input('value'),
                'user_id' => $request->input('userId'),
                'session_id' => $request->input('sessionId'),
                'audience' => $request->input('audience'),
                'user_agent' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'converted_at' => $request->input('timestamp'),
                'created_at' => now()
            ];

            // Store conversion in database
            DB::table('ab_test_conversions')->insert($conversionData);

            // Update conversion cache for real-time metrics
            $this->updateConversionCache($conversionData);

            // Log high-value conversions
            if ($conversionData['value'] >= 100) {
                Log::info('High-value A/B test conversion', [
                    'test_id' => $conversionData['test_id'],
                    'variant_id' => $conversionData['variant_id'],
                    'goal_id' => $conversionData['goal_id'],
                    'value' => $conversionData['value']
                ]);
            }

            return response()->json([
                'success' => true,
                'conversion_id' => DB::getPdo()->lastInsertId()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store A/B test conversion', [
                'error' => $e->getMessage(),
                'test_id' => $request->input('testId'),
                'session_id' => $request->input('sessionId')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to store conversion'
            ], 500);
        }
    }

    /**
     * Get A/B test results
     */
    public function getTestResults(Request $request, string $testId): JsonResponse
    {
        try {
            $cacheKey = "ab_test_results_{$testId}";
            
            $results = Cache::remember($cacheKey, 300, function () use ($testId) {
                return $this->calculateTestResults($testId);
            });

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Failed to get A/B test results', [
                'error' => $e->getMessage(),
                'test_id' => $testId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve test results'
            ], 500);
        }
    }

    /**
     * Get A/B test statistics
     */
    public function getTestStatistics(Request $request, string $testId): JsonResponse
    {
        try {
            $cacheKey = "ab_test_statistics_{$testId}";
            
            $statistics = Cache::remember($cacheKey, 300, function () use ($testId) {
                return $this->calculateTestStatistics($testId);
            });

            return response()->json($statistics);

        } catch (\Exception $e) {
            Log::error('Failed to get A/B test statistics', [
                'error' => $e->getMessage(),
                'test_id' => $testId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve test statistics'
            ], 500);
        }
    }

    /**
     * Create new A/B test
     */
    public function createTest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'audience' => 'required|in:individual,institutional,both',
            'variants' => 'required|array|min:2',
            'variants.*.id' => 'required|string|max:100',
            'variants.*.name' => 'required|string|max:200',
            'variants.*.weight' => 'required|integer|min:1|max:100',
            'variants.*.componentOverrides' => 'required|array',
            'trafficAllocation' => 'required|integer|min:1|max:100',
            'conversionGoals' => 'required|array|min:1',
            'conversionGoals.*.id' => 'required|string|max:100',
            'conversionGoals.*.name' => 'required|string|max:200',
            'conversionGoals.*.type' => 'required|string|max:100',
            'conversionGoals.*.value' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate variant weights sum to 100
            $totalWeight = array_sum(array_column($request->input('variants'), 'weight'));
            if ($totalWeight !== 100) {
                return response()->json([
                    'success' => false,
                    'error' => 'Variant weights must sum to 100'
                ], 422);
            }

            $testData = [
                'name' => $request->input('name'),
                'audience' => $request->input('audience'),
                'variants' => json_encode($request->input('variants')),
                'traffic_allocation' => $request->input('trafficAllocation'),
                'conversion_goals' => json_encode($request->input('conversionGoals')),
                'description' => $request->input('description'),
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now()
            ];

            $testId = DB::table('ab_tests')->insertGetId($testData);

            // Clear active tests cache
            Cache::forget("active_ab_tests_{$request->input('audience')}");
            if ($request->input('audience') === 'both') {
                Cache::forget('active_ab_tests_individual');
                Cache::forget('active_ab_tests_institutional');
            }

            return response()->json([
                'success' => true,
                'id' => $testId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create A/B test', [
                'error' => $e->getMessage(),
                'test_name' => $request->input('name')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create test'
            ], 500);
        }
    }

    /**
     * Update A/B test
     */
    public function updateTest(Request $request, string $testId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:200',
            'audience' => 'sometimes|in:individual,institutional,both',
            'variants' => 'sometimes|array|min:2',
            'trafficAllocation' => 'sometimes|integer|min:1|max:100',
            'conversionGoals' => 'sometimes|array|min:1',
            'status' => 'sometimes|in:draft,running,paused,completed',
            'startDate' => 'sometimes|date',
            'endDate' => 'sometimes|date|after:startDate',
            'description' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = array_filter($request->only([
                'name', 'audience', 'variants', 'trafficAllocation', 
                'conversionGoals', 'status', 'description'
            ]));

            // Handle JSON fields
            if (isset($updateData['variants'])) {
                $updateData['variants'] = json_encode($updateData['variants']);
            }
            if (isset($updateData['conversionGoals'])) {
                $updateData['conversion_goals'] = json_encode($updateData['conversionGoals']);
                unset($updateData['conversionGoals']);
            }
            if (isset($updateData['trafficAllocation'])) {
                $updateData['traffic_allocation'] = $updateData['trafficAllocation'];
                unset($updateData['trafficAllocation']);
            }

            // Handle date fields
            if ($request->has('startDate')) {
                $updateData['start_date'] = $request->input('startDate');
            }
            if ($request->has('endDate')) {
                $updateData['end_date'] = $request->input('endDate');
            }

            $updateData['updated_at'] = now();

            $updated = DB::table('ab_tests')
                ->where('id', $testId)
                ->update($updateData);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'error' => 'Test not found'
                ], 404);
            }

            // Clear relevant caches
            $this->clearTestCaches($testId);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to update A/B test', [
                'error' => $e->getMessage(),
                'test_id' => $testId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update test'
            ], 500);
        }
    }

    /**
     * Delete A/B test
     */
    public function deleteTest(Request $request, string $testId): JsonResponse
    {
        try {
            // Check if test has any data
            $hasAssignments = DB::table('ab_test_assignments')
                ->where('test_id', $testId)
                ->exists();

            $hasConversions = DB::table('ab_test_conversions')
                ->where('test_id', $testId)
                ->exists();

            if ($hasAssignments || $hasConversions) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot delete test with existing data. Archive it instead.'
                ], 422);
            }

            $deleted = DB::table('ab_tests')
                ->where('id', $testId)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'error' => 'Test not found'
                ], 404);
            }

            // Clear relevant caches
            $this->clearTestCaches($testId);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to delete A/B test', [
                'error' => $e->getMessage(),
                'test_id' => $testId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete test'
            ], 500);
        }
    }

    /**
     * Get all A/B tests (admin)
     */
    public function getAllTests(Request $request): JsonResponse
    {
        try {
            $query = DB::table('ab_tests')
                ->select([
                    'id',
                    'name',
                    'audience',
                    'status',
                    'start_date',
                    'end_date',
                    'traffic_allocation',
                    'created_at',
                    'updated_at'
                ]);

            // Apply filters
            if ($request->has('audience')) {
                $query->where('audience', $request->input('audience'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $tests = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json($tests);

        } catch (\Exception $e) {
            Log::error('Failed to get all A/B tests', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve tests'
            ], 500);
        }
    }

    // Private Helper Methods

    private function updateAssignmentCache(array $assignmentData): void
    {
        $testId = $assignmentData['test_id'];
        $variantId = $assignmentData['variant_id'];
        $audience = $assignmentData['audience'];
        $dateKey = Carbon::parse($assignmentData['assigned_at'])->format('Y-m-d');

        // Update assignment counts
        Cache::increment("ab_assignments_{$testId}_{$variantId}_{$dateKey}", 1);
        Cache::increment("ab_assignments_{$testId}_{$dateKey}", 1);
        Cache::increment("ab_assignments_{$audience}_{$dateKey}", 1);
    }

    private function updateConversionCache(array $conversionData): void
    {
        $testId = $conversionData['test_id'];
        $variantId = $conversionData['variant_id'];
        $goalId = $conversionData['goal_id'];
        $audience = $conversionData['audience'];
        $dateKey = Carbon::parse($conversionData['converted_at'])->format('Y-m-d');

        // Update conversion counts
        Cache::increment("ab_conversions_{$testId}_{$variantId}_{$goalId}_{$dateKey}", 1);
        Cache::increment("ab_conversions_{$testId}_{$variantId}_{$dateKey}", 1);
        Cache::increment("ab_conversions_{$testId}_{$dateKey}", 1);
        
        // Update conversion value
        Cache::increment("ab_conversion_value_{$testId}_{$variantId}_{$dateKey}", $conversionData['value']);
    }

    private function calculateTestResults(string $testId): array
    {
        // Get test details
        $test = DB::table('ab_tests')->where('id', $testId)->first();
        if (!$test) {
            throw new \Exception("Test not found: {$testId}");
        }

        $variants = json_decode($test->variants, true);
        $conversionGoals = json_decode($test->conversion_goals, true);

        // Get assignment counts by variant
        $assignments = DB::table('ab_test_assignments')
            ->select('variant_id', DB::raw('COUNT(*) as count'))
            ->where('test_id', $testId)
            ->groupBy('variant_id')
            ->pluck('count', 'variant_id')
            ->toArray();

        // Get conversion counts by variant and goal
        $conversions = DB::table('ab_test_conversions')
            ->select('variant_id', 'goal_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(value) as total_value'))
            ->where('test_id', $testId)
            ->groupBy('variant_id', 'goal_id')
            ->get()
            ->groupBy('variant_id')
            ->toArray();

        // Calculate results for each variant
        $variantResults = [];
        foreach ($variants as $variant) {
            $variantId = $variant['id'];
            $assignmentCount = $assignments[$variantId] ?? 0;
            $variantConversions = $conversions[$variantId] ?? [];

            $goalResults = [];
            foreach ($conversionGoals as $goal) {
                $goalId = $goal['id'];
                $goalConversions = collect($variantConversions)->where('goal_id', $goalId)->first();
                
                $conversionCount = $goalConversions->count ?? 0;
                $conversionValue = $goalConversions->total_value ?? 0;
                $conversionRate = $assignmentCount > 0 ? $conversionCount / $assignmentCount : 0;

                $goalResults[] = [
                    'goalId' => $goalId,
                    'goalName' => $goal['name'],
                    'conversions' => $conversionCount,
                    'conversionRate' => round($conversionRate, 4),
                    'totalValue' => $conversionValue
                ];
            }

            $variantResults[] = [
                'variantId' => $variantId,
                'variantName' => $variant['name'],
                'assignments' => $assignmentCount,
                'goals' => $goalResults
            ];
        }

        // Calculate statistical significance (simplified)
        $significance = $this->calculateStatisticalSignificance($variantResults);

        return [
            'testId' => $testId,
            'testName' => $test->name,
            'status' => $test->status,
            'startDate' => $test->start_date,
            'endDate' => $test->end_date,
            'variants' => $variantResults,
            'statisticalSignificance' => $significance,
            'winner' => $significance['winner'] ?? null
        ];
    }

    private function calculateTestStatistics(string $testId): array
    {
        // Get basic statistics
        $totalAssignments = DB::table('ab_test_assignments')
            ->where('test_id', $testId)
            ->count();

        $totalConversions = DB::table('ab_test_conversions')
            ->where('test_id', $testId)
            ->count();

        $totalValue = DB::table('ab_test_conversions')
            ->where('test_id', $testId)
            ->sum('value');

        // Get daily statistics
        $dailyStats = DB::table('ab_test_assignments')
            ->select(
                DB::raw('DATE(assigned_at) as date'),
                DB::raw('COUNT(*) as assignments')
            )
            ->where('test_id', $testId)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        // Get conversion statistics by goal
        $goalStats = DB::table('ab_test_conversions')
            ->select(
                'goal_id',
                DB::raw('COUNT(*) as conversions'),
                DB::raw('SUM(value) as total_value')
            )
            ->where('test_id', $testId)
            ->groupBy('goal_id')
            ->get()
            ->toArray();

        return [
            'testId' => $testId,
            'totalAssignments' => $totalAssignments,
            'totalConversions' => $totalConversions,
            'totalValue' => $totalValue,
            'overallConversionRate' => $totalAssignments > 0 ? $totalConversions / $totalAssignments : 0,
            'dailyStats' => $dailyStats,
            'goalStats' => $goalStats
        ];
    }

    private function calculateStatisticalSignificance(array $variantResults): array
    {
        if (count($variantResults) < 2) {
            return ['significant' => false, 'confidence' => 0];
        }

        // Use first variant as control
        $control = $variantResults[0];
        $variant = $variantResults[1];

        // Calculate for primary goal (first goal)
        if (empty($control['goals']) || empty($variant['goals'])) {
            return ['significant' => false, 'confidence' => 0];
        }

        $controlGoal = $control['goals'][0];
        $variantGoal = $variant['goals'][0];

        $controlConversions = $controlGoal['conversions'];
        $controlSamples = $control['assignments'];
        $variantConversions = $variantGoal['conversions'];
        $variantSamples = $variant['assignments'];

        if ($controlSamples < 30 || $variantSamples < 30) {
            return ['significant' => false, 'confidence' => 0, 'reason' => 'Insufficient sample size'];
        }

        $controlRate = $controlSamples > 0 ? $controlConversions / $controlSamples : 0;
        $variantRate = $variantSamples > 0 ? $variantConversions / $variantSamples : 0;

        $pooledRate = ($controlConversions + $variantConversions) / ($controlSamples + $variantSamples);
        $standardError = sqrt($pooledRate * (1 - $pooledRate) * (1/$controlSamples + 1/$variantSamples));

        if ($standardError == 0) {
            return ['significant' => false, 'confidence' => 0];
        }

        $zScore = abs($controlRate - $variantRate) / $standardError;
        $pValue = 2 * (1 - $this->normalCDF(abs($zScore)));

        $significant = $pValue < 0.05;
        $confidence = (1 - $pValue) * 100;

        $winner = null;
        if ($significant) {
            $winner = $variantRate > $controlRate ? $variant['variantId'] : $control['variantId'];
        }

        return [
            'significant' => $significant,
            'confidence' => round($confidence, 2),
            'pValue' => round($pValue, 4),
            'zScore' => round($zScore, 4),
            'winner' => $winner,
            'improvement' => $controlRate > 0 ? round((($variantRate - $controlRate) / $controlRate) * 100, 2) : 0
        ];
    }

    private function normalCDF(float $x): float
    {
        return 0.5 * (1 + $this->erf($x / sqrt(2)));
    }

    private function erf(float $x): float
    {
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;

        $sign = $x >= 0 ? 1 : -1;
        $x = abs($x);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - (((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x));

        return $sign * $y;
    }

    private function clearTestCaches(string $testId): void
    {
        // Clear test-specific caches
        Cache::forget("ab_test_results_{$testId}");
        Cache::forget("ab_test_statistics_{$testId}");
        
        // Clear active tests caches
        Cache::forget('active_ab_tests_individual');
        Cache::forget('active_ab_tests_institutional');
        Cache::forget('active_ab_tests_both');
    }
}
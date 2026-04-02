<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Career Prediction Service
 *
 * Provides AI-powered career outcome predictions based on learning analytics.
 * Integrates with external AI models to update user career predictions
 * when learning milestones are achieved.
 */
class CareerPredictionService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const PREDICTION_CACHE_KEY = 'career_prediction_%s';

    private ConsentService $consentService;

    /**
     * Initialize service with dependencies
     */
    public function __construct(ConsentService $consentService)
    {
        $this->consentService = $consentService;
    }

    /**
     * Update learning impact on career prediction
     *
     * Feeds engagement scores and certification data to AI model
     * to update user's career outcome predictions.
     *
     * @param  int  $userId  User ID
     * @param  array  $learningData  Learning progress data
     * @return bool Success status
     */
    public function updateLearningImpact(int $userId, array $learningData): bool
    {
        try {
            // Check consent before processing
            if (! $this->consentService->checkConsent($userId, 'analytics')) {
                Log::info('Career prediction update skipped due to lack of consent', [
                    'user_id' => $userId,
                ]);

                return false;
            }

            // Get current prediction score
            $currentScore = $this->getCurrentPredictionScore($userId);

            // Calculate learning impact factor
            $impactFactor = $this->calculateLearningImpact($learningData);

            // Update prediction via AI model or regression formula
            $newScore = $this->updatePredictionScore($userId, $currentScore, $impactFactor, $learningData);

            // Cache updated prediction
            $this->cachePredictionScore($userId, $newScore);

            Log::info('Career prediction updated with learning impact', [
                'user_id' => $userId,
                'old_score' => $currentScore,
                'new_score' => $newScore,
                'impact_factor' => $impactFactor,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to update career prediction', [
                'user_id' => $userId,
                'learning_data' => $learningData,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get current career prediction score for user
     */
    private function getCurrentPredictionScore(int $userId): float
    {
        $cacheKey = sprintf(self::PREDICTION_CACHE_KEY, $userId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            // In a real implementation, this would query a UserCareerPrediction model
            // For now, return a default score based on user profile
            return $this->calculateBasePredictionScore($userId);
        });
    }

    /**
     * Calculate base prediction score from user profile
     */
    private function calculateBasePredictionScore(int $userId): float
    {
        // Simplified regression formula based on user attributes
        // In practice, this would use historical data and ML models

        $user = User::find($userId);
        if (! $user) {
            return 50.0;
        } // Default neutral score

        $score = 50.0; // Base score

        // Education factor (simplified)
        if ($user->education_level) {
            $educationMultiplier = match ($user->education_level) {
                'high_school' => 0.8,
                'bachelors' => 1.0,
                'masters' => 1.2,
                'phd' => 1.3,
                default => 1.0
            };
            $score *= $educationMultiplier;
        }

        // Experience factor
        if ($user->years_experience) {
            $experienceBonus = min($user->years_experience * 2, 20); // Max 20 points
            $score += $experienceBonus;
        }

        return min(max($score, 0), 100); // Clamp between 0-100
    }

    /**
     * Calculate learning impact factor from progress data
     */
    private function calculateLearningImpact(array $learningData): float
    {
        $engagementScore = $learningData['engagement_score'] ?? 0;
        $totalScore = $learningData['total_score'] ?? 0;
        $modulesCompleted = $learningData['modules_completed'] ?? 0;
        $certified = $learningData['certified'] ?? false;

        // Weighted formula for learning impact
        $impact = ($engagementScore * 0.4) + ($totalScore * 0.3) + ($modulesCompleted * 2);

        // Certification bonus
        if ($certified) {
            $impact += 15;
        }

        // Normalize to 0-1 factor
        return min($impact / 100, 1.0);
    }

    /**
     * Update prediction score using AI model or regression
     */
    private function updatePredictionScore(int $userId, float $currentScore, float $impactFactor, array $learningData): float
    {
        // Option 1: Use external AI API (if configured)
        if ($this->isAiApiConfigured()) {
            return $this->updateViaAiApi($userId, $currentScore, $impactFactor, $learningData);
        }

        // Option 2: Use regression formula
        return $this->updateViaRegression($currentScore, $impactFactor, $learningData);
    }

    /**
     * Check if AI API is configured
     */
    private function isAiApiConfigured(): bool
    {
        return config('services.career_prediction.api_url') &&
               config('services.career_prediction.api_key');
    }

    /**
     * Update prediction via external AI API
     */
    private function updateViaAiApi(int $userId, float $currentScore, float $impactFactor, array $learningData): float
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.career_prediction.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.career_prediction.api_url').'/predict', [
                'user_id' => $userId,
                'current_score' => $currentScore,
                'learning_impact' => $impactFactor,
                'engagement_score' => $learningData['engagement_score'] ?? 0,
                'certified' => $learningData['certified'] ?? false,
                'modules_completed' => $learningData['modules_completed'] ?? 0,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['prediction_score'] ?? $currentScore;
            }

            Log::warning('AI API prediction failed, falling back to regression', [
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

        } catch (Exception $e) {
            Log::warning('AI API call failed, falling back to regression', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to regression
        return $this->updateViaRegression($currentScore, $impactFactor, $learningData);
    }

    /**
     * Update prediction using regression formula
     */
    private function updateViaRegression(float $currentScore, float $impactFactor, array $learningData): float
    {
        // Simple regression: new_score = current_score + (impact_factor * learning_weight) + certification_bonus
        $learningWeight = 10; // Points added per impact factor
        $certificationBonus = ($learningData['certified'] ?? false) ? 5 : 0;

        $adjustment = ($impactFactor * $learningWeight) + $certificationBonus;

        // Apply diminishing returns for very high scores
        if ($currentScore > 80) {
            $adjustment *= 0.5;
        }

        $newScore = $currentScore + $adjustment;

        return min(max($newScore, 0), 100); // Clamp between 0-100
    }

    /**
     * Cache prediction score
     */
    private function cachePredictionScore(int $userId, float $score): void
    {
        $cacheKey = sprintf(self::PREDICTION_CACHE_KEY, $userId);
        Cache::put($cacheKey, $score, self::CACHE_TTL);
    }

    /**
     * Get career prediction score for user
     *
     * @param  int  $userId  User ID
     * @return float Prediction score (0-100)
     */
    public function getPredictionScore(int $userId): float
    {
        return $this->getCurrentPredictionScore($userId);
    }

    /**
     * Clear prediction cache for user
     */
    public function clearPredictionCache(int $userId): void
    {
        $cacheKey = sprintf(self::PREDICTION_CACHE_KEY, $userId);
        Cache::forget($cacheKey);
    }
}

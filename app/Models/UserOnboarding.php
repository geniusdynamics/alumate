<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnboarding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_new_user',
        'has_completed_onboarding',
        'progress',
        'preferences',
        'explored_features',
        'dismissed_prompts',
        'feature_discovery_viewed_at',
        'whats_new_viewed_at',
        'completed_at',
        'skipped_at',
    ];

    protected $casts = [
        'is_new_user' => 'boolean',
        'has_completed_onboarding' => 'boolean',
        'progress' => 'array',
        'preferences' => 'array',
        'explored_features' => 'array',
        'dismissed_prompts' => 'array',
        'feature_discovery_viewed_at' => 'datetime',
        'whats_new_viewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    /**
     * Get the user that owns the onboarding record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has completed onboarding
     */
    public function isCompleted(): bool
    {
        return $this->has_completed_onboarding;
    }

    /**
     * Check if user is considered new
     */
    public function isNewUser(): bool
    {
        return $this->is_new_user;
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $progress = $this->progress ?? [];
        $completedSteps = $progress['completed_steps'] ?? [];
        $totalSteps = $this->getTotalStepsForRole();

        if ($totalSteps === 0) {
            return 100;
        }

        return round((count($completedSteps) / $totalSteps) * 100);
    }

    /**
     * Get total steps based on user role
     */
    private function getTotalStepsForRole(): int
    {
        $role = $this->user->roles->first()?->name ?? 'graduate';

        $stepCounts = [
            'graduate' => 7,
            'employer' => 4,
            'institution-admin' => 4,
            'student' => 4,
        ];

        return $stepCounts[$role] ?? 7;
    }

    /**
     * Check if a feature has been explored
     */
    public function hasExploredFeature(string $featureId): bool
    {
        return in_array($featureId, $this->explored_features ?? []);
    }

    /**
     * Check if a prompt has been dismissed
     */
    public function hasPromptBeenDismissed(string $prompt): bool
    {
        return in_array($prompt, $this->dismissed_prompts ?? []);
    }

    /**
     * Get user preference value
     */
    public function getPreference(string $key, $default = null)
    {
        return ($this->preferences ?? [])[$key] ?? $default;
    }

    /**
     * Mark step as completed
     */
    public function markStepCompleted(string $stepId): void
    {
        $progress = $this->progress ?? ['completed_steps' => [], 'skipped_steps' => []];

        if (! in_array($stepId, $progress['completed_steps'])) {
            $progress['completed_steps'][] = $stepId;
            $this->update(['progress' => $progress]);
        }
    }

    /**
     * Mark step as skipped
     */
    public function markStepSkipped(string $stepId): void
    {
        $progress = $this->progress ?? ['completed_steps' => [], 'skipped_steps' => []];

        if (! in_array($stepId, $progress['skipped_steps'])) {
            $progress['skipped_steps'][] = $stepId;
            $this->update(['progress' => $progress]);
        }
    }

    /**
     * Add explored feature
     */
    public function addExploredFeature(string $featureId): void
    {
        $exploredFeatures = $this->explored_features ?? [];

        if (! in_array($featureId, $exploredFeatures)) {
            $exploredFeatures[] = $featureId;
            $this->update(['explored_features' => $exploredFeatures]);
        }
    }

    /**
     * Add dismissed prompt
     */
    public function addDismissedPrompt(string $prompt): void
    {
        $dismissedPrompts = $this->dismissed_prompts ?? [];

        if (! in_array($prompt, $dismissedPrompts)) {
            $dismissedPrompts[] = $prompt;
            $this->update(['dismissed_prompts' => $dismissedPrompts]);
        }
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(array $newPreferences): void
    {
        $preferences = array_merge($this->preferences ?? [], $newPreferences);
        $this->update(['preferences' => $preferences]);
    }
}

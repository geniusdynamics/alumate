<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;

class RateLimitValidation implements ValidationRule
{
    private string $key;
    private int $maxAttempts;
    private int $decayMinutes;
    private string $identifier;

    public function __construct(
        string $key = 'form_submission',
        int $maxAttempts = 5,
        int $decayMinutes = 60,
        ?string $identifier = null
    ) {
        $this->key = $key;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->identifier = $identifier ?? $this->getDefaultIdentifier();
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rateLimitKey = $this->getRateLimitKey();
        
        // Check if rate limit is exceeded
        if (RateLimiter::tooManyAttempts($rateLimitKey, $this->maxAttempts)) {
            $availableIn = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($availableIn / 60);
            
            $fail("Too many attempts. Please try again in {$minutes} minute(s).");
            return;
        }

        // Check for suspicious rapid submissions
        if ($this->isSuspiciousActivity()) {
            $fail('Suspicious activity detected. Please wait before submitting again.');
            return;
        }

        // Record this attempt
        RateLimiter::hit($rateLimitKey, $this->decayMinutes * 60);
        $this->recordSubmissionTime();
    }

    /**
     * Get the rate limit key
     */
    private function getRateLimitKey(): string
    {
        return "{$this->key}:{$this->identifier}";
    }

    /**
     * Get default identifier (IP address or user ID)
     */
    private function getDefaultIdentifier(): string
    {
        if (auth()->check()) {
            return 'user:' . auth()->id();
        }
        
        return 'ip:' . request()->ip();
    }

    /**
     * Check for suspicious rapid submission patterns
     */
    private function isSuspiciousActivity(): bool
    {
        $submissionKey = "submissions:{$this->identifier}";
        $submissions = Cache::get($submissionKey, []);
        
        $now = time();
        
        // Remove old submissions (older than 1 hour)
        $submissions = array_filter($submissions, fn($time) => $now - $time < 3600);
        
        // Check for rapid submissions (more than 3 in 5 minutes)
        $recentSubmissions = array_filter($submissions, fn($time) => $now - $time < 300);
        
        if (count($recentSubmissions) >= 3) {
            return true;
        }

        // Check for very rapid submissions (less than 30 seconds apart)
        if (count($submissions) > 0) {
            $lastSubmission = max($submissions);
            if ($now - $lastSubmission < 30) {
                return true;
            }
        }

        return false;
    }

    /**
     * Record submission time for pattern analysis
     */
    private function recordSubmissionTime(): void
    {
        $submissionKey = "submissions:{$this->identifier}";
        $submissions = Cache::get($submissionKey, []);
        
        $submissions[] = time();
        
        // Keep only last 10 submissions
        if (count($submissions) > 10) {
            $submissions = array_slice($submissions, -10);
        }
        
        Cache::put($submissionKey, $submissions, 3600); // Store for 1 hour
    }

    /**
     * Create a rate limit rule for specific scenarios
     */
    public static function forFormSubmission(string $formType = 'general'): self
    {
        return new self(
            key: "form_submission:{$formType}",
            maxAttempts: 5,
            decayMinutes: 60
        );
    }

    /**
     * Create a strict rate limit for sensitive operations
     */
    public static function strict(): self
    {
        return new self(
            key: 'strict_operation',
            maxAttempts: 2,
            decayMinutes: 120
        );
    }

    /**
     * Create a lenient rate limit for regular operations
     */
    public static function lenient(): self
    {
        return new self(
            key: 'lenient_operation',
            maxAttempts: 10,
            decayMinutes: 30
        );
    }
}
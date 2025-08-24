<?php

namespace App\Services;

use App\Models\User;
use App\Models\TwoFactorAuth;
use App\Models\FailedLoginAttempt;
use App\Models\SecurityEvent;
use App\Models\SessionSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

// Note: Google2FA package may not be installed, using fallback for secret generation
// If needed, install with: composer require pragmarx/google2fa

class SecurityService
{
    public function __construct()
    {
        //
    }

    /**
     * Enable two-factor authentication for a user
     */
    public function enableTwoFactorAuth(User $user, array $options = []): TwoFactorAuth
    {
        // Generate secret key (fallback if Google2FA not available)
        $secret = $this->generateSecretKey();
        
        // Generate recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = Str::random(10);
        }
        
        $twoFactor = TwoFactorAuth::updateOrCreate(
            ['user_id' => $user->id],
            [
                'enabled' => true,
                'secret' => $secret,
                'recovery_codes' => $recoveryCodes,
                'enabled_at' => now(),
                'backup_method' => $options['backup_method'] ?? 'email',
                'backup_contact' => $options['backup_contact'] ?? $user->email,
            ]
        );
        
        // Update user's two_factor_enabled flag
        $user->update(['two_factor_enabled' => true]);
        
        // Log security event
        $this->logSecurityEvent(
            SecurityEvent::TYPE_TWO_FACTOR_ENABLED,
            SecurityEvent::SEVERITY_LOW,
            'Two-factor authentication enabled',
            ['method' => 'google_authenticator'],
            $user->id
        );
        
        return $twoFactor;
    }
    
    /**
     * Disable two-factor authentication for a user
     */
    public function disableTwoFactorAuth(User $user): bool
    {
        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->first();
        
        if ($twoFactor) {
            $twoFactor->update([
                'enabled' => false,
                'secret' => null,
                'recovery_codes' => null,
            ]);
        }
        
        // Update user's two_factor_enabled flag
        $user->update(['two_factor_enabled' => false]);
        
        // Log security event
        $this->logSecurityEvent(
            SecurityEvent::TYPE_TWO_FACTOR_DISABLED,
            SecurityEvent::SEVERITY_MEDIUM,
            'Two-factor authentication disabled',
            [],
            $user->id
        );
        
        return true;
    }
    
    /**
     * Handle failed login attempt
     */
    public function handleFailedLogin(string $email, string $ip, Request $request = null): FailedLoginAttempt
    {
        $userAgent = $request ? $request->userAgent() : null;
        
        $attempt = FailedLoginAttempt::where('email', $email)
            ->where('ip_address', $ip)
            ->first();
            
        if ($attempt) {
            $attempt->increment('attempts');
            $attempt->update([
                'last_attempt_at' => now(),
                'user_agent' => $userAgent,
            ]);
            
            // Block after 5 attempts for 15 minutes
            if ($attempt->attempts >= 5) {
                $attempt->update([
                    'blocked_until' => now()->addMinutes(15)
                ]);
            }
        } else {
            $attempt = FailedLoginAttempt::create([
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'attempts' => 1,
                'last_attempt_at' => now(),
            ]);
        }
        
        // Log security event
        $this->logSecurityEvent(
            SecurityEvent::TYPE_FAILED_LOGIN,
            SecurityEvent::SEVERITY_MEDIUM,
            'Failed login attempt',
            [
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'attempts' => $attempt->attempts
            ]
        );
        
        return $attempt;
    }
    
    /**
     * Check security policy for user action
     */
    public function checkSecurityPolicy(User $user, string $action): bool
    {
        // Basic security policy checks
        switch ($action) {
            case 'login':
                return !$user->is_suspended;
                
            case 'admin_access':
                return $user->hasRole('super-admin') || $user->hasRole('institution-admin');
                
            case 'user_management':
                return $user->can('manage-users');
                
            case 'data_export':
                return $user->can('export-data') && !$this->hasRecentSuspiciousActivity($user);
                
            case 'sensitive_data_access':
                return $user->two_factor_enabled && !$this->hasRecentSuspiciousActivity($user);
                
            default:
                return true;
        }
    }
    
    /**
     * Handle successful login
     */
    public function handleSuccessfulLogin(User $user, Request $request = null): void
    {
        $ip = $request ? $request->ip() : request()->ip();
        $userAgent = $request ? $request->userAgent() : request()->userAgent();
        
        // Clear failed login attempts
        FailedLoginAttempt::where('email', $user->email)->delete();
        
        // Track session
        SessionSecurity::create([
            'session_id' => session()->getId(),
            'user_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'last_activity' => now(),
            'expires_at' => now()->addHours(config('session.lifetime', 120) / 60),
        ]);
        
        // Update user last login
        $user->update(['last_login_at' => now()]);
    }

    /**
     * Detect malicious requests
     */
    public function detectMaliciousRequest(?Request $request = null): bool
    {
        $request = $request ?: request();
        
        // Check for common SQL injection patterns
        $inputs = array_merge($request->all(), $request->headers->all());
        $maliciousPatterns = [
            '/\bunion\b.*\bselect\b/i',
            '/\bdrop\b.*\btable\b/i',
            '/\binsert\b.*\binto\b/i',
            '/\bdelete\b.*\bfrom\b/i',
            '/\bupdate\b.*\bset\b/i',
            '/\bor\b.*\b1\s*=\s*1\b/i',
            '/\band\b.*\b1\s*=\s*1\b/i',
            '/[\'"];.*--/i',
            '/<script[^>]*>/i',
            '/javascript:/i',
        ];
        
        foreach ($inputs as $input) {
            if (is_string($input)) {
                foreach ($maliciousPatterns as $pattern) {
                    if (preg_match($pattern, $input)) {
                        // Log the malicious request
                        $this->logSecurityEvent(
                            SecurityEvent::TYPE_MALICIOUS_REQUEST,
                            SecurityEvent::SEVERITY_CRITICAL,
                            'Malicious request detected',
                            [
                                'pattern_matched' => $pattern,
                                'input_value' => $input,
                                'request_path' => $request->path(),
                            ]
                        );
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $type, string $severity, string $description, array $metadata = [], ?int $userId = null): SecurityEvent
    {
        $event = SecurityEvent::create([
            'event_type' => $type,
            'severity' => $severity,
            'description' => $description,
            'metadata' => $metadata,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
        ]);
        
        // Also log to Laravel logs
        Log::info("Security Event: {$type}", array_merge($metadata, [
            'severity' => $severity,
            'description' => $description,
            'user_id' => $userId,
        ]));
        
        return $event;
    }

    /**
     * Check if IP is blocked
     */
    public function isIpBlocked(string $ip): bool
    {
        // Placeholder for IP blocking logic
        return false;
    }

    /**
     * Check for suspicious patterns
     */
    public function checkSuspiciousPatterns(Request $request): bool
    {
        // Placeholder for pattern detection
        return false;
    }

    /**
     * Detect rate limit violations
     */
    public function detectRateLimitViolation(string $identifier, int $maxAttempts, int $minutes): bool
    {
        $key = "rate_limit:{$identifier}";
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return true;
        }
        
        Cache::put($key, $attempts + 1, now()->addMinutes($minutes));
        return false;
    }

    /**
     * Log data access
     */
    public function logDataAccess(string $resourceType, $resourceId, string $accessType, bool $success, ?string $context = null): void
    {
        // Placeholder for data access logging
        Log::info("Data Access: {$resourceType}:{$resourceId} - {$accessType}", [
            'success' => $success,
            'context' => $context,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
        ]);
    }
    
    /**
     * Validate session security
     */
    public function validateSessionSecurity(string $sessionId, string $ipAddress, string $userAgent): bool
    {
        $session = SessionSecurity::where('session_id', $sessionId)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$session) {
            return false;
        }
        
        // Check IP address consistency
        if ($session->ip_address !== $ipAddress) {
            $this->logSecurityEvent(
                SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
                SecurityEvent::SEVERITY_HIGH,
                'Session IP address mismatch',
                [
                    'session_id' => $sessionId,
                    'original_ip' => $session->ip_address,
                    'current_ip' => $ipAddress,
                ]
            );
            return false;
        }
        
        // Update last activity
        $session->update(['last_activity' => now()]);
        
        return true;
    }
    
    /**
     * Generate security report
     */
    public function generateSecurityReport(): array
    {
        $report = [
            'events_summary' => [
                'total_events' => SecurityEvent::count(),
                'critical_events' => SecurityEvent::where('severity', SecurityEvent::SEVERITY_CRITICAL)->count(),
                'high_events' => SecurityEvent::where('severity', SecurityEvent::SEVERITY_HIGH)->count(),
                'recent_events' => SecurityEvent::where('occurred_at', '>=', now()->subDays(7))->count(),
            ],
            'failed_logins' => [
                'total_attempts' => FailedLoginAttempt::sum('attempts'),
                'unique_ips' => FailedLoginAttempt::distinct('ip_address')->count(),
                'blocked_attempts' => FailedLoginAttempt::whereNotNull('blocked_until')
                    ->where('blocked_until', '>', now())
                    ->count(),
                'recent_attempts' => FailedLoginAttempt::where('last_attempt_at', '>=', now()->subDays(7))->count(),
            ],
            'active_sessions' => [
                'total' => SessionSecurity::where('expires_at', '>', now())->count(),
                'unique_users' => SessionSecurity::where('expires_at', '>', now())
                    ->distinct('user_id')
                    ->count(),
            ],
            'security_score' => $this->calculateSecurityScore(),
            'generated_at' => now(),
        ];
        
        return $report;
    }
    
    /**
     * Calculate overall security score
     */
    public function calculateSecurityScore(): float
    {
        $score = 100.0;
        
        // Deduct points for recent critical events
        $criticalEvents = SecurityEvent::where('severity', SecurityEvent::SEVERITY_CRITICAL)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->count();
        $score -= min(50, $criticalEvents * 10);
        
        // Deduct points for failed login attempts
        $failedLogins = FailedLoginAttempt::where('last_attempt_at', '>=', now()->subDays(7))
            ->sum('attempts');
        $score -= min(20, $failedLogins * 0.5);
        
        // Deduct points for unresolved security events
        $unresolvedEvents = SecurityEvent::where('resolved', false)
            ->whereIn('severity', [SecurityEvent::SEVERITY_HIGH, SecurityEvent::SEVERITY_CRITICAL])
            ->count();
        $score -= min(15, $unresolvedEvents * 3);
        
        // Bonus points for 2FA adoption
        $totalUsers = User::count();
        $twoFactorUsers = User::where('two_factor_enabled', true)->count();
        if ($totalUsers > 0) {
            $adoptionRate = ($twoFactorUsers / $totalUsers) * 100;
            $score += min(15, $adoptionRate * 0.15);
        }
        
        return max(0.0, min(100.0, round($score, 2)));
    }
    
    /**
     * Detect suspicious activity
     */
    public function detectSuspiciousActivity(): bool
    {
        // Check for multiple failed logins from same IP
        $suspiciousIps = FailedLoginAttempt::where('last_attempt_at', '>=', now()->subHour())
            ->groupBy('ip_address')
            ->havingRaw('COUNT(DISTINCT email) >= 5')
            ->pluck('ip_address');
            
        if ($suspiciousIps->isNotEmpty()) {
            foreach ($suspiciousIps as $ip) {
                $this->logSecurityEvent(
                    SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
                    SecurityEvent::SEVERITY_HIGH,
                    'Multiple failed login attempts from same IP',
                    ['ip_address' => $ip]
                );
            }
            return true;
        }
        
        // Check for rapid succession of events
        $recentEvents = SecurityEvent::where('occurred_at', '>=', now()->subMinutes(10))
            ->count();
            
        if ($recentEvents > 20) {
            $this->logSecurityEvent(
                SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY,
                SecurityEvent::SEVERITY_MEDIUM,
                'High volume of security events detected',
                ['event_count' => $recentEvents]
            );
            return true;
        }
        
        return false;
    }
    
    /**
     * Cleanup expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        $deletedCount = SessionSecurity::where('expires_at', '<', now())
            ->delete();
            
        if ($deletedCount > 0) {
            $this->logSecurityEvent(
                SecurityEvent::TYPE_SESSION_CLEANUP,
                SecurityEvent::SEVERITY_LOW,
                'Expired sessions cleaned up',
                ['deleted_count' => $deletedCount]
            );
        }
        
        return $deletedCount;
    }
    
    /**
     * Check if user has recent suspicious activity
     */
    protected function hasRecentSuspiciousActivity(User $user): bool
    {
        // Check for recent failed login attempts
        $recentFailedLogins = FailedLoginAttempt::where('email', $user->email)
            ->where('last_attempt_at', '>=', now()->subHours(24))
            ->sum('attempts');
            
        if ($recentFailedLogins > 3) {
            return true;
        }
        
        // Check for recent suspicious security events
        $suspiciousEvents = SecurityEvent::where('user_id', $user->id)
            ->where('event_type', SecurityEvent::TYPE_SUSPICIOUS_ACTIVITY)
            ->where('occurred_at', '>=', now()->subHours(24))
            ->count();
            
        return $suspiciousEvents > 0;
    }
    
    /**
     * Generate a secret key for two-factor authentication
     * Fallback implementation when Google2FA is not available
     */
    protected function generateSecretKey(): string
    {
        // Generate a 16-character base32 secret
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < 16; $i++) {
            $secret .= $base32chars[random_int(0, 31)];
        }
        
        return $secret;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class FormValidationService
{
    /**
     * Enhanced form validation with progressive enhancement
     */
    public function validateWithProgressiveEnhancement(Request $request, array $rules, array $messages = []): array
    {
        // Start with basic validation
        $validatedData = $request->validate($rules, $messages);
        
        // Apply progressive enhancements
        $this->applySpamProtection($request);
        $this->applyRateLimiting($request);
        $this->validateUserAgent($request);
        $this->validateReferrer($request);
        $this->checkHoneypot($request);
        
        // Enhanced field validation
        $validatedData = $this->applyEnhancedValidation($validatedData, $request);
        
        return $validatedData;
    }
    
    /**
     * Apply spam protection measures
     */
    private function applySpamProtection(Request $request): void
    {
        $spamScore = $this->calculateSpamScore($request);
        
        if ($spamScore > 0.8) {
            Log::warning('High spam score detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'spam_score' => $spamScore,
                'form_data' => $request->except(['password', 'password_confirmation'])
            ]);
            
            throw ValidationException::withMessages([
                'form' => 'Your submission appears to be spam. Please try again.'
            ]);
        }
        
        if ($spamScore > 0.5) {
            // Add delay for suspicious submissions
            sleep(2);
        }
    }
    
    /**
     * Calculate spam score based on various factors
     */
    private function calculateSpamScore(Request $request): float
    {
        $score = 0.0;
        
        // Check user agent
        $userAgent = $request->userAgent();
        if (empty($userAgent) || $this->isSuspiciousUserAgent($userAgent)) {
            $score += 0.3;
        }
        
        // Check form submission speed (too fast = likely bot)
        $formStartTime = $request->input('_form_start_time');
        if ($formStartTime) {
            $submissionTime = time() - $formStartTime;
            if ($submissionTime < 3) { // Less than 3 seconds
                $score += 0.4;
            }
        }
        
        // Check for suspicious content patterns
        $formData = $request->except(['_token', '_form_start_time', 'password', 'password_confirmation']);
        foreach ($formData as $field => $value) {
            if (is_string($value)) {
                $score += $this->analyzeTextForSpam($value);
            }
        }
        
        // Check IP reputation
        $score += $this->checkIpReputation($request->ip());
        
        // Check for multiple rapid submissions from same IP
        $recentSubmissions = Cache::get("form_submissions_{$request->ip()}", 0);
        if ($recentSubmissions > 5) {
            $score += 0.3;
        }
        
        return min($score, 1.0);
    }
    
    /**
     * Check if user agent is suspicious
     */
    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python', 'java', 'perl', 'ruby', 'php', 'node'
        ];
        
        $userAgentLower = strtolower($userAgent);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Analyze text content for spam indicators
     */
    private function analyzeTextForSpam(string $text): float
    {
        $score = 0.0;
        $textLower = strtolower($text);
        
        // Check for spam keywords
        $spamKeywords = [
            'free', 'urgent', 'act now', 'limited time', 'click here',
            'guaranteed', 'make money', 'work from home', 'viagra',
            'casino', 'lottery', 'winner', 'congratulations'
        ];
        
        foreach ($spamKeywords as $keyword) {
            if (str_contains($textLower, $keyword)) {
                $score += 0.1;
            }
        }
        
        // Check for excessive punctuation
        $punctuationCount = preg_match_all('/[!?]{2,}/', $text);
        $score += $punctuationCount * 0.05;
        
        // Check for excessive caps
        $capsCount = preg_match_all('/\b[A-Z]{3,}\b/', $text);
        $score += $capsCount * 0.05;
        
        // Check for URLs
        $urlCount = preg_match_all('/https?:\/\/\S+/', $text);
        if ($urlCount > 2) {
            $score += 0.2;
        }
        
        return min($score, 0.5);
    }
    
    /**
     * Check IP reputation (simplified implementation)
     */
    private function checkIpReputation(string $ip): float
    {
        // Check if IP is in our blocklist
        $blockedIps = Cache::get('blocked_ips', []);
        if (in_array($ip, $blockedIps)) {
            return 0.5;
        }
        
        // Check for known VPN/proxy ranges (simplified)
        if ($this->isVpnOrProxy($ip)) {
            return 0.2;
        }
        
        return 0.0;
    }
    
    /**
     * Simple VPN/Proxy detection
     */
    private function isVpnOrProxy(string $ip): bool
    {
        // This is a simplified implementation
        // In production, use a proper IP reputation service
        $suspiciousRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16'
        ];
        
        foreach ($suspiciousRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }
    
    /**
     * Apply rate limiting
     */
    private function applyRateLimiting(Request $request): void
    {
        $key = 'form_submission:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 10)) { // 10 attempts per minute
            $seconds = RateLimiter::availableIn($key);
            
            throw ValidationException::withMessages([
                'form' => "Too many form submissions. Please try again in {$seconds} seconds."
            ]);
        }
        
        RateLimiter::hit($key, 60); // 1 minute window
        
        // Track submission count for spam scoring
        $submissionKey = "form_submissions_{$request->ip()}";
        $count = Cache::get($submissionKey, 0);
        Cache::put($submissionKey, $count + 1, now()->addMinutes(10));
    }
    
    /**
     * Validate user agent
     */
    private function validateUserAgent(Request $request): void
    {
        $userAgent = $request->userAgent();
        
        if (empty($userAgent)) {
            throw ValidationException::withMessages([
                'form' => 'Invalid browser information. Please use a standard web browser.'
            ]);
        }
        
        if (strlen($userAgent) < 20 || strlen($userAgent) > 1000) {
            throw ValidationException::withMessages([
                'form' => 'Invalid browser information detected.'
            ]);
        }
    }
    
    /**
     * Validate referrer
     */
    private function validateReferrer(Request $request): void
    {
        $referrer = $request->header('referer');
        $host = $request->getHost();
        
        // Allow empty referrer (direct access)
        if (empty($referrer)) {
            return;
        }
        
        // Check if referrer is from same domain or allowed domains
        $referrerHost = parse_url($referrer, PHP_URL_HOST);
        $allowedHosts = [$host, 'www.' . $host];
        
        if (!in_array($referrerHost, $allowedHosts)) {
            // Log suspicious referrer but don't block (could be legitimate)
            Log::info('Form submission from external referrer', [
                'referrer' => $referrer,
                'ip' => $request->ip()
            ]);
        }
    }
    
    /**
     * Check honeypot field
     */
    private function checkHoneypot(Request $request): void
    {
        // Check for honeypot field (should be empty)
        $honeypot = $request->input('website'); // Common honeypot field name
        
        if (!empty($honeypot)) {
            Log::warning('Honeypot field filled', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'honeypot_value' => $honeypot
            ]);
            
            throw ValidationException::withMessages([
                'form' => 'Invalid form submission detected.'
            ]);
        }
    }
    
    /**
     * Apply enhanced field validation
     */
    private function applyEnhancedValidation(array $data, Request $request): array
    {
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                // Sanitize input
                $data[$field] = $this->sanitizeInput($value);
                
                // Check for injection attempts
                if ($this->containsInjectionAttempt($value)) {
                    Log::warning('Injection attempt detected', [
                        'field' => $field,
                        'value' => $value,
                        'ip' => $request->ip()
                    ]);
                    
                    throw ValidationException::withMessages([
                        $field => 'Invalid characters detected in ' . $field
                    ]);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitize input data
     */
    private function sanitizeInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove control characters except newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        return $input;
    }
    
    /**
     * Check for injection attempts
     */
    private function containsInjectionAttempt(string $input): bool
    {
        $patterns = [
            // SQL injection
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";].*(\bOR\b|\bAND\b)/i',
            
            // XSS
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            
            // Command injection
            '/[;&|`$]/',
            '/\.\.\//i',
            '/\.\.\\\\/i',
            
            // LDAP injection
            '/[()&|!]/',
            
            // XML injection
            '/<!ENTITY/i',
            '/<!DOCTYPE/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Preserve user input on validation failure
     */
    public function preserveUserInput(Request $request): array
    {
        $input = $request->except(['password', 'password_confirmation', '_token']);
        
        // Store in session for repopulation
        session()->flashInput($input);
        
        return $input;
    }
    
    /**
     * Get validation error state for frontend
     */
    public function getErrorState(ValidationException $exception): array
    {
        return [
            'errors' => $exception->errors(),
            'message' => $exception->getMessage(),
            'preserved_input' => session()->getOldInput()
        ];
    }
}
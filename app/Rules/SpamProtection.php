<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SpamProtection implements ValidationRule
{
    /**
     * Known spam patterns and suspicious user agents
     */
    private array $suspiciousPatterns = [
        // Bot patterns
        'bot',
        'crawler',
        'spider',
        'scraper',
        'curl',
        'wget',
        'python',
        'java',
        'perl',
        'ruby',
        'php',
        'node',
        'go-http',
        'libwww',
        'lwp',
        'mechanize',
        'scrapy',
        'beautifulsoup',
        'selenium',
        'phantomjs',
        'headless',
        'puppeteer',
        'playwright',
        'chromedriver',
        'geckodriver',
        'webdriver',
        
        // Suspicious patterns
        'test',
        'automated',
        'script',
        'tool',
        'utility',
        'monitor',
        'check',
        'scan',
        'probe',
        'fetch',
        'download',
        'harvest',
        'extract',
        'collect',
        'gather',
        'mine',
        'parse',
        'analyze',
        'inspect',
        'examine',
        'evaluate',
        'validate',
        'verify',
        'confirm',
        'submit',
        'post',
        'send',
        'transmit',
        'upload',
        'inject',
        'exploit',
        'attack',
        'hack',
        'penetrate',
        'breach',
        'compromise',
        'malware',
        'virus',
        'trojan',
        'worm',
        'backdoor',
        'rootkit',
        'keylogger',
        'spyware',
        'adware',
        'ransomware',
        
        // Additional spam indicators
        'spam',
        'scam',
        'fraud',
        'phishing',
        'fake',
        'counterfeit',
        'replica',
        'imitation',
        'duplicate',
        'clone',
        'copy',
        'mirror',
        'proxy',
        'vpn',
        'tor',
        'anonymous',
        'hidden',
        'stealth',
        'bypass',
        'evade',
        'circumvent',
        'avoid',
        'skip',
        'ignore',
        'disable',
        'block',
        'filter',
        'captcha',
        'recaptcha',
        'challenge',
        'verification',
    ];

    /**
     * Legitimate user agents that should be allowed
     */
    private array $legitimatePatterns = [
        'Mozilla',
        'Chrome',
        'Safari',
        'Firefox',
        'Edge',
        'Opera',
        'Internet Explorer',
        'MSIE',
        'Trident',
        'WebKit',
        'Gecko',
        'Blink',
        'Presto',
        'Mobile',
        'Android',
        'iPhone',
        'iPad',
        'Windows',
        'Macintosh',
        'Linux',
        'X11',
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('The request appears to be missing required browser information.');
            return;
        }

        $userAgent = strtolower($value);

        // Check for suspicious patterns
        foreach ($this->suspiciousPatterns as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                // Allow some exceptions for legitimate use cases
                if ($this->hasLegitimateContext($userAgent)) {
                    continue;
                }
                
                $fail('The request appears to be automated or suspicious. Please use a standard web browser.');
                return;
            }
        }

        // Check if user agent is too short (likely fake)
        if (strlen($value) < 20) {
            $fail('The request appears to be from an invalid browser.');
            return;
        }

        // Check if user agent is too long (likely fake or malicious)
        if (strlen($value) > 1000) {
            $fail('The request contains invalid browser information.');
            return;
        }

        // Check for legitimate browser patterns
        $hasLegitimatePattern = false;
        foreach ($this->legitimatePatterns as $pattern) {
            if (str_contains($userAgent, strtolower($pattern))) {
                $hasLegitimatePattern = true;
                break;
            }
        }

        if (!$hasLegitimatePattern) {
            $fail('The request must be made from a standard web browser.');
            return;
        }

        // Check for common fake user agent patterns
        if ($this->isFakeUserAgent($userAgent)) {
            $fail('The request appears to be from an invalid or modified browser.');
            return;
        }

        // Additional checks for suspicious behavior
        if ($this->hasSuspiciousCharacters($value)) {
            $fail('The request contains invalid characters.');
            return;
        }
    }

    /**
     * Check if the user agent has legitimate context despite containing suspicious patterns
     */
    private function hasLegitimateContext(string $userAgent): bool
    {
        // Check if it's a legitimate browser with suspicious keywords in version or other parts
        $legitimateContexts = [
            'mozilla' => true,
            'chrome' => true,
            'safari' => true,
            'firefox' => true,
            'edge' => true,
            'opera' => true,
        ];

        foreach ($legitimateContexts as $context => $allowed) {
            if (str_contains($userAgent, $context) && $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user agent appears to be fake
     */
    private function isFakeUserAgent(string $userAgent): bool
    {
        // Common fake user agent patterns
        $fakePatterns = [
            '/^mozilla\/[0-9]\.[0-9]$/i',
            '/^user-?agent$/i',
            '/^test/i',
            '/^fake/i',
            '/^dummy/i',
            '/^sample/i',
            '/^example/i',
            '/^default/i',
            '/^unknown/i',
            '/^null/i',
            '/^undefined/i',
            '/^none/i',
            '/^empty/i',
            '/^blank/i',
            '/^\s*$/i',
            '/^[a-z]{1,5}$/i', // Too short and simple
            '/^[0-9]+$/i', // Only numbers
            '/(.)\1{10,}/', // Repeated characters
        ];

        foreach ($fakePatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious characters in user agent
     */
    private function hasSuspiciousCharacters(string $userAgent): bool
    {
        // Check for SQL injection patterns
        if (preg_match('/[\'";]|union|select|insert|update|delete|drop|create|alter/i', $userAgent)) {
            return true;
        }

        // Check for XSS patterns
        if (preg_match('/<script|javascript:|vbscript:|onload=|onerror=/i', $userAgent)) {
            return true;
        }

        // Check for path traversal patterns
        if (preg_match('/\.\.\/|\.\.\\\\/', $userAgent)) {
            return true;
        }

        // Check for command injection patterns
        if (preg_match('/[;&|`$]/', $userAgent)) {
            return true;
        }

        return false;
    }
}

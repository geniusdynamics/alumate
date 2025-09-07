<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContentFilter implements ValidationRule
{
    private array $profanityWords = [
        // Basic profanity filter - in production, use a more comprehensive list
        'spam', 'scam', 'fraud', 'fake', 'phishing', 'malware', 'virus',
        'hack', 'exploit', 'attack', 'breach', 'illegal', 'stolen'
    ];

    private array $spamKeywords = [
        'free money', 'guaranteed', 'no obligation', 'risk free', 'act now',
        'limited time', 'urgent', 'congratulations', 'winner', 'lottery',
        'casino', 'viagra', 'cialis', 'weight loss', 'make money fast',
        'work from home', 'earn $$$', 'click here', 'buy now', 'call now',
        'order now', 'subscribe now', 'sign up now', 'join now', 'get rich',
        'miracle', 'amazing', 'incredible', 'unbelievable', 'fantastic'
    ];

    private array $suspiciousPatterns = [
        '/[!?]{3,}/',           // Excessive punctuation
        '/[A-Z]{10,}/',         // Excessive caps
        '/(.)\1{5,}/',          // Repeated characters
        '/https?:\/\/\S+/',     // URLs (context dependent)
        '/\b\d{4}[\s-]\d{4}[\s-]\d{4}[\s-]\d{4}\b/', // Credit card patterns
        '/\b\d{3}[\s-]\d{2}[\s-]\d{4}\b/', // SSN patterns
    ];

    private string $filterType;
    private int $maxUrls;
    private bool $allowHtml;

    public function __construct(
        string $filterType = 'moderate',
        int $maxUrls = 2,
        bool $allowHtml = false
    ) {
        $this->filterType = $filterType;
        $this->maxUrls = $maxUrls;
        $this->allowHtml = $allowHtml;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $content = is_string($value) ? $value : (string) $value;
        $lowerContent = strtolower($content);

        // Check for profanity
        if ($this->filterType !== 'none' && $this->containsProfanity($lowerContent)) {
            $fail('The :attribute contains inappropriate language.');
            return;
        }

        // Check for spam content
        if ($this->filterType === 'strict' && $this->containsSpam($lowerContent)) {
            $fail('The :attribute appears to contain spam content.');
            return;
        }

        // Check for suspicious patterns
        if ($this->hasSuspiciousPatterns($content)) {
            $fail('The :attribute contains suspicious content patterns.');
            return;
        }

        // Check URL count
        if ($this->exceedsUrlLimit($content)) {
            $fail("The :attribute contains too many URLs. Maximum allowed: {$this->maxUrls}.");
            return;
        }

        // Check for HTML if not allowed
        if (!$this->allowHtml && $this->containsHtml($content)) {
            $fail('The :attribute cannot contain HTML tags.');
            return;
        }

        // Check for potential XSS
        if ($this->containsXss($content)) {
            $fail('The :attribute contains potentially dangerous content.');
            return;
        }

        // Check for SQL injection patterns
        if ($this->containsSqlInjection($content)) {
            $fail('The :attribute contains invalid characters.');
            return;
        }

        // Check content length and quality
        if ($this->isLowQualityContent($content)) {
            $fail('The :attribute appears to be low quality or gibberish.');
            return;
        }
    }

    /**
     * Check if content contains profanity
     */
    private function containsProfanity(string $content): bool
    {
        foreach ($this->profanityWords as $word) {
            if (str_contains($content, $word)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if content contains spam keywords
     */
    private function containsSpam(string $content): bool
    {
        $spamScore = 0;
        
        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                $spamScore++;
            }
        }
        
        // Consider spam if multiple keywords found
        return $spamScore >= 2;
    }

    /**
     * Check for suspicious patterns
     */
    private function hasSuspiciousPatterns(string $content): bool
    {
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if content exceeds URL limit
     */
    private function exceedsUrlLimit(string $content): bool
    {
        $urlCount = preg_match_all('/https?:\/\/\S+/', $content);
        return $urlCount > $this->maxUrls;
    }

    /**
     * Check if content contains HTML
     */
    private function containsHtml(string $content): bool
    {
        return $content !== strip_tags($content);
    }

    /**
     * Check for XSS patterns
     */
    private function containsXss(string $content): bool
    {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/onmouseover\s*=/i',
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<form\b/i',
            '/expression\s*\(/i',
            '/url\s*\(/i',
            '/@import/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for SQL injection patterns
     */
    private function containsSqlInjection(string $content): bool
    {
        $sqlPatterns = [
            '/(\bselect\b|\bunion\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b|\bcreate\b|\balter\b)/i',
            '/(\bor\b|\band\b)\s+\d+\s*=\s*\d+/i',
            '/[\'";].*(\bor\b|\band\b).*[\'";]/i',
            '/\b(exec|execute|sp_|xp_)\b/i',
            '/(\-\-|\#|\/\*|\*\/)/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content is low quality
     */
    private function isLowQualityContent(string $content): bool
    {
        $content = trim($content);
        
        // Too short
        if (strlen($content) < 3) {
            return false; // Let other validation handle minimum length
        }

        // All same character
        if (preg_match('/^(.)\1+$/', $content)) {
            return true;
        }

        // Mostly numbers
        $numberRatio = preg_match_all('/\d/', $content) / strlen($content);
        if ($numberRatio > 0.8) {
            return true;
        }

        // Mostly special characters
        $specialCharRatio = preg_match_all('/[^a-zA-Z0-9\s]/', $content) / strlen($content);
        if ($specialCharRatio > 0.5) {
            return true;
        }

        // Random character sequences (basic heuristic)
        $words = preg_split('/\s+/', $content);
        $shortWords = array_filter($words, fn($word) => strlen($word) < 3);
        $shortWordRatio = count($shortWords) / max(count($words), 1);
        
        if ($shortWordRatio > 0.7 && count($words) > 5) {
            return true;
        }

        return false;
    }
}
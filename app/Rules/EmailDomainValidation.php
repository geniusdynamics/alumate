<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EmailDomainValidation implements ValidationRule
{
    private array $disposableEmailDomains = [
        '10minutemail.com', 'tempmail.org', 'guerrillamail.com', 'mailinator.com',
        'yopmail.com', 'temp-mail.org', 'throwaway.email', 'getnada.com',
        'maildrop.cc', 'sharklasers.com', 'grr.la', 'guerrillamailblock.com',
        'pokemail.net', 'spam4.me', 'bccto.me', 'chacuo.net', 'dispostable.com',
        'fakeinbox.com', 'spambox.us', 'tempr.email', 'trashmail.com',
        'wegwerfmail.de', 'zehnminutenmail.de', 'emailondeck.com',
        'mailcatch.com', 'mailnesia.com', 'soodonims.com', 'spamherald.com',
        'spamspot.com', 'tradedoubler.com', 'vsimcard.com', 'vubby.com',
        'wasteland.rfc822.org', 'webemail.me', 'zetmail.com', 'junk1e.com'
    ];

    private array $commonTypos = [
        'gmial.com' => 'gmail.com',
        'gmai.com' => 'gmail.com',
        'gmil.com' => 'gmail.com',
        'yahooo.com' => 'yahoo.com',
        'yaho.com' => 'yahoo.com',
        'hotmial.com' => 'hotmail.com',
        'hotmil.com' => 'hotmail.com',
        'outlok.com' => 'outlook.com',
        'outloo.com' => 'outlook.com',
        'aol.co' => 'aol.com',
        'live.co' => 'live.com'
    ];

    private bool $allowDisposable;
    private bool $checkMxRecord;
    private bool $suggestCorrections;

    public function __construct(
        bool $allowDisposable = false,
        bool $checkMxRecord = true,
        bool $suggestCorrections = true
    ) {
        $this->allowDisposable = $allowDisposable;
        $this->checkMxRecord = $checkMxRecord;
        $this->suggestCorrections = $suggestCorrections;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Extract domain from email
        $emailParts = explode('@', $value);
        if (count($emailParts) !== 2) {
            $fail('The :attribute must be a valid email address.');
            return;
        }

        $domain = strtolower(trim($emailParts[1]));

        // Check for disposable email domains
        if (!$this->allowDisposable && $this->isDisposableEmail($domain)) {
            $fail('The :attribute cannot use a disposable email address. Please use a permanent email address.');
            return;
        }

        // Check for common typos and suggest corrections
        if ($this->suggestCorrections && isset($this->commonTypos[$domain])) {
            $suggestion = $this->commonTypos[$domain];
            $fail("The :attribute domain appears to have a typo. Did you mean {$suggestion}?");
            return;
        }

        // Check MX record if enabled
        if ($this->checkMxRecord && !$this->hasMxRecord($domain)) {
            $fail('The :attribute domain does not appear to accept emails. Please check the email address.');
            return;
        }

        // Additional domain validation
        if (!$this->isValidDomain($domain)) {
            $fail('The :attribute domain is not valid.');
            return;
        }
    }

    /**
     * Check if email domain is disposable
     */
    private function isDisposableEmail(string $domain): bool
    {
        // Check against known disposable domains
        if (in_array($domain, $this->disposableEmailDomains)) {
            return true;
        }

        // Check against online disposable email API (cached)
        return Cache::remember("disposable_email_check_{$domain}", 3600, function () use ($domain) {
            try {
                $response = Http::timeout(5)->get("https://open.kickbox.com/v1/disposable/{$domain}");
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['disposable'] ?? false;
                }
            } catch (\Exception $e) {
                // If API fails, fall back to local check only
            }
            
            return false;
        });
    }

    /**
     * Check if domain has MX record
     */
    private function hasMxRecord(string $domain): bool
    {
        return Cache::remember("mx_record_check_{$domain}", 1800, function () use ($domain) {
            try {
                return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
            } catch (\Exception $e) {
                // If DNS check fails, assume valid to avoid false positives
                return true;
            }
        });
    }

    /**
     * Validate domain format
     */
    private function isValidDomain(string $domain): bool
    {
        // Basic domain format validation
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return false;
        }

        // Check for minimum domain requirements
        if (strlen($domain) < 4 || strlen($domain) > 253) {
            return false;
        }

        // Must contain at least one dot
        if (!str_contains($domain, '.')) {
            return false;
        }

        // Check for valid TLD
        $parts = explode('.', $domain);
        $tld = end($parts);
        
        if (strlen($tld) < 2 || strlen($tld) > 6) {
            return false;
        }

        // Check for suspicious patterns
        if (preg_match('/[^a-z0-9\-\.]/', $domain)) {
            return false;
        }

        return true;
    }
}
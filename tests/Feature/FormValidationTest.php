<?php

use App\Services\FormValidationService;
use App\Rules\SpamProtection;
use App\Rules\PhoneNumber;
use App\Rules\InstitutionalDomain;
use App\Rules\RateLimitValidation;
use App\Rules\ContentFilter;
use App\Rules\EmailDomainValidation;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->validationService = app(FormValidationService::class);
    RateLimiter::clear('form_submission:' . request()->ip());
    Cache::flush();
});

describe('Form Validation Service', function () {
    it('validates form with progressive enhancement', function () {
        $request = request();
        $request->merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message',
            '_form_start_time' => time() - 10 // 10 seconds ago
        ]);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string|min:10'
        ];

        $result = $this->validationService->validateWithProgressiveEnhancement($request, $rules);

        expect($result)->toHaveKey('name', 'John Doe');
        expect($result)->toHaveKey('email', 'john@example.com');
        expect($result)->toHaveKey('message', 'This is a test message');
    });

    it('detects spam submissions', function () {
        $request = request();
        $request->merge([
            'name' => 'URGENT FREE MONEY!!!',
            'email' => 'spam@example.com',
            'message' => 'FREE GUARANTEED MONEY! ACT NOW! CLICK HERE!',
            '_form_start_time' => time() - 1 // 1 second ago (too fast)
        ]);
        $request->headers->set('User-Agent', 'bot/1.0');

        $rules = [
            'name' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string'
        ];

        expect(fn() => $this->validationService->validateWithProgressiveEnhancement($request, $rules))
            ->toThrow(ValidationException::class);
    });

    it('applies rate limiting correctly', function () {
        $request = request();
        $request->merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message'
        ]);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $rules = ['name' => 'required'];

        // First 10 submissions should work
        for ($i = 0; $i < 10; $i++) {
            $this->validationService->validateWithProgressiveEnhancement($request, $rules);
        }

        // 11th submission should fail
        expect(fn() => $this->validationService->validateWithProgressiveEnhancement($request, $rules))
            ->toThrow(ValidationException::class);
    });

    it('preserves user input on validation failure', function () {
        $request = request();
        $request->merge([
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'Test message'
        ]);

        $preserved = $this->validationService->preserveUserInput($request);

        expect($preserved)->toHaveKey('name', 'John Doe');
        expect($preserved)->toHaveKey('email', 'invalid-email');
        expect($preserved)->toHaveKey('message', 'Test message');
        expect($preserved)->not->toHaveKey('_token');
    });
});

describe('Spam Protection Rule', function () {
    it('allows legitimate user agents', function () {
        $rule = new SpamProtection();
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        
        $failed = false;
        $rule->validate('user_agent', $userAgent, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('blocks suspicious user agents', function () {
        $rule = new SpamProtection();
        $userAgent = 'bot/1.0';
        
        $failed = false;
        $rule->validate('user_agent', $userAgent, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('blocks fake user agents', function () {
        $rule = new SpamProtection();
        $userAgent = 'test';
        
        $failed = false;
        $rule->validate('user_agent', $userAgent, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('blocks user agents with suspicious characters', function () {
        $rule = new SpamProtection();
        $userAgent = 'Mozilla<script>alert("xss")</script>';
        
        $failed = false;
        $rule->validate('user_agent', $userAgent, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('Phone Number Rule', function () {
    it('validates US phone numbers', function () {
        $rule = new PhoneNumber();
        $phone = '+1-555-123-4567';
        
        $failed = false;
        $rule->validate('phone', $phone, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('validates international phone numbers', function () {
        $rule = new PhoneNumber();
        $phone = '+44-20-7946-0958';
        
        $failed = false;
        $rule->validate('phone', $phone, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects invalid phone numbers', function () {
        $rule = new PhoneNumber();
        $phone = '123';
        
        $failed = false;
        $rule->validate('phone', $phone, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects fake phone numbers', function () {
        $rule = new PhoneNumber();
        $phone = '1111111111';
        
        $failed = false;
        $rule->validate('phone', $phone, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('rejects emergency numbers', function () {
        $rule = new PhoneNumber();
        $phone = '911';
        
        $failed = false;
        $rule->validate('phone', $phone, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('Institutional Domain Rule', function () {
    it('allows educational domains', function () {
        $rule = new InstitutionalDomain();
        $email = 'student@university.edu';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('allows academic domains', function () {
        $rule = new InstitutionalDomain();
        $email = 'researcher@cambridge.ac.uk';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('rejects personal email domains', function () {
        $rule = new InstitutionalDomain();
        $email = 'user@gmail.com';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('allows domains with institutional keywords', function () {
        $rule = new InstitutionalDomain();
        $email = 'staff@cityschool.org';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });
});

describe('Rate Limit Validation Rule', function () {
    it('allows submissions within rate limit', function () {
        $rule = RateLimitValidation::forFormSubmission('test');
        
        $failed = false;
        $rule->validate('form', 'test', function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('blocks submissions exceeding rate limit', function () {
        $rule = RateLimitValidation::forFormSubmission('test');
        
        // Make 5 submissions (the limit)
        for ($i = 0; $i < 5; $i++) {
            $failed = false;
            $rule->validate('form', 'test', function() use (&$failed) {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        }

        // 6th submission should fail
        $failed = false;
        $rule->validate('form', 'test', function() use (&$failed) {
            $failed = true;
        });
        expect($failed)->toBeTrue();
    });

    it('creates strict rate limits', function () {
        $rule = RateLimitValidation::strict();
        
        // First 2 submissions should work
        for ($i = 0; $i < 2; $i++) {
            $failed = false;
            $rule->validate('form', 'test', function() use (&$failed) {
                $failed = true;
            });
            expect($failed)->toBeFalse();
        }

        // 3rd submission should fail
        $failed = false;
        $rule->validate('form', 'test', function() use (&$failed) {
            $failed = true;
        });
        expect($failed)->toBeTrue();
    });
});

describe('Content Filter Rule', function () {
    it('allows clean content', function () {
        $rule = new ContentFilter();
        $content = 'This is a clean, professional message about our services.';
        
        $failed = false;
        $rule->validate('message', $content, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('blocks profanity', function () {
        $rule = new ContentFilter();
        $content = 'This message contains spam content.';
        
        $failed = false;
        $rule->validate('message', $content, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('blocks spam keywords', function () {
        $rule = new ContentFilter('strict');
        $content = 'Free money guaranteed! Act now!';
        
        $failed = false;
        $rule->validate('message', $content, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('blocks excessive URLs', function () {
        $rule = new ContentFilter('moderate', 1); // Max 1 URL
        $content = 'Check out https://example.com and https://test.com and https://demo.com';
        
        $failed = false;
        $rule->validate('message', $content, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('blocks XSS attempts', function () {
        $rule = new ContentFilter();
        $content = '<script>alert("xss")</script>';
        
        $failed = false;
        $rule->validate('message', $content, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('blocks SQL injection attempts', function () {
        $rule = new ContentFilter();
        $content = "'; DROP TABLE users; --";
        
        $failed = false;
        $rule->validate('message', $content, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('Email Domain Validation Rule', function () {
    it('allows legitimate email domains', function () {
        $rule = new EmailDomainValidation();
        $email = 'user@example.com';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('blocks disposable email domains', function () {
        $rule = new EmailDomainValidation(allowDisposable: false);
        $email = 'user@10minutemail.com';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });

    it('allows disposable emails when configured', function () {
        $rule = new EmailDomainValidation(allowDisposable: true);
        $email = 'user@10minutemail.com';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeFalse();
    });

    it('suggests corrections for typos', function () {
        $rule = new EmailDomainValidation();
        $email = 'user@gmial.com';
        
        $failed = false;
        $failMessage = '';
        $rule->validate('email', $email, function($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        expect($failed)->toBeTrue();
        expect($failMessage)->toContain('gmail.com');
    });

    it('validates domain format', function () {
        $rule = new EmailDomainValidation();
        $email = 'user@invalid..domain';
        
        $failed = false;
        $rule->validate('email', $email, function() use (&$failed) {
            $failed = true;
        });

        expect($failed)->toBeTrue();
    });
});

describe('Progressive Enhancement Features', function () {
    it('handles missing user agent gracefully', function () {
        $request = request();
        $request->merge(['name' => 'John Doe']);
        $request->headers->remove('User-Agent');

        $rules = ['name' => 'required'];

        expect(fn() => $this->validationService->validateWithProgressiveEnhancement($request, $rules))
            ->toThrow(ValidationException::class);
    });

    it('detects honeypot field usage', function () {
        $request = request();
        $request->merge([
            'name' => 'John Doe',
            'website' => 'http://spam.com' // Honeypot field
        ]);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $rules = ['name' => 'required'];

        expect(fn() => $this->validationService->validateWithProgressiveEnhancement($request, $rules))
            ->toThrow(ValidationException::class);
    });

    it('sanitizes input data', function () {
        $request = request();
        $request->merge([
            'name' => "  John\x00Doe  ", // Null byte and extra spaces
            'message' => "Test\x0Bmessage" // Control character
        ]);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $rules = [
            'name' => 'required',
            'message' => 'required'
        ];

        $result = $this->validationService->validateWithProgressiveEnhancement($request, $rules);

        expect($result['name'])->toBe('JohnDoe');
        expect($result['message'])->toBe('Testmessage');
    });

    it('tracks submission patterns', function () {
        $request = request();
        $request->merge(['name' => 'John Doe']);
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $rules = ['name' => 'required'];

        // Make several submissions
        for ($i = 0; $i < 3; $i++) {
            $this->validationService->validateWithProgressiveEnhancement($request, $rules);
        }

        // Check that submission count is tracked
        $submissionKey = "form_submissions_{$request->ip()}";
        $count = Cache::get($submissionKey, 0);
        expect($count)->toBeGreaterThan(0);
    });
});
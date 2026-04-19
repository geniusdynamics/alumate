# Security Best Practices Guide

This guide covers security best practices for developing and maintaining the Alumate Platform, including authentication, authorization, data protection, and secure coding practices.

## Table of Contents

1. [Security Principles](#security-principles)
2. [Authentication](#authentication)
3. [Authorization](#authorization)
4. [Input Validation](#input-validation)
5. [Data Protection](#data-protection)
6. [API Security](#api-security)
7. [Frontend Security](#frontend-security)
8. [Database Security](#database-security)
9. [Infrastructure Security](#infrastructure-security)
10. [Security Checklist](#security-checklist)

## Security Principles

### Defense in Depth

Implement multiple layers of security:

```
┌─────────────────────────────────────────────────────────────┐
│                    Network Security                          │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                Application Firewall                    │  │
│  │  ┌─────────────────────────────────────────────────┐  │  │
│  │  │              Rate Limiting                       │  │  │
│  │  │  ┌───────────────────────────────────────────┐  │  │  │
│  │  │  │           Authentication                   │  │  │  │
│  │  │  │  ┌─────────────────────────────────────┐  │  │  │  │
│  │  │  │  │         Authorization               │  │  │  │  │
│  │  │  │  │  ┌───────────────────────────────┐  │  │  │  │  │
│  │  │  │  │  │      Input Validation         │  │  │  │  │  │
│  │  │  │  │  │  ┌─────────────────────────┐  │  │  │  │  │  │
│  │  │  │  │  │  │    Data Encryption      │  │  │  │  │  │  │
│  │  │  │  │  │  │  ┌───────────────────┐  │  │  │  │  │  │  │
│  │  │  │  │  │  │  │   Application     │  │  │  │  │  │  │  │
│  │  │  │  │  │  │  └───────────────────┘  │  │  │  │  │  │  │
│  │  │  │  │  │  └─────────────────────────┘  │  │  │  │  │  │
│  │  │  │  │  └───────────────────────────────┘  │  │  │  │  │
│  │  │  │  └─────────────────────────────────────┘  │  │  │  │
│  │  │  └───────────────────────────────────────────┘  │  │  │
│  │  └─────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Principle of Least Privilege

```php
<?php

// ❌ Bad: Overly permissive
public function viewAny(User $user): bool
{
    return true; // Anyone can view
}

// ✅ Good: Minimum necessary permissions
public function viewAny(User $user): bool
{
    return $user->hasPermission('alumni.view');
}

// ✅ Better: Context-aware permissions
public function view(User $user, Alumni $alumni): bool
{
    // Own profile
    if ($user->id === $alumni->user_id) {
        return true;
    }
    
    // Same tenant
    if ($user->tenant_id === $alumni->tenant_id) {
        return $user->hasPermission('alumni.view');
    }
    
    // Cross-tenant requires special permission
    return $user->hasPermission('alumni.view_cross_tenant');
}
```

## Authentication

### Secure Password Handling

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Hash password with strong algorithm
     */
    public function hashPassword(string $password): string
    {
        return Hash::make($password, [
            'rounds' => 12, // Bcrypt rounds
        ]);
    }

    /**
     * Verify password with timing-safe comparison
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return Hash::check($password, $hash);
    }

    /**
     * Check if password needs rehashing
     */
    public function needsRehash(string $hash): bool
    {
        return Hash::needsRehash($hash);
    }
}
```

### Password Validation Rules

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // Check against breached passwords
            ],
        ];
    }
}
```

### Token-Based Authentication

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Log failed attempt
            $this->logFailedAttempt($request);
            
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check for account lockout
        if ($user->isLockedOut()) {
            return response()->json([
                'message' => 'Account temporarily locked. Please try again later.',
            ], 423);
        }

        // Create token with abilities
        $token = $user->createToken($request->device_name, $this->getTokenAbilities($user));

        // Log successful login
        $this->logSuccessfulLogin($user, $request);

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ]);
    }

    private function getTokenAbilities(User $user): array
    {
        $abilities = ['read'];
        
        if ($user->hasRole('admin')) {
            $abilities = array_merge($abilities, ['write', 'delete', 'admin']);
        } elseif ($user->hasRole('staff')) {
            $abilities = array_merge($abilities, ['write']);
        }
        
        return $abilities;
    }

    private function logFailedAttempt(Request $request): void
    {
        \Log::warning('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
```

### Multi-Factor Authentication

```php
<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl(string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $email,
            $secret
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }
}
```

## Authorization

### Policy-Based Authorization

```php
<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;

class AlumniPolicy
{
    /**
     * Determine if user can view any alumni
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('alumni.view');
    }

    /**
     * Determine if user can view specific alumni
     */
    public function view(User $user, Alumni $alumni): bool
    {
        // Own profile
        if ($user->id === $alumni->user_id) {
            return true;
        }

        // Check tenant isolation
        if (!$this->sameTenant($user, $alumni)) {
            return false;
        }

        return $user->hasPermission('alumni.view');
    }

    /**
     * Determine if user can update alumni
     */
    public function update(User $user, Alumni $alumni): bool
    {
        // Own profile
        if ($user->id === $alumni->user_id) {
            return true;
        }

        // Admin can update any in same tenant
        if ($this->sameTenant($user, $alumni) && $user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can delete alumni
     */
    public function delete(User $user, Alumni $alumni): bool
    {
        // Only admins can delete
        return $this->sameTenant($user, $alumni) && $user->hasRole('admin');
    }

    private function sameTenant(User $user, Alumni $alumni): bool
    {
        return $user->tenant_id === $alumni->tenant_id;
    }
}
```

### Role-Based Access Control

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}

// User model trait
trait HasRoles
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles->some(fn($role) => $role->hasPermission($permission));
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return collect($permissions)->some(fn($p) => $this->hasPermission($p));
    }
}
```

## Input Validation

### Request Validation

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAlumniRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\']+$/u'],
            'email' => ['required', 'email:rfc,dns', 'unique:alumni'],
            'graduation_year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 10)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'linkedin_url' => ['nullable', 'url', 'regex:/^https:\/\/(www\.)?linkedin\.com\//'],
            'phone' => ['nullable', 'regex:/^\+?[1-9]\d{1,14}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name can only contain letters, spaces, hyphens, and apostrophes.',
            'linkedin_url.regex' => 'Please provide a valid LinkedIn URL.',
        ];
    }

    /**
     * Sanitize input before validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strip_tags($this->name),
            'bio' => strip_tags($this->bio),
            'email' => strtolower(trim($this->email)),
        ]);
    }
}
```

### Input Sanitization Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    protected array $except = [
        'password',
        'password_confirmation',
    ];

    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value, $key) {
            if (!in_array($key, $this->except) && is_string($value)) {
                // Remove null bytes
                $value = str_replace(chr(0), '', $value);
                
                // Trim whitespace
                $value = trim($value);
                
                // Convert special characters
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
            }
        });
        
        $request->merge($input);
        
        return $next($request);
    }
}
```

### SQL Injection Prevention

```php
<?php

// ❌ Bad: Vulnerable to SQL injection
$users = DB::select("SELECT * FROM users WHERE name = '$name'");

// ✅ Good: Use parameter binding
$users = DB::select('SELECT * FROM users WHERE name = ?', [$name]);

// ✅ Good: Use Eloquent ORM
$users = User::where('name', $name)->get();

// ✅ Good: Use Query Builder
$users = DB::table('users')->where('name', $name)->get();

// ⚠️ Careful with raw expressions
$users = User::whereRaw('LOWER(name) = ?', [strtolower($name)])->get();
```

## Data Protection

### Encryption

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    /**
     * Encrypt sensitive data
     */
    public function encrypt(string $data): string
    {
        return Crypt::encryptString($data);
    }

    /**
     * Decrypt sensitive data
     */
    public function decrypt(string $encryptedData): string
    {
        return Crypt::decryptString($encryptedData);
    }

    /**
     * Hash data for comparison (one-way)
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data . config('app.key'));
    }
}

// Model attribute encryption
class User extends Model
{
    protected $casts = [
        'ssn' => 'encrypted',
        'bank_account' => 'encrypted',
    ];
}
```

### Data Masking

```php
<?php

namespace App\Services;

class DataMaskingService
{
    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        
        return $maskedName . '@' . $domain;
    }

    public function maskPhone(string $phone): string
    {
        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }

    public function maskSSN(string $ssn): string
    {
        return '***-**-' . substr($ssn, -4);
    }

    public function maskCreditCard(string $card): string
    {
        return str_repeat('*', strlen($card) - 4) . substr($card, -4);
    }
}
```

### Secure File Handling

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SecureFileService
{
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
    ];

    private int $maxFileSize = 10485760; // 10MB

    public function upload(UploadedFile $file, string $directory): string
    {
        // Validate file type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException('Invalid file type');
        }

        // Validate file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException('File too large');
        }

        // Generate secure filename
        $filename = $this->generateSecureFilename($file);

        // Store in private storage
        $path = $file->storeAs($directory, $filename, 'private');

        // Scan for malware (if service available)
        $this->scanForMalware($path);

        return $path;
    }

    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    private function scanForMalware(string $path): void
    {
        // Implement malware scanning if available
    }
}
```

## API Security

### Rate Limiting

```php
<?php

// RouteServiceProvider
protected function configureRateLimiting(): void
{
    // Standard API rate limit
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Strict rate limit for sensitive endpoints
    RateLimiter::for('sensitive', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
    });

    // Authentication rate limit
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });
}
```

### Security Headers Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // Content Security Policy
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
        ]));

        // HSTS (only in production)
        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
```

### CORS Configuration

```php
<?php

// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
    'max_age' => 86400,
    'supports_credentials' => true,
];
```

## Frontend Security

### XSS Prevention

```vue
<template>
  <!-- ✅ Good: Vue automatically escapes -->
  <p>{{ userInput }}</p>
  
  <!-- ⚠️ Careful: v-html can be dangerous -->
  <div v-html="sanitizedHtml"></div>
</template>

<script setup lang="ts">
import DOMPurify from 'dompurify'

const props = defineProps<{
  userInput: string
  rawHtml: string
}>()

// Sanitize HTML before rendering
const sanitizedHtml = computed(() => {
  return DOMPurify.sanitize(props.rawHtml, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'a', 'p', 'br'],
    ALLOWED_ATTR: ['href', 'target'],
  })
})
</script>
```

### CSRF Protection

```typescript
// Axios CSRF setup
import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Get CSRF token from meta tag
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token
}
```

### Secure Storage

```typescript
// ❌ Bad: Storing sensitive data in localStorage
localStorage.setItem('authToken', token)

// ✅ Good: Use httpOnly cookies for tokens (set by server)
// Or use sessionStorage for temporary data
sessionStorage.setItem('tempData', JSON.stringify(data))

// ✅ Good: Encrypt sensitive data if must store client-side
import CryptoJS from 'crypto-js'

const encryptData = (data: string, key: string): string => {
  return CryptoJS.AES.encrypt(data, key).toString()
}

const decryptData = (encrypted: string, key: string): string => {
  const bytes = CryptoJS.AES.decrypt(encrypted, key)
  return bytes.toString(CryptoJS.enc.Utf8)
}
```

## Database Security

### Tenant Isolation

```php
<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = auth()->user()?->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}

// Apply to model
class Alumni extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->user()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

### Audit Logging

```php
<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            self::logAudit($model, 'created');
        });

        static::updated(function ($model) {
            self::logAudit($model, 'updated', $model->getChanges());
        });

        static::deleted(function ($model) {
            self::logAudit($model, 'deleted');
        });
    }

    private static function logAudit($model, string $action, array $changes = []): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

## Infrastructure Security

### Environment Configuration

```bash
# .env.production
APP_DEBUG=false
APP_ENV=production

# Strong session configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Database SSL
DB_SSLMODE=require

# Redis authentication
REDIS_PASSWORD=your-strong-password

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

### Secrets Management

```php
<?php

// Use environment variables for secrets
$apiKey = env('EXTERNAL_API_KEY');

// Never commit secrets to version control
// Use .env.example as template without actual values

// For production, use secret management services:
// - AWS Secrets Manager
// - HashiCorp Vault
// - Azure Key Vault
```

## Security Checklist

### Authentication
- [ ] Strong password requirements enforced
- [ ] Password hashing with bcrypt/argon2
- [ ] Account lockout after failed attempts
- [ ] Multi-factor authentication available
- [ ] Secure session management
- [ ] Token expiration implemented

### Authorization
- [ ] Role-based access control
- [ ] Policy-based authorization
- [ ] Tenant isolation enforced
- [ ] Principle of least privilege

### Input Validation
- [ ] All input validated server-side
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] File upload validation
- [ ] Input sanitization

### Data Protection
- [ ] Sensitive data encrypted at rest
- [ ] Data encrypted in transit (HTTPS)
- [ ] PII properly handled
- [ ] Data masking for logs

### API Security
- [ ] Rate limiting implemented
- [ ] CORS properly configured
- [ ] Security headers set
- [ ] API versioning

### Infrastructure
- [ ] Debug mode disabled in production
- [ ] Secrets properly managed
- [ ] Audit logging enabled
- [ ] Regular security updates

---

**Related Documentation**:
- [API Development Guide](./api-development-guide.md)
- [Debugging Guide](./debugging-guide.md)
- [Troubleshooting Guide](./troubleshooting-guide.md)

**Last Updated**: February 2026

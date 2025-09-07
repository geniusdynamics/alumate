<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'name',
        'type',
        'provider',
        'configuration',
        'credentials',
        'field_mappings',
        'webhook_settings',
        'sync_settings',
        'is_active',
        'is_test_mode',
        'last_sync_at',
        'sync_status',
        'sync_error',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'credentials' => 'encrypted:array',
            'field_mappings' => 'array',
            'webhook_settings' => 'array',
            'sync_settings' => 'array',
            'is_active' => 'boolean',
            'is_test_mode' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    protected $hidden = [
        'credentials',
    ];

    // Integration types
    public const TYPE_EMAIL_MARKETING = 'email_marketing';

    public const TYPE_CALENDAR = 'calendar';

    public const TYPE_SSO = 'sso';

    public const TYPE_CRM = 'crm';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_ANALYTICS = 'analytics';

    public const TYPE_WEBHOOK = 'webhook';

    // Relationships
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'institution_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    // Helper methods
    public function getConfig(?string $key = null)
    {
        if ($key) {
            return $this->configuration[$key] ?? null;
        }

        return $this->configuration ?? [];
    }

    public function setConfig(string $key, $value): void
    {
        $config = $this->configuration ?? [];
        $config[$key] = $value;
        $this->configuration = $config;
        $this->save();
    }

    public function getCredential(string $key)
    {
        return $this->credentials[$key] ?? null;
    }

    public function setCredential(string $key, $value): void
    {
        $credentials = $this->credentials ?? [];
        $credentials[$key] = $value;
        $this->credentials = $credentials;
        $this->save();
    }

    public function getFieldMapping(string $externalField): ?string
    {
        return $this->field_mappings[$externalField] ?? null;
    }

    public function setFieldMapping(string $externalField, string $internalField): void
    {
        $mappings = $this->field_mappings ?? [];
        $mappings[$externalField] = $internalField;
        $this->field_mappings = $mappings;
        $this->save();
    }

    public function isEmailMarketing(): bool
    {
        return $this->type === self::TYPE_EMAIL_MARKETING;
    }

    public function isCalendar(): bool
    {
        return $this->type === self::TYPE_CALENDAR;
    }

    public function isSSO(): bool
    {
        return $this->type === self::TYPE_SSO;
    }

    public function isCRM(): bool
    {
        return $this->type === self::TYPE_CRM;
    }

    public function needsSync(): bool
    {
        if (! $this->last_sync_at) {
            return true;
        }

        $syncInterval = $this->sync_settings['interval_hours'] ?? 24;

        return $this->last_sync_at->diffInHours(now()) >= $syncInterval;
    }

    public function markSyncSuccessful(): void
    {
        $this->update([
            'last_sync_at' => now(),
            'sync_status' => 'success',
            'sync_error' => null,
        ]);
    }

    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error,
        ]);
    }

    public function validateConfiguration(): array
    {
        $errors = [];

        switch ($this->type) {
            case self::TYPE_EMAIL_MARKETING:
                $errors = $this->validateEmailMarketingConfig();
                break;
            case self::TYPE_CALENDAR:
                $errors = $this->validateCalendarConfig();
                break;
            case self::TYPE_SSO:
                $errors = $this->validateSSOConfig();
                break;
            case self::TYPE_CRM:
                $errors = $this->validateCRMConfig();
                break;
        }

        return $errors;
    }

    protected function validateEmailMarketingConfig(): array
    {
        $errors = [];
        $config = $this->configuration ?? [];

        if (empty($config['api_key']) && empty($this->getCredential('api_key'))) {
            $errors[] = 'API key is required for email marketing integration';
        }

        if (empty($config['list_id']) && $this->provider !== 'internal') {
            $errors[] = 'List ID is required for external email marketing providers';
        }

        return $errors;
    }

    protected function validateCalendarConfig(): array
    {
        $errors = [];
        $config = $this->configuration ?? [];

        if (empty($config['client_id']) && empty($this->getCredential('client_id'))) {
            $errors[] = 'Client ID is required for calendar integration';
        }

        if (empty($config['client_secret']) && empty($this->getCredential('client_secret'))) {
            $errors[] = 'Client secret is required for calendar integration';
        }

        return $errors;
    }

    protected function validateSSOConfig(): array
    {
        $errors = [];
        $config = $this->configuration ?? [];

        if (empty($config['entity_id'])) {
            $errors[] = 'Entity ID is required for SSO configuration';
        }

        if (empty($config['sso_url'])) {
            $errors[] = 'SSO URL is required for SSO configuration';
        }

        return $errors;
    }

    protected function validateCRMConfig(): array
    {
        $errors = [];
        $config = $this->configuration ?? [];

        if (empty($config['api_url'])) {
            $errors[] = 'API URL is required for CRM integration';
        }

        if (empty($config['api_key']) && empty($this->getCredential('api_key'))) {
            $errors[] = 'API key is required for CRM integration';
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validateConfiguration());
    }

    public function getWebhookUrl(): ?string
    {
        if (! $this->webhook_settings || ! $this->webhook_settings['enabled']) {
            return null;
        }

        return route('webhooks.integration', [
            'integration' => $this->id,
            'token' => $this->webhook_settings['token'] ?? null,
        ]);
    }

    public function generateWebhookToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $webhookSettings = $this->webhook_settings ?? [];
        $webhookSettings['token'] = $token;
        $webhookSettings['enabled'] = true;

        $this->webhook_settings = $webhookSettings;
        $this->save();

        return $token;
    }
}

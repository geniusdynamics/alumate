<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SsoConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'protocol',
        'institution_id',
        'configuration',
        'attribute_mapping',
        'role_mapping',
        'is_active',
        'auto_provision',
        'auto_update',
        'entity_id',
        'certificate',
        'private_key',
        'sso_url',
        'sls_url',
        'client_id',
        'client_secret',
        'discovery_url',
        'scopes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'attribute_mapping' => 'array',
            'role_mapping' => 'array',
            'scopes' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'auto_provision' => 'boolean',
            'auto_update' => 'boolean',
        ];
    }

    protected $hidden = [
        'private_key',
        'client_secret',
    ];

    // Relationships
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'institution_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByProtocol($query, string $protocol)
    {
        return $query->where('protocol', $protocol);
    }

    // Helper methods
    public function isSaml(): bool
    {
        return $this->protocol === 'saml2';
    }

    public function isOAuth(): bool
    {
        return in_array($this->protocol, ['oauth2', 'oidc']);
    }

    public function getProviderConfig(?string $key = null)
    {
        if ($key) {
            return $this->configuration[$key] ?? null;
        }

        return $this->configuration;
    }

    public function setProviderConfig(string $key, $value): void
    {
        $config = $this->configuration ?? [];
        $config[$key] = $value;
        $this->configuration = $config;
    }

    public function getAttributeMapping(string $externalAttribute): ?string
    {
        return $this->attribute_mapping[$externalAttribute] ?? null;
    }

    public function getRoleMapping(string $externalRole): ?string
    {
        return $this->role_mapping[$externalRole] ?? null;
    }

    public function getScopes(): array
    {
        return $this->scopes ?? [];
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->getScopes());
    }

    public function getRedirectUrl(): string
    {
        return route('auth.sso.callback', ['provider' => $this->provider, 'config' => $this->id]);
    }

    public function getLoginUrl(): string
    {
        return route('auth.sso.redirect', ['provider' => $this->provider, 'config' => $this->id]);
    }

    // Validation methods
    public function validateConfiguration(): array
    {
        $errors = [];

        if ($this->isSaml()) {
            if (empty($this->entity_id)) {
                $errors[] = 'Entity ID is required for SAML configuration';
            }
            if (empty($this->sso_url)) {
                $errors[] = 'SSO URL is required for SAML configuration';
            }
            if (empty($this->certificate)) {
                $errors[] = 'Certificate is required for SAML configuration';
            }
        }

        if ($this->isOAuth()) {
            if (empty($this->client_id)) {
                $errors[] = 'Client ID is required for OAuth configuration';
            }
            if (empty($this->client_secret)) {
                $errors[] = 'Client Secret is required for OAuth configuration';
            }
            if ($this->protocol === 'oidc' && empty($this->discovery_url)) {
                $errors[] = 'Discovery URL is required for OIDC configuration';
            }
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validateConfiguration());
    }
}

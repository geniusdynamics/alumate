<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'theme_id',
        'name',
        'slug',
        'category',
        'type',
        'description',
        'config',
        'metadata',
        'version',
        'is_active',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'version' => 'string',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'version' => '1.0.0',
        'config' => '{}',
        'metadata' => '{}',
        'usage_count' => 0,
    ];

    /**
     * The categories that components can belong to
     */
    public const CATEGORIES = [
        'hero',
        'forms',
        'testimonials',
        'statistics',
        'ctas',
        'media',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically (skip in testing)
        try {
            if (app()->bound('auth') && auth()->check() && auth()->user() && auth()->user()->tenant_id) {
                static::addGlobalScope('tenant', function ($builder) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                });
            }
        } catch (\Exception $e) {
            // Skip tenant scoping in test environment or when auth is not available
        }
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to active components only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope query by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the tenant that owns the component
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the theme applied to this component
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(ComponentTheme::class);
    }

    /**
     * Get the component instances
     * Note: ComponentInstance model will be created in a later task
     */
    public function instances(): HasMany
    {
        return $this->hasMany(ComponentInstance::class);
    }

    /**
     * Get the component versions
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ComponentVersion::class);
    }

    /**
     * Get formatted config data with defaults merged
     */
    public function getFormattedConfigAttribute(): array
    {
        $defaultConfig = $this->getDefaultConfigForCategory();

        return array_merge($defaultConfig, $this->config ?? []);
    }

    /**
     * Get the component's display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? ucfirst($this->type ?? 'Component');
    }

    /**
     * Check if component is in a specific category
     */
    public function isCategory(string $category): bool
    {
        return $this->category === $category;
    }

    /**
     * Check if component has specific configuration key
     */
    public function hasConfigKey(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Get configuration value with fallback
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Set configuration value
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
    }

    /**
     * Get default configuration for category
     */
    protected function getDefaultConfigForCategory(): array
    {
        return match ($this->category) {
            'hero' => [
                'headline' => '',
                'subheading' => '',
                'cta_text' => 'Get Started',
                'cta_url' => '#',
                'background_type' => 'image',
                'background_media' => null,
                'show_statistics' => false,
            ],
            'forms' => [
                'fields' => [],
                'submit_text' => 'Submit',
                'success_message' => 'Thank you for your submission!',
                'validation_rules' => [],
                'crm_integration' => false,
            ],
            'testimonials' => [
                'layout' => 'single',
                'show_author_photo' => true,
                'show_company' => true,
                'show_graduation_year' => false,
                'filter_by_audience' => false,
            ],
            'statistics' => [
                'animation_type' => 'counter',
                'trigger_on_scroll' => true,
                'data_source' => 'manual',
                'format_numbers' => true,
            ],
            'ctas' => [
                'style' => 'primary',
                'size' => 'medium',
                'track_conversions' => true,
                'utm_parameters' => [],
            ],
            'media' => [
                'type' => 'image',
                'lazy_load' => true,
                'responsive' => true,
                'accessibility_alt' => '',
            ],
            default => [],
        };
    }

    /**
     * Validate component configuration
     */
    public function validateConfig(): bool
    {
        $rules = $this->getValidationRulesForCategory();

        if (empty($rules)) {
            return true;
        }

        $validator = Validator::make($this->config ?? [], $rules);

        return $validator->passes();
    }

    /**
     * Get validation rules for category
     */
    protected function getValidationRulesForCategory(): array
    {
        return match ($this->category) {
            'hero' => [
                'headline' => 'string|max:255',
                'subheading' => 'string|max:500',
                'cta_text' => 'string|max:50',
                'cta_url' => 'string|url|max:255',
                'background_type' => Rule::in(['image', 'video', 'gradient']),
                'show_statistics' => 'boolean',
            ],
            'forms' => [
                'fields' => 'array',
                'fields.*.type' => Rule::in(['text', 'email', 'phone', 'select', 'checkbox', 'textarea']),
                'fields.*.label' => 'required|string|max:255',
                'fields.*.required' => 'boolean',
                'submit_text' => 'string|max:50',
                'success_message' => 'string|max:500',
                'crm_integration' => 'boolean',
            ],
            'testimonials' => [
                'layout' => Rule::in(['single', 'carousel', 'grid']),
                'show_author_photo' => 'boolean',
                'show_company' => 'boolean',
                'show_graduation_year' => 'boolean',
                'filter_by_audience' => 'boolean',
            ],
            'statistics' => [
                'animation_type' => Rule::in(['counter', 'progress', 'chart']),
                'trigger_on_scroll' => 'boolean',
                'data_source' => Rule::in(['manual', 'api']),
                'format_numbers' => 'boolean',
            ],
            'ctas' => [
                'style' => Rule::in(['primary', 'secondary', 'outline', 'text']),
                'size' => Rule::in(['small', 'medium', 'large']),
                'track_conversions' => 'boolean',
                'utm_parameters' => 'array',
            ],
            'media' => [
                'type' => Rule::in(['image', 'video', 'gallery']),
                'lazy_load' => 'boolean',
                'responsive' => 'boolean',
                'accessibility_alt' => 'string|max:255',
            ],
            default => [],
        };
    }

    /**
     * Create a new Eloquent Collection instance
     */
    public function newCollection(array $models = []): ComponentCollection
    {
        return new ComponentCollection($models);
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'type' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'config' => 'nullable|array',
            'metadata' => 'nullable|array',
            'version' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        $uniqueRule = 'unique:components,slug';
        if ($ignoreId) {
            $uniqueRule .= ','.$ignoreId;
        }

        $rules['slug'] .= '|'.$uniqueRule;

        return $rules;
    }

    /**
     * Get responsive configuration for the component
     */
    public function getResponsiveConfig(): array
    {
        return $this->getConfigValue('responsive', [
            'desktop' => [],
            'tablet' => [],
            'mobile' => []
        ]);
    }

    /**
     * Set responsive configuration for a specific device
     */
    public function setResponsiveConfig(string $device, array $config): void
    {
        $responsiveConfig = $this->getResponsiveConfig();
        $responsiveConfig[$device] = $config;
        $this->setConfigValue('responsive', $responsiveConfig);
    }

    /**
     * Get device-specific configuration
     */
    public function getDeviceConfig(string $device): array
    {
        $responsiveConfig = $this->getResponsiveConfig();
        return $responsiveConfig[$device] ?? [];
    }

    /**
     * Check if component has responsive configuration
     */
    public function hasResponsiveConfig(): bool
    {
        $responsiveConfig = $this->getResponsiveConfig();
        return !empty($responsiveConfig['desktop']) || 
               !empty($responsiveConfig['tablet']) || 
               !empty($responsiveConfig['mobile']);
    }

    /**
     * Get accessibility metadata
     */
    public function getAccessibilityMetadata(): array
    {
        return $this->getConfigValue('accessibility', [
            'semanticTag' => 'div',
            'keyboardNavigation' => ['focusable' => false],
            'motionPreferences' => ['respectReducedMotion' => true]
        ]);
    }

    /**
     * Set accessibility metadata
     */
    public function setAccessibilityMetadata(array $metadata): void
    {
        $this->setConfigValue('accessibility', $metadata);
    }

    /**
     * Get component grouping metadata
     */
    public function getGroupingMetadata(): array
    {
        return $this->getConfigValue('grouping', [
            'tags' => [$this->category],
            'relationships' => [],
            'grapeJSCategory' => $this->getGrapeJSCategoryName()
        ]);
    }

    /**
     * Set component grouping metadata
     */
    public function setGroupingMetadata(array $metadata): void
    {
        $this->setConfigValue('grouping', $metadata);
    }

    /**
     * Get Tailwind CSS class mappings
     */
    public function getTailwindMappings(): array
    {
        return $this->getConfigValue('tailwindMapping', []);
    }

    /**
     * Set Tailwind CSS class mappings
     */
    public function setTailwindMappings(array $mappings): void
    {
        $this->setConfigValue('tailwindMapping', $mappings);
    }

    /**
     * Get component constraints
     */
    public function getConstraints(): array
    {
        return $this->getConfigValue('constraints', [
            'responsive' => [],
            'accessibility' => [],
            'performance' => []
        ]);
    }

    /**
     * Set component constraints
     */
    public function setConstraints(array $constraints): void
    {
        $this->setConfigValue('constraints', $constraints);
    }

    /**
     * Get GrapeJS metadata
     */
    public function getGrapeJSMetadata(): array
    {
        return $this->getConfigValue('grapeJSMetadata', [
            'deviceManager' => [
                'desktop' => ['width' => 1200, 'height' => 800, 'widthMedia' => 'min-width: 1024px'],
                'tablet' => ['width' => 768, 'height' => 1024, 'widthMedia' => 'min-width: 768px and max-width: 1023px'],
                'mobile' => ['width' => 375, 'height' => 667, 'widthMedia' => 'max-width: 767px']
            ],
            'styleManager' => ['sectors' => []],
            'traitManager' => ['traits' => []]
        ]);
    }

    /**
     * Set GrapeJS metadata
     */
    public function setGrapeJSMetadata(array $metadata): void
    {
        $this->setConfigValue('grapeJSMetadata', $metadata);
    }

    /**
     * Check if component is mobile-optimized
     */
    public function isMobileOptimized(): bool
    {
        $mobileConfig = $this->getDeviceConfig('mobile');
        return !empty($mobileConfig) || $this->hasConfigKey('mobileOptimized');
    }

    /**
     * Check if component has accessibility features
     */
    public function hasAccessibilityFeatures(): bool
    {
        $accessibility = $this->getAccessibilityMetadata();
        return !empty($accessibility['ariaLabel']) || 
               !empty($accessibility['keyboardNavigation']) ||
               !empty($accessibility['screenReaderText']);
    }

    /**
     * Get GrapeJS category name for this component
     */
    private function getGrapeJSCategoryName(): string
    {
        return match ($this->category) {
            'hero' => 'Hero Components',
            'forms' => 'Forms & Inputs',
            'testimonials' => 'Social Proof',
            'statistics' => 'Data & Metrics',
            'ctas' => 'Call-to-Actions',
            'media' => 'Media & Content',
            default => 'Components'
        };
    }

    /**
     * Generate responsive variants for this component
     */
    public function generateResponsiveVariants(): array
    {
        $variants = [];
        $devices = ['desktop', 'tablet', 'mobile'];
        
        foreach ($devices as $device) {
            $deviceConfig = $this->getDeviceConfig($device);
            $baseConfig = $this->config ?? [];
            
            $variants[] = [
                'device' => $device,
                'config' => array_merge($baseConfig, $deviceConfig),
                'enabled' => !empty($deviceConfig),
                'inheritFromParent' => empty($deviceConfig)
            ];
        }
        
        return $variants;
    }

    /**
     * Validate responsive configuration
     */
    public function validateResponsiveConfig(): array
    {
        $errors = [];
        $warnings = [];
        
        $responsiveConfig = $this->getResponsiveConfig();
        
        // Check for mobile optimization
        if (empty($responsiveConfig['mobile'])) {
            $warnings[] = 'Component lacks mobile-specific configuration';
        }
        
        // Check for accessibility metadata
        $accessibility = $this->getAccessibilityMetadata();
        if (empty($accessibility['ariaLabel']) && empty($accessibility['ariaLabelledBy'])) {
            $warnings[] = 'Component should have accessible name (aria-label or aria-labelledby)';
        }
        
        // Check for semantic HTML usage
        if ($accessibility['semanticTag'] === 'div') {
            $warnings[] = 'Consider using semantic HTML elements instead of generic div';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get component usage statistics for analytics
     */
    public function getUsageStats(): array
    {
        return [
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at,
            'instances_count' => $this->instances()->count(),
            'is_popular' => $this->usage_count > 10,
            'recently_used' => $this->last_used_at && $this->last_used_at->isAfter(now()->subDays(7))
        ];
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }
}

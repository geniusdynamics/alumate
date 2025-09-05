<?php

namespace App\Services;

use App\Models\BrandConfig;
use App\Models\LandingPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\BrandConfigNotFoundException;
use App\Exceptions\BrandConfigValidationException;
use App\Exceptions\BrandConfigDeletionException;

/**
 * Brand Configuration Service
 *
 * Handles brand configuration management, validation, and tenant isolation
 */
class BrandConfigService
{
    private int $cacheTtl = 3600; // 1 hour

    public function __construct()
    {
        // Config should be defined in the models but override here if needed
    }

    /**
     * Create a new brand configuration
     *
     * @param array $data
     * @return BrandConfig
     * @throws BrandConfigValidationException
     */
    public function create(array $data): BrandConfig
    {
        $this->validateBrandConfig($data);

        // Ensure uniqueness within tenant
        if (isset($data['name']) && isset($data['tenant_id'])) {
            if (BrandConfig::where('tenant_id', $data['tenant_id'])
                          ->where('name', $data['name'])
                          ->exists()) {
                throw new BrandConfigValidationException('Brand configuration with this name already exists for this tenant');
            }
        }

        $brandConfig = BrandConfig::create(array_merge($data, [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        // Clear tenant-specific cache
        Cache::tags(['brand-configs'])->forget("tenant.{$data['tenant_id']}.configs");

        Log::info('Brand configuration created', [
            'brand_config_id' => $brandConfig->id,
            'name' => $brandConfig->name,
            'tenant_id' => $brandConfig->tenant_id,
        ]);

        return $brandConfig->fresh();
    }

    /**
     * Get brand configuration by ID
     *
     * @param int $id
     * @return BrandConfig
     * @throws BrandConfigNotFoundException
     */
    public function getById(int $id): BrandConfig
    {
        $brandConfig = BrandConfig::find($id);

        if (!$brandConfig) {
            throw new BrandConfigNotFoundException("Brand configuration with ID {$id} not found");
        }

        return $brandConfig;
    }

    /**
     * Get all brand configurations with filters
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $tenantId = $filters['tenant_id'] ?? 'all';
        return Cache::tags(['brand-configs'])->remember(
            "tenant.{$tenantId}.configs." . md5(serialize($filters)),
            $this->cacheTtl,
            function () use ($filters) {
                $query = BrandConfig::query();

                if (isset($filters['tenant_id'])) {
                    $query->forTenant($filters['tenant_id']);
                }

                if (isset($filters['is_active'])) {
                    $query->where('is_active', $filters['is_active']);
                }

                if (isset($filters['is_default'])) {
                    $query->where('is_default', $filters['is_default']);
                }

                if (!empty($filters['search'])) {
                    $query->where('name', 'like', '%' . $filters['search'] . '%');
                }

                if (!empty($filters['per_page'])) {
                    return $query->paginate($filters['per_page']);
                }

                return $query->get();
            }
        );
    }

    /**
     * Get default brand configuration for tenant
     *
     * @param int|null $tenantId If null, uses current tenant context
     * @return BrandConfig|null
     */
    public function getDefault(?int $tenantId = null): ?BrandConfig
    {
        $tenantId = $tenantId ?? (tenant()->id ?? 1);

        return Cache::tags(['brand-configs'])->remember(
            "tenant.{$tenantId}.default",
            $this->cacheTtl,
            fn() => BrandConfig::forTenant($tenantId)->default()->active()->first()
        );
    }

    /**
     * Update brand configuration
     *
     * @param int $id
     * @param array $data
     * @return BrandConfig
     * @throws BrandConfigNotFoundException
     * @throws BrandConfigValidationException
     */
    public function update(int $id, array $data): BrandConfig
    {
        $brandConfig = $this->getById($id);

        // Validate uniqueness if name is being changed
        if (isset($data['name']) && $data['name'] !== $brandConfig->name) {
            if (BrandConfig::where('tenant_id', $brandConfig->tenant_id)
                          ->where('name', $data['name'])
                          ->where('id', '!=', $id)
                          ->exists()) {
                throw new BrandConfigValidationException('Brand configuration with this name already exists for this tenant');
            }
        }

        // Handle default flag changes
        if (isset($data['is_default']) && $data['is_default']) {
            // Remove default from all other brand configs for this tenant
            BrandConfig::where('tenant_id', $brandConfig->tenant_id)
                       ->where('id', '!=', $id)
                       ->update(['is_default' => false]);
        }

        $brandConfig->update(array_merge($data, [
            'updated_by' => auth()->id(),
        ]));

        // Clear caches
        Cache::tags(['brand-configs'])->flush();

        return $brandConfig->fresh();
    }

    /**
     * Set brand configuration as default for tenant
     *
     * @param int $id
     * @return bool
     * @throws BrandConfigNotFoundException
     */
    public function setAsDefault(int $id): bool
    {
        $brandConfig = $this->getById($id);

        // Remove default from all brand configs for this tenant
        BrandConfig::where('tenant_id', $brandConfig->tenant_id)
                   ->update(['is_default' => false]);

        // Set this as default
        $brandConfig->update(['is_default' => true]);

        // Clear cache
        Cache::tags(['brand-configs'])->flush();

        return true;
    }

    /**
     * Duplicate brand configuration
     *
     * @param int $id
     * @param array $overrides
     * @return BrandConfig
     * @throws BrandConfigNotFoundException
     */
    public function duplicate(int $id, array $overrides = []): BrandConfig
    {
        $original = $this->getById($id);

        $duplicate = $original->replicate();

        // Apply overrides
        $duplicate->fill(array_merge([
            'name' => $original->name . ' (Copy)',
            'is_default' => false,
        ], $overrides));

        $duplicate->updated_by = auth()->id();
        $duplicate->created_by = auth()->id();
        $duplicate->save();

        // Clear cache
        Cache::tags(['brand-configs'])->forget("tenant.{$original->tenant_id}.configs");

        return $duplicate->fresh();
    }

    /**
     * Delete brand configuration
     *
     * @param int $id
     * @return bool
     * @throws BrandConfigNotFoundException
     * @throws BrandConfigDeletionException
     */
    public function delete(int $id): bool
    {
        $brandConfig = $this->getById($id);

        // Check if this is the default and if there are alternatives
        if ($brandConfig->is_default) {
            $alternatives = BrandConfig::where('tenant_id', $brandConfig->tenant_id)
                                      ->where('id', '!=', $id)
                                      ->count();

            if ($alternatives === 0) {
                throw new BrandConfigDeletionException('Cannot delete the default brand configuration without an alternative');
            }

            // Set first alternative as default
            $alternative = BrandConfig::where('tenant_id', $brandConfig->tenant_id)
                                     ->where('id', '!=', $id)
                                     ->first();
            $alternative->update(['is_default' => true]);
        }

        // Check if brand config is in use
        if ($this->isInUse($brandConfig)) {
            throw new BrandConfigDeletionException('Cannot delete brand configuration that is currently in use by landing pages');
        }

        $brandConfig->delete();

        // Clear cache
        Cache::tags(['brand-configs'])->flush();

        Log::info('Brand configuration deleted', [
            'brand_config_id' => $id,
            'name' => $brandConfig->name,
            'tenant_id' => $brandConfig->tenant_id,
        ]);

        return true;
    }

    /**
     * Get usage statistics for brand configuration
     *
     * @param int $id
     * @return array
     */
    public function getUsageStats(int $id): array
    {
        $brandConfig = $this->getById($id);

        return Cache::tags(['brand-configs'])->remember(
            "brand-config.{$id}.usage",
            $this->cacheTtl,
            function () use ($brandConfig) {
                $landingPages = LandingPage::where('brand_config', 'like', '%"brand_config_id":"'.$brandConfig->id.'"%')
                                         ->orWhere('brand_config->id', $brandConfig->id)
                                         ->get();

                return [
                    'total_landing_pages' => $landingPages->count(),
                    'published_pages' => $landingPages->where('status', 'published')->count(),
                    'draft_pages' => $landingPages->where('status', 'draft')->count(),
                    'usage_by_campaign' => $landingPages->groupBy('campaign_type')->map->count(),
                    'total_views' => $landingPages->sum('views_count'),
                    'total_conversions' => $landingPages->sum('conversion_count'),
                    'last_used' => $landingPages->max('updated_at'),
                ];
            }
        );
    }

    /**
     * Generate brand preview
     *
     * @param array $brandData
     * @return array
     */
    public function generatePreview(array $brandData): array
    {
        $preview = [
            'colors' => array_merge([
                'primary' => '#007bff',
                'secondary' => '#6c757d',
                'accent' => '#28a745',
            ], $brandData['colors'] ?? []),
            'typography' => [
                'font_family' => $brandData['font_family'] ?? 'Inter, sans-serif',
                'heading_font_family' => $brandData['heading_font_family'] ?? $brandData['font_family'] ?? 'Inter, sans-serif',
                'body_font_family' => $brandData['body_font_family'] ?? $brandData['font_family'] ?? 'Inter, sans-serif',
                'font_weights' => $brandData['font_weights'] ?? [400, 500, 600, 700],
            ],
            'logo' => [
                'url' => $brandData['logo_url'] ?? null,
                'alt' => 'Brand Logo',
            ],
            'spacing' => $brandData['spacing_settings'] ?? [
                'base_unit' => '1rem',
                'scale' => 1.5,
            ],
            'custom_css' => $brandData['custom_css'] ?? '',
        ];

        return $preview;
    }

    /**
     * Apply brand configuration to landing page content
     *
     * @param array $content
     * @param array $brandConfig
     * @return array
     */
    public function applyBrandToContent(array $content, array $brandConfig): array
    {
        // This would apply brand-specific styling to content
        // For now, simply return the content with brand metadata
        return array_merge($content, [
            'brand_applied' => true,
            'brand_config' => $brandConfig,
        ]);
    }

    /**
     * Export brand configuration
     *
     * @param int $id
     * @param array $options
     * @return array
     */
    public function export(int $id, array $options = []): array
    {
        $brandConfig = $this->getById($id);
        $usageStats = $this->getUsageStats($id);

        $export = [
            'brand_config' => $brandConfig->toArray(),
            'usage_stats' => $usageStats,
            'export timestamp' => now()->toISOString(),
            'version' => '1.0',
        ];

        if (!empty($options['include_assets'])) {
            $export['assets'] = [
                'logos' => [], // Would fetch actual logo files
                'fonts' => [], // Would reference custom fonts
            ];
        }

        return $export;
    }

    /**
     * Import brand configuration
     *
     * @param array $importData
     * @param array $options
     * @return BrandConfig
     * @throws BrandConfigValidationException
     */
    public function import(array $importData, array $options = []): BrandConfig
    {
        if (!isset($importData['brand_config'])) {
            throw new BrandConfigValidationException('Invalid import data: brand_config key missing');
        }

        $brandData = array_merge($importData['brand_config'], array_filter([
            'tenant_id' => $options['tenant_id'] ?? null,
            'is_default' => false,
            'is_active' => true,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        return $this->create($brandData);
    }

    /**
     * Check if brand configuration is currently in use
     *
     * @param BrandConfig $brandConfig
     * @return bool
     */
    private function isInUse(BrandConfig $brandConfig): bool
    {
        return LandingPage::where(function ($query) use ($brandConfig) {
            $query->where('brand_config', 'like', '%' . $brandConfig->name . '%')
                  ->orWhereJsonContains('brand_config', $brandConfig->id);
        })->exists();
    }

    /**
     * Validate brand configuration data
     *
     * @param array $data
     * @throws BrandConfigValidationException
     */
    private function validateBrandConfig(array $data): void
    {
        if (empty($data['tenant_id'])) {
            throw new BrandConfigValidationException('Tenant ID is required');
        }

        if (empty($data['name'])) {
            throw new BrandConfigValidationException('Brand configuration name is required');
        }

        // Validate color formats
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $colorField) {
            if (!empty($data[$colorField]) && !preg_match('/^#[a-fA-F0-9]{6}$/', $data[$colorField])) {
                throw new BrandConfigValidationException("Invalid {$colorField} format. Must be a valid hex color code.");
            }
        }
    }
}
<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use ZipArchive;

class ComponentExportImportService
{
    public function __construct(
        private ComponentVersionService $versionService,
        private ComponentAnalyticsService $analyticsService
    ) {}

    /**
     * Export component with all versions and metadata for GrapeJS
     */
    public function exportComponent(Component $component, array $options = []): array
    {
        $includeVersions = $options['include_versions'] ?? true;
        $includeAnalytics = $options['include_analytics'] ?? false;
        $format = $options['format'] ?? 'grapejs';

        $exportData = [
            'export_info' => [
                'version' => '1.0.0',
                'format' => $format,
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->id(),
                'component_id' => $component->id,
            ],
            'component' => $this->serializeComponent($component, $format),
        ];

        if ($includeVersions) {
            $exportData['versions'] = $this->exportVersionHistory($component);
        }

        if ($includeAnalytics) {
            $exportData['analytics'] = $this->exportAnalytics($component);
        }

        // Add GrapeJS-specific data
        if ($format === 'grapejs') {
            $exportData['grapejs'] = $this->generateGrapeJSExportData($component);
        }

        return $exportData;
    }

    /**
     * Export multiple components as a package
     */
    public function exportComponentPackage(Collection $components, array $options = []): array
    {
        $packageData = [
            'package_info' => [
                'version' => '1.0.0',
                'name' => $options['package_name'] ?? 'Component Package',
                'description' => $options['description'] ?? 'Exported component package',
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->id(),
                'component_count' => $components->count(),
            ],
            'components' => [],
        ];

        foreach ($components as $component) {
            $packageData['components'][] = $this->exportComponent($component, $options);
        }

        return $packageData;
    }

    /**
     * Import component from export data
     */
    public function importComponent(array $exportData, array $options = []): Component
    {
        $overwriteExisting = $options['overwrite_existing'] ?? false;
        $preserveIds = $options['preserve_ids'] ?? false;
        $tenantId = $options['tenant_id'] ?? auth()->user()->tenant_id;

        return DB::transaction(function () use ($exportData, $overwriteExisting, $preserveIds, $tenantId) {
            $componentData = $exportData['component'];
            
            // Check if component already exists
            $existingComponent = null;
            if ($preserveIds && isset($componentData['id'])) {
                $existingComponent = Component::find($componentData['id']);
            } else {
                $existingComponent = Component::where('slug', $componentData['slug'])
                    ->where('tenant_id', $tenantId)
                    ->first();
            }

            if ($existingComponent && !$overwriteExisting) {
                throw new \Exception("Component with slug '{$componentData['slug']}' already exists");
            }

            // Prepare component data for import
            $importData = $this->prepareComponentDataForImport($componentData, $tenantId, $preserveIds);

            // Create or update component
            if ($existingComponent && $overwriteExisting) {
                $existingComponent->update($importData);
                $component = $existingComponent;
            } else {
                $component = Component::create($importData);
            }

            // Import versions if available
            if (isset($exportData['versions']) && !empty($exportData['versions'])) {
                $this->importVersionHistory($component, $exportData['versions']);
            }

            // Import analytics if available
            if (isset($exportData['analytics']) && !empty($exportData['analytics'])) {
                $this->importAnalytics($component, $exportData['analytics']);
            }

            Log::info('Component imported successfully', [
                'component_id' => $component->id,
                'slug' => $component->slug,
                'tenant_id' => $tenantId,
            ]);

            return $component;
        });
    }

    /**
     * Create component template from GrapeJS configuration
     */
    public function createTemplateFromGrapeJS(array $grapeJSData, array $templateInfo): Component
    {
        return DB::transaction(function () use ($grapeJSData, $templateInfo) {
            // Extract component configuration from GrapeJS data
            $componentConfig = $this->extractConfigFromGrapeJS($grapeJSData);

            // Create component from template
            $component = Component::create([
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $templateInfo['name'],
                'slug' => Str::slug($templateInfo['name']),
                'category' => $templateInfo['category'] ?? 'general',
                'type' => $templateInfo['type'] ?? 'template',
                'description' => $templateInfo['description'] ?? 'Created from GrapeJS template',
                'config' => $componentConfig,
                'metadata' => [
                    'created_from' => 'grapejs_template',
                    'grapejs_data' => $grapeJSData,
                    'template_info' => $templateInfo,
                ],
                'version' => '1.0.0',
                'is_active' => true,
            ]);

            // Create initial version
            $this->versionService->createVersion($component, [
                'action' => 'created_from_template',
                'source' => 'grapejs',
            ], 'Initial version created from GrapeJS template');

            Log::info('Component template created from GrapeJS', [
                'component_id' => $component->id,
                'template_name' => $templateInfo['name'],
            ]);

            return $component;
        });
    }

    /**
     * Export component as downloadable file
     */
    public function exportToFile(Component $component, string $format = 'json'): string
    {
        $exportData = $this->exportComponent($component, ['format' => 'grapejs']);
        $filename = "component-{$component->slug}-" . now()->format('Y-m-d-H-i-s');

        switch ($format) {
            case 'json':
                $content = json_encode($exportData, JSON_PRETTY_PRINT);
                $filename .= '.json';
                break;
            
            case 'zip':
                return $this->createZipExport($component, $exportData);
            
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        $path = "exports/components/{$filename}";
        Storage::disk('local')->put($path, $content);

        return $path;
    }

    /**
     * Import component from file
     */
    public function importFromFile(string $filePath, array $options = []): Component
    {
        if (!Storage::disk('local')->exists($filePath)) {
            throw new \Exception("Import file not found: {$filePath}");
        }

        $content = Storage::disk('local')->get($filePath);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'json':
                $exportData = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format in import file');
                }
                break;
            
            case 'zip':
                $exportData = $this->extractFromZipImport($filePath);
                break;
            
            default:
                throw new \InvalidArgumentException("Unsupported import format: {$extension}");
        }

        return $this->importComponent($exportData, $options);
    }

    /**
     * Serialize component for export
     */
    private function serializeComponent(Component $component, string $format): array
    {
        $data = [
            'id' => $component->id,
            'name' => $component->name,
            'slug' => $component->slug,
            'category' => $component->category,
            'type' => $component->type,
            'description' => $component->description,
            'config' => $component->config,
            'metadata' => $component->metadata,
            'version' => $component->version,
            'is_active' => $component->is_active,
            'created_at' => $component->created_at->toISOString(),
            'updated_at' => $component->updated_at->toISOString(),
        ];

        if ($format === 'grapejs') {
            $data['grapejs_metadata'] = $component->getGrapeJSMetadata();
            $data['responsive_config'] = $component->getResponsiveConfig();
            $data['accessibility_metadata'] = $component->getAccessibilityMetadata();
            $data['tailwind_mappings'] = $component->getTailwindMappings();
        }

        return $data;
    }

    /**
     * Export version history
     */
    private function exportVersionHistory(Component $component): array
    {
        return $component->versions()
            ->with('creator')
            ->orderBy('version_number')
            ->get()
            ->map(function (ComponentVersion $version) {
                return [
                    'version_number' => $version->version_number,
                    'config' => $version->config,
                    'metadata' => $version->metadata,
                    'changes' => $version->changes,
                    'description' => $version->description,
                    'created_by' => $version->creator?->name,
                    'created_at' => $version->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Export analytics data
     */
    private function exportAnalytics(Component $component): array
    {
        return [
            'usage_stats' => $component->getUsageStats(),
            'performance_metrics' => $this->analyticsService->getComponentPerformanceMetrics($component->id),
            'conversion_data' => $this->analyticsService->getComponentConversionData($component->id),
        ];
    }

    /**
     * Generate GrapeJS-specific export data
     */
    private function generateGrapeJSExportData(Component $component): array
    {
        return [
            'block_definition' => $this->generateGrapeJSBlockDefinition($component),
            'style_manager_config' => $this->generateStyleManagerConfig($component),
            'trait_manager_config' => $this->generateTraitManagerConfig($component),
            'device_manager_config' => $component->getGrapeJSMetadata()['deviceManager'] ?? [],
        ];
    }

    /**
     * Generate GrapeJS block definition
     */
    private function generateGrapeJSBlockDefinition(Component $component): array
    {
        return [
            'id' => "component-{$component->id}",
            'label' => $component->name,
            'category' => $component->getGrapeJSMetadata()['category'] ?? 'Components',
            'content' => $this->generateGrapeJSContent($component),
            'attributes' => [
                'class' => $this->generateCSSClasses($component),
                'data-component-id' => $component->id,
                'data-component-type' => $component->type,
            ],
        ];
    }

    /**
     * Generate GrapeJS content from component config
     */
    private function generateGrapeJSContent(Component $component): string
    {
        $config = $component->config ?? [];
        
        // Generate basic HTML structure based on component type
        return match ($component->category) {
            'hero' => $this->generateHeroContent($config),
            'forms' => $this->generateFormContent($config),
            'testimonials' => $this->generateTestimonialContent($config),
            'statistics' => $this->generateStatisticsContent($config),
            'ctas' => $this->generateCTAContent($config),
            'media' => $this->generateMediaContent($config),
            default => '<div class="component-placeholder">Component Content</div>',
        };
    }

    /**
     * Generate CSS classes for component
     */
    private function generateCSSClasses(Component $component): string
    {
        $classes = [
            'component',
            "component-{$component->category}",
            "component-{$component->type}",
        ];

        // Add responsive classes
        $responsiveConfig = $component->getResponsiveConfig();
        if (!empty($responsiveConfig)) {
            $classes[] = 'responsive-component';
        }

        // Add accessibility classes
        if ($component->hasAccessibilityFeatures()) {
            $classes[] = 'accessible-component';
        }

        return implode(' ', $classes);
    }

    /**
     * Prepare component data for import
     */
    private function prepareComponentDataForImport(array $componentData, int $tenantId, bool $preserveIds): array
    {
        $importData = [
            'tenant_id' => $tenantId,
            'name' => $componentData['name'],
            'slug' => $componentData['slug'],
            'category' => $componentData['category'],
            'type' => $componentData['type'],
            'description' => $componentData['description'] ?? null,
            'config' => $componentData['config'] ?? [],
            'metadata' => $componentData['metadata'] ?? [],
            'version' => $componentData['version'] ?? '1.0.0',
            'is_active' => $componentData['is_active'] ?? true,
        ];

        if ($preserveIds && isset($componentData['id'])) {
            $importData['id'] = $componentData['id'];
        }

        return $importData;
    }

    /**
     * Import version history
     */
    private function importVersionHistory(Component $component, array $versions): void
    {
        foreach ($versions as $versionData) {
            ComponentVersion::create([
                'component_id' => $component->id,
                'version_number' => $versionData['version_number'],
                'config' => $versionData['config'] ?? [],
                'metadata' => $versionData['metadata'] ?? [],
                'changes' => $versionData['changes'] ?? [],
                'description' => $versionData['description'] ?? null,
                'created_by' => auth()->id(), // Use current user as importer
                'created_at' => isset($versionData['created_at']) 
                    ? Carbon::parse($versionData['created_at']) 
                    : now(),
            ]);
        }
    }

    /**
     * Import analytics data
     */
    private function importAnalytics(Component $component, array $analytics): void
    {
        // Import usage stats
        if (isset($analytics['usage_stats'])) {
            $component->update([
                'usage_count' => $analytics['usage_stats']['usage_count'] ?? 0,
                'last_used_at' => isset($analytics['usage_stats']['last_used_at']) 
                    ? Carbon::parse($analytics['usage_stats']['last_used_at']) 
                    : null,
            ]);
        }

        // Note: Performance metrics and conversion data would be imported
        // through the analytics service if needed
    }

    /**
     * Extract component configuration from GrapeJS data
     */
    private function extractConfigFromGrapeJS(array $grapeJSData): array
    {
        $config = [];

        // Extract basic properties
        if (isset($grapeJSData['components'])) {
            $config['components'] = $grapeJSData['components'];
        }

        if (isset($grapeJSData['style'])) {
            $config['styles'] = $grapeJSData['style'];
        }

        // Extract custom properties
        if (isset($grapeJSData['attributes'])) {
            $config['attributes'] = $grapeJSData['attributes'];
        }

        return $config;
    }

    /**
     * Create ZIP export with all component files
     */
    private function createZipExport(Component $component, array $exportData): string
    {
        $filename = "component-{$component->slug}-" . now()->format('Y-m-d-H-i-s') . '.zip';
        $zipPath = storage_path("app/exports/components/{$filename}");

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create ZIP file');
        }

        // Add main component data
        $zip->addFromString('component.json', json_encode($exportData, JSON_PRETTY_PRINT));

        // Add README
        $readme = $this->generateExportReadme($component);
        $zip->addFromString('README.md', $readme);

        $zip->close();

        return "exports/components/{$filename}";
    }

    /**
     * Generate README for export
     */
    private function generateExportReadme(Component $component): string
    {
        return "# Component Export: {$component->name}\n\n" .
               "**Category:** {$component->category}\n" .
               "**Type:** {$component->type}\n" .
               "**Version:** {$component->version}\n\n" .
               "## Description\n\n" .
               ($component->description ?? 'No description provided.') . "\n\n" .
               "## Export Information\n\n" .
               "- Exported at: " . now()->toDateTimeString() . "\n" .
               "- Exported by: " . (auth()->user()->name ?? 'Unknown') . "\n" .
               "- Component ID: {$component->id}\n\n" .
               "## Import Instructions\n\n" .
               "Use the ComponentExportImportService to import this component into your system.\n";
    }

    // Content generation methods for different component types
    private function generateHeroContent(array $config): string
    {
        $headline = $config['headline'] ?? 'Hero Headline';
        $subheading = $config['subheading'] ?? 'Hero subheading text';
        $ctaText = $config['cta_text'] ?? 'Get Started';
        
        return "<div class='hero-component'>" .
               "<h1>{$headline}</h1>" .
               "<p>{$subheading}</p>" .
               "<button class='cta-button'>{$ctaText}</button>" .
               "</div>";
    }

    private function generateFormContent(array $config): string
    {
        return "<form class='form-component'>" .
               "<div class='form-fields'></div>" .
               "<button type='submit'>" . ($config['submit_text'] ?? 'Submit') . "</button>" .
               "</form>";
    }

    private function generateTestimonialContent(array $config): string
    {
        return "<div class='testimonial-component'>" .
               "<blockquote>Testimonial content</blockquote>" .
               "<cite>Author Name</cite>" .
               "</div>";
    }

    private function generateStatisticsContent(array $config): string
    {
        return "<div class='statistics-component'>" .
               "<div class='stat-item'>" .
               "<span class='stat-number'>100</span>" .
               "<span class='stat-label'>Statistic</span>" .
               "</div>" .
               "</div>";
    }

    private function generateCTAContent(array $config): string
    {
        $text = $config['text'] ?? 'Call to Action';
        return "<button class='cta-component'>{$text}</button>";
    }

    private function generateMediaContent(array $config): string
    {
        return "<div class='media-component'>" .
               "<img src='placeholder.jpg' alt='Media content' />" .
               "</div>";
    }

    private function generateStyleManagerConfig(Component $component): array
    {
        return [
            'sectors' => [
                [
                    'name' => 'General',
                    'properties' => [
                        'display',
                        'position',
                        'top',
                        'right',
                        'left',
                        'bottom',
                    ]
                ],
                [
                    'name' => 'Layout',
                    'properties' => [
                        'width',
                        'height',
                        'max-width',
                        'min-height',
                        'margin',
                        'padding',
                    ]
                ],
                [
                    'name' => 'Typography',
                    'properties' => [
                        'font-family',
                        'font-size',
                        'font-weight',
                        'letter-spacing',
                        'color',
                        'line-height',
                        'text-align',
                        'text-decoration',
                        'text-shadow',
                    ]
                ],
                [
                    'name' => 'Decorations',
                    'properties' => [
                        'opacity',
                        'border-radius',
                        'border',
                        'box-shadow',
                        'background',
                    ]
                ],
            ]
        ];
    }

    private function generateTraitManagerConfig(Component $component): array
    {
        $baseTraits = [
            [
                'type' => 'text',
                'name' => 'id',
                'label' => 'ID',
            ],
            [
                'type' => 'text',
                'name' => 'title',
                'label' => 'Title',
            ],
        ];

        // Add component-specific traits
        $categoryTraits = match ($component->category) {
            'hero' => [
                [
                    'type' => 'text',
                    'name' => 'headline',
                    'label' => 'Headline',
                ],
                [
                    'type' => 'textarea',
                    'name' => 'subheading',
                    'label' => 'Subheading',
                ],
            ],
            'forms' => [
                [
                    'type' => 'text',
                    'name' => 'action',
                    'label' => 'Form Action',
                ],
                [
                    'type' => 'select',
                    'name' => 'method',
                    'label' => 'Method',
                    'options' => [
                        ['value' => 'get', 'name' => 'GET'],
                        ['value' => 'post', 'name' => 'POST'],
                    ],
                ],
            ],
            default => [],
        };

        return [
            'traits' => array_merge($baseTraits, $categoryTraits)
        ];
    }

    private function extractFromZipImport(string $filePath): array
    {
        // Implementation for ZIP import would go here
        // For now, throw an exception as this is a complex feature
        throw new \Exception('ZIP import not yet implemented');
    }
}
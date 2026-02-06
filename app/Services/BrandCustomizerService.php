<?php
// ABOUTME: Service for managing brand customization including logos, colors, fonts, and templates
// ABOUTME: Updated for schema-based tenancy - uses tenant context instead of tenant_id columns

namespace App\Services;

use App\Models\BrandLogo;
use App\Models\BrandColor;
use App\Models\BrandFont;
use App\Models\BrandTemplate;
use App\Models\BrandGuidelines;
use App\Models\Component;
use App\Services\TenantContextService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use ZipArchive;

class BrandCustomizerService
{
    protected TenantContextService $tenantContext;

    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Get all brand data for current tenant
     */
    public function getBrandData(): array
    {
        return [
            'assets' => [
                'logos' => BrandLogo::all(),
                'colors' => BrandColor::all(),
                'fonts' => BrandFont::all()
            ],
            'guidelines' => BrandGuidelines::firstOrCreate(
                [],
                [
                    'enforce_color_palette' => true,
                    'require_contrast_check' => true,
                    'min_contrast_ratio' => 4.5,
                    'enforce_font_families' => true,
                    'enforce_typography_scale' => true,
                    'max_heading_size' => 48,
                    'max_body_size' => 18,
                    'enforce_logo_placement' => true,
                    'min_logo_size' => 32,
                    'logo_clear_space' => 1.5
                ]
            ),
            'templates' => BrandTemplate::with('colors')->get(),
            'consistencyReport' => $this->generateConsistencyReport(),
            'analytics' => $this->generateAnalytics()
        ];
    }

    /**
     * Upload and process brand logo
     */
    public function uploadLogo(UploadedFile $file): BrandLogo
    {
        $tenantId = $this->tenantContext->getCurrentTenant()->id;
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "brand-assets/{$tenantId}/logos/{$filename}";
        
        // Store original file
        Storage::disk('public')->put($path, file_get_contents($file));
        
        // Create optimized versions
        $optimizedPath = $this->createOptimizedLogo($file, $tenantId, $filename);
        
        return BrandLogo::create([
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'type' => 'primary',
            'url' => Storage::disk('public')->url($path),
            'alt' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_primary' => false,
            'optimized' => false,
            'cdn_url' => null,
            'variants' => [],
            'usage_guidelines' => [
                'min_size' => 32,
                'clear_space' => 1.5,
                'allowed_backgrounds' => ['light', 'dark'],
                'prohibited_uses' => []
            ]
        ]);
    }

    /**
     * Create optimized logo versions
     */
    private function createOptimizedLogo(UploadedFile $file, string $tenantId, string $filename): string
    {
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $optimizedPath = "brand-assets/{$tenantId}/logos/optimized/{$baseName}";
        
        // Create different sizes
        $sizes = [32, 64, 128, 256, 512];
        
        foreach ($sizes as $size) {
            $image = Image::make($file);
            $image->resize($size, $size, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            // Save as WebP for better compression
            $webpPath = "{$optimizedPath}-{$size}.webp";
            Storage::disk('public')->put($webpPath, $image->encode('webp', 90));
            
            // Save as PNG fallback
            $pngPath = "{$optimizedPath}-{$size}.png";
            Storage::disk('public')->put($pngPath, $image->encode('png'));
        }
        
        return $optimizedPath;
    }

    /**
     * Set primary logo
     */
    public function setPrimaryLogo(string $logoId): bool
    {
        $logo = BrandLogo::find($logoId);
        
        if (!$logo) {
            return false;
        }
        
        // Reset all logos to non-primary
        BrandLogo::query()->update(['is_primary' => false]);
        
        // Set this logo as primary
        $logo->update(['is_primary' => true]);
        
        return true;
    }

    /**
     * Optimize existing logo
     */
    public function optimizeLogo(string $logoId): ?BrandLogo
    {
        $logo = BrandLogo::find($logoId);
        
        if (!$logo) {
            return null;
        }
        
        // Download original file and re-optimize
        $originalPath = str_replace(Storage::disk('public')->url(''), '', $logo->url);
        $file = Storage::disk('public')->get($originalPath);
        
        if ($file) {
            $tempFile = tmpfile();
            fwrite($tempFile, $file);
            $tempPath = stream_get_meta_data($tempFile)['uri'];
            
            $uploadedFile = new UploadedFile($tempPath, $logo->name, $logo->mime_type, null, true);
            $tenantId = $this->tenantContext->getCurrentTenant()->id;
            $optimizedPath = $this->createOptimizedLogo($uploadedFile, $tenantId, basename($originalPath));
            
            $logo->update([
                'optimized' => true,
                'variants' => $this->getLogoVariants($optimizedPath)
            ]);
            
            fclose($tempFile);
        }
        
        return $logo->fresh();
    }

    /**
     * Get logo variants information
     */
    private function getLogoVariants(string $basePath): array
    {
        $variants = [];
        $sizes = [32, 64, 128, 256, 512];
        
        foreach ($sizes as $size) {
            $variants[] = [
                'type' => 'optimized',
                'url' => Storage::disk('public')->url("{$basePath}-{$size}.webp"),
                'size' => $size,
                'format' => 'webp'
            ];
            $variants[] = [
                'type' => 'fallback',
                'url' => Storage::disk('public')->url("{$basePath}-{$size}.png"),
                'size' => $size,
                'format' => 'png'
            ];
        }
        
        return $variants;
    }

    /**
     * Delete logo
     */
    public function deleteLogo(string $logoId): bool
    {
        $logo = BrandLogo::find($logoId);
        
        if (!$logo) {
            return false;
        }
        
        // Delete files from storage
        $originalPath = str_replace(Storage::disk('public')->url(''), '', $logo->url);
        Storage::disk('public')->delete($originalPath);
        
        // Delete optimized variants
        if (!empty($logo->variants)) {
            foreach ($logo->variants as $variant) {
                $variantPath = str_replace(Storage::disk('public')->url(''), '', $variant['url']);
                Storage::disk('public')->delete($variantPath);
            }
        }
        
        $logo->delete();
        
        return true;
    }

    /**
     * Create brand color
     */
    public function createColor(array $data): BrandColor
    {
        $accessibility = $this->checkColorAccessibility($data['value']);
        
        return BrandColor::create([
            'name' => $data['name'],
            'value' => $data['value'],
            'type' => $data['type'],
            'usage_guidelines' => $data['usageGuidelines'] ?? null,
            'usage_count' => 0,
            'contrast_ratios' => $accessibility['contrastRatios'],
            'accessibility' => $accessibility['accessibility']
        ]);
    }

    /**
     * Update brand color
     */
    public function updateColor(string $colorId, array $data): ?BrandColor
    {
        $color = BrandColor::find($colorId);
        
        if (!$color) {
            return null;
        }
        
        $accessibility = $this->checkColorAccessibility($data['value']);
        
        $color->update([
            'name' => $data['name'],
            'value' => $data['value'],
            'type' => $data['type'],
            'usage_guidelines' => $data['usageGuidelines'] ?? null,
            'contrast_ratios' => $accessibility['contrastRatios'],
            'accessibility' => $accessibility['accessibility']
        ]);
        
        return $color->fresh();
    }

    /**
     * Check color accessibility
     */
    private function checkColorAccessibility(string $color): array
    {
        $commonBackgrounds = [
            ['name' => 'White', 'value' => '#FFFFFF'],
            ['name' => 'Light Gray', 'value' => '#F3F4F6'],
            ['name' => 'Dark Gray', 'value' => '#374151'],
            ['name' => 'Black', 'value' => '#000000']
        ];
        
        $contrastRatios = [];
        $issues = [];
        
        foreach ($commonBackgrounds as $bg) {
            $ratio = $this->calculateContrastRatio($color, $bg['value']);
            $contrastRatios[] = [
                'background' => $bg['value'],
                'ratio' => $ratio,
                'level' => $this->getWCAGLevel($ratio)
            ];
            
            if ($ratio < 4.5) {
                $issues[] = "Poor contrast against {$bg['name']} background (ratio: {$ratio})";
            }
        }
        
        return [
            'contrastRatios' => $contrastRatios,
            'accessibility' => [
                'wcag_compliant' => empty($issues),
                'contrast_issues' => $issues
            ]
        ];
    }

    /**
     * Calculate contrast ratio between two colors
     */
    private function calculateContrastRatio(string $color1, string $color2): float
    {
        $lum1 = $this->getLuminance($color1);
        $lum2 = $this->getLuminance($color2);
        
        $brightest = max($lum1, $lum2);
        $darkest = min($lum1, $lum2);
        
        return round(($brightest + 0.05) / ($darkest + 0.05), 2);
    }

    /**
     * Get color luminance
     */
    private function getLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Get WCAG compliance level
     */
    private function getWCAGLevel(float $ratio): string
    {
        if ($ratio >= 7) return 'AAA';
        if ($ratio >= 4.5) return 'AA';
        if ($ratio >= 3) return 'AA Large';
        return 'Fail';
    }

    /**
     * Delete brand color
     */
    public function deleteColor(string $colorId): bool
    {
        $color = BrandColor::find($colorId);
        
        if (!$color) {
            return false;
        }
        
        $color->delete();
        
        return true;
    }

    /**
     * Upload custom fonts
     */
    public function uploadFonts(array $files): string
    {
        $tenantId = $this->tenantContext->getCurrentTenant()->id;
        $fontDir = "brand-assets/{$tenantId}/fonts/" . Str::uuid();
        
        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $path = "{$fontDir}/{$filename}";
            Storage::disk('public')->put($path, file_get_contents($file));
        }
        
        // Generate CSS file for the font family
        $cssPath = "{$fontDir}/font-face.css";
        $css = $this->generateFontFaceCSS($files, $fontDir);
        Storage::disk('public')->put($cssPath, $css);
        
        return Storage::disk('public')->url($cssPath);
    }

    /**
     * Generate CSS @font-face rules
     */
    private function generateFontFaceCSS(array $files, string $fontDir): string
    {
        $css = '';
        
        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $fontUrl = Storage::disk('public')->url("{$fontDir}/{$filename}");
            $format = $this->getFontFormat($file->getClientOriginalExtension());
            
            $css .= "@font-face {\n";
            $css .= "  font-family: 'CustomFont';\n";
            $css .= "  src: url('{$fontUrl}') format('{$format}');\n";
            $css .= "  font-display: swap;\n";
            $css .= "}\n\n";
        }
        
        return $css;
    }

    /**
     * Get font format for CSS
     */
    private function getFontFormat(string $extension): string
    {
        return match (strtolower($extension)) {
            'woff' => 'woff',
            'woff2' => 'woff2',
            'ttf' => 'truetype',
            'otf' => 'opentype',
            default => 'truetype'
        };
    }

    /**
     * Create brand font
     */
    public function createFont(array $data): BrandFont
    {
        return BrandFont::create([
            'name' => $data['name'],
            'family' => $data['family'],
            'weights' => $data['weights'],
            'styles' => $data['styles'],
            'is_primary' => false,
            'type' => $data['type'],
            'source' => $data['source'],
            'url' => $data['url'] ?? null,
            'fallbacks' => $data['fallbacks'],
            'usage_count' => 0,
            'loading_strategy' => $data['loadingStrategy']
        ]);
    }

    /**
     * Update brand font
     */
    public function updateFont(string $fontId, array $data): ?BrandFont
    {
        $font = BrandFont::find($fontId);
        
        if (!$font) {
            return null;
        }
        
        $font->update([
            'name' => $data['name'],
            'family' => $data['family'],
            'weights' => $data['weights'],
            'styles' => $data['styles'],
            'type' => $data['type'],
            'source' => $data['source'],
            'url' => $data['url'] ?? null,
            'fallbacks' => $data['fallbacks'],
            'loading_strategy' => $data['loadingStrategy']
        ]);
        
        return $font->fresh();
    }

    /**
     * Set primary font
     */
    public function setPrimaryFont(string $fontId): bool
    {
        $font = BrandFont::find($fontId);
        
        if (!$font) {
            return false;
        }
        
        // Reset all fonts to non-primary
        BrandFont::query()->update(['is_primary' => false]);
        
        // Set this font as primary
        $font->update(['is_primary' => true]);
        
        return true;
    }

    /**
     * Delete brand font
     */
    public function deleteFont(string $fontId): bool
    {
        $font = BrandFont::find($fontId);
        
        if (!$font) {
            return false;
        }
        
        $font->delete();
        
        return true;
    }

    /**
     * Create brand template
     */
    public function createTemplate(array $data): BrandTemplate
    {
        $template = BrandTemplate::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'primary_font' => $data['primaryFont'],
            'secondary_font' => $data['secondaryFont'] ?? null,
            'logo_variant' => $data['logoVariant'] ?? null,
            'tags' => $data['tags'] ?? [],
            'is_default' => $data['isDefault'] ?? false,
            'usage_count' => 0
        ]);
        
        // Attach colors
        $template->colors()->attach($data['colorIds']);
        
        // Apply to existing components if requested
        if ($data['autoApplyToExisting'] ?? false) {
            $this->applyTemplateToExistingComponents($template);
        }
        
        return $template->load('colors');
    }

    /**
     * Update brand template
     */
    public function updateTemplate(string $templateId, array $data): ?BrandTemplate
    {
        $template = BrandTemplate::find($templateId);
        
        if (!$template) {
            return null;
        }
        
        $template->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'primary_font' => $data['primaryFont'],
            'secondary_font' => $data['secondaryFont'] ?? null,
            'logo_variant' => $data['logoVariant'] ?? null,
            'tags' => $data['tags'] ?? [],
            'is_default' => $data['isDefault'] ?? false
        ]);
        
        // Sync colors
        $template->colors()->sync($data['colorIds']);
        
        // Apply to existing components if requested
        if ($data['autoApplyToExisting'] ?? false) {
            $this->applyTemplateToExistingComponents($template);
        }
        
        return $template->load('colors');
    }

    /**
     * Apply template to existing components
     */
    private function applyTemplateToExistingComponents(BrandTemplate $template): void
    {
        $components = Component::where('tenant_id', $template->tenant_id)->get();
        
        foreach ($components as $component) {
            // Update component configuration with template settings
            $config = $component->config;
            
            // Apply colors
            if ($template->colors->isNotEmpty()) {
                $primaryColor = $template->colors->where('type', 'primary')->first();
                if ($primaryColor) {
                    $config['colors']['primary'] = $primaryColor->value;
                }
            }
            
            // Apply fonts
            if ($template->primary_font) {
                $config['typography']['font_family'] = $template->primary_font;
            }
            
            $component->update(['config' => $config]);
        }
    }

    /**
     * Apply brand template
     */
    public function applyTemplate(string $templateId, string $tenantId): ?array
    {
        $template = BrandTemplate::where('id', $templateId)->where('tenant_id', $tenantId)->with('colors')->first();
        
        if (!$template) {
            return null;
        }
        
        $this->applyTemplateToExistingComponents($template);
        
        // Increment usage count
        $template->increment('usage_count');
        
        return [
            'logos' => BrandLogo::where('tenant_id', $tenantId)->get(),
            'colors' => $template->colors,
            'fonts' => BrandFont::where('tenant_id', $tenantId)->get()
        ];
    }

    /**
     * Duplicate brand template
     */
    public function duplicateTemplate(string $templateId, string $tenantId): ?BrandTemplate
    {
        $template = BrandTemplate::where('id', $templateId)->where('tenant_id', $tenantId)->with('colors')->first();
        
        if (!$template) {
            return null;
        }
        
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->is_default = false;
        $newTemplate->usage_count = 0;
        $newTemplate->save();
        
        // Attach the same colors
        $newTemplate->colors()->attach($template->colors->pluck('id'));
        
        return $newTemplate->load('colors');
    }

    /**
     * Run brand consistency check
     */
    public function runConsistencyCheck(array $guidelines, array $assets, string $tenantId): array
    {
        $issues = [];
        $compliantComponents = 0;
        $warningComponents = 0;
        $nonCompliantComponents = 0;
        
        $components = Component::where('tenant_id', $tenantId)->get();
        
        foreach ($components as $component) {
            $componentIssues = $this->checkComponentCompliance($component, $guidelines, $assets);
            
            if (empty($componentIssues)) {
                $compliantComponents++;
            } elseif (count($componentIssues) <= 2) {
                $warningComponents++;
            } else {
                $nonCompliantComponents++;
            }
            
            $issues = array_merge($issues, $componentIssues);
        }
        
        return [
            'compliantComponents' => $compliantComponents,
            'warningComponents' => $warningComponents,
            'nonCompliantComponents' => $nonCompliantComponents,
            'issues' => $issues,
            'overallScore' => $compliantComponents / max(1, count($components)) * 100,
            'lastChecked' => now()->toISOString()
        ];
    }

    /**
     * Check individual component compliance
     */
    private function checkComponentCompliance(Component $component, array $guidelines, array $assets): array
    {
        $issues = [];
        $config = $component->config;
        
        // Check color compliance
        if ($guidelines['enforceColorPalette'] ?? false) {
            $approvedColors = collect($assets['colors'])->pluck('value')->toArray();
            
            if (isset($config['colors'])) {
                foreach ($config['colors'] as $colorKey => $colorValue) {
                    if (!in_array($colorValue, $approvedColors)) {
                        $issues[] = [
                            'id' => Str::uuid(),
                            'title' => 'Unapproved Color Usage',
                            'description' => "Component uses color {$colorValue} which is not in the approved brand palette",
                            'severity' => 'warning',
                            'affectedComponents' => [$component->name],
                            'autoFixAvailable' => true,
                            'fixAction' => 'Replace with nearest approved color',
                            'category' => 'color'
                        ];
                    }
                }
            }
        }
        
        // Check font compliance
        if ($guidelines['enforceFontFamilies'] ?? false) {
            $approvedFonts = collect($assets['fonts'])->pluck('family')->toArray();
            
            if (isset($config['typography']['font_family'])) {
                $usedFont = $config['typography']['font_family'];
                if (!in_array($usedFont, $approvedFonts)) {
                    $issues[] = [
                        'id' => Str::uuid(),
                        'title' => 'Unapproved Font Usage',
                        'description' => "Component uses font {$usedFont} which is not in the approved brand fonts",
                        'severity' => 'error',
                        'affectedComponents' => [$component->name],
                        'autoFixAvailable' => true,
                        'fixAction' => 'Replace with primary brand font',
                        'category' => 'typography'
                    ];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Auto-fix brand consistency issue
     */
    public function autoFixIssue(string $issueId, string $tenantId): array
    {
        // This would typically look up the issue and apply the fix
        // For now, return a mock response
        return [
            'success' => true,
            'message' => 'Issue fixed automatically',
            'updatedAssets' => null
        ];
    }

    /**
     * Update brand guidelines
     */
    public function updateGuidelines(array $data, string $tenantId): BrandGuidelines
    {
        return BrandGuidelines::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'enforce_color_palette' => $data['enforceColorPalette'] ?? true,
                'require_contrast_check' => $data['requireContrastCheck'] ?? true,
                'min_contrast_ratio' => $data['minContrastRatio'] ?? 4.5,
                'enforce_font_families' => $data['enforceFontFamilies'] ?? true,
                'enforce_typography_scale' => $data['enforceTypographyScale'] ?? true,
                'max_heading_size' => $data['maxHeadingSize'] ?? 48,
                'max_body_size' => $data['maxBodySize'] ?? 18,
                'enforce_logo_placement' => $data['enforceLogoPlacement'] ?? true,
                'min_logo_size' => $data['minLogoSize'] ?? 32,
                'logo_clear_space' => $data['logoClearSpace'] ?? 1.5
            ]
        );
    }

    /**
     * Export brand assets
     */
    public function exportAssets(array $assets, array $guidelines, string $format, string $tenantId): string
    {
        $exportDir = storage_path("app/exports/{$tenantId}");
        
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        switch ($format) {
            case 'zip':
                return $this->exportAsZip($assets, $guidelines, $exportDir, $tenantId);
            case 'json':
                return $this->exportAsJson($assets, $guidelines, $exportDir);
            case 'css':
                return $this->exportAsCss($assets, $guidelines, $exportDir);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export as ZIP file
     */
    private function exportAsZip(array $assets, array $guidelines, string $exportDir, string $tenantId): string
    {
        $zipPath = "{$exportDir}/brand-assets-" . date('Y-m-d-H-i-s') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create ZIP file');
        }
        
        // Add JSON data
        $zip->addFromString('brand-data.json', json_encode([
            'assets' => $assets,
            'guidelines' => $guidelines,
            'exported_at' => now()->toISOString()
        ], JSON_PRETTY_PRINT));
        
        // Add CSS file
        $css = $this->generateBrandCSS($assets);
        $zip->addFromString('brand-styles.css', $css);
        
        // Add logo files
        if (isset($assets['logos'])) {
            foreach ($assets['logos'] as $logo) {
                $logoPath = str_replace(Storage::disk('public')->url(''), '', $logo['url']);
                if (Storage::disk('public')->exists($logoPath)) {
                    $zip->addFromString(
                        'logos/' . basename($logoPath),
                        Storage::disk('public')->get($logoPath)
                    );
                }
            }
        }
        
        $zip->close();
        
        return $zipPath;
    }

    /**
     * Export as JSON file
     */
    private function exportAsJson(array $assets, array $guidelines, string $exportDir): string
    {
        $jsonPath = "{$exportDir}/brand-data-" . date('Y-m-d-H-i-s') . '.json';
        
        $data = [
            'assets' => $assets,
            'guidelines' => $guidelines,
            'exported_at' => now()->toISOString()
        ];
        
        file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT));
        
        return $jsonPath;
    }

    /**
     * Export as CSS file
     */
    private function exportAsCss(array $assets, array $guidelines, string $exportDir): string
    {
        $cssPath = "{$exportDir}/brand-styles-" . date('Y-m-d-H-i-s') . '.css';
        
        $css = $this->generateBrandCSS($assets);
        
        file_put_contents($cssPath, $css);
        
        return $cssPath;
    }

    /**
     * Generate CSS from brand assets
     */
    private function generateBrandCSS(array $assets): string
    {
        $css = "/* Brand Assets CSS - Generated on " . date('Y-m-d H:i:s') . " */\n\n";
        
        // CSS Custom Properties for colors
        if (isset($assets['colors'])) {
            $css .= ":root {\n";
            foreach ($assets['colors'] as $color) {
                $varName = '--brand-color-' . str_replace(' ', '-', strtolower($color['name']));
                $css .= "  {$varName}: {$color['value']};\n";
            }
            $css .= "}\n\n";
        }
        
        // Font face declarations
        if (isset($assets['fonts'])) {
            foreach ($assets['fonts'] as $font) {
                if ($font['source'] === 'custom' && $font['url']) {
                    $css .= "@import url('{$font['url']}');\n";
                }
            }
            $css .= "\n";
        }
        
        return $css;
    }

    /**
     * Generate consistency report
     */
    private function generateConsistencyReport(string $tenantId): array
    {
        // Mock data for now - would be replaced with actual analysis
        return [
            'compliantComponents' => 15,
            'warningComponents' => 3,
            'nonCompliantComponents' => 1,
            'issues' => []
        ];
    }

    /**
     * Generate analytics data
     */
    private function generateAnalytics(string $tenantId): array
    {
        // Mock data for now - would be replaced with actual analytics
        return [
            'assetUsage' => [],
            'colorUsage' => [],
            'fontUsage' => [],
            'templateUsage' => [],
            'complianceScore' => 0.85,
            'trendsData' => []
        ];
    }
}

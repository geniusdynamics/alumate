<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StylePreset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'styles',
        'tailwind_classes',
        'tenant_id',
        'created_by',
    ];

    protected $casts = [
        'styles' => 'array',
        'tailwind_classes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created this style preset
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get the formatted styles for GrapeJS
     */
    public function getFormattedStylesAttribute(): array
    {
        $styles = $this->styles ?? [];

        // Convert any camelCase properties to kebab-case for CSS
        $formattedStyles = [];
        foreach ($styles as $property => $value) {
            $cssProperty = $this->camelToKebab($property);
            $formattedStyles[$cssProperty] = $value;
        }

        return $formattedStyles;
    }

    /**
     * Get the Tailwind classes as a string
     */
    public function getTailwindClassesStringAttribute(): string
    {
        return implode(' ', $this->tailwind_classes ?? []);
    }

    /**
     * Check if this preset uses brand-compliant colors
     */
    public function isBrandCompliant(): bool
    {
        $brandColors = [
            '#3B82F6', '#1E40AF', '#10B981', '#F59E0B',
            '#6B7280', '#059669', '#D97706', '#DC2626',
        ];

        $styles = $this->styles ?? [];

        // Check common color properties
        $colorProperties = ['color', 'background-color', 'border-color'];

        foreach ($colorProperties as $property) {
            if (isset($styles[$property])) {
                $color = strtoupper($styles[$property]);
                if (! in_array($color, array_map('strtoupper', $brandColors))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the preview style for displaying in UI
     */
    public function getPreviewStyle(): array
    {
        $styles = $this->formatted_styles;

        // Add some default styles for preview
        return array_merge([
            'width' => '100%',
            'height' => '40px',
            'display' => 'flex',
            'align-items' => 'center',
            'justify-content' => 'center',
            'font-size' => '12px',
            'border-radius' => '4px',
        ], $styles);
    }

    /**
     * Convert camelCase to kebab-case
     */
    private function camelToKebab(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    /**
     * Create a duplicate of this preset
     */
    public function duplicate(?string $newName = null): self
    {
        return self::create([
            'name' => $newName ?? $this->name.' (Copy)',
            'description' => $this->description,
            'category' => $this->category,
            'styles' => $this->styles,
            'tailwind_classes' => $this->tailwind_classes,
            'tenant_id' => $this->tenant_id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Apply this preset's styles to a GrapeJS component
     */
    public function applyToComponent(array $componentData): array
    {
        // Merge the preset styles with existing component styles
        $existingStyles = $componentData['style'] ?? [];
        $presetStyles = $this->formatted_styles;

        $componentData['style'] = array_merge($existingStyles, $presetStyles);

        // Add Tailwind classes
        $existingClasses = $componentData['classes'] ?? [];
        if (is_string($existingClasses)) {
            $existingClasses = explode(' ', $existingClasses);
        }

        $newClasses = array_unique(array_merge($existingClasses, $this->tailwind_classes ?? []));
        $componentData['classes'] = implode(' ', array_filter($newClasses));

        return $componentData;
    }

    /**
     * Export this preset for sharing or backup
     */
    public function export(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'styles' => $this->styles,
            'tailwind_classes' => $this->tailwind_classes,
            'is_brand_compliant' => $this->isBrandCompliant(),
            'exported_at' => now()->toISOString(),
        ];
    }
}

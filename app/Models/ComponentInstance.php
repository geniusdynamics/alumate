<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\Rule;

class ComponentInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'page_type',
        'page_id',
        'position',
        'custom_config',
    ];

    protected $casts = [
        'custom_config' => 'array',
        'position' => 'integer',
        'page_id' => 'integer',
    ];

    protected $attributes = [
        'custom_config' => '{}',
        'position' => 0,
    ];

    /**
     * Supported page types for polymorphic relationship
     */
    public const PAGE_TYPES = [
        'landing_page',
        'template',
        'homepage',
        'about',
        'contact',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically manage positions when creating/updating
        static::creating(function ($instance) {
            if ($instance->position === null) {
                $instance->position = $instance->getNextPosition();
            }
        });

        static::updating(function ($instance) {
            if ($instance->isDirty('position')) {
                $instance->validatePositionUniqueness();
            }
        });
    }

    /**
     * Get the component that this instance belongs to
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Get the page that this component instance is placed on (polymorphic)
     */
    public function page(): MorphTo
    {
        return $this->morphTo('page', 'page_type', 'page_id');
    }

    /**
     * Scope query to specific page
     */
    public function scopeForPage($query, string $pageType, int $pageId)
    {
        return $query->where('page_type', $pageType)
            ->where('page_id', $pageId);
    }

    /**
     * Scope query ordered by position
     */
    public function scopeOrderedByPosition($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Get the next available position for this page
     */
    public function getNextPosition(): int
    {
        $maxPosition = static::where('page_type', $this->page_type)
            ->where('page_id', $this->page_id)
            ->max('position');

        return ($maxPosition ?? -1) + 1;
    }

    /**
     * Move this instance to a specific position
     */
    public function moveToPosition(int $newPosition): bool
    {
        if ($newPosition < 0) {
            return false;
        }

        $oldPosition = $this->position;

        if ($oldPosition === $newPosition) {
            return true;
        }

        // Start transaction to ensure consistency
        return \DB::transaction(function () use ($oldPosition, $newPosition) {
            // Get all instances on the same page
            $instances = static::where('page_type', $this->page_type)
                ->where('page_id', $this->page_id)
                ->where('id', '!=', $this->id)
                ->orderBy('position')
                ->get();

            // Remove current instance from the list temporarily
            $this->position = -1;
            $this->saveQuietly();

            // Shift positions to make room
            if ($newPosition > $oldPosition) {
                // Moving down - shift items up
                $instances->where('position', '>', $oldPosition)
                    ->where('position', '<=', $newPosition)
                    ->each(function ($instance) {
                        $instance->position--;
                        $instance->saveQuietly();
                    });
            } else {
                // Moving up - shift items down
                $instances->where('position', '>=', $newPosition)
                    ->where('position', '<', $oldPosition)
                    ->each(function ($instance) {
                        $instance->position++;
                        $instance->saveQuietly();
                    });
            }

            // Set the new position
            $this->position = $newPosition;

            return $this->save();
        });
    }

    /**
     * Move this instance up one position
     */
    public function moveUp(): bool
    {
        if ($this->position <= 0) {
            return false;
        }

        return $this->moveToPosition($this->position - 1);
    }

    /**
     * Move this instance down one position
     */
    public function moveDown(): bool
    {
        $maxPosition = static::where('page_type', $this->page_type)
            ->where('page_id', $this->page_id)
            ->max('position');

        if ($this->position >= $maxPosition) {
            return false;
        }

        return $this->moveToPosition($this->position + 1);
    }

    /**
     * Reorder all instances on the same page to eliminate gaps
     */
    public function reorderPageInstances(): void
    {
        $instances = static::where('page_type', $this->page_type)
            ->where('page_id', $this->page_id)
            ->orderBy('position')
            ->get();

        $instances->each(function ($instance, $index) {
            if ($instance->position !== $index) {
                $instance->position = $index;
                $instance->saveQuietly();
            }
        });
    }

    /**
     * Get merged configuration (component config + custom config)
     */
    public function getMergedConfig(): array
    {
        $componentConfig = $this->component->formatted_config ?? [];
        $customConfig = $this->custom_config ?? [];

        return array_merge($componentConfig, $customConfig);
    }

    /**
     * Get a specific configuration value with fallback
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $mergedConfig = $this->getMergedConfig();

        return data_get($mergedConfig, $key, $default);
    }

    /**
     * Set a custom configuration value
     */
    public function setCustomConfigValue(string $key, mixed $value): void
    {
        $customConfig = $this->custom_config ?? [];
        data_set($customConfig, $key, $value);
        $this->custom_config = $customConfig;
    }

    /**
     * Remove a custom configuration value
     */
    public function removeCustomConfigValue(string $key): void
    {
        $customConfig = $this->custom_config ?? [];

        if (isset($customConfig[$key])) {
            unset($customConfig[$key]);
            $this->custom_config = $customConfig;
        }
    }

    /**
     * Check if this instance has custom configuration for a key
     */
    public function hasCustomConfig(string $key): bool
    {
        return isset($this->custom_config[$key]);
    }

    /**
     * Render this component instance with merged configuration
     */
    public function render(array $additionalData = []): array
    {
        $mergedConfig = $this->getMergedConfig();

        return [
            'id' => $this->id,
            'component_id' => $this->component_id,
            'component_name' => $this->component->name,
            'component_category' => $this->component->category,
            'component_type' => $this->component->type,
            'position' => $this->position,
            'config' => $mergedConfig,
            'metadata' => $this->component->metadata ?? [],
            'additional_data' => $additionalData,
        ];
    }

    /**
     * Generate preview data for this component instance
     */
    public function generatePreview(): array
    {
        $renderData = $this->render();

        // Add sample data based on component category
        $sampleData = $this->generateSampleDataForCategory($this->component->category);

        return array_merge($renderData, ['sample_data' => $sampleData]);
    }

    /**
     * Validate position uniqueness within page context
     */
    public function validatePositionUniqueness(): bool
    {
        $exists = static::where('page_type', $this->page_type)
            ->where('page_id', $this->page_id)
            ->where('position', $this->position)
            ->where('id', '!=', $this->id)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException(
                "Position {$this->position} is already taken on {$this->page_type} {$this->page_id}"
            );
        }

        return true;
    }

    /**
     * Validate custom configuration
     */
    public function validateCustomConfig(): bool
    {
        if (empty($this->custom_config)) {
            return true;
        }

        // Use the component's validateConfig method which handles the validation internally
        // Create a temporary component with merged config to validate
        $tempComponent = clone $this->component;
        $tempComponent->config = $this->getMergedConfig();

        return $tempComponent->validateConfig();
    }

    /**
     * Generate sample data based on component category
     */
    protected function generateSampleDataForCategory(string $category): array
    {
        return match ($category) {
            'hero' => [
                'headline' => 'Welcome to Our Alumni Network',
                'subheading' => 'Connect with thousands of graduates and advance your career',
                'statistics' => [
                    ['label' => 'Alumni', 'value' => 15000],
                    ['label' => 'Companies', 'value' => 2500],
                    ['label' => 'Job Placements', 'value' => 8500],
                ],
            ],
            'forms' => [
                'form_fields' => [
                    ['type' => 'text', 'label' => 'Full Name', 'required' => true],
                    ['type' => 'email', 'label' => 'Email Address', 'required' => true],
                    ['type' => 'phone', 'label' => 'Phone Number', 'required' => false],
                ],
            ],
            'testimonials' => [
                'testimonials' => [
                    [
                        'quote' => 'This platform helped me connect with amazing opportunities.',
                        'author' => 'Sarah Johnson',
                        'title' => 'Software Engineer',
                        'company' => 'Tech Corp',
                        'graduation_year' => 2018,
                    ],
                ],
            ],
            'statistics' => [
                'metrics' => [
                    ['label' => 'Success Rate', 'value' => 95, 'suffix' => '%'],
                    ['label' => 'Average Salary Increase', 'value' => 35, 'suffix' => '%'],
                    ['label' => 'Network Connections', 'value' => 1250, 'suffix' => '+'],
                ],
            ],
            'ctas' => [
                'primary_action' => 'Join Our Network',
                'secondary_action' => 'Learn More',
            ],
            'media' => [
                'images' => [
                    ['url' => '/images/sample-1.jpg', 'alt' => 'Sample image 1'],
                    ['url' => '/images/sample-2.jpg', 'alt' => 'Sample image 2'],
                ],
            ],
            default => [],
        };
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'component_id' => 'required|exists:components,id',
            'page_type' => ['required', 'string', 'max:255', Rule::in(self::PAGE_TYPES)],
            'page_id' => 'required|integer|min:1',
            'position' => 'required|integer|min:0|max:999',
            'custom_config' => 'nullable|array',
        ];
    }

    /**
     * Get unique validation rules (for position uniqueness)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        // Position uniqueness will be handled by the unique constraint in the database
        // and validated in the validatePositionUniqueness method

        return $rules;
    }
}

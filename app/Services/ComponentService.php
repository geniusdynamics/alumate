<?php
// ABOUTME: Component service for managing components with schema-based tenant context
// ABOUTME: Updated to work with schema-based tenancy instead of tenant_id columns

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentTheme;
use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ComponentService
{
    protected TenantContextService $tenantContext;

    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }
    /**
     * Create a new component with validation and tenant scoping
     */
    public function create(array $data): Component
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        // Validate the data
        $this->validateComponentData($data);

        // Validate component configuration if provided
        if (! empty($data['config'])) {
            $this->validateComponentConfig($data['config'], $data['category']);
        }

        return DB::transaction(function () use ($data) {
            $component = Component::create($data);

            // Apply default theme if no theme specified
            if (empty($data['theme_id'])) {
                $defaultTheme = ComponentTheme::where('is_default', true)
                    ->first();

                if ($defaultTheme) {
                    $component->theme_id = $defaultTheme->id;
                    $component->save();
                }
            }

            return $component->fresh();
        });
    }

    /**
     * Update an existing component with validation
     */
    public function update(Component $component, array $data): Component
    {
        // Generate new slug if name changed
        if (isset($data['name']) && $data['name'] !== $component->name && empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $component->id);
        }

        // Validate the data
        $this->validateComponentData($data, $component->id);

        // Validate component configuration if provided
        if (isset($data['config'])) {
            $category = $data['category'] ?? $component->category;
            $this->validateComponentConfig($data['config'], $category);
        }

        return DB::transaction(function () use ($component, $data) {
            $component->update($data);

            return $component->fresh();
        });
    }

    /**
     * Delete a component and its instances
     */
    public function delete(Component $component): bool
    {
        return DB::transaction(function () use ($component) {
            // Delete all component instances first
            $component->instances()->delete();

            // Delete the component
            return $component->delete();
        });
    }

    /**
     * Duplicate a component with optional modifications
     */
    public function duplicate(Component $component, array $modifications = []): Component
    {
        $data = $component->toArray();

        // Remove fields that shouldn't be duplicated
        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        // Apply modifications
        $data = array_merge($data, $modifications);

        // Generate new name and slug if not provided in modifications
        if (! isset($modifications['name'])) {
            $data['name'] = $data['name'].' (Copy)';
        }

        if (! isset($modifications['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        // Set as inactive by default for duplicates
        if (! isset($modifications['is_active'])) {
            $data['is_active'] = false;
        }

        return $this->create($data);
    }

    /**
     * Create a new version of a component
     */
    public function createVersion(Component $component, string $newVersion, array $changes = []): Component
    {
        // Validate version format
        if (! preg_match('/^\d+\.\d+\.\d+$/', $newVersion)) {
            throw new \InvalidArgumentException('Version must be in format x.y.z');
        }

        // Check if version already exists
        $existingVersion = Component::where('name', $component->name)
            ->where('version', $newVersion)
            ->first();

        if ($existingVersion) {
            throw new \InvalidArgumentException("Version {$newVersion} already exists for this component");
        }

        $modifications = array_merge($changes, [
            'version' => $newVersion,
            'slug' => $component->slug.'-v'.str_replace('.', '-', $newVersion),
            'is_active' => false, // New versions start inactive
        ]);

        return $this->duplicate($component, $modifications);
    }

    /**
     * Activate a component
     */
    public function activate(Component $component): Component
    {
        $component->update(['is_active' => true]);

        return $component->fresh();
    }

    /**
     * Deactivate a component
     */
    public function deactivate(Component $component): Component
    {
        $component->update(['is_active' => false]);

        return $component->fresh();
    }

    /**
     * Search and filter components with advanced options
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Component::query();

        // Search by name or description
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if (! empty($filters['category'])) {
            if (is_array($filters['category'])) {
                $query->whereIn('category', $filters['category']);
            } else {
                $query->byCategory($filters['category']);
            }
        }

        // Filter by type
        if (! empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $query->whereIn('type', $filters['type']);
            } else {
                $query->byType($filters['type']);
            }
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Filter by theme
        if (! empty($filters['theme_id'])) {
            $query->where('theme_id', $filters['theme_id']);
        }

        // Filter by version
        if (! empty($filters['version'])) {
            $query->where('version', $filters['version']);
        }

        // Filter by metadata
        if (! empty($filters['metadata'])) {
            foreach ($filters['metadata'] as $key => $value) {
                $query->whereJsonContains('metadata->'.$key, $value);
            }
        }

        // Filter by configuration
        if (! empty($filters['config'])) {
            foreach ($filters['config'] as $key => $value) {
                $query->whereJsonContains('config->'.$key, $value);
            }
        }

        // Date range filters
        if (! empty($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (! empty($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $allowedSortFields = ['name', 'category', 'type', 'version', 'is_active', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Include relationships
        $query->with(['theme', 'instances']);

        return $query->paginate($perPage);
    }

    /**
     * Get components by category with optional filtering
     */
    public function getByCategory(string $category, array $filters = []): Collection
    {
        $query = Component::byCategory($category);

        // Apply additional filters
        if (! empty($filters['is_active'])) {
            $query->active();
        }

        if (! empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        return $query->with(['theme', 'instances'])->get();
    }

    /**
     * Validate component data
     */
    protected function validateComponentData(array $data, ?int $ignoreId = null): void
    {
        $rules = $ignoreId ? Component::getUniqueValidationRules($ignoreId) : Component::getValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate component configuration against schema
     */
    protected function validateComponentConfig(array $config, string $category): void
    {
        // Create a temporary component to use its validation method
        $tempComponent = new Component([
            'category' => $category,
            'config' => $config,
        ]);

        if (! $tempComponent->validateConfig()) {
            // Get validation rules based on category
            $rules = $this->getValidationRulesForCategory($category);
            $validator = Validator::make($config, $rules);
            throw new ValidationException($validator);
        }
    }

    /**
     * Get validation rules for a specific category
     */
    protected function getValidationRulesForCategory(string $category): array
    {
        return match ($category) {
            'hero' => [
                'headline' => 'string|max:255',
                'subheading' => 'string|max:500',
                'cta_text' => 'string|max:50',
                'cta_url' => 'string|url|max:255',
                'background_type' => 'string|in:image,video,gradient',
                'show_statistics' => 'boolean',
            ],
            'forms' => [
                'fields' => 'array',
                'fields.*.type' => 'string|in:text,email,phone,select,checkbox,textarea',
                'fields.*.label' => 'required|string|max:255',
                'fields.*.required' => 'boolean',
                'submit_text' => 'string|max:50',
                'success_message' => 'string|max:500',
                'crm_integration' => 'boolean',
            ],
            'testimonials' => [
                'layout' => 'string|in:single,carousel,grid',
                'show_author_photo' => 'boolean',
                'show_company' => 'boolean',
                'show_graduation_year' => 'boolean',
                'filter_by_audience' => 'boolean',
            ],
            'statistics' => [
                'animation_type' => 'string|in:counter,progress,chart',
                'trigger_on_scroll' => 'boolean',
                'data_source' => 'string|in:manual,api',
                'format_numbers' => 'boolean',
            ],
            'ctas' => [
                'style' => 'string|in:primary,secondary,outline,text',
                'size' => 'string|in:small,medium,large',
                'track_conversions' => 'boolean',
                'utm_parameters' => 'array',
            ],
            'media' => [
                'type' => 'string|in:image,video,gallery',
                'lazy_load' => 'boolean',
                'responsive' => 'boolean',
                'accessibility_alt' => 'string|max:255',
            ],
            default => [],
        };
    }

    /**
     * Generate a unique slug for the component
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists
     */
    protected function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = Component::where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Generate a unique slug for a component
     */
    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate preview data for a component
     */
    public function generatePreview(Component $component, array $customConfig = []): array
    {
        // Merge component config with custom config
        $config = array_merge($component->formatted_config, $customConfig);

        // Generate sample data based on category
        $sampleData = $this->generateSampleDataForCategory($component->category);

        return [
            'id' => $component->id,
            'name' => $component->name,
            'category' => $component->category,
            'type' => $component->type,
            'config' => $config,
            'sample_data' => $sampleData,
            'theme' => $component->theme ? [
                'id' => $component->theme->id,
                'name' => $component->theme->name,
                'css_variables' => $component->theme->generateCssVariables(),
            ] : null,
            'preview_html' => $this->generatePreviewHtml($component, $config, $sampleData),
        ];
    }

    /**
     * Generate sample data for component preview based on category
     */
    protected function generateSampleDataForCategory(string $category): array
    {
        return match ($category) {
            'hero' => [
                'headline' => 'Connect with Your Alumni Network',
                'subheading' => 'Join thousands of graduates advancing their careers together',
                'cta_text' => 'Join Now',
                'background_image' => '/images/hero-sample.jpg',
                'statistics' => [
                    ['label' => 'Alumni Members', 'value' => 15000],
                    ['label' => 'Companies Hiring', 'value' => 2500],
                    ['label' => 'Success Stories', 'value' => 8500],
                ],
            ],
            'forms' => [
                'title' => 'Get Started Today',
                'description' => 'Fill out the form below to join our alumni network',
                'fields' => [
                    ['type' => 'text', 'label' => 'Full Name', 'placeholder' => 'Enter your full name', 'required' => true],
                    ['type' => 'email', 'label' => 'Email Address', 'placeholder' => 'your.email@example.com', 'required' => true],
                    ['type' => 'phone', 'label' => 'Phone Number', 'placeholder' => '(555) 123-4567', 'required' => false],
                    ['type' => 'select', 'label' => 'Graduation Year', 'options' => ['2020', '2021', '2022', '2023', '2024'], 'required' => true],
                ],
            ],
            'testimonials' => [
                'testimonials' => [
                    [
                        'quote' => 'This platform transformed my career. I connected with amazing opportunities and mentors.',
                        'author' => 'Sarah Johnson',
                        'title' => 'Senior Software Engineer',
                        'company' => 'Tech Innovations Inc.',
                        'graduation_year' => 2018,
                        'photo' => '/images/testimonial-1.jpg',
                    ],
                    [
                        'quote' => 'The networking opportunities here are unmatched. I found my dream job through alumni connections.',
                        'author' => 'Michael Chen',
                        'title' => 'Product Manager',
                        'company' => 'StartupCorp',
                        'graduation_year' => 2019,
                        'photo' => '/images/testimonial-2.jpg',
                    ],
                ],
            ],
            'statistics' => [
                'title' => 'Our Impact',
                'metrics' => [
                    ['label' => 'Job Placement Rate', 'value' => 95, 'suffix' => '%', 'description' => 'of alumni find jobs within 6 months'],
                    ['label' => 'Average Salary Increase', 'value' => 35, 'suffix' => '%', 'description' => 'after joining our network'],
                    ['label' => 'Network Connections', 'value' => 1250, 'suffix' => '+', 'description' => 'meaningful professional relationships'],
                    ['label' => 'Companies Partnered', 'value' => 500, 'suffix' => '+', 'description' => 'actively hiring our alumni'],
                ],
            ],
            'ctas' => [
                'primary' => [
                    'text' => 'Join Our Network',
                    'url' => '/register',
                    'style' => 'primary',
                ],
                'secondary' => [
                    'text' => 'Learn More',
                    'url' => '/about',
                    'style' => 'secondary',
                ],
            ],
            'media' => [
                'images' => [
                    ['url' => '/images/gallery-1.jpg', 'alt' => 'Alumni networking event', 'caption' => 'Annual Alumni Meetup 2024'],
                    ['url' => '/images/gallery-2.jpg', 'alt' => 'Career fair', 'caption' => 'Career Fair with Top Employers'],
                    ['url' => '/images/gallery-3.jpg', 'alt' => 'Graduation ceremony', 'caption' => 'Class of 2024 Graduation'],
                ],
                'videos' => [
                    ['url' => '/videos/success-stories.mp4', 'thumbnail' => '/images/video-thumb-1.jpg', 'title' => 'Alumni Success Stories'],
                ],
            ],
            default => [],
        };
    }

    /**
     * Generate preview HTML for a component
     */
    protected function generatePreviewHtml(Component $component, array $config, array $sampleData): string
    {
        $html = "<div class='component-preview' data-category='{$component->category}' data-type='{$component->type}'>";

        switch ($component->category) {
            case 'hero':
                $html .= $this->generateHeroPreviewHtml($config, $sampleData);
                break;
            case 'forms':
                $html .= $this->generateFormPreviewHtml($config, $sampleData);
                break;
            case 'testimonials':
                $html .= $this->generateTestimonialPreviewHtml($config, $sampleData);
                break;
            case 'statistics':
                $html .= $this->generateStatisticsPreviewHtml($config, $sampleData);
                break;
            case 'ctas':
                $html .= $this->generateCtaPreviewHtml($config, $sampleData);
                break;
            case 'media':
                $html .= $this->generateMediaPreviewHtml($config, $sampleData);
                break;
            default:
                $html .= "<div class='placeholder'>Component preview not available</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate hero component preview HTML
     */
    protected function generateHeroPreviewHtml(array $config, array $sampleData): string
    {
        $headline = $config['headline'] ?? $sampleData['headline'];
        $subheading = $config['subheading'] ?? $sampleData['subheading'];
        $ctaText = $config['cta_text'] ?? $sampleData['cta_text'];

        $html = "<div class='hero-preview'>";
        $html .= "<h1 class='hero-headline'>{$headline}</h1>";
        $html .= "<p class='hero-subheading'>{$subheading}</p>";
        $html .= "<button class='hero-cta'>{$ctaText}</button>";

        if ($config['show_statistics'] ?? false) {
            $html .= "<div class='hero-statistics'>";
            foreach ($sampleData['statistics'] as $stat) {
                $html .= "<div class='stat-item'>";
                $html .= "<span class='stat-value'>{$stat['value']}</span>";
                $html .= "<span class='stat-label'>{$stat['label']}</span>";
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate form component preview HTML
     */
    protected function generateFormPreviewHtml(array $config, array $sampleData): string
    {
        $html = "<div class='form-preview'>";
        $html .= "<h3>{$sampleData['title']}</h3>";
        $html .= "<p>{$sampleData['description']}</p>";
        $html .= "<form class='preview-form'>";

        $fields = $config['fields'] ?? $sampleData['fields'];
        foreach ($fields as $field) {
            $html .= "<div class='form-field'>";
            $html .= "<label>{$field['label']}".($field['required'] ? ' *' : '').'</label>';

            switch ($field['type']) {
                case 'select':
                    $html .= '<select>';
                    foreach ($field['options'] ?? [] as $option) {
                        $html .= "<option>{$option}</option>";
                    }
                    $html .= '</select>';
                    break;
                case 'textarea':
                    $html .= "<textarea placeholder='{$field['placeholder']}'></textarea>";
                    break;
                default:
                    $html .= "<input type='{$field['type']}' placeholder='{$field['placeholder']}'>";
            }

            $html .= '</div>';
        }

        $submitText = $config['submit_text'] ?? 'Submit';
        $html .= "<button type='submit'>{$submitText}</button>";
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate testimonial component preview HTML
     */
    protected function generateTestimonialPreviewHtml(array $config, array $sampleData): string
    {
        $layout = $config['layout'] ?? 'single';
        $testimonials = $sampleData['testimonials'];

        $html = "<div class='testimonial-preview layout-{$layout}'>";

        if ($layout === 'single') {
            $testimonial = $testimonials[0];
            $html .= $this->renderSingleTestimonial($testimonial, $config);
        } else {
            foreach ($testimonials as $testimonial) {
                $html .= $this->renderSingleTestimonial($testimonial, $config);
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render a single testimonial
     */
    protected function renderSingleTestimonial(array $testimonial, array $config): string
    {
        $html = "<div class='testimonial-item'>";
        $html .= "<blockquote>{$testimonial['quote']}</blockquote>";
        $html .= "<div class='testimonial-author'>";

        if ($config['show_author_photo'] ?? true) {
            $html .= "<img src='{$testimonial['photo']}' alt='{$testimonial['author']}' class='author-photo'>";
        }

        $html .= "<div class='author-info'>";
        $html .= "<span class='author-name'>{$testimonial['author']}</span>";
        $html .= "<span class='author-title'>{$testimonial['title']}</span>";

        if ($config['show_company'] ?? true) {
            $html .= "<span class='author-company'>{$testimonial['company']}</span>";
        }

        if ($config['show_graduation_year'] ?? false) {
            $html .= "<span class='graduation-year'>Class of {$testimonial['graduation_year']}</span>";
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate statistics component preview HTML
     */
    protected function generateStatisticsPreviewHtml(array $config, array $sampleData): string
    {
        $html = "<div class='statistics-preview'>";
        $html .= "<h3>{$sampleData['title']}</h3>";
        $html .= "<div class='statistics-grid'>";

        foreach ($sampleData['metrics'] as $metric) {
            $html .= "<div class='stat-item'>";
            $html .= "<span class='stat-value'>{$metric['value']}{$metric['suffix']}</span>";
            $html .= "<span class='stat-label'>{$metric['label']}</span>";
            $html .= "<span class='stat-description'>{$metric['description']}</span>";
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate CTA component preview HTML
     */
    protected function generateCtaPreviewHtml(array $config, array $sampleData): string
    {
        $style = $config['style'] ?? 'primary';
        $size = $config['size'] ?? 'medium';

        $html = "<div class='cta-preview'>";

        if (isset($sampleData['primary'])) {
            $primary = $sampleData['primary'];
            $html .= "<a href='{$primary['url']}' class='cta-button {$primary['style']} {$size}'>{$primary['text']}</a>";
        }

        if (isset($sampleData['secondary'])) {
            $secondary = $sampleData['secondary'];
            $html .= "<a href='{$secondary['url']}' class='cta-button {$secondary['style']} {$size}'>{$secondary['text']}</a>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate media component preview HTML
     */
    protected function generateMediaPreviewHtml(array $config, array $sampleData): string
    {
        $type = $config['type'] ?? 'image';

        $html = "<div class='media-preview type-{$type}'>";

        if ($type === 'gallery' && isset($sampleData['images'])) {
            $html .= "<div class='image-gallery'>";
            foreach ($sampleData['images'] as $image) {
                $html .= "<div class='gallery-item'>";
                $html .= "<img src='{$image['url']}' alt='{$image['alt']}'>";
                $html .= "<span class='image-caption'>{$image['caption']}</span>";
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        if (isset($sampleData['videos'])) {
            $html .= "<div class='video-gallery'>";
            foreach ($sampleData['videos'] as $video) {
                $html .= "<div class='video-item'>";
                $html .= "<img src='{$video['thumbnail']}' alt='{$video['title']}' class='video-thumbnail'>";
                $html .= "<span class='video-title'>{$video['title']}</span>";
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Handle errors and missing dependencies
     */
    public function handleComponentError(Component $component, \Exception $exception): array
    {
        $errorData = [
            'component_id' => $component->id,
            'component_name' => $component->name,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'timestamp' => now(),
        ];

        // Log the error
        \Log::error('Component error', $errorData);

        // Check for common issues
        $suggestions = $this->generateErrorSuggestions($component, $exception);

        return [
            'error' => $errorData,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Generate error suggestions based on the exception
     */
    protected function generateErrorSuggestions(Component $component, \Exception $exception): array
    {
        $suggestions = [];

        if ($exception instanceof ValidationException) {
            $suggestions[] = 'Check component configuration against the schema requirements';
            $suggestions[] = 'Ensure all required fields are provided';
        }

        if (str_contains($exception->getMessage(), 'theme')) {
            $suggestions[] = 'Verify that the component theme exists and is active';
            $suggestions[] = 'Check theme configuration for missing or invalid values';
        }

        if (str_contains($exception->getMessage(), 'config')) {
            $suggestions[] = 'Validate component configuration structure';
            $suggestions[] = 'Check for missing required configuration keys';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Check component dependencies and relationships';
            $suggestions[] = 'Verify tenant permissions and data access';
        }

        return $suggestions;
    }

    /**
     * Get component statistics for analytics
     */
    public function getComponentStats(): array
    {
        $stats = [
            'total_components' => Component::count(),
            'active_components' => Component::active()->count(),
            'components_by_category' => [],
            'recent_components' => Component::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'category', 'created_at']),
        ];

        // Get counts by category
        foreach (Component::CATEGORIES as $category) {
            $stats['components_by_category'][$category] = Component::byCategory($category)
                ->count();
        }

        return $stats;
    }
}

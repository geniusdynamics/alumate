<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentInstance;
use App\Models\ComponentTheme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class ComponentRenderService
{
    /**
     * Cache duration for compiled components (in minutes)
     */
    protected const CACHE_DURATION = 60;

    /**
     * Responsive breakpoints for mobile optimization
     */
    protected const BREAKPOINTS = [
        'mobile' => 768,
        'tablet' => 1024,
        'desktop' => 1200,
    ];

    /**
     * Render a component with merged configuration
     */
    public function render(Component $component, array $instanceConfig = [], array $options = []): array
    {
        // Generate cache key for performance optimization
        $cacheKey = $this->generateCacheKey($component, $instanceConfig, $options);

        if (($options['use_cache'] ?? true) && app()->bound('cache')) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        try {
            // Merge configurations (default + theme + instance customizations)
            $mergedConfig = $this->mergeConfigurations($component, $instanceConfig);

            // Generate sample data for preview
            $sampleData = $this->generateSampleData($component->category, $mergedConfig);

            // Compile component template
            $compiledTemplate = $this->compileTemplate($component, $mergedConfig, $sampleData);

            // Handle responsive breakpoints
            $responsiveConfig = $this->generateResponsiveConfig($mergedConfig);

            // Inject accessibility attributes
            $accessibilityAttributes = $this->generateAccessibilityAttributes($component, $mergedConfig);

            $result = [
                'id' => $component->id,
                'name' => $component->name,
                'category' => $component->category,
                'type' => $component->type,
                'version' => $component->version,
                'config' => $mergedConfig,
                'sample_data' => $sampleData,
                'template' => $compiledTemplate,
                'responsive_config' => $responsiveConfig,
                'accessibility' => $accessibilityAttributes,
                'css_variables' => $this->generateCssVariables($component, $mergedConfig),
                'performance_hints' => $this->generatePerformanceHints($component, $mergedConfig),
                'cache_key' => $cacheKey,
                'rendered_at' => now()->toISOString(),
            ];

            // Cache the result for performance
            if (($options['use_cache'] ?? true) && app()->bound('cache')) {
                Cache::put($cacheKey, $result, self::CACHE_DURATION);
            }

            return $result;

        } catch (\Exception $e) {
            return $this->handleRenderError($component, $e, $instanceConfig);
        }
    }

    /**
     * Render a component instance with polymorphic page association
     */
    public function renderInstance(ComponentInstance $instance, array $options = []): array
    {
        $component = $instance->component;
        $instanceConfig = $instance->custom_config ?? [];

        return $this->render($component, $instanceConfig, $options);
    }

    /**
     * Merge component configurations in priority order
     */
    protected function mergeConfigurations(Component $component, array $instanceConfig = []): array
    {
        // Start with default configuration for the category
        $defaultConfig = $this->getDefaultConfigForCategory($component->category);

        // Merge with component's base configuration
        $componentConfig = array_merge($defaultConfig, $component->config ?? []);

        // Apply theme configuration if theme exists
        if ($component->theme) {
            $themeConfig = $this->applyThemeConfiguration($component->theme, $componentConfig);
            $componentConfig = array_merge($componentConfig, $themeConfig);
        }

        // Finally, apply instance-specific customizations (highest priority)
        return array_merge($componentConfig, $instanceConfig);
    }

    /**
     * Apply theme configuration to component config
     */
    protected function applyThemeConfiguration(ComponentTheme $theme, array $baseConfig): array
    {
        $themeConfig = $theme->config ?? [];
        $appliedConfig = [];

        // Apply theme colors
        if (isset($themeConfig['colors'])) {
            $appliedConfig['theme_colors'] = $themeConfig['colors'];
        }

        // Apply theme typography
        if (isset($themeConfig['typography'])) {
            $appliedConfig['theme_typography'] = $themeConfig['typography'];
        }

        // Apply theme spacing
        if (isset($themeConfig['spacing'])) {
            $appliedConfig['theme_spacing'] = $themeConfig['spacing'];
        }

        // Apply theme-specific component overrides
        if (isset($themeConfig['component_overrides'])) {
            $componentOverrides = $themeConfig['component_overrides'];
            if (isset($componentOverrides['all'])) {
                $appliedConfig = array_merge($appliedConfig, $componentOverrides['all']);
            }
            if (isset($componentOverrides[$baseConfig['category'] ?? ''])) {
                $appliedConfig = array_merge($appliedConfig, $componentOverrides[$baseConfig['category']]);
            }
        }

        return $appliedConfig;
    }

    /**
     * Compile component template with Vue 3 and TypeScript support
     */
    protected function compileTemplate(Component $component, array $config, array $sampleData): array
    {
        $templateData = [
            'vue_template' => $this->generateVueTemplate($component, $config, $sampleData),
            'typescript_props' => $this->generateTypeScriptProps($component, $config),
            'html_preview' => $this->generateHtmlPreview($component, $config, $sampleData),
            'css_classes' => $this->generateCssClasses($component, $config),
        ];

        return $templateData;
    }

    /**
     * Generate Vue 3 template for the component
     */
    protected function generateVueTemplate(Component $component, array $config, array $sampleData): string
    {
        $templateName = "components.{$component->category}.{$component->type}";

        // Check if custom template exists (skip in testing environment)
        if (app()->bound('view') && View::exists($templateName)) {
            return View::make($templateName, [
                'config' => $config,
                'sampleData' => $sampleData,
                'component' => $component,
            ])->render();
        }

        // Generate default template based on category
        return $this->generateDefaultVueTemplate($component, $config, $sampleData);
    }

    /**
     * Generate default Vue template based on component category
     */
    protected function generateDefaultVueTemplate(Component $component, array $config, array $sampleData): string
    {
        return match ($component->category) {
            'hero' => $this->generateHeroVueTemplate($config, $sampleData),
            'forms' => $this->generateFormVueTemplate($config, $sampleData),
            'testimonials' => $this->generateTestimonialVueTemplate($config, $sampleData),
            'statistics' => $this->generateStatisticsVueTemplate($config, $sampleData),
            'ctas' => $this->generateCtaVueTemplate($config, $sampleData),
            'media' => $this->generateMediaVueTemplate($config, $sampleData),
            default => $this->generateGenericVueTemplate($component, $config, $sampleData),
        };
    }

    /**
     * Generate TypeScript props interface for the component
     */
    protected function generateTypeScriptProps(Component $component, array $config): string
    {
        $propsInterface = "interface {$component->name}Props {\n";

        foreach ($config as $key => $value) {
            $type = $this->inferTypeScriptType($value);
            $optional = $this->isOptionalProp($key, $component->category) ? '?' : '';
            $propsInterface .= "  {$key}{$optional}: {$type};\n";
        }

        $propsInterface .= '}';

        return $propsInterface;
    }

    /**
     * Generate sample data for component previews
     */
    protected function generateSampleData(string $category, array $config): array
    {
        return match ($category) {
            'hero' => $this->generateHeroSampleData($config),
            'forms' => $this->generateFormSampleData($config),
            'testimonials' => $this->generateTestimonialSampleData($config),
            'statistics' => $this->generateStatisticsSampleData($config),
            'ctas' => $this->generateCtaSampleData($config),
            'media' => $this->generateMediaSampleData($config),
            default => [],
        };
    }

    /**
     * Generate responsive configuration for different breakpoints
     */
    protected function generateResponsiveConfig(array $config): array
    {
        $responsiveConfig = [];

        foreach (self::BREAKPOINTS as $breakpoint => $width) {
            $responsiveConfig[$breakpoint] = $this->adaptConfigForBreakpoint($config, $breakpoint, $width);
        }

        return $responsiveConfig;
    }

    /**
     * Adapt configuration for specific breakpoint
     */
    protected function adaptConfigForBreakpoint(array $config, string $breakpoint, int $width): array
    {
        $adaptedConfig = $config;

        // Mobile optimizations
        if ($breakpoint === 'mobile') {
            // Reduce font sizes for mobile
            if (isset($adaptedConfig['font_size'])) {
                $adaptedConfig['font_size'] = $this->scaleFontForMobile($adaptedConfig['font_size']);
            }

            // Adjust spacing for mobile
            if (isset($adaptedConfig['padding'])) {
                $adaptedConfig['padding'] = $this->scaleSpacingForMobile($adaptedConfig['padding']);
            }

            // Optimize touch targets
            if (isset($adaptedConfig['button_size'])) {
                $adaptedConfig['button_size'] = max($adaptedConfig['button_size'], 44); // Minimum 44px for touch
            }

            // Simplify layouts for mobile
            if (isset($adaptedConfig['layout']) && $adaptedConfig['layout'] === 'grid') {
                $adaptedConfig['layout'] = 'stack';
            }
        }

        return $adaptedConfig;
    }

    /**
     * Generate accessibility attributes for the component
     */
    protected function generateAccessibilityAttributes(Component $component, array $config): array
    {
        $attributes = [
            'role' => $this->determineAriaRole($component->category),
            'aria_label' => $config['aria_label'] ?? $this->generateDefaultAriaLabel($component),
            'semantic_html' => $this->generateSemanticHtmlStructure($component, $config),
            'keyboard_navigation' => $this->generateKeyboardNavigationHints($component),
            'screen_reader' => $this->generateScreenReaderOptimizations($component, $config),
        ];

        // Add category-specific accessibility attributes
        $attributes = array_merge($attributes, $this->getCategorySpecificAccessibility($component->category, $config));

        return $attributes;
    }

    /**
     * Generate CSS variables for theme integration
     */
    protected function generateCssVariables(Component $component, array $config): array
    {
        $cssVars = [];

        // Theme colors
        if (isset($config['theme_colors'])) {
            foreach ($config['theme_colors'] as $key => $color) {
                $cssVars["--component-color-{$key}"] = $color;
            }
        }

        // Theme typography
        if (isset($config['theme_typography'])) {
            foreach ($config['theme_typography'] as $key => $value) {
                $cssVars["--component-font-{$key}"] = $value;
            }
        }

        // Theme spacing
        if (isset($config['theme_spacing'])) {
            foreach ($config['theme_spacing'] as $key => $value) {
                $cssVars["--component-spacing-{$key}"] = $value;
            }
        }

        // Component-specific variables
        $cssVars = array_merge($cssVars, $this->generateComponentSpecificCssVars($component, $config));

        return $cssVars;
    }

    /**
     * Generate performance optimization hints
     */
    protected function generatePerformanceHints(Component $component, array $config): array
    {
        $hints = [
            'lazy_load' => $this->shouldLazyLoad($component, $config),
            'preload_resources' => $this->getPreloadResources($component, $config),
            'critical_css' => $this->getCriticalCss($component, $config),
            'image_optimization' => $this->getImageOptimizationHints($component, $config),
            'caching_strategy' => $this->getCachingStrategy($component, $config),
        ];

        return $hints;
    }

    /**
     * Generate cache key for component rendering
     */
    protected function generateCacheKey(Component $component, array $instanceConfig, array $options): string
    {
        $keyData = [
            'component_id' => $component->id,
            'component_updated' => $component->updated_at->timestamp,
            'theme_id' => $component->theme_id,
            'instance_config' => md5(serialize($instanceConfig)),
            'options' => md5(serialize($options)),
        ];

        if ($component->theme) {
            $keyData['theme_updated'] = $component->theme->updated_at->timestamp;
        }

        return 'component_render_'.md5(serialize($keyData));
    }

    /**
     * Handle rendering errors gracefully
     */
    protected function handleRenderError(Component $component, \Exception $e, array $instanceConfig): array
    {
        \Log::error('Component rendering failed', [
            'component_id' => $component->id,
            'component_name' => $component->name,
            'error' => $e->getMessage(),
            'instance_config' => $instanceConfig,
        ]);

        return [
            'id' => $component->id,
            'name' => $component->name,
            'category' => $component->category,
            'type' => $component->type,
            'error' => true,
            'error_message' => $e->getMessage(),
            'fallback_template' => $this->generateErrorFallbackTemplate($component),
            'rendered_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate error fallback template
     */
    protected function generateErrorFallbackTemplate(Component $component): string
    {
        return "<div class='component-error' data-component-id='{$component->id}'>
            <div class='error-message'>
                <h3>Component Error</h3>
                <p>Unable to render {$component->name} component.</p>
                <small>Component ID: {$component->id}</small>
            </div>
        </div>";
    }

    /**
     * Clear component cache
     */
    public function clearCache(?Component $component = null): bool
    {
        if (! app()->bound('cache')) {
            return true; // Return true in test environment
        }

        if ($component) {
            // Clear cache for specific component
            $pattern = "component_render_*{$component->id}*";

            return Cache::flush(); // In production, use more specific cache clearing
        }

        // Clear all component render cache
        return Cache::flush();
    }

    /**
     * Get default configuration for category
     */
    protected function getDefaultConfigForCategory(string $category): array
    {
        return match ($category) {
            'hero' => [
                'headline' => '',
                'subheading' => '',
                'cta_text' => 'Get Started',
                'cta_url' => '#',
                'background_type' => 'image',
                'background_media' => null,
                'show_statistics' => false,
                'layout' => 'centered',
                'text_alignment' => 'center',
            ],
            'forms' => [
                'fields' => [],
                'submit_text' => 'Submit',
                'success_message' => 'Thank you for your submission!',
                'error_message' => 'Please correct the errors below.',
                'layout' => 'vertical',
                'validation' => 'real_time',
            ],
            'testimonials' => [
                'layout' => 'single',
                'show_author_photo' => true,
                'show_company' => true,
                'show_graduation_year' => false,
                'filter_by_audience' => false,
                'autoplay' => false,
                'transition_speed' => 500,
            ],
            'statistics' => [
                'animation_type' => 'counter',
                'trigger_on_scroll' => true,
                'data_source' => 'manual',
                'format_numbers' => true,
                'layout' => 'grid',
                'columns' => 4,
            ],
            'ctas' => [
                'style' => 'primary',
                'size' => 'medium',
                'track_conversions' => true,
                'utm_parameters' => [],
                'hover_effects' => true,
                'focus_visible' => true,
            ],
            'media' => [
                'type' => 'image',
                'lazy_load' => true,
                'responsive' => true,
                'accessibility_alt' => '',
                'optimization' => 'auto',
                'cdn_enabled' => true,
            ],
            default => [],
        };
    }

    // ========================================
    // Vue Template Generation Methods
    // ========================================

    /**
     * Generate Hero component Vue template
     */
    protected function generateHeroVueTemplate(array $config, array $sampleData): string
    {
        return '<template>
  <section 
    :class="heroClasses" 
    :style="heroStyles"
    role="banner"
    :aria-label="config.aria_label || \'Hero section\'"
  >
    <div class="hero-background" v-if="config.background_type">
      <img 
        v-if="config.background_type === \'image\'" 
        :src="config.background_media || sampleData.background_image"
        :alt="config.background_alt || \'Hero background\'"
        class="hero-bg-image"
        loading="lazy"
      />
      <video 
        v-if="config.background_type === \'video\'"
        :src="config.background_media"
        autoplay
        muted
        loop
        playsinline
        class="hero-bg-video"
        :aria-label="config.video_description || \'Background video\'"
      />
    </div>
    
    <div class="hero-content">
      <h1 class="hero-headline" v-if="config.headline || sampleData.headline">
        {{ config.headline || sampleData.headline }}
      </h1>
      
      <p class="hero-subheading" v-if="config.subheading || sampleData.subheading">
        {{ config.subheading || sampleData.subheading }}
      </p>
      
      <div class="hero-actions" v-if="config.cta_text || sampleData.cta_text">
        <button 
          class="hero-cta"
          :class="ctaClasses"
          @click="handleCtaClick"
          :aria-label="config.cta_aria_label || config.cta_text"
        >
          {{ config.cta_text || sampleData.cta_text }}
        </button>
      </div>
      
      <div 
        class="hero-statistics" 
        v-if="config.show_statistics && sampleData.statistics"
        role="region"
        aria-label="Key statistics"
      >
        <div 
          v-for="(stat, index) in sampleData.statistics" 
          :key="index"
          class="stat-item"
        >
          <span class="stat-value" :aria-label="`${stat.value} ${stat.label}`">
            {{ formatNumber(stat.value) }}
          </span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
      </div>
    </div>
  </section>
</template>';
    }

    /**
     * Generate Form component Vue template
     */
    protected function generateFormVueTemplate(array $config, array $sampleData): string
    {
        return '<template>
  <form 
    @submit.prevent="handleSubmit"
    class="component-form"
    :class="formClasses"
    role="form"
    :aria-label="config.form_title || sampleData.title"
    novalidate
  >
    <div class="form-header" v-if="sampleData.title || sampleData.description">
      <h3 v-if="sampleData.title">{{ sampleData.title }}</h3>
      <p v-if="sampleData.description">{{ sampleData.description }}</p>
    </div>
    
    <div class="form-fields">
      <div 
        v-for="(field, index) in formFields" 
        :key="field.id || index"
        class="form-field"
        :class="getFieldClasses(field)"
      >
        <label 
          :for="getFieldId(field, index)"
          class="field-label"
          :class="{ required: field.required }"
        >
          {{ field.label }}
          <span v-if="field.required" class="required-indicator" aria-label="required">*</span>
        </label>
        
        <input
          v-if="isInputField(field.type)"
          :id="getFieldId(field, index)"
          :type="field.type"
          :name="field.name || field.label"
          :placeholder="field.placeholder"
          :required="field.required"
          :aria-describedby="getFieldErrorId(field, index)"
          :aria-invalid="hasFieldError(field)"
          v-model="formData[field.name || field.label]"
          @blur="validateField(field)"
          class="field-input"
        />
        
        <select
          v-else-if="field.type === \'select\'"
          :id="getFieldId(field, index)"
          :name="field.name || field.label"
          :required="field.required"
          :aria-describedby="getFieldErrorId(field, index)"
          :aria-invalid="hasFieldError(field)"
          v-model="formData[field.name || field.label]"
          @change="validateField(field)"
          class="field-select"
        >
          <option value="">{{ field.placeholder || \'Select an option\' }}</option>
          <option 
            v-for="option in field.options" 
            :key="option.value || option"
            :value="option.value || option"
          >
            {{ option.label || option }}
          </option>
        </select>
        
        <textarea
          v-else-if="field.type === \'textarea\'"
          :id="getFieldId(field, index)"
          :name="field.name || field.label"
          :placeholder="field.placeholder"
          :required="field.required"
          :aria-describedby="getFieldErrorId(field, index)"
          :aria-invalid="hasFieldError(field)"
          v-model="formData[field.name || field.label]"
          @blur="validateField(field)"
          class="field-textarea"
          :rows="field.rows || 4"
        />
        
        <div 
          v-if="hasFieldError(field)"
          :id="getFieldErrorId(field, index)"
          class="field-error"
          role="alert"
          aria-live="polite"
        >
          {{ getFieldError(field) }}
        </div>
      </div>
    </div>
    
    <div class="form-actions">
      <button 
        type="submit"
        class="form-submit"
        :class="submitClasses"
        :disabled="isSubmitting || !isFormValid"
        :aria-label="config.submit_text || \'Submit form\'"
      >
        <span v-if="!isSubmitting">{{ config.submit_text || \'Submit\' }}</span>
        <span v-else>Submitting...</span>
      </button>
    </div>
    
    <div 
      v-if="submitMessage"
      class="form-message"
      :class="submitMessageType"
      role="alert"
      aria-live="polite"
    >
      {{ submitMessage }}
    </div>
  </form>
</template>';
    }

    /**
     * Generate Testimonial component Vue template
     */
    protected function generateTestimonialVueTemplate(array $config, array $sampleData): string
    {
        return '<template>
  <section 
    class="testimonials-component"
    :class="testimonialClasses"
    role="region"
    aria-label="Customer testimonials"
  >
    <div v-if="config.layout === \'single\'" class="testimonial-single">
      <div 
        v-if="sampleData.testimonials && sampleData.testimonials[0]"
        class="testimonial-item"
      >
        <blockquote class="testimonial-quote">
          {{ sampleData.testimonials[0].quote }}
        </blockquote>
        
        <div class="testimonial-author">
          <img 
            v-if="config.show_author_photo && sampleData.testimonials[0].photo"
            :src="sampleData.testimonials[0].photo"
            :alt="sampleData.testimonials[0].author"
            class="author-photo"
            loading="lazy"
          />
          
          <div class="author-info">
            <cite class="author-name">{{ sampleData.testimonials[0].author }}</cite>
            <span class="author-title">{{ sampleData.testimonials[0].title }}</span>
            <span 
              v-if="config.show_company"
              class="author-company"
            >
              {{ sampleData.testimonials[0].company }}
            </span>
            <span 
              v-if="config.show_graduation_year"
              class="graduation-year"
            >
              Class of {{ sampleData.testimonials[0].graduation_year }}
            </span>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else-if="config.layout === \'carousel\'" class="testimonial-carousel">
      <div 
        class="carousel-container"
        :aria-label="`Testimonial ${currentSlide + 1} of ${sampleData.testimonials.length}`"
      >
        <div 
          v-for="(testimonial, index) in sampleData.testimonials"
          :key="index"
          class="testimonial-slide"
          :class="{ active: index === currentSlide }"
          :aria-hidden="index !== currentSlide"
        >
          <blockquote class="testimonial-quote">
            {{ testimonial.quote }}
          </blockquote>
          
          <div class="testimonial-author">
            <img 
              v-if="config.show_author_photo && testimonial.photo"
              :src="testimonial.photo"
              :alt="testimonial.author"
              class="author-photo"
              loading="lazy"
            />
            
            <div class="author-info">
              <cite class="author-name">{{ testimonial.author }}</cite>
              <span class="author-title">{{ testimonial.title }}</span>
              <span v-if="config.show_company" class="author-company">
                {{ testimonial.company }}
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="carousel-controls" role="group" aria-label="Testimonial navigation">
        <button 
          @click="previousSlide"
          class="carousel-btn carousel-prev"
          :disabled="currentSlide === 0"
          aria-label="Previous testimonial"
        >
          ‹
        </button>
        <button 
          @click="nextSlide"
          class="carousel-btn carousel-next"
          :disabled="currentSlide === sampleData.testimonials.length - 1"
          aria-label="Next testimonial"
        >
          ›
        </button>
      </div>
    </div>
    
    <div v-else class="testimonial-grid">
      <div 
        v-for="(testimonial, index) in sampleData.testimonials"
        :key="index"
        class="testimonial-item"
      >
        <blockquote class="testimonial-quote">
          {{ testimonial.quote }}
        </blockquote>
        
        <div class="testimonial-author">
          <img 
            v-if="config.show_author_photo && testimonial.photo"
            :src="testimonial.photo"
            :alt="testimonial.author"
            class="author-photo"
            loading="lazy"
          />
          
          <div class="author-info">
            <cite class="author-name">{{ testimonial.author }}</cite>
            <span class="author-title">{{ testimonial.title }}</span>
            <span v-if="config.show_company" class="author-company">
              {{ testimonial.company }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>';
    }

    // ========================================
    // Sample Data Generation Methods
    // ========================================

    /**
     * Generate hero sample data
     */
    protected function generateHeroSampleData(array $config): array
    {
        return [
            'headline' => 'Connect with Your Alumni Network',
            'subheading' => 'Join thousands of graduates advancing their careers together',
            'cta_text' => 'Join Now',
            'background_image' => '/images/samples/hero-bg.jpg',
            'statistics' => [
                ['label' => 'Alumni Members', 'value' => 15000],
                ['label' => 'Companies Hiring', 'value' => 2500],
                ['label' => 'Success Stories', 'value' => 8500],
                ['label' => 'Job Placements', 'value' => 12000],
            ],
        ];
    }

    /**
     * Generate form sample data
     */
    protected function generateFormSampleData(array $config): array
    {
        return [
            'title' => 'Get Started Today',
            'description' => 'Fill out the form below to join our alumni network',
            'fields' => [
                [
                    'type' => 'text',
                    'label' => 'Full Name',
                    'name' => 'full_name',
                    'placeholder' => 'Enter your full name',
                    'required' => true,
                ],
                [
                    'type' => 'email',
                    'label' => 'Email Address',
                    'name' => 'email',
                    'placeholder' => 'your.email@example.com',
                    'required' => true,
                ],
                [
                    'type' => 'phone',
                    'label' => 'Phone Number',
                    'name' => 'phone',
                    'placeholder' => '(555) 123-4567',
                    'required' => false,
                ],
                [
                    'type' => 'select',
                    'label' => 'Graduation Year',
                    'name' => 'graduation_year',
                    'options' => ['2020', '2021', '2022', '2023', '2024'],
                    'required' => true,
                ],
            ],
        ];
    }

    /**
     * Generate testimonial sample data
     */
    protected function generateTestimonialSampleData(array $config): array
    {
        return [
            'testimonials' => [
                [
                    'quote' => 'This platform transformed my career. I connected with amazing opportunities and mentors.',
                    'author' => 'Sarah Johnson',
                    'title' => 'Senior Software Engineer',
                    'company' => 'Tech Innovations Inc.',
                    'graduation_year' => 2018,
                    'photo' => '/images/samples/testimonial-1.jpg',
                ],
                [
                    'quote' => 'The networking opportunities here are unmatched. I found my dream job through alumni connections.',
                    'author' => 'Michael Chen',
                    'title' => 'Product Manager',
                    'company' => 'StartupCorp',
                    'graduation_year' => 2019,
                    'photo' => '/images/samples/testimonial-2.jpg',
                ],
                [
                    'quote' => 'Being part of this community opened doors I never knew existed. Highly recommended!',
                    'author' => 'Emily Rodriguez',
                    'title' => 'Marketing Director',
                    'company' => 'Global Marketing Solutions',
                    'graduation_year' => 2017,
                    'photo' => '/images/samples/testimonial-3.jpg',
                ],
            ],
        ];
    }

    /**
     * Generate statistics sample data
     */
    protected function generateStatisticsSampleData(array $config): array
    {
        return [
            'title' => 'Our Impact',
            'metrics' => [
                [
                    'label' => 'Job Placement Rate',
                    'value' => 95,
                    'suffix' => '%',
                    'description' => 'of alumni find jobs within 6 months',
                ],
                [
                    'label' => 'Average Salary Increase',
                    'value' => 35,
                    'suffix' => '%',
                    'description' => 'after joining our network',
                ],
                [
                    'label' => 'Network Connections',
                    'value' => 1250,
                    'suffix' => '+',
                    'description' => 'meaningful professional relationships',
                ],
                [
                    'label' => 'Companies Partnered',
                    'value' => 500,
                    'suffix' => '+',
                    'description' => 'actively hiring our alumni',
                ],
            ],
        ];
    }

    /**
     * Generate CTA sample data
     */
    protected function generateCtaSampleData(array $config): array
    {
        return [
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
        ];
    }

    /**
     * Generate media sample data
     */
    protected function generateMediaSampleData(array $config): array
    {
        return [
            'images' => [
                [
                    'url' => '/images/samples/gallery-1.jpg',
                    'alt' => 'Alumni networking event',
                    'caption' => 'Annual Alumni Meetup 2024',
                ],
                [
                    'url' => '/images/samples/gallery-2.jpg',
                    'alt' => 'Career fair',
                    'caption' => 'Career Fair with Top Employers',
                ],
                [
                    'url' => '/images/samples/gallery-3.jpg',
                    'alt' => 'Graduation ceremony',
                    'caption' => 'Class of 2024 Graduation',
                ],
            ],
            'videos' => [
                [
                    'url' => '/videos/samples/success-stories.mp4',
                    'thumbnail' => '/images/samples/video-thumb-1.jpg',
                    'title' => 'Alumni Success Stories',
                ],
            ],
        ];
    }

    // ========================================
    // Utility Methods
    // ========================================

    /**
     * Generate HTML preview for component
     */
    protected function generateHtmlPreview(Component $component, array $config, array $sampleData): string
    {
        // This would generate a static HTML preview for non-Vue contexts
        return "<div class='component-preview {$component->category}-preview' data-component-id='{$component->id}'>
            <div class='preview-placeholder'>
                <h3>{$component->name}</h3>
                <p>Category: {$component->category}</p>
                <p>Type: {$component->type}</p>
            </div>
        </div>";
    }

    /**
     * Generate CSS classes for component
     */
    protected function generateCssClasses(Component $component, array $config): array
    {
        $classes = [
            'component',
            "component-{$component->category}",
            "component-{$component->type}",
        ];

        // Add configuration-based classes
        if (isset($config['layout'])) {
            $classes[] = "layout-{$config['layout']}";
        }

        if (isset($config['style'])) {
            $classes[] = "style-{$config['style']}";
        }

        if (isset($config['size'])) {
            $classes[] = "size-{$config['size']}";
        }

        return $classes;
    }

    /**
     * Infer TypeScript type from value
     */
    protected function inferTypeScriptType(mixed $value): string
    {
        return match (gettype($value)) {
            'boolean' => 'boolean',
            'integer', 'double' => 'number',
            'string' => 'string',
            'array' => 'any[]',
            'object' => 'object',
            default => 'any',
        };
    }

    /**
     * Check if prop is optional for category
     */
    protected function isOptionalProp(string $key, string $category): bool
    {
        $requiredProps = match ($category) {
            'hero' => ['headline'],
            'forms' => ['fields'],
            'testimonials' => ['layout'],
            'statistics' => ['metrics'],
            'ctas' => ['text', 'url'],
            'media' => ['type'],
            default => [],
        };

        return ! in_array($key, $requiredProps);
    }

    /**
     * Scale font size for mobile
     */
    protected function scaleFontForMobile(string|int $fontSize): string
    {
        if (is_numeric($fontSize)) {
            return max(14, $fontSize * 0.9).'px';
        }

        // Handle CSS units
        if (preg_match('/(\d+)(px|rem|em)/', $fontSize, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];
            $scaledValue = max(14, $value * 0.9);

            return $scaledValue.$unit;
        }

        return $fontSize;
    }

    /**
     * Scale spacing for mobile
     */
    protected function scaleSpacingForMobile(string|int $spacing): string
    {
        if (is_numeric($spacing)) {
            return max(8, $spacing * 0.75).'px';
        }

        if (preg_match('/(\d+)(px|rem|em)/', $spacing, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2];
            $scaledValue = max(8, $value * 0.75);

            return $scaledValue.$unit;
        }

        return $spacing;
    }

    /**
     * Determine ARIA role for component category
     */
    protected function determineAriaRole(string $category): string
    {
        return match ($category) {
            'hero' => 'banner',
            'forms' => 'form',
            'testimonials' => 'region',
            'statistics' => 'region',
            'ctas' => 'button',
            'media' => 'img',
            default => 'region',
        };
    }

    /**
     * Generate default ARIA label
     */
    protected function generateDefaultAriaLabel(Component $component): string
    {
        return match ($component->category) {
            'hero' => 'Hero section',
            'forms' => 'Contact form',
            'testimonials' => 'Customer testimonials',
            'statistics' => 'Key statistics',
            'ctas' => 'Call to action',
            'media' => 'Media content',
            default => $component->name.' component',
        };
    }

    /**
     * Generate semantic HTML structure hints
     */
    protected function generateSemanticHtmlStructure(Component $component, array $config): array
    {
        return match ($component->category) {
            'hero' => [
                'container' => 'section',
                'heading' => 'h1',
                'subheading' => 'p',
                'cta' => 'button',
            ],
            'forms' => [
                'container' => 'form',
                'fieldset' => 'fieldset',
                'legend' => 'legend',
                'label' => 'label',
                'input' => 'input',
            ],
            'testimonials' => [
                'container' => 'section',
                'quote' => 'blockquote',
                'author' => 'cite',
                'list' => 'ul',
                'item' => 'li',
            ],
            default => [
                'container' => 'div',
            ],
        };
    }

    /**
     * Generate keyboard navigation hints
     */
    protected function generateKeyboardNavigationHints(Component $component): array
    {
        return match ($component->category) {
            'hero' => [
                'focusable_elements' => ['cta_button'],
                'tab_order' => ['headline', 'subheading', 'cta_button'],
            ],
            'forms' => [
                'focusable_elements' => ['inputs', 'selects', 'textareas', 'submit_button'],
                'tab_order' => 'sequential',
                'required_indicators' => true,
            ],
            'testimonials' => [
                'focusable_elements' => ['navigation_buttons'],
                'arrow_key_navigation' => true,
            ],
            default => [
                'focusable_elements' => [],
            ],
        };
    }

    /**
     * Generate screen reader optimizations
     */
    protected function generateScreenReaderOptimizations(Component $component, array $config): array
    {
        return [
            'live_regions' => $this->getLiveRegions($component->category),
            'skip_links' => $this->getSkipLinks($component->category),
            'descriptions' => $this->getScreenReaderDescriptions($component, $config),
        ];
    }

    /**
     * Get category-specific accessibility attributes
     */
    protected function getCategorySpecificAccessibility(string $category, array $config): array
    {
        return match ($category) {
            'hero' => [
                'landmark' => 'banner',
                'heading_level' => 1,
            ],
            'forms' => [
                'fieldset_required' => true,
                'error_announcements' => true,
                'progress_indicators' => true,
            ],
            'testimonials' => [
                'carousel_announcements' => true,
                'slide_count' => true,
            ],
            default => [],
        };
    }

    /**
     * Generate component-specific CSS variables
     */
    protected function generateComponentSpecificCssVars(Component $component, array $config): array
    {
        $vars = [];

        // Component dimensions
        if (isset($config['width'])) {
            $vars['--component-width'] = $config['width'];
        }

        if (isset($config['height'])) {
            $vars['--component-height'] = $config['height'];
        }

        // Component colors
        if (isset($config['background_color'])) {
            $vars['--component-bg-color'] = $config['background_color'];
        }

        if (isset($config['text_color'])) {
            $vars['--component-text-color'] = $config['text_color'];
        }

        return $vars;
    }

    /**
     * Check if component should be lazy loaded
     */
    protected function shouldLazyLoad(Component $component, array $config): bool
    {
        // Components below the fold should be lazy loaded
        $lazyLoadCategories = ['testimonials', 'statistics', 'media'];

        return in_array($component->category, $lazyLoadCategories) ||
               ($config['lazy_load'] ?? false);
    }

    /**
     * Get resources to preload
     */
    protected function getPreloadResources(Component $component, array $config): array
    {
        $resources = [];

        if ($component->category === 'hero' && isset($config['background_media'])) {
            $resources[] = [
                'url' => $config['background_media'],
                'type' => $config['background_type'] === 'video' ? 'video' : 'image',
                'priority' => 'high',
            ];
        }

        return $resources;
    }

    /**
     * Get critical CSS for component
     */
    protected function getCriticalCss(Component $component, array $config): array
    {
        // Return critical CSS rules that should be inlined
        return [
            'above_fold' => $component->category === 'hero',
            'critical_rules' => $this->getCriticalCssRules($component->category),
        ];
    }

    /**
     * Get image optimization hints
     */
    protected function getImageOptimizationHints(Component $component, array $config): array
    {
        return [
            'formats' => ['webp', 'avif', 'jpg'],
            'sizes' => ['320w', '768w', '1024w', '1200w'],
            'lazy_loading' => $config['lazy_load'] ?? true,
            'responsive' => $config['responsive'] ?? true,
        ];
    }

    /**
     * Get caching strategy for component
     */
    protected function getCachingStrategy(Component $component, array $config): array
    {
        return [
            'cache_duration' => self::CACHE_DURATION,
            'cache_key_factors' => ['component_id', 'theme_id', 'config_hash'],
            'invalidation_triggers' => ['component_update', 'theme_update'],
        ];
    }

    /**
     * Generate generic Vue template for unknown categories
     */
    protected function generateGenericVueTemplate(Component $component, array $config, array $sampleData): string
    {
        return '<template>
  <div 
    class="generic-component"
    :class="componentClasses"
    role="region"
    :aria-label="config.aria_label || componentName"
  >
    <h3 v-if="config.title">{{ config.title }}</h3>
    <div class="component-content">
      <p>{{ config.description || "Generic component content" }}</p>
    </div>
  </div>
</template>';
    }

    /**
     * Helper methods for accessibility
     */
    protected function getLiveRegions(string $category): array
    {
        switch ($category) {
            case 'forms':
                return ['error_messages', 'success_messages'];
            case 'testimonials':
                return ['slide_announcements'];
            case 'statistics':
                return ['counter_updates'];
            default:
                return [];
        }
    }

    protected function getSkipLinks(string $category): array
    {
        switch ($category) {
            case 'hero':
                return ['#main-content'];
            case 'forms':
                return ['#form-submit'];
            default:
                return [];
        }
    }

    protected function getScreenReaderDescriptions(Component $component, array $config): array
    {
        return [
            'component_purpose' => $this->generateDefaultAriaLabel($component),
            'interaction_hints' => $this->getInteractionHints($component->category),
        ];
    }

    protected function getInteractionHints(string $category): array
    {
        switch ($category) {
            case 'forms':
                return ['Use Tab to navigate between fields', 'Press Enter to submit'];
            case 'testimonials':
                return ['Use arrow keys to navigate testimonials'];
            case 'ctas':
                return ['Press Enter or Space to activate'];
            default:
                return [];
        }
    }

    protected function getCriticalCssRules(string $category): array
    {
        switch ($category) {
            case 'hero':
                return [
                    'display: block',
                    'position: relative',
                    'min-height: 400px',
                ];
            case 'forms':
                return [
                    'display: block',
                    'max-width: 600px',
                ];
            default:
                return [
                    'display: block',
                ];
        }
    }
}

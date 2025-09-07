# Component Library Developer Guide

## Getting Started

This guide provides comprehensive instructions for developers working with the Component Library System and its GrapeJS integration. Whether you're adding new components, customizing existing ones, or integrating with external systems, this guide covers all the essential information.

## Prerequisites

### Required Knowledge
- Laravel 11+ framework
- Vue 3 with Composition API
- TypeScript
- GrapeJS page builder
- Tailwind CSS
- Multi-tenant architecture concepts

### Development Environment Setup

1. **Clone and Setup Project**
   ```bash
   git clone <repository-url>
   cd alumni-tracking-system
   composer install
   npm install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Start Development Server**
   ```bash
   # Use the provided development script
   .\start-dev.ps1
   # Or manually
   php artisan serve &
   npm run dev
   ```

## Component Development Workflow

### 1. Planning Your Component

Before creating a component, consider:

- **Purpose**: What problem does this component solve?
- **Audience**: Who will use this component (admins, end-users)?
- **Category**: Which category does it belong to (hero, forms, testimonials, etc.)?
- **Configuration**: What properties should be customizable?
- **Responsive Design**: How should it behave on different devices?
- **Accessibility**: What accessibility features are needed?
- **Theme Integration**: How will it work with different themes?

### 2. Component Architecture

Each component consists of several parts:

```
app/Models/Component.php              # Database model
resources/js/components/ComponentLibrary/
  ├── Category/
  │   ├── ComponentName.vue          # Vue component
  │   ├── ComponentBase.vue          # Base component (if applicable)
  │   └── index.ts                   # Export definitions
database/migrations/
  └── create_components_table.php    # Database schema
tests/
  ├── Unit/ComponentNameTest.php     # Unit tests
  └── Feature/ComponentApiTest.php   # Feature tests
```

### 3. Creating a New Component

#### Step 1: Define Component Model

Create or extend the Component model with your component-specific logic:

```php
<?php
// app/Models/CustomComponent.php

namespace App\Models;

class CustomComponent extends Component
{
    protected $table = 'components';
    
    // Component-specific configuration schema
    public function getConfigSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'minLength' => 1,
                    'maxLength' => 100,
                    'description' => 'Component title'
                ],
                'description' => [
                    'type' => 'string',
                    'maxLength' => 500,
                    'description' => 'Component description'
                ],
                'style' => [
                    'type' => 'object',
                    'properties' => [
                        'backgroundColor' => [
                            'type' => 'string',
                            'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$'
                        ],
                        'textColor' => [
                            'type' => 'string',
                            'pattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$'
                        ]
                    ]
                ]
            ],
            'required' => ['title'],
            'additionalProperties' => false
        ];
    }
    
    // GrapeJS metadata for block generation
    public function getGrapeJSMetadata(): array
    {
        return [
            'blockId' => "custom-{$this->type}",
            'category' => 'Custom Components',
            'label' => $this->name,
            'icon' => $this->getIconSvg(),
            'traits' => [
                [
                    'type' => 'text',
                    'name' => 'title',
                    'label' => 'Title',
                    'changeProp' => 1,
                    'default' => 'Default Title'
                ],
                [
                    'type' => 'textarea',
                    'name' => 'description',
                    'label' => 'Description',
                    'changeProp' => 1
                ],
                [
                    'type' => 'color',
                    'name' => 'backgroundColor',
                    'label' => 'Background Color',
                    'changeProp' => 1,
                    'default' => '#ffffff'
                ],
                [
                    'type' => 'color',
                    'name' => 'textColor',
                    'label' => 'Text Color',
                    'changeProp' => 1,
                    'default' => '#000000'
                ]
            ],
            'responsive' => true,
            'accessibility' => [
                'ariaLabel' => 'Custom component',
                'semanticTag' => 'section',
                'keyboardNavigable' => true
            ]
        ];
    }
    
    // Generate SVG icon for the component
    protected function getIconSvg(): string
    {
        return '<svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2L2 7v10c0 5.55 3.84 9.74 9 11 5.16-1.26 9-5.45 9-11V7l-10-5z"/>
        </svg>';
    }
    
    // Validate component configuration
    public function validateConfig(array $config): array
    {
        $validator = new ComponentSchemaValidator();
        return $validator->validate($this->getConfigSchema(), $config);
    }
    
    // Get required theme colors for this component
    public function getRequiredColors(): array
    {
        return [
            'primary.500',
            'text.primary',
            'background.primary'
        ];
    }
}
```

#### Step 2: Create Vue Component

Create the Vue component with proper TypeScript support:

```vue
<!-- resources/js/components/ComponentLibrary/Custom/CustomComponent.vue -->
<template>
  <section
    :class="componentClasses"
    :style="componentStyles"
    :aria-label="config.ariaLabel || config.title"
    role="region"
  >
    <!-- Component Header -->
    <header v-if="config.title || config.description" class="component-header">
      <h2 
        v-if="config.title" 
        class="component-title"
        :style="{ color: config.textColor || theme?.colors?.text?.primary }"
      >
        {{ config.title }}
      </h2>
      <p 
        v-if="config.description" 
        class="component-description"
        :style="{ color: config.textColor || theme?.colors?.text?.secondary }"
      >
        {{ config.description }}
      </p>
    </header>
    
    <!-- Component Content -->
    <div class="component-content">
      <slot>
        <!-- Default content when no slot provided -->
        <div class="default-content">
          <p>This is a custom component. Configure it using the properties panel.</p>
        </div>
      </slot>
    </div>
    
    <!-- Component Footer (if needed) -->
    <footer v-if="$slots.footer" class="component-footer">
      <slot name="footer" />
    </footer>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import type { ComponentConfig, ComponentTheme } from '@/types/components'
import { useAnalytics } from '@/composables/useAnalytics'
import { useIntersectionObserver } from '@/composables/useIntersectionObserver'

// Props interface
interface Props {
  config: ComponentConfig
  theme?: ComponentTheme
  responsive?: boolean
  preview?: boolean
  analytics?: boolean
}

// Props with defaults
const props = withDefaults(defineProps<Props>(), {
  responsive: true,
  preview: false,
  analytics: true
})

// Emits
const emit = defineEmits<{
  interaction: [event: ComponentInteractionEvent]
  configured: [config: ComponentConfig]
}>()

// Composables
const { trackEvent } = useAnalytics()
const { isIntersecting, targetRef } = useIntersectionObserver({
  threshold: 0.1,
  rootMargin: '50px'
})

// Computed properties
const componentClasses = computed(() => [
  'custom-component',
  'w-full',
  'transition-all',
  'duration-300',
  'ease-in-out',
  {
    'responsive-enabled': props.responsive,
    'preview-mode': props.preview,
    [`theme-${props.theme?.name}`]: props.theme?.name,
    'in-view': isIntersecting.value
  }
])

const componentStyles = computed(() => ({
  backgroundColor: props.config.backgroundColor || props.theme?.colors?.background?.primary || '#ffffff',
  color: props.config.textColor || props.theme?.colors?.text?.primary || '#000000',
  padding: props.theme?.spacing?.scale?.[4] || '1rem',
  borderRadius: props.theme?.borders?.radius?.md || '0.375rem'
}))

// Methods
const handleInteraction = (type: string, data?: any) => {
  const event = {
    type,
    componentId: props.config.id,
    timestamp: new Date().toISOString(),
    data
  }
  
  emit('interaction', event)
  
  if (props.analytics && !props.preview) {
    trackEvent('component_interaction', {
      component_type: 'custom',
      interaction_type: type,
      ...data
    })
  }
}

// Lifecycle hooks
onMounted(() => {
  if (props.analytics && !props.preview) {
    trackEvent('component_view', {
      component_type: 'custom',
      component_id: props.config.id
    })
  }
})

// Watch for intersection changes (for analytics)
watch(isIntersecting, (newValue) => {
  if (newValue && props.analytics && !props.preview) {
    handleInteraction('view')
  }
})

// Expose methods for parent components
defineExpose({
  handleInteraction,
  getConfig: () => props.config,
  getTheme: () => props.theme
})
</script>

<style scoped>
.custom-component {
  @apply relative overflow-hidden;
}

.component-header {
  @apply mb-4;
}

.component-title {
  @apply text-2xl font-bold mb-2;
}

.component-description {
  @apply text-base opacity-80;
}

.component-content {
  @apply flex-1;
}

.default-content {
  @apply p-8 text-center border-2 border-dashed border-gray-300 rounded-lg;
}

.component-footer {
  @apply mt-4 pt-4 border-t border-gray-200;
}

/* Responsive breakpoints */
@media (max-width: 768px) {
  .custom-component {
    @apply px-4 py-2;
  }
  
  .component-title {
    @apply text-xl;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  .custom-component {
    @apply px-6 py-3;
  }
}

@media (min-width: 1025px) {
  .custom-component {
    @apply px-8 py-4;
  }
}

/* Theme-specific styles */
.theme-dark .custom-component {
  @apply shadow-lg;
}

.theme-light .custom-component {
  @apply shadow-sm;
}

/* Animation states */
.custom-component.in-view {
  @apply transform translate-y-0 opacity-100;
}

.custom-component:not(.in-view) {
  @apply transform translate-y-4 opacity-0;
}

/* Preview mode styles */
.preview-mode {
  @apply pointer-events-none;
}

.preview-mode .component-content {
  @apply min-h-[200px] flex items-center justify-center;
}
</style>
```

#### Step 3: Create Component Service

Create a service class for component-specific business logic:

```php
<?php
// app/Services/CustomComponentService.php

namespace App\Services;

use App\Models\CustomComponent;
use App\Services\ComponentService;
use Illuminate\Support\Collection;

class CustomComponentService extends ComponentService
{
    protected string $componentType = 'custom';
    
    public function create(array $data): CustomComponent
    {
        // Validate configuration against schema
        $component = new CustomComponent(['type' => $this->componentType]);
        $validation = $component->validateConfig($data['config'] ?? []);
        
        if (!$validation['valid']) {
            throw new ValidationException('Invalid component configuration', $validation['errors']);
        }
        
        // Create component with tenant scoping
        return CustomComponent::create([
            'tenant_id' => $this->getCurrentTenantId(),
            'name' => $data['name'],
            'category' => 'custom',
            'type' => $this->componentType,
            'config' => $data['config'],
            'metadata' => $data['metadata'] ?? [],
            'version' => '1.0.0',
            'is_active' => true
        ]);
    }
    
    public function generateSampleData(): array
    {
        return [
            'title' => 'Sample Custom Component',
            'description' => 'This is a sample description for the custom component.',
            'backgroundColor' => '#f8fafc',
            'textColor' => '#1f2937'
        ];
    }
    
    public function getTemplates(): Collection
    {
        return collect([
            [
                'id' => 'custom-basic',
                'name' => 'Basic Custom Component',
                'description' => 'A simple custom component with title and description',
                'config' => [
                    'title' => 'Custom Component Title',
                    'description' => 'Add your custom content here.',
                    'backgroundColor' => '#ffffff',
                    'textColor' => '#000000'
                ]
            ],
            [
                'id' => 'custom-featured',
                'name' => 'Featured Custom Component',
                'description' => 'A featured custom component with enhanced styling',
                'config' => [
                    'title' => 'Featured Component',
                    'description' => 'This component stands out with special styling.',
                    'backgroundColor' => '#3b82f6',
                    'textColor' => '#ffffff'
                ]
            ]
        ]);
    }
    
    public function validateThemeCompatibility(CustomComponent $component, ComponentTheme $theme): array
    {
        $issues = [];
        $requiredColors = $component->getRequiredColors();
        
        foreach ($requiredColors as $colorPath) {
            if (!$this->hasThemeColor($theme, $colorPath)) {
                $issues[] = [
                    'type' => 'missing_color',
                    'message' => "Required color missing: {$colorPath}",
                    'severity' => 'error'
                ];
            }
        }
        
        return $issues;
    }
    
    private function hasThemeColor(ComponentTheme $theme, string $colorPath): bool
    {
        $parts = explode('.', $colorPath);
        $colors = $theme->config['colors'] ?? [];
        
        foreach ($parts as $part) {
            if (!isset($colors[$part])) {
                return false;
            }
            $colors = $colors[$part];
        }
        
        return true;
    }
}
```

#### Step 4: Register Component with GrapeJS

Create the GrapeJS integration:

```typescript
// resources/js/integrations/customComponentIntegration.ts

import { Editor } from 'grapesjs'
import { ComponentLibraryBridge } from '@/services/ComponentLibraryBridge'
import CustomComponent from '@/components/ComponentLibrary/Custom/CustomComponent.vue'

export function registerCustomComponent(editor: Editor, bridge: ComponentLibraryBridge) {
  // Register Vue component
  bridge.registerVueComponent('custom-component', CustomComponent)
  
  // Register GrapeJS component type
  editor.DomComponents.addType('custom-component', {
    model: {
      defaults: {
        tagName: 'div',
        attributes: {
          'data-component-type': 'custom',
          'data-component-id': 'custom-component'
        },
        traits: [
          {
            type: 'text',
            name: 'title',
            label: 'Title',
            changeProp: 1,
            default: 'Custom Component Title'
          },
          {
            type: 'textarea',
            name: 'description',
            label: 'Description',
            changeProp: 1,
            default: 'Component description'
          },
          {
            type: 'color',
            name: 'backgroundColor',
            label: 'Background Color',
            changeProp: 1,
            default: '#ffffff'
          },
          {
            type: 'color',
            name: 'textColor',
            label: 'Text Color',
            changeProp: 1,
            default: '#000000'
          }
        ],
        // Component configuration
        config: {
          title: 'Custom Component Title',
          description: 'Component description',
          backgroundColor: '#ffffff',
          textColor: '#000000'
        }
      },
      
      // Initialize component
      init() {
        this.on('change:attributes', this.handleAttributeChange)
        this.on('change:config', this.handleConfigChange)
      },
      
      // Handle attribute changes from traits
      handleAttributeChange() {
        const attributes = this.getAttributes()
        const config = { ...this.get('config') }
        
        // Update config from attributes
        Object.keys(attributes).forEach(key => {
          if (key !== 'data-component-type' && key !== 'data-component-id') {
            config[key] = attributes[key]
          }
        })
        
        this.set('config', config)
        this.trigger('component:update')
      },
      
      // Handle config changes
      handleConfigChange() {
        const config = this.get('config')
        
        // Validate configuration
        if (this.validateConfig && !this.validateConfig(config)) {
          console.warn('Invalid component configuration:', config)
          return
        }
        
        // Update attributes from config
        this.addAttributes(config)
        this.trigger('component:render')
      },
      
      // Validate component configuration
      validateConfig(config: any): boolean {
        // Basic validation
        if (!config.title || config.title.trim() === '') {
          return false
        }
        
        // Color validation
        const colorRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/
        if (config.backgroundColor && !colorRegex.test(config.backgroundColor)) {
          return false
        }
        
        if (config.textColor && !colorRegex.test(config.textColor)) {
          return false
        }
        
        return true
      }
    },
    
    view: {
      // Render the component
      onRender() {
        const config = this.model.get('config')
        const theme = bridge.getCurrentTheme()
        
        // Create Vue component instance
        this.renderVueComponent('custom-component', {
          config,
          theme,
          preview: true
        })
      },
      
      // Handle events
      events: {
        click: 'handleClick',
        dblclick: 'handleDoubleClick'
      },
      
      handleClick(event: Event) {
        event.stopPropagation()
        // Handle component selection
        this.model.trigger('component:select')
      },
      
      handleDoubleClick(event: Event) {
        event.stopPropagation()
        // Handle component editing
        this.model.trigger('component:edit')
      }
    }
  })
  
  // Add component to Block Manager
  editor.BlockManager.add('custom-component', {
    id: 'custom-component',
    label: 'Custom Component',
    category: 'Custom Components',
    media: `<svg viewBox="0 0 24 24" fill="currentColor">
      <path d="M12 2L2 7v10c0 5.55 3.84 9.74 9 11 5.16-1.26 9-5.45 9-11V7l-10-5z"/>
    </svg>`,
    content: {
      type: 'custom-component',
      attributes: {
        'data-component-type': 'custom',
        'data-component-id': 'custom-component'
      }
    },
    activate: true
  })
}
```

#### Step 5: Create Tests

Create comprehensive tests for your component:

```php
<?php
// tests/Unit/CustomComponentTest.php

use App\Models\CustomComponent;
use App\Services\CustomComponentService;

describe('CustomComponent', function () {
    beforeEach(function () {
        $this->component = CustomComponent::factory()->create([
            'type' => 'custom',
            'config' => [
                'title' => 'Test Component',
                'description' => 'Test description',
                'backgroundColor' => '#ffffff',
                'textColor' => '#000000'
            ]
        ]);
    });
    
    it('validates configuration schema', function () {
        $validConfig = [
            'title' => 'Valid Title',
            'description' => 'Valid description',
            'backgroundColor' => '#ff0000',
            'textColor' => '#000000'
        ];
        
        $validation = $this->component->validateConfig($validConfig);
        
        expect($validation['valid'])->toBeTrue();
        expect($validation['errors'])->toBeEmpty();
    });
    
    it('rejects invalid configuration', function () {
        $invalidConfig = [
            // Missing required title
            'description' => 'Valid description',
            'backgroundColor' => 'invalid-color',
            'textColor' => '#000000'
        ];
        
        $validation = $this->component->validateConfig($invalidConfig);
        
        expect($validation['valid'])->toBeFalse();
        expect($validation['errors'])->not->toBeEmpty();
    });
    
    it('generates correct GrapeJS metadata', function () {
        $metadata = $this->component->getGrapeJSMetadata();
        
        expect($metadata)
            ->toHaveKey('blockId', 'custom-custom')
            ->toHaveKey('category', 'Custom Components')
            ->toHaveKey('label', $this->component->name)
            ->toHaveKey('traits')
            ->and($metadata['traits'])->toBeArray()
            ->and($metadata['responsive'])->toBeTrue();
    });
    
    it('returns required theme colors', function () {
        $requiredColors = $this->component->getRequiredColors();
        
        expect($requiredColors)->toContain('primary.500')
            ->and($requiredColors)->toContain('text.primary')
            ->and($requiredColors)->toContain('background.primary');
    });
});

// tests/Feature/CustomComponentApiTest.php

use App\Models\CustomComponent;
use App\Models\User;

describe('Custom Component API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });
    
    it('creates custom component via API', function () {
        $componentData = [
            'name' => 'New Custom Component',
            'category' => 'custom',
            'type' => 'custom',
            'config' => [
                'title' => 'API Created Component',
                'description' => 'Created via API',
                'backgroundColor' => '#3b82f6',
                'textColor' => '#ffffff'
            ]
        ];
        
        $response = $this->postJson('/api/components', $componentData);
        
        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Custom Component')
            ->assertJsonPath('data.config.title', 'API Created Component');
        
        $this->assertDatabaseHas('components', [
            'name' => 'New Custom Component',
            'type' => 'custom'
        ]);
    });
    
    it('validates component configuration on creation', function () {
        $invalidData = [
            'name' => 'Invalid Component',
            'category' => 'custom',
            'type' => 'custom',
            'config' => [
                // Missing required title
                'description' => 'Invalid component',
                'backgroundColor' => 'invalid-color'
            ]
        ];
        
        $response = $this->postJson('/api/components', $invalidData);
        
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['config.title', 'config.backgroundColor']);
    });
    
    it('updates custom component configuration', function () {
        $component = CustomComponent::factory()->create();
        
        $updateData = [
            'config' => [
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'backgroundColor' => '#ef4444',
                'textColor' => '#ffffff'
            ]
        ];
        
        $response = $this->putJson("/api/components/{$component->id}", $updateData);
        
        $response->assertOk()
            ->assertJsonPath('data.config.title', 'Updated Title')
            ->assertJsonPath('data.config.backgroundColor', '#ef4444');
    });
});
```

## Advanced Integration Patterns

### Custom Trait Types

Create custom trait types for specialized configuration:

```typescript
// resources/js/traits/customTraits.ts

export function registerCustomTraits(editor: Editor) {
  const traitManager = editor.TraitManager
  
  // Image selector trait
  traitManager.addType('image-select', {
    createInput({ trait }) {
      const input = document.createElement('div')
      input.innerHTML = `
        <div class="image-selector">
          <button type="button" class="select-image-btn">
            Select Image
          </button>
          <div class="selected-image" style="display: none;">
            <img src="" alt="Selected image" />
            <button type="button" class="remove-image-btn">Remove</button>
          </div>
        </div>
      `
      
      const selectBtn = input.querySelector('.select-image-btn')
      const removeBtn = input.querySelector('.remove-image-btn')
      const imageContainer = input.querySelector('.selected-image')
      const img = input.querySelector('img')
      
      selectBtn?.addEventListener('click', () => {
        this.openImageSelector((imageUrl: string) => {
          img.src = imageUrl
          imageContainer.style.display = 'block'
          selectBtn.style.display = 'none'
          this.onChange(imageUrl)
        })
      })
      
      removeBtn?.addEventListener('click', () => {
        img.src = ''
        imageContainer.style.display = 'none'
        selectBtn.style.display = 'block'
        this.onChange('')
      })
      
      return input
    },
    
    onUpdate({ elInput, component }) {
      const value = this.getComponentValue(component)
      const img = elInput.querySelector('img')
      const imageContainer = elInput.querySelector('.selected-image')
      const selectBtn = elInput.querySelector('.select-image-btn')
      
      if (value) {
        img.src = value
        imageContainer.style.display = 'block'
        selectBtn.style.display = 'none'
      } else {
        imageContainer.style.display = 'none'
        selectBtn.style.display = 'block'
      }
    },
    
    openImageSelector(callback: (url: string) => void) {
      // Open image selection modal
      const modal = new ImageSelectorModal({
        onSelect: callback
      })
      modal.open()
    }
  })
  
  // Color palette trait
  traitManager.addType('color-palette', {
    createInput({ trait }) {
      const colors = trait.get('colors') || []
      const input = document.createElement('div')
      input.className = 'color-palette-selector'
      
      colors.forEach((color: string) => {
        const colorBtn = document.createElement('button')
        colorBtn.type = 'button'
        colorBtn.className = 'color-option'
        colorBtn.style.backgroundColor = color
        colorBtn.dataset.color = color
        colorBtn.addEventListener('click', () => {
          this.onChange(color)
          this.updateSelection(input, color)
        })
        input.appendChild(colorBtn)
      })
      
      return input
    },
    
    updateSelection(container: HTMLElement, selectedColor: string) {
      const options = container.querySelectorAll('.color-option')
      options.forEach(option => {
        option.classList.toggle('selected', option.dataset.color === selectedColor)
      })
    }
  })
}
```

### Component Composition

Create composable components that can be combined:

```vue
<!-- resources/js/components/ComponentLibrary/Composable/ComposableSection.vue -->
<template>
  <div class="composable-section" :class="sectionClasses">
    <component
      v-for="(child, index) in config.children"
      :key="`child-${index}`"
      :is="getChildComponent(child.type)"
      :config="child.config"
      :theme="theme"
      @interaction="handleChildInteraction"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ComposableConfig, ComponentTheme } from '@/types/components'

interface Props {
  config: ComposableConfig
  theme?: ComponentTheme
}

const props = defineProps<Props>()

const emit = defineEmits<{
  interaction: [event: ComponentInteractionEvent]
}>()

const sectionClasses = computed(() => [
  'composable-section',
  `layout-${props.config.layout || 'vertical'}`,
  `gap-${props.config.gap || '4'}`,
  {
    'full-width': props.config.fullWidth,
    'centered': props.config.centered
  }
])

const getChildComponent = (type: string) => {
  // Dynamic component resolution
  return () => import(`@/components/ComponentLibrary/${type}/${type}.vue`)
}

const handleChildInteraction = (event: ComponentInteractionEvent) => {
  emit('interaction', {
    ...event,
    parentComponent: 'composable-section'
  })
}
</script>

<style scoped>
.composable-section {
  @apply w-full;
}

.layout-vertical {
  @apply flex flex-col;
}

.layout-horizontal {
  @apply flex flex-row;
}

.layout-grid {
  @apply grid;
}

.gap-1 { @apply gap-1; }
.gap-2 { @apply gap-2; }
.gap-4 { @apply gap-4; }
.gap-6 { @apply gap-6; }
.gap-8 { @apply gap-8; }

.full-width {
  @apply w-full;
}

.centered {
  @apply mx-auto;
}
</style>
```

## Performance Optimization

### Component Lazy Loading

Implement lazy loading for better performance:

```typescript
// resources/js/services/ComponentLoader.ts

export class ComponentLoader {
  private componentCache = new Map<string, any>()
  private loadingPromises = new Map<string, Promise<any>>()
  
  async loadComponent(componentType: string): Promise<any> {
    // Return cached component if available
    if (this.componentCache.has(componentType)) {
      return this.componentCache.get(componentType)
    }
    
    // Return existing loading promise if component is being loaded
    if (this.loadingPromises.has(componentType)) {
      return this.loadingPromises.get(componentType)
    }
    
    // Create loading promise
    const loadingPromise = this.loadComponentModule(componentType)
    this.loadingPromises.set(componentType, loadingPromise)
    
    try {
      const component = await loadingPromise
      this.componentCache.set(componentType, component)
      this.loadingPromises.delete(componentType)
      return component
    } catch (error) {
      this.loadingPromises.delete(componentType)
      throw error
    }
  }
  
  private async loadComponentModule(componentType: string): Promise<any> {
    const componentMap: Record<string, () => Promise<any>> = {
      'hero': () => import('@/components/ComponentLibrary/Hero/HeroComponent.vue'),
      'form': () => import('@/components/ComponentLibrary/Forms/FormComponent.vue'),
      'testimonial': () => import('@/components/ComponentLibrary/Testimonials/TestimonialComponent.vue'),
      'statistics': () => import('@/components/ComponentLibrary/Statistics/StatisticsComponent.vue'),
      'cta': () => import('@/components/ComponentLibrary/CTAs/CTAComponent.vue'),
      'media': () => import('@/components/ComponentLibrary/Media/MediaComponent.vue'),
      'custom': () => import('@/components/ComponentLibrary/Custom/CustomComponent.vue')
    }
    
    const loader = componentMap[componentType]
    if (!loader) {
      throw new Error(`Unknown component type: ${componentType}`)
    }
    
    return loader()
  }
  
  preloadComponents(componentTypes: string[]): Promise<any[]> {
    return Promise.all(
      componentTypes.map(type => this.loadComponent(type))
    )
  }
  
  clearCache(): void {
    this.componentCache.clear()
    this.loadingPromises.clear()
  }
}
```

### Bundle Optimization

Configure Vite for optimal component bundling:

```typescript
// vite.config.ts additions for component library

export default defineConfig({
  // ... existing config
  
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          // Component library chunks
          'component-library-core': [
            'resources/js/services/ComponentLibraryBridge.ts',
            'resources/js/utils/componentSerialization.ts',
            'resources/js/utils/componentSchemaValidator.ts'
          ],
          'component-library-hero': [
            'resources/js/components/ComponentLibrary/Hero/HeroComponent.vue',
            'resources/js/components/ComponentLibrary/Hero/HeroBase.vue'
          ],
          'component-library-forms': [
            'resources/js/components/ComponentLibrary/Forms/FormComponent.vue',
            'resources/js/components/ComponentLibrary/Forms/FormBase.vue'
          ],
          // ... other component chunks
        }
      }
    }
  },
  
  optimizeDeps: {
    include: [
      'grapesjs',
      '@grapesjs/cli',
      'vue',
      'vue-router'
    ]
  }
})
```

This comprehensive developer guide provides all the necessary information for creating, integrating, and optimizing components within the Component Library System while ensuring seamless GrapeJS integration.
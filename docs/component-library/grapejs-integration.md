# Component Library GrapeJS Integration Documentation

## Overview

This documentation covers the integration between the Component Library System and GrapeJS page builder, providing a seamless drag-and-drop experience for creating landing pages with pre-built components.

## Table of Contents

1. [Component-to-GrapeJS Block Conversion](#component-to-grapejs-block-conversion)
2. [Developer Guide](./developer-guide.md)
3. [User Guide](./user-guide.md)
4. [Configuration Schemas](#configuration-schemas)
5. [Troubleshooting](./troubleshooting-guide.md)
6. [API Reference](./api-reference.md)
7. [Theme Integration](./theme-integration.md)

## Related Documentation

- **[Developer Guide](./developer-guide.md)**: Comprehensive guide for developers adding new components and customizing the system
- **[User Guide](./user-guide.md)**: Complete user manual for creating pages with the component library
- **[API Reference](./api-reference.md)**: Detailed API documentation for all services and endpoints
- **[Theme Integration](./theme-integration.md)**: Theme system documentation with validation and compatibility guidelines
- **[Troubleshooting Guide](./troubleshooting-guide.md)**: Solutions for common integration and usage issues

## Component-to-GrapeJS Block Conversion

### Conversion Process

The Component Library System automatically converts components into GrapeJS blocks through the `ComponentLibraryBridge` service. Each component follows a standardized conversion process:

1. **Component Registration**: Components are registered with GrapeJS metadata
2. **Block Generation**: Component configurations are converted to GrapeJS block definitions
3. **Trait Mapping**: Component properties are mapped to GrapeJS traits
4. **Preview Generation**: Component previews are generated for the Block Manager
5. **Serialization**: Components are serialized for GrapeJS data format compatibility

### Block Structure

Each component is converted to a GrapeJS block with the following structure:

```javascript
{
  id: 'component-hero-individual',
  label: 'Individual Hero',
  category: 'Hero Components',
  media: '<svg>...</svg>', // or image URL
  content: {
    type: 'component-hero',
    attributes: {
      'data-component-id': 'hero-individual',
      'data-component-config': '{...}'
    }
  },
  traits: [
    {
      type: 'text',
      name: 'headline',
      label: 'Headline',
      changeProp: 1
    }
    // ... more traits
  ]
}
```

### Component Categories

Components are organized into the following GrapeJS categories:

- **Hero Components**: Landing page headers and banners
- **Form Components**: Lead capture and contact forms
- **Testimonial Components**: Social proof and credibility elements
- **Statistics Components**: Metrics and data visualization
- **CTA Components**: Call-to-action buttons and banners
- **Media Components**: Images, videos, and interactive content

## Standards and Best Practices

### Component Metadata Requirements

All components must include the following metadata for GrapeJS compatibility:

```php
// Component model metadata
protected $grapeJSMetadata = [
    'blockId' => 'unique-block-identifier',
    'category' => 'Component Category',
    'label' => 'Human Readable Name',
    'icon' => 'svg-icon-or-image-url',
    'traits' => [
        // Trait definitions
    ],
    'responsive' => true,
    'accessibility' => [
        'ariaLabel' => 'default-aria-label',
        'semanticTag' => 'section'
    ]
];
```

### Trait Mapping Standards

Component properties are mapped to GrapeJS traits using standardized patterns:

- **Text Properties**: Use `text` trait type
- **Color Properties**: Use `color` trait type with validation
- **Number Properties**: Use `number` trait type with min/max
- **Select Properties**: Use `select` trait type with options
- **Boolean Properties**: Use `checkbox` trait type
- **Complex Objects**: Use `composite` trait type

### Responsive Design Standards

All components must support responsive design through:

- Mobile-first CSS classes
- Tailwind CSS responsive prefixes
- Device-specific component variants
- Breakpoint-aware trait configurations

### Accessibility Standards

Components must meet WCAG 2.1 AA standards:

- Semantic HTML structure
- Proper ARIA labels and roles
- Keyboard navigation support
- Screen reader compatibility
- Color contrast compliance
## De
veloper Guide

### Adding New Components with GrapeJS Compatibility

#### Step 1: Create Component Model

Create a new component model with GrapeJS metadata:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomComponent extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'type',
        'config',
        'metadata',
        'version',
        'is_active'
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    // GrapeJS metadata for block generation
    public function getGrapeJSMetadata(): array
    {
        return [
            'blockId' => "custom-{$this->type}",
            'category' => $this->category,
            'label' => $this->name,
            'icon' => $this->getIconSvg(),
            'traits' => $this->getTraitDefinitions(),
            'responsive' => true,
            'accessibility' => $this->getAccessibilityConfig()
        ];
    }

    protected function getTraitDefinitions(): array
    {
        return [
            [
                'type' => 'text',
                'name' => 'title',
                'label' => 'Title',
                'changeProp' => 1,
                'default' => 'Default Title'
            ],
            [
                'type' => 'color',
                'name' => 'backgroundColor',
                'label' => 'Background Color',
                'changeProp' => 1,
                'default' => '#ffffff'
            ]
            // Add more traits as needed
        ];
    }
}
```

#### Step 2: Create Vue Component

Create the Vue component with GrapeJS compatibility:

```vue
<template>
  <div 
    :class="componentClasses"
    :style="componentStyles"
    :aria-label="config.ariaLabel"
    role="region"
  >
    <h2 v-if="config.title" class="component-title">
      {{ config.title }}
    </h2>
    <!-- Component content -->
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ComponentConfig } from '@/types/components'

interface Props {
  config: ComponentConfig
  theme?: any
  responsive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  responsive: true
})

// Computed classes for responsive design
const componentClasses = computed(() => [
  'custom-component',
  'w-full',
  {
    'responsive-enabled': props.responsive,
    [`theme-${props.theme?.name}`]: props.theme?.name
  }
])

// Dynamic styles from configuration
const componentStyles = computed(() => ({
  backgroundColor: props.config.backgroundColor || '#ffffff',
  color: props.config.textColor || '#000000'
}))
</script>

<style scoped>
.custom-component {
  @apply transition-all duration-300 ease-in-out;
}

/* Responsive breakpoints */
@media (max-width: 768px) {
  .custom-component {
    @apply px-4 py-2;
  }
}

@media (min-width: 769px) {
  .custom-component {
    @apply px-8 py-4;
  }
}
</style>
```

#### Step 3: Register with ComponentLibraryBridge

Register the component with the bridge service:

```typescript
// resources/js/services/ComponentLibraryBridge.ts
import { ComponentLibraryBridge } from '@/services/ComponentLibraryBridge'

// Register custom component
ComponentLibraryBridge.registerComponent({
  id: 'custom-component',
  category: 'Custom Components',
  component: () => import('@/components/Custom/CustomComponent.vue'),
  metadata: {
    blockId: 'custom-component',
    label: 'Custom Component',
    icon: '<svg>...</svg>',
    traits: [
      {
        type: 'text',
        name: 'title',
        label: 'Title'
      }
    ]
  }
})
```

#### Step 4: Add Component Tests

Create comprehensive tests for the component:

```php
<?php

use App\Models\CustomComponent;
use App\Services\ComponentLibraryBridge;

it('generates correct GrapeJS block metadata', function () {
    $component = CustomComponent::factory()->create([
        'name' => 'Test Component',
        'category' => 'Custom Components',
        'type' => 'custom'
    ]);

    $metadata = $component->getGrapeJSMetadata();

    expect($metadata)
        ->toHaveKey('blockId', 'custom-custom')
        ->toHaveKey('category', 'Custom Components')
        ->toHaveKey('label', 'Test Component')
        ->toHaveKey('traits')
        ->and($metadata['traits'])->toBeArray();
});

it('converts component to GrapeJS block format', function () {
    $component = CustomComponent::factory()->create();
    $bridge = new ComponentLibraryBridge();

    $block = $bridge->convertToGrapeJSBlock($component);

    expect($block)
        ->toHaveKey('id')
        ->toHaveKey('label')
        ->toHaveKey('category')
        ->toHaveKey('content')
        ->toHaveKey('traits');
});
```

### Component Configuration Schema

#### Schema Definition

Each component must define a JSON schema for configuration validation:

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "title": {
      "type": "string",
      "minLength": 1,
      "maxLength": 100,
      "description": "Component title"
    },
    "backgroundColor": {
      "type": "string",
      "pattern": "^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$",
      "description": "Background color in hex format"
    },
    "responsive": {
      "type": "object",
      "properties": {
        "mobile": {
          "type": "object",
          "properties": {
            "hidden": { "type": "boolean" },
            "classes": { "type": "string" }
          }
        },
        "tablet": {
          "type": "object",
          "properties": {
            "hidden": { "type": "boolean" },
            "classes": { "type": "string" }
          }
        },
        "desktop": {
          "type": "object",
          "properties": {
            "hidden": { "type": "boolean" },
            "classes": { "type": "string" }
          }
        }
      }
    }
  },
  "required": ["title"],
  "additionalProperties": false
}
```

#### Validation Implementation

```typescript
// resources/js/utils/componentSchemaValidator.ts
import Ajv from 'ajv'
import type { ComponentConfig } from '@/types/components'

export class ComponentSchemaValidator {
  private ajv: Ajv

  constructor() {
    this.ajv = new Ajv({ allErrors: true })
  }

  validateConfig(schema: object, config: ComponentConfig): ValidationResult {
    const validate = this.ajv.compile(schema)
    const valid = validate(config)

    return {
      valid,
      errors: validate.errors || []
    }
  }

  getValidationErrors(schema: object, config: ComponentConfig): string[] {
    const result = this.validateConfig(schema, config)
    return result.errors.map(error => 
      `${error.instancePath}: ${error.message}`
    )
  }
}
```

### GrapeJS Trait Mappings

#### Standard Trait Types

| Component Property | GrapeJS Trait | Configuration |
|-------------------|---------------|---------------|
| Text fields | `text` | `{ type: 'text', name: 'fieldName', label: 'Field Label' }` |
| Colors | `color` | `{ type: 'color', name: 'colorName', label: 'Color Label' }` |
| Numbers | `number` | `{ type: 'number', name: 'numberName', min: 0, max: 100 }` |
| Selections | `select` | `{ type: 'select', name: 'selectName', options: [...] }` |
| Checkboxes | `checkbox` | `{ type: 'checkbox', name: 'checkboxName', valueTrue: 'yes' }` |

#### Custom Trait Implementation

```typescript
// Custom trait for image selection
const imageSelectTrait = {
  type: 'select',
  name: 'backgroundImage',
  label: 'Background Image',
  changeProp: 1,
  options: [
    { value: '', name: 'None' },
    { value: 'hero-bg-1.jpg', name: 'Hero Background 1' },
    { value: 'hero-bg-2.jpg', name: 'Hero Background 2' }
  ]
}

// Custom trait for responsive settings
const responsiveTrait = {
  type: 'composite',
  name: 'responsive',
  label: 'Responsive Settings',
  traits: [
    {
      type: 'checkbox',
      name: 'mobileHidden',
      label: 'Hide on Mobile'
    },
    {
      type: 'checkbox',
      name: 'tabletHidden',
      label: 'Hide on Tablet'
    }
  ]
}
```## User Gui
de

### Using Components in GrapeJS Page Builder

#### Accessing the Component Library

1. **Open Page Builder**: Navigate to the page builder interface
2. **Component Panel**: The component library appears in the left sidebar
3. **Category Navigation**: Components are organized by category tabs
4. **Search Function**: Use the search bar to find specific components

#### Adding Components to Pages

1. **Browse Components**: Click on category tabs to view available components
2. **Preview Components**: Hover over component cards to see live previews
3. **Drag and Drop**: Drag components from the library to the canvas
4. **Drop Zones**: Components highlight valid drop zones when dragging

#### Configuring Components

1. **Select Component**: Click on a component in the canvas to select it
2. **Properties Panel**: The right sidebar shows configuration options
3. **Live Preview**: Changes update in real-time as you modify properties
4. **Responsive Settings**: Use device icons to configure mobile/tablet variants

#### Component Categories Guide

##### Hero Components
- **Individual Hero**: Optimized for alumni targeting
- **Institution Hero**: Designed for institutional partnerships
- **Employer Hero**: Focused on talent acquisition messaging

**Configuration Options**:
- Headline and subheading text
- Background image or video
- Call-to-action buttons
- Statistics counters

##### Form Components
- **Lead Capture**: Simple signup forms
- **Demo Request**: Institutional inquiry forms
- **Contact Forms**: General contact and support

**Configuration Options**:
- Form fields (drag-and-drop arrangement)
- Validation rules
- Success/error messages
- CRM integration settings

##### Testimonial Components
- **Single Quote**: Featured testimonial display
- **Carousel**: Multiple testimonials with navigation
- **Video Testimonials**: Rich media testimonials

**Configuration Options**:
- Testimonial filtering (audience, industry, year)
- Author information display
- Layout and styling options

##### Statistics Components
- **Animated Counters**: Number animations on scroll
- **Progress Bars**: Visual progress indicators
- **Comparison Charts**: Before/after comparisons

**Configuration Options**:
- Data source (live API or manual input)
- Animation settings
- Color and styling

##### CTA Components
- **Primary Buttons**: Main conversion actions
- **Banner CTAs**: Full-width promotional sections
- **Inline Links**: Contextual action links

**Configuration Options**:
- Button text and styling
- Link destinations
- Tracking parameters
- A/B testing variants

##### Media Components
- **Image Gallery**: Photo showcases with lightbox
- **Video Embed**: YouTube/Vimeo integration
- **Interactive Demo**: Product walkthroughs

**Configuration Options**:
- Media upload and selection
- Responsive image variants
- Accessibility settings (alt text, captions)

#### Best Practices for Users

##### Design Consistency
- Use theme settings to maintain brand consistency
- Apply the same color palette across components
- Maintain consistent spacing and typography

##### Mobile Optimization
- Preview pages on different device sizes
- Test touch interactions on mobile devices
- Ensure text remains readable on small screens

##### Accessibility
- Add alt text to all images
- Use proper heading hierarchy
- Ensure sufficient color contrast
- Test with keyboard navigation

##### Performance
- Optimize images before uploading
- Limit the number of video embeds per page
- Use lazy loading for media-heavy sections

### Theme Customization

#### Applying Themes
1. **Theme Manager**: Access through the top toolbar
2. **Select Theme**: Choose from available brand themes
3. **Preview Changes**: See theme applied across all components
4. **Save Changes**: Apply theme to the current page

#### Custom Theme Creation
1. **Create New Theme**: Click "New Theme" in theme manager
2. **Color Palette**: Define primary, secondary, and accent colors
3. **Typography**: Select fonts and sizing scales
4. **Spacing**: Configure padding and margin defaults
5. **Save Theme**: Name and save for reuse

#### Brand Guidelines Enforcement
- Themes automatically enforce brand color palettes
- Font selections are limited to approved typefaces
- Spacing follows established design system rules

### Collaboration Features

#### Sharing and Review
1. **Preview Links**: Generate shareable preview URLs
2. **Comment System**: Add comments for team review
3. **Version History**: Track changes and revert if needed
4. **Approval Workflow**: Submit pages for stakeholder approval

#### Multi-User Editing
- Real-time collaboration with conflict resolution
- User presence indicators
- Change attribution and history
- Role-based permissions (editor, reviewer, admin)

## Configuration Schemas

### Component Configuration Schema Structure

Each component type has a specific configuration schema that defines:

- **Required Properties**: Must be provided for component to function
- **Optional Properties**: Enhance component functionality
- **Validation Rules**: Ensure data integrity and proper formatting
- **Default Values**: Fallback values when properties are not set

### Hero Component Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Hero Component Configuration",
  "type": "object",
  "properties": {
    "variant": {
      "type": "string",
      "enum": ["individual", "institution", "employer"],
      "description": "Target audience variant"
    },
    "headline": {
      "type": "string",
      "minLength": 1,
      "maxLength": 120,
      "description": "Main headline text"
    },
    "subheading": {
      "type": "string",
      "maxLength": 300,
      "description": "Supporting subheading text"
    },
    "backgroundMedia": {
      "type": "object",
      "properties": {
        "type": {
          "type": "string",
          "enum": ["image", "video", "gradient"]
        },
        "url": {
          "type": "string",
          "format": "uri"
        },
        "overlay": {
          "type": "object",
          "properties": {
            "color": {
              "type": "string",
              "pattern": "^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
            },
            "opacity": {
              "type": "number",
              "minimum": 0,
              "maximum": 1
            }
          }
        }
      },
      "required": ["type"]
    },
    "cta": {
      "type": "object",
      "properties": {
        "text": {
          "type": "string",
          "minLength": 1,
          "maxLength": 50
        },
        "url": {
          "type": "string",
          "format": "uri"
        },
        "style": {
          "type": "string",
          "enum": ["primary", "secondary", "outline"]
        }
      },
      "required": ["text", "url"]
    },
    "statistics": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "value": {
            "type": "number",
            "minimum": 0
          },
          "label": {
            "type": "string",
            "minLength": 1,
            "maxLength": 50
          },
          "suffix": {
            "type": "string",
            "maxLength": 10
          }
        },
        "required": ["value", "label"]
      },
      "maxItems": 4
    }
  },
  "required": ["variant", "headline"],
  "additionalProperties": false
}
```

### Form Component Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Form Component Configuration",
  "type": "object",
  "properties": {
    "formType": {
      "type": "string",
      "enum": ["lead-capture", "demo-request", "contact"],
      "description": "Type of form for appropriate field selection"
    },
    "title": {
      "type": "string",
      "maxLength": 100,
      "description": "Form title/heading"
    },
    "fields": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "id": {
            "type": "string",
            "pattern": "^[a-zA-Z][a-zA-Z0-9_]*$"
          },
          "type": {
            "type": "string",
            "enum": ["text", "email", "phone", "select", "textarea", "checkbox"]
          },
          "label": {
            "type": "string",
            "minLength": 1,
            "maxLength": 100
          },
          "required": {
            "type": "boolean",
            "default": false
          },
          "placeholder": {
            "type": "string",
            "maxLength": 100
          },
          "validation": {
            "type": "object",
            "properties": {
              "pattern": {
                "type": "string",
                "description": "Regex pattern for validation"
              },
              "minLength": {
                "type": "integer",
                "minimum": 0
              },
              "maxLength": {
                "type": "integer",
                "minimum": 1
              }
            }
          },
          "options": {
            "type": "array",
            "items": {
              "type": "object",
              "properties": {
                "value": { "type": "string" },
                "label": { "type": "string" }
              },
              "required": ["value", "label"]
            },
            "description": "Options for select fields"
          }
        },
        "required": ["id", "type", "label"]
      },
      "minItems": 1,
      "maxItems": 20
    },
    "submitButton": {
      "type": "object",
      "properties": {
        "text": {
          "type": "string",
          "minLength": 1,
          "maxLength": 50,
          "default": "Submit"
        },
        "style": {
          "type": "string",
          "enum": ["primary", "secondary"],
          "default": "primary"
        }
      }
    },
    "successMessage": {
      "type": "string",
      "maxLength": 500,
      "description": "Message shown after successful submission"
    },
    "crmIntegration": {
      "type": "object",
      "properties": {
        "enabled": {
          "type": "boolean",
          "default": false
        },
        "webhook": {
          "type": "string",
          "format": "uri"
        },
        "leadSource": {
          "type": "string",
          "maxLength": 100
        }
      }
    }
  },
  "required": ["formType", "fields"],
  "additionalProperties": false
}
```

### Testimonial Component Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Testimonial Component Configuration",
  "type": "object",
  "properties": {
    "layout": {
      "type": "string",
      "enum": ["single", "carousel", "grid"],
      "description": "Display layout for testimonials"
    },
    "filters": {
      "type": "object",
      "properties": {
        "audienceType": {
          "type": "array",
          "items": {
            "type": "string",
            "enum": ["individual", "institution", "employer"]
          }
        },
        "industry": {
          "type": "array",
          "items": {
            "type": "string"
          }
        },
        "graduationYear": {
          "type": "object",
          "properties": {
            "min": {
              "type": "integer",
              "minimum": 1900,
              "maximum": 2030
            },
            "max": {
              "type": "integer",
              "minimum": 1900,
              "maximum": 2030
            }
          }
        }
      }
    },
    "displayOptions": {
      "type": "object",
      "properties": {
        "showPhoto": {
          "type": "boolean",
          "default": true
        },
        "showCompany": {
          "type": "boolean",
          "default": true
        },
        "showTitle": {
          "type": "boolean",
          "default": true
        },
        "showGraduationYear": {
          "type": "boolean",
          "default": false
        }
      }
    },
    "carouselSettings": {
      "type": "object",
      "properties": {
        "autoplay": {
          "type": "boolean",
          "default": false
        },
        "interval": {
          "type": "integer",
          "minimum": 3000,
          "maximum": 10000,
          "default": 5000
        },
        "showDots": {
          "type": "boolean",
          "default": true
        },
        "showArrows": {
          "type": "boolean",
          "default": true
        }
      }
    }
  },
  "required": ["layout"],
  "additionalProperties": false
}
```## Tr
oubleshooting

### Common Integration Issues

#### Component Not Appearing in Block Manager

**Symptoms**:
- Component is registered but doesn't show in GrapeJS Block Manager
- Component appears but has no preview image
- Component category is missing or incorrect

**Causes and Solutions**:

1. **Missing GrapeJS Metadata**
   ```php
   // ❌ Incorrect - Missing metadata
   class Component extends Model {
       // No getGrapeJSMetadata() method
   }

   // ✅ Correct - Include metadata
   class Component extends Model {
       public function getGrapeJSMetadata(): array {
           return [
               'blockId' => "component-{$this->type}",
               'category' => $this->category,
               'label' => $this->name,
               'icon' => $this->getIconSvg()
           ];
       }
   }
   ```

2. **Incorrect Block Registration**
   ```typescript
   // ❌ Incorrect - Missing required fields
   editor.BlockManager.add('my-block', {
       label: 'My Block'
       // Missing category, content, etc.
   });

   // ✅ Correct - Complete registration
   editor.BlockManager.add('my-block', {
       id: 'my-block',
       label: 'My Block',
       category: 'Custom Components',
       media: '<svg>...</svg>',
       content: {
           type: 'my-component',
           attributes: { 'data-component-id': 'my-block' }
       }
   });
   ```

3. **Category Not Defined**
   ```typescript
   // ❌ Incorrect - Category doesn't exist
   editor.BlockManager.add('my-block', {
       category: 'Non-existent Category'
   });

   // ✅ Correct - Define category first
   editor.BlockManager.getCategories().add({
       id: 'custom-components',
       label: 'Custom Components'
   });
   ```

#### Component Configuration Not Updating

**Symptoms**:
- Changes in trait panel don't reflect in component
- Component shows default values instead of configured values
- Configuration changes are lost after page reload

**Causes and Solutions**:

1. **Trait Not Properly Mapped**
   ```typescript
   // ❌ Incorrect - Missing changeProp
   {
       type: 'text',
       name: 'title',
       label: 'Title'
   }

   // ✅ Correct - Include changeProp
   {
       type: 'text',
       name: 'title',
       label: 'Title',
       changeProp: 1  // Enables live updates
   }
   ```

2. **Component Not Listening to Changes**
   ```vue
   <!-- ❌ Incorrect - Static props -->
   <template>
     <h1>{{ staticTitle }}</h1>
   </template>

   <!-- ✅ Correct - Reactive props -->
   <template>
     <h1>{{ config.title || 'Default Title' }}</h1>
   </template>

   <script setup>
   const props = defineProps<{
     config: ComponentConfig
   }>()
   </script>
   ```

3. **Configuration Not Persisted**
   ```typescript
   // ❌ Incorrect - Changes not saved
   component.addAttributes({ title: 'New Title' });

   // ✅ Correct - Trigger change event
   component.addAttributes({ title: 'New Title' });
   component.trigger('change:attributes');
   ```

#### Responsive Design Issues

**Symptoms**:
- Component doesn't respond to device changes
- Mobile layout identical to desktop
- Responsive traits not working

**Causes and Solutions**:

1. **Missing Responsive Configuration**
   ```typescript
   // ❌ Incorrect - No responsive setup
   editor.DomComponents.addType('my-component', {
       model: {
           defaults: {
               // No responsive configuration
           }
       }
   });

   // ✅ Correct - Include responsive traits
   editor.DomComponents.addType('my-component', {
       model: {
           defaults: {
               traits: [
                 {
                   type: 'checkbox',
                   name: 'mobileHidden',
                   label: 'Hide on Mobile',
                   changeProp: 1
                 }
               ]
           }
       }
   });
   ```

2. **CSS Classes Not Applied**
   ```vue
   <!-- ❌ Incorrect - No responsive classes -->
   <template>
     <div class="component">
       Content
     </div>
   </template>

   <!-- ✅ Correct - Responsive classes -->
   <template>
     <div :class="[
       'component',
       'w-full',
       { 'hidden md:block': config.mobileHidden }
     ]">
       Content
     </div>
   </template>
   ```

#### Theme Integration Problems

**Symptoms**:
- Component doesn't inherit theme colors
- Theme changes don't apply to components
- Inconsistent styling across components

**Causes and Solutions**:

1. **Theme Variables Not Used**
   ```vue
   <!-- ❌ Incorrect - Hardcoded colors -->
   <template>
     <div style="background-color: #blue; color: #white;">
       Content
     </div>
   </template>

   <!-- ✅ Correct - Theme variables -->
   <template>
     <div :style="{
       backgroundColor: theme.colors.primary,
       color: theme.colors.text
     }">
       Content
     </div>
   </template>
   ```

2. **Theme Not Passed to Component**
   ```typescript
   // ❌ Incorrect - No theme prop
   const componentProps = {
       config: componentConfig
   };

   // ✅ Correct - Include theme
   const componentProps = {
       config: componentConfig,
       theme: currentTheme
   };
   ```

### Performance Issues

#### Slow Component Loading

**Symptoms**:
- Components take long time to appear in Block Manager
- Page builder feels sluggish when adding components
- High memory usage in browser

**Solutions**:

1. **Implement Lazy Loading**
   ```typescript
   // ✅ Lazy load component definitions
   const componentRegistry = {
     'hero-component': () => import('@/components/Hero/HeroComponent.vue'),
     'form-component': () => import('@/components/Forms/FormComponent.vue')
   };
   ```

2. **Optimize Component Previews**
   ```typescript
   // ✅ Generate lightweight previews
   const generatePreview = (component) => {
     return {
       thumbnail: generateThumbnail(component),
       metadata: getEssentialMetadata(component)
     };
   };
   ```

3. **Cache Component Metadata**
   ```php
   // ✅ Cache expensive operations
   public function getGrapeJSMetadata(): array
   {
       return Cache::remember(
           "component-metadata-{$this->id}",
           3600,
           fn() => $this->generateMetadata()
       );
   }
   ```

#### Memory Leaks

**Symptoms**:
- Browser memory usage increases over time
- Page becomes unresponsive after extended use
- Components stop responding to interactions

**Solutions**:

1. **Proper Event Cleanup**
   ```vue
   <script setup>
   import { onUnmounted } from 'vue'

   const cleanup = () => {
     // Remove event listeners
     // Clear intervals/timeouts
     // Dispose of resources
   }

   onUnmounted(cleanup)
   </script>
   ```

2. **Component Instance Management**
   ```typescript
   // ✅ Proper cleanup
   class ComponentManager {
     private instances = new Map();

     removeComponent(id: string) {
       const instance = this.instances.get(id);
       if (instance) {
         instance.destroy();
         this.instances.delete(id);
       }
     }
   }
   ```

### Debugging Tools

#### Enable Debug Mode

```typescript
// Enable GrapeJS debug mode
const editor = grapesjs.init({
  container: '#gjs',
  // ... other options
  plugins: ['gjs-blocks-basic'],
  pluginsOpts: {
    'gjs-blocks-basic': {
      // Plugin options
    }
  },
  // Enable debug logging
  log: ['info', 'warning', 'error']
});

// Component Library debug mode
window.ComponentLibraryDebug = true;
```

#### Console Debugging Commands

```javascript
// Check registered components
console.log(editor.BlockManager.getAll());

// Inspect component model
const selected = editor.getSelected();
console.log(selected.getAttributes());

// Check component traits
console.log(selected.getTraits());

// Validate component configuration
ComponentLibraryBridge.validateComponent(selected);
```

#### Error Logging

```typescript
// Enhanced error logging
class ComponentError extends Error {
  constructor(
    message: string,
    public componentId: string,
    public context: any
  ) {
    super(message);
    this.name = 'ComponentError';
  }
}

// Usage
try {
  ComponentLibraryBridge.registerComponent(config);
} catch (error) {
  console.error(new ComponentError(
    'Failed to register component',
    config.id,
    { config, error }
  ));
}
```

### Support and Resources

#### Getting Help

1. **Check Documentation**: Review this guide and API documentation
2. **Search Issues**: Look for similar problems in the issue tracker
3. **Enable Debug Mode**: Use debug tools to gather information
4. **Create Minimal Reproduction**: Isolate the problem to specific components
5. **Contact Support**: Provide debug information and reproduction steps

#### Useful Resources

- **GrapeJS Documentation**: https://grapesjs.com/docs/
- **Component Library API**: `/docs/api/component-library`
- **Theme System Guide**: `/docs/themes/integration`
- **Testing Guidelines**: `/docs/testing/components`

#### Community Support

- **Developer Forum**: Internal developer discussions
- **Slack Channel**: #component-library for real-time help
- **Code Reviews**: Request reviews for complex integrations
- **Knowledge Base**: Searchable solutions database
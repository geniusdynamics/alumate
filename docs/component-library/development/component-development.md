# Component Development Guide

## Overview

This guide covers creating custom components for the Component Library System. Components are built using Vue 3 with TypeScript and follow a standardized architecture for consistency, accessibility, and maintainability.

## Component Architecture

### Component Structure

Each component consists of several key files:

```
components/
├── hero/
│   ├── HeroBase.vue              # Base component template
│   ├── HeroIndividual.vue        # Individual alumni variant
│   ├── HeroInstitution.vue       # Institution variant
│   ├── HeroEmployer.vue          # Employer variant
│   ├── hero-schema.json          # Configuration schema
│   └── hero-config.ts            # TypeScript interfaces
```

### Base Component Template

All components extend from a base template that provides common functionality:

```vue
<template>
  <div 
    :class="componentClasses"
    :style="componentStyles"
    :aria-label="config.aria_label"
    role="region"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import type { ComponentConfig, ThemeConfig } from '@/types/components'

interface Props {
  config: ComponentConfig
  theme?: ThemeConfig
  preview?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  preview: false
})

// Theme injection for global styling
const globalTheme = inject<ThemeConfig>('theme')

const componentClasses = computed(() => {
  const baseClasses = [
    'component-base',
    `component-${props.config.category}`,
    `component-${props.config.type}`
  ]
  
  if (props.preview) {
    baseClasses.push('component-preview')
  }
  
  return baseClasses.join(' ')
})

const componentStyles = computed(() => {
  const theme = props.theme || globalTheme
  if (!theme) return {}
  
  return {
    '--primary-color': theme.colors.primary,
    '--secondary-color': theme.colors.secondary,
    '--font-family': theme.typography.font_family,
    '--border-radius': theme.borders.radius.md
  }
})
</script>
```

## Creating a New Component

### Step 1: Define the Component Schema

Create a JSON schema that defines the component's configuration structure:

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "title": "Hero Component Configuration",
  "description": "Configuration schema for hero section components",
  "properties": {
    "headline": {
      "type": "string",
      "title": "Headline",
      "description": "Main headline text",
      "maxLength": 100,
      "default": "Welcome to Our Platform"
    },
    "subheading": {
      "type": "string",
      "title": "Subheading",
      "description": "Supporting text below the headline",
      "maxLength": 200,
      "default": "Connect with thousands of alumni worldwide"
    },
    "cta_text": {
      "type": "string",
      "title": "Call-to-Action Text",
      "description": "Text for the primary action button",
      "maxLength": 50,
      "default": "Get Started"
    },
    "cta_url": {
      "type": "string",
      "title": "Call-to-Action URL",
      "description": "URL for the primary action button",
      "format": "uri",
      "default": "#"
    },
    "background_type": {
      "type": "string",
      "title": "Background Type",
      "description": "Type of background to display",
      "enum": ["image", "video", "gradient", "solid"],
      "default": "gradient"
    },
    "background_image": {
      "type": "string",
      "title": "Background Image",
      "description": "URL of background image",
      "format": "uri",
      "condition": {
        "field": "background_type",
        "value": "image"
      }
    },
    "gradient_colors": {
      "type": "array",
      "title": "Gradient Colors",
      "description": "Array of colors for gradient background",
      "items": {
        "type": "string",
        "pattern": "^#[0-9A-Fa-f]{6}$"
      },
      "minItems": 2,
      "maxItems": 4,
      "default": ["#3B82F6", "#1F2937"],
      "condition": {
        "field": "background_type",
        "value": "gradient"
      }
    },
    "text_alignment": {
      "type": "string",
      "title": "Text Alignment",
      "description": "Alignment of text content",
      "enum": ["left", "center", "right"],
      "default": "center"
    },
    "show_statistics": {
      "type": "boolean",
      "title": "Show Statistics",
      "description": "Whether to display animated statistics",
      "default": false
    },
    "statistics": {
      "type": "array",
      "title": "Statistics",
      "description": "Array of statistics to display",
      "items": {
        "type": "object",
        "properties": {
          "label": {
            "type": "string",
            "title": "Statistic Label",
            "maxLength": 50
          },
          "value": {
            "type": "number",
            "title": "Statistic Value",
            "minimum": 0
          },
          "suffix": {
            "type": "string",
            "title": "Value Suffix",
            "maxLength": 10,
            "default": ""
          }
        },
        "required": ["label", "value"]
      },
      "condition": {
        "field": "show_statistics",
        "value": true
      }
    }
  },
  "required": ["headline", "cta_text", "cta_url", "background_type"],
  "additionalProperties": false
}
```

### Step 2: Create TypeScript Interfaces

Define TypeScript interfaces for type safety:

```typescript
// types/components/hero.ts
export interface HeroConfig {
  headline: string
  subheading?: string
  cta_text: string
  cta_url: string
  background_type: 'image' | 'video' | 'gradient' | 'solid'
  background_image?: string
  gradient_colors?: string[]
  text_alignment: 'left' | 'center' | 'right'
  show_statistics: boolean
  statistics?: HeroStatistic[]
}

export interface HeroStatistic {
  label: string
  value: number
  suffix?: string
}

export interface HeroProps {
  config: HeroConfig
  theme?: ThemeConfig
  preview?: boolean
  variant?: 'individual' | 'institution' | 'employer'
}
```

### Step 3: Implement the Component

Create the Vue component with full accessibility and responsive design:

```vue
<template>
  <section 
    class="hero-component"
    :class="heroClasses"
    :style="heroStyles"
    :aria-label="config.headline"
    role="banner"
  >
    <!-- Background Layer -->
    <div class="hero-background" :class="backgroundClasses">
      <video 
        v-if="config.background_type === 'video' && config.background_video"
        :src="config.background_video"
        autoplay
        muted
        loop
        playsinline
        class="hero-video"
        :aria-label="config.video_description || 'Background video'"
      />
      <img 
        v-else-if="config.background_type === 'image' && config.background_image"
        :src="config.background_image"
        :alt="config.background_alt || ''"
        class="hero-image"
        loading="lazy"
      />
    </div>

    <!-- Content Layer -->
    <div class="hero-content" :class="contentClasses">
      <div class="hero-text" :class="textAlignmentClass">
        <h1 class="hero-headline" v-html="sanitizedHeadline" />
        <p 
          v-if="config.subheading" 
          class="hero-subheading"
          v-html="sanitizedSubheading"
        />
        
        <!-- Statistics Section -->
        <div 
          v-if="config.show_statistics && config.statistics?.length"
          class="hero-statistics"
          role="region"
          aria-label="Key statistics"
        >
          <div 
            v-for="(stat, index) in config.statistics"
            :key="index"
            class="hero-statistic"
          >
            <AnimatedCounter
              :target="stat.value"
              :suffix="stat.suffix"
              class="hero-statistic-value"
              :aria-label="`${stat.label}: ${stat.value}${stat.suffix || ''}`"
            />
            <span class="hero-statistic-label">{{ stat.label }}</span>
          </div>
        </div>

        <!-- Call-to-Action -->
        <div class="hero-actions">
          <a
            :href="config.cta_url"
            class="hero-cta-button"
            :class="ctaButtonClasses"
            :aria-label="`${config.cta_text} - ${config.cta_description || 'Primary action'}`"
            @click="trackClick('cta_button')"
          >
            {{ config.cta_text }}
          </a>
          
          <a
            v-if="config.secondary_cta_text"
            :href="config.secondary_cta_url"
            class="hero-secondary-button"
            :aria-label="`${config.secondary_cta_text} - Secondary action`"
            @click="trackClick('secondary_button')"
          >
            {{ config.secondary_cta_text }}
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, inject } from 'vue'
import DOMPurify from 'dompurify'
import AnimatedCounter from '@/components/ui/AnimatedCounter.vue'
import { useAnalytics } from '@/composables/useAnalytics'
import type { HeroProps, ThemeConfig } from '@/types/components'

const props = withDefaults(defineProps<HeroProps>(), {
  preview: false,
  variant: 'individual'
})

const theme = inject<ThemeConfig>('theme')
const { trackEvent } = useAnalytics()

// Sanitize HTML content to prevent XSS
const sanitizedHeadline = computed(() => 
  DOMPurify.sanitize(props.config.headline)
)

const sanitizedSubheading = computed(() => 
  props.config.subheading ? DOMPurify.sanitize(props.config.subheading) : ''
)

// Dynamic classes based on configuration
const heroClasses = computed(() => [
  `hero-${props.variant}`,
  `hero-bg-${props.config.background_type}`,
  {
    'hero-preview': props.preview,
    'hero-with-stats': props.config.show_statistics
  }
])

const backgroundClasses = computed(() => [
  'hero-background',
  `hero-background-${props.config.background_type}`
])

const contentClasses = computed(() => [
  'hero-content',
  `hero-content-${props.config.text_alignment}`
])

const textAlignmentClass = computed(() => 
  `text-${props.config.text_alignment}`
)

const ctaButtonClasses = computed(() => [
  'btn',
  'btn-primary',
  'btn-lg',
  {
    'btn-outline': props.variant === 'employer'
  }
])

// Dynamic styles based on theme and configuration
const heroStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.config.background_type === 'gradient' && props.config.gradient_colors) {
    const colors = props.config.gradient_colors.join(', ')
    styles['--hero-gradient'] = `linear-gradient(135deg, ${colors})`
  }
  
  if (props.config.background_type === 'solid' && props.config.background_color) {
    styles['--hero-background'] = props.config.background_color
  }
  
  if (theme) {
    styles['--hero-primary'] = theme.colors.primary
    styles['--hero-secondary'] = theme.colors.secondary
    styles['--hero-text'] = theme.colors.text
    styles['--hero-font'] = theme.typography.font_family
  }
  
  return styles
})

// Analytics tracking
const trackClick = (element: string) => {
  if (!props.preview) {
    trackEvent('click', {
      component: 'hero',
      variant: props.variant,
      element,
      headline: props.config.headline
    })
  }
}

// Track component view on mount
onMounted(() => {
  if (!props.preview) {
    trackEvent('view', {
      component: 'hero',
      variant: props.variant,
      headline: props.config.headline
    })
  }
})
</script>

<style scoped>
.hero-component {
  @apply relative min-h-screen flex items-center justify-center overflow-hidden;
  background: var(--hero-gradient, var(--hero-background, theme('colors.gray.900')));
}

.hero-background {
  @apply absolute inset-0 z-0;
}

.hero-video,
.hero-image {
  @apply w-full h-full object-cover;
}

.hero-content {
  @apply relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8;
}

.hero-text {
  @apply max-w-4xl mx-auto;
}

.text-left {
  @apply text-left;
}

.text-center {
  @apply text-center;
}

.text-right {
  @apply text-right;
}

.hero-headline {
  @apply text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6;
  font-family: var(--hero-font, theme('fontFamily.sans'));
  line-height: 1.1;
}

.hero-subheading {
  @apply text-xl sm:text-2xl text-gray-200 mb-8 leading-relaxed;
}

.hero-statistics {
  @apply grid grid-cols-2 md:grid-cols-4 gap-8 mb-12;
}

.hero-statistic {
  @apply text-center;
}

.hero-statistic-value {
  @apply text-3xl sm:text-4xl font-bold text-white block;
  color: var(--hero-primary, theme('colors.blue.400'));
}

.hero-statistic-label {
  @apply text-sm sm:text-base text-gray-300 mt-2;
}

.hero-actions {
  @apply flex flex-col sm:flex-row gap-4 justify-center items-center;
}

.hero-cta-button {
  @apply inline-flex items-center px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-200;
  @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-4 focus:ring-blue-500 focus:ring-opacity-50;
  @apply transform hover:scale-105 active:scale-95;
  background-color: var(--hero-primary, theme('colors.blue.600'));
}

.hero-secondary-button {
  @apply inline-flex items-center px-8 py-4 text-lg font-semibold rounded-lg transition-all duration-200;
  @apply border-2 border-white text-white hover:bg-white hover:text-gray-900;
  @apply focus:ring-4 focus:ring-white focus:ring-opacity-50;
}

/* Variant-specific styles */
.hero-individual .hero-headline {
  @apply text-blue-100;
}

.hero-institution .hero-headline {
  @apply text-green-100;
}

.hero-employer .hero-headline {
  @apply text-purple-100;
}

/* Responsive design */
@media (max-width: 640px) {
  .hero-component {
    @apply min-h-screen;
  }
  
  .hero-headline {
    @apply text-3xl;
  }
  
  .hero-subheading {
    @apply text-lg;
  }
  
  .hero-statistics {
    @apply grid-cols-2 gap-4 mb-8;
  }
  
  .hero-actions {
    @apply flex-col gap-3;
  }
  
  .hero-cta-button,
  .hero-secondary-button {
    @apply w-full text-center;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .hero-cta-button,
  .hero-secondary-button {
    @apply transform-none;
  }
  
  .hero-cta-button:hover,
  .hero-secondary-button:hover {
    @apply scale-100;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .hero-headline,
  .hero-subheading {
    @apply text-white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
  }
  
  .hero-cta-button {
    @apply border-2 border-white;
  }
}
</style>
```

### Step 4: Create Component Variants

Create specific variants for different audiences:

```vue
<!-- HeroIndividual.vue -->
<template>
  <HeroBase 
    :config="individualConfig" 
    :theme="theme"
    :preview="preview"
    variant="individual"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import HeroBase from './HeroBase.vue'
import type { HeroProps } from '@/types/components'

const props = defineProps<HeroProps>()

const individualConfig = computed(() => ({
  ...props.config,
  // Individual-specific defaults
  headline: props.config.headline || "Welcome Back, Alumni",
  subheading: props.config.subheading || "Reconnect with your network and advance your career",
  cta_text: props.config.cta_text || "Join Network",
  gradient_colors: props.config.gradient_colors || ["#3B82F6", "#1E40AF"]
}))
</script>
```

### Step 5: Register the Component

Add the component to the component registry:

```typescript
// composables/useComponentRegistry.ts
import { markRaw } from 'vue'
import HeroBase from '@/components/hero/HeroBase.vue'
import HeroIndividual from '@/components/hero/HeroIndividual.vue'
import HeroInstitution from '@/components/hero/HeroInstitution.vue'
import HeroEmployer from '@/components/hero/HeroEmployer.vue'

export const componentRegistry = {
  hero: {
    base: markRaw(HeroBase),
    individual: markRaw(HeroIndividual),
    institution: markRaw(HeroInstitution),
    employer: markRaw(HeroEmployer)
  }
  // ... other component categories
}

export function useComponentRegistry() {
  const getComponent = (category: string, variant: string = 'base') => {
    return componentRegistry[category]?.[variant] || componentRegistry[category]?.base
  }
  
  const registerComponent = (category: string, variant: string, component: any) => {
    if (!componentRegistry[category]) {
      componentRegistry[category] = {}
    }
    componentRegistry[category][variant] = markRaw(component)
  }
  
  return {
    getComponent,
    registerComponent,
    registry: componentRegistry
  }
}
```

## Component Testing

### Unit Tests

Create comprehensive unit tests for your component:

```typescript
// tests/components/HeroBase.test.ts
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import HeroBase from '@/components/hero/HeroBase.vue'
import type { HeroConfig } from '@/types/components'

const mockConfig: HeroConfig = {
  headline: 'Test Headline',
  subheading: 'Test Subheading',
  cta_text: 'Test CTA',
  cta_url: 'https://example.com',
  background_type: 'gradient',
  gradient_colors: ['#3B82F6', '#1F2937'],
  text_alignment: 'center',
  show_statistics: false
}

describe('HeroBase', () => {
  it('renders with basic configuration', () => {
    const wrapper = mount(HeroBase, {
      props: { config: mockConfig }
    })
    
    expect(wrapper.find('.hero-headline').text()).toBe('Test Headline')
    expect(wrapper.find('.hero-subheading').text()).toBe('Test Subheading')
    expect(wrapper.find('.hero-cta-button').text()).toBe('Test CTA')
  })
  
  it('applies correct CSS classes based on configuration', () => {
    const wrapper = mount(HeroBase, {
      props: { 
        config: mockConfig,
        variant: 'individual'
      }
    })
    
    expect(wrapper.classes()).toContain('hero-individual')
    expect(wrapper.classes()).toContain('hero-bg-gradient')
  })
  
  it('renders statistics when enabled', () => {
    const configWithStats = {
      ...mockConfig,
      show_statistics: true,
      statistics: [
        { label: 'Alumni', value: 10000, suffix: '+' },
        { label: 'Companies', value: 500 }
      ]
    }
    
    const wrapper = mount(HeroBase, {
      props: { config: configWithStats }
    })
    
    expect(wrapper.find('.hero-statistics').exists()).toBe(true)
    expect(wrapper.findAll('.hero-statistic')).toHaveLength(2)
  })
  
  it('handles click events and analytics tracking', async () => {
    const mockTrackEvent = vi.fn()
    vi.mock('@/composables/useAnalytics', () => ({
      useAnalytics: () => ({ trackEvent: mockTrackEvent })
    }))
    
    const wrapper = mount(HeroBase, {
      props: { config: mockConfig }
    })
    
    await wrapper.find('.hero-cta-button').trigger('click')
    
    expect(mockTrackEvent).toHaveBeenCalledWith('click', {
      component: 'hero',
      variant: 'individual',
      element: 'cta_button',
      headline: 'Test Headline'
    })
  })
  
  it('sanitizes HTML content to prevent XSS', () => {
    const maliciousConfig = {
      ...mockConfig,
      headline: '<script>alert("xss")</script>Safe Headline',
      subheading: '<img src="x" onerror="alert(\'xss\')" />Safe Subheading'
    }
    
    const wrapper = mount(HeroBase, {
      props: { config: maliciousConfig }
    })
    
    expect(wrapper.find('.hero-headline').html()).not.toContain('<script>')
    expect(wrapper.find('.hero-subheading').html()).not.toContain('onerror')
    expect(wrapper.find('.hero-headline').text()).toContain('Safe Headline')
  })
  
  it('applies accessibility attributes correctly', () => {
    const wrapper = mount(HeroBase, {
      props: { config: mockConfig }
    })
    
    expect(wrapper.attributes('role')).toBe('banner')
    expect(wrapper.attributes('aria-label')).toBe('Test Headline')
    expect(wrapper.find('.hero-cta-button').attributes('aria-label')).toContain('Test CTA')
  })
  
  it('responds to theme changes', () => {
    const mockTheme = {
      colors: {
        primary: '#FF0000',
        secondary: '#00FF00',
        text: '#000000'
      },
      typography: {
        font_family: 'Arial'
      }
    }
    
    const wrapper = mount(HeroBase, {
      props: { config: mockConfig, theme: mockTheme }
    })
    
    const styles = wrapper.attributes('style')
    expect(styles).toContain('--hero-primary: #FF0000')
    expect(styles).toContain('--hero-font: Arial')
  })
})
```

### Integration Tests

Test component integration with the broader system:

```typescript
// tests/integration/ComponentSystem.test.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createApp } from 'vue'
import ComponentRenderer from '@/components/ComponentRenderer.vue'
import { useComponentRegistry } from '@/composables/useComponentRegistry'

describe('Component System Integration', () => {
  it('renders components dynamically from registry', () => {
    const { getComponent } = useComponentRegistry()
    const HeroComponent = getComponent('hero', 'individual')
    
    expect(HeroComponent).toBeDefined()
    
    const wrapper = mount(ComponentRenderer, {
      props: {
        component: {
          category: 'hero',
          variant: 'individual',
          config: {
            headline: 'Dynamic Hero',
            cta_text: 'Click Me',
            cta_url: '#',
            background_type: 'gradient',
            text_alignment: 'center',
            show_statistics: false
          }
        }
      }
    })
    
    expect(wrapper.find('.hero-headline').text()).toBe('Dynamic Hero')
  })
  
  it('applies themes consistently across components', () => {
    const app = createApp({})
    app.provide('theme', {
      colors: { primary: '#123456' },
      typography: { font_family: 'TestFont' }
    })
    
    const wrapper = mount(ComponentRenderer, {
      props: {
        component: {
          category: 'hero',
          variant: 'individual',
          config: { /* config */ }
        }
      },
      global: {
        plugins: [app]
      }
    })
    
    expect(wrapper.attributes('style')).toContain('--hero-primary: #123456')
  })
})
```

## Best Practices

### Performance Optimization

1. **Lazy Loading**: Use dynamic imports for component variants
2. **Image Optimization**: Implement responsive images with WebP support
3. **Bundle Splitting**: Separate component code from main bundle
4. **Caching**: Cache component configurations and rendered output

### Accessibility Guidelines

1. **Semantic HTML**: Use proper HTML elements and ARIA attributes
2. **Keyboard Navigation**: Ensure all interactive elements are keyboard accessible
3. **Screen Readers**: Provide descriptive labels and announcements
4. **Color Contrast**: Maintain WCAG 2.1 AA contrast ratios
5. **Motion Preferences**: Respect user motion preferences

### Security Considerations

1. **Input Sanitization**: Always sanitize user-provided HTML content
2. **XSS Prevention**: Use DOMPurify for HTML sanitization
3. **URL Validation**: Validate and sanitize URLs before use
4. **Content Security Policy**: Implement CSP headers for additional protection

### Code Quality

1. **TypeScript**: Use strict TypeScript for type safety
2. **ESLint**: Follow established linting rules
3. **Testing**: Maintain high test coverage (>90%)
4. **Documentation**: Document all props, events, and methods
5. **Version Control**: Use semantic versioning for component updates

## Deployment and Distribution

### Component Packaging

Components can be packaged for distribution:

```bash
# Build component library
npm run build:components

# Generate component documentation
npm run docs:generate

# Package for distribution
npm run package:components
```

### Version Management

Follow semantic versioning for component updates:

- **Major**: Breaking changes to component API
- **Minor**: New features, backward compatible
- **Patch**: Bug fixes, no API changes

### Component Registry Updates

When deploying new components:

1. Update component registry
2. Run database migrations for new schemas
3. Update API documentation
4. Deploy frontend assets
5. Clear component caches

This comprehensive guide provides everything needed to create, test, and deploy custom components within the Component Library System.
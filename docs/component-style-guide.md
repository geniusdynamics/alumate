# Component Style Guide

## Overview

This style guide defines the design system and component standards for the Modern Alumni Platform. It ensures consistency, accessibility, and maintainability across all UI components.

## Design Principles

### 1. Accessibility First
- All components must meet WCAG 2.1 AA standards
- Keyboard navigation support is mandatory
- Screen reader compatibility is required
- Color contrast ratios must exceed 4.5:1

### 2. Mobile-First Design
- Components must work on all screen sizes
- Touch targets must be at least 44px
- Responsive design is required
- Progressive enhancement approach

### 3. Performance Optimized
- Components should be lightweight
- Lazy loading where appropriate
- Minimal bundle impact
- Efficient re-rendering

### 4. Consistent Experience
- Unified visual language
- Predictable interactions
- Standardized spacing and typography
- Coherent color usage

## Color System

### Primary Colors
```css
/* Blue Scale */
--color-blue-50: #eff6ff;
--color-blue-100: #dbeafe;
--color-blue-200: #bfdbfe;
--color-blue-300: #93c5fd;
--color-blue-400: #60a5fa;
--color-blue-500: #3b82f6;
--color-blue-600: #2563eb;
--color-blue-700: #1d4ed8;
--color-blue-800: #1e40af;
--color-blue-900: #1e3a8a;
```

### Semantic Colors
```css
/* Success */
--color-success: #10b981;
--color-success-light: #d1fae5;
--color-success-dark: #047857;

/* Warning */
--color-warning: #f59e0b;
--color-warning-light: #fef3c7;
--color-warning-dark: #d97706;

/* Error */
--color-error: #ef4444;
--color-error-light: #fee2e2;
--color-error-dark: #dc2626;

/* Info */
--color-info: #3b82f6;
--color-info-light: #dbeafe;
--color-info-dark: #1d4ed8;
```

### Neutral Colors
```css
/* Gray Scale */
--color-gray-50: #f9fafb;
--color-gray-100: #f3f4f6;
--color-gray-200: #e5e7eb;
--color-gray-300: #d1d5db;
--color-gray-400: #9ca3af;
--color-gray-500: #6b7280;
--color-gray-600: #4b5563;
--color-gray-700: #374151;
--color-gray-800: #1f2937;
--color-gray-900: #111827;
```

## Typography

### Font Families
```css
--font-sans: 'Inter', system-ui, -apple-system, sans-serif;
--font-mono: 'JetBrains Mono', 'Fira Code', monospace;
```

### Font Sizes
```css
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */
--text-4xl: 2.25rem;   /* 36px */
```

### Font Weights
```css
--font-light: 300;
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### Line Heights
```css
--leading-tight: 1.25;
--leading-snug: 1.375;
--leading-normal: 1.5;
--leading-relaxed: 1.625;
--leading-loose: 2;
```

## Spacing System

### Base Unit: 4px (0.25rem)

```css
--space-0: 0;
--space-1: 0.25rem;  /* 4px */
--space-2: 0.5rem;   /* 8px */
--space-3: 0.75rem;  /* 12px */
--space-4: 1rem;     /* 16px */
--space-5: 1.25rem;  /* 20px */
--space-6: 1.5rem;   /* 24px */
--space-8: 2rem;     /* 32px */
--space-10: 2.5rem;  /* 40px */
--space-12: 3rem;    /* 48px */
--space-16: 4rem;    /* 64px */
--space-20: 5rem;    /* 80px */
--space-24: 6rem;    /* 96px */
```

## Border Radius

```css
--radius-none: 0;
--radius-sm: 0.125rem;   /* 2px */
--radius-base: 0.25rem;  /* 4px */
--radius-md: 0.375rem;   /* 6px */
--radius-lg: 0.5rem;     /* 8px */
--radius-xl: 0.75rem;    /* 12px */
--radius-2xl: 1rem;      /* 16px */
--radius-full: 9999px;
```

## Shadows

```css
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
--shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
```

## Component Standards

### BaseButton

#### Props Interface
```typescript
interface BaseButtonProps {
  // Element type
  tag?: 'button' | 'a' | 'router-link'
  type?: 'button' | 'submit' | 'reset'
  href?: string
  to?: string | object
  
  // Appearance
  variant?: 'primary' | 'secondary' | 'tertiary' | 'danger' | 'success' | 'warning' | 'ghost'
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  fullWidth?: boolean
  
  // State
  disabled?: boolean
  loading?: boolean
  
  // Icons
  leftIcon?: Component
  rightIcon?: Component
  iconOnly?: boolean
  
  // Badge
  badge?: string | number
  
  // Accessibility
  ariaLabel?: string
  ariaDescribedby?: string
  ariaExpanded?: boolean | string
  ariaControls?: string
  ariaPressed?: boolean | string
}
```

#### Usage Examples
```vue
<!-- Primary button -->
<BaseButton variant="primary" size="md">
  Save Changes
</BaseButton>

<!-- Button with icon -->
<BaseButton variant="secondary" :left-icon="PlusIcon">
  Add Item
</BaseButton>

<!-- Loading state -->
<BaseButton variant="primary" :loading="isSubmitting">
  Submit Form
</BaseButton>

<!-- Icon-only button -->
<BaseButton 
  variant="ghost" 
  icon-only 
  :left-icon="XMarkIcon"
  aria-label="Close dialog"
/>
```

### BaseInput

#### Props Interface
```typescript
interface BaseInputProps {
  // Input type and behavior
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'textarea'
  modelValue?: string | number
  placeholder?: string
  
  // Validation
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  error?: string | boolean
  
  // Attributes
  autocomplete?: string
  autocapitalize?: string
  autocorrect?: string
  spellcheck?: boolean
  min?: number | string
  max?: number | string
  step?: number | string
  minlength?: number
  maxlength?: number
  pattern?: string
  
  // Textarea specific
  rows?: number
  cols?: number
  
  // Appearance
  size?: 'sm' | 'md' | 'lg'
  leftIcon?: Component
  rightIcon?: Component
  clearable?: boolean
  loading?: boolean
  
  // Labels and help
  label?: string
  helpText?: string
  showCharacterCount?: boolean
  
  // Accessibility
  ariaLabel?: string
  ariaDescribedby?: string
}
```

#### Usage Examples
```vue
<!-- Basic input -->
<BaseInput
  v-model="email"
  type="email"
  label="Email Address"
  placeholder="Enter your email"
  required
/>

<!-- Input with validation -->
<BaseInput
  v-model="password"
  type="password"
  label="Password"
  :error="passwordError"
  help-text="Must be at least 8 characters"
/>

<!-- Input with icon -->
<BaseInput
  v-model="search"
  type="search"
  placeholder="Search..."
  :left-icon="MagnifyingGlassIcon"
  clearable
/>
```

### BaseModal

#### Props Interface
```typescript
interface BaseModalProps {
  // Visibility
  show?: boolean
  
  // Appearance
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  centered?: boolean
  
  // Behavior
  closable?: boolean
  closeOnBackdrop?: boolean
  closeOnEscape?: boolean
  persistent?: boolean
  
  // Content
  title?: string
  description?: string
  
  // Accessibility
  ariaLabel?: string
  ariaDescribedby?: string
  role?: string
}
```

#### Usage Examples
```vue
<!-- Basic modal -->
<BaseModal
  :show="showModal"
  title="Confirm Action"
  @close="showModal = false"
>
  <p>Are you sure you want to delete this item?</p>
  
  <template #footer>
    <BaseButton variant="tertiary" @click="showModal = false">
      Cancel
    </BaseButton>
    <BaseButton variant="danger" @click="confirmDelete">
      Delete
    </BaseButton>
  </template>
</BaseModal>
```

## Loading States

### LoadingSpinner Component

```vue
<template>
  <div 
    class="loading-spinner"
    :class="sizeClasses"
    role="status"
    :aria-label="ariaLabel || 'Loading'"
  >
    <svg 
      class="animate-spin" 
      fill="none" 
      viewBox="0 0 24 24"
      :class="colorClasses"
    >
      <circle 
        class="opacity-25" 
        cx="12" 
        cy="12" 
        r="10" 
        stroke="currentColor" 
        stroke-width="4"
      />
      <path 
        class="opacity-75" 
        fill="currentColor" 
        d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      />
    </svg>
    <span v-if="text" class="ml-2 text-sm">{{ text }}</span>
  </div>
</template>
```

### SkeletonLoader Component

```vue
<template>
  <div 
    class="skeleton-loader animate-pulse"
    :class="[shapeClasses, sizeClasses]"
    :style="customStyles"
    role="status"
    aria-label="Loading content"
  >
    <span class="sr-only">Loading...</span>
  </div>
</template>
```

## Accessibility Guidelines

### ARIA Patterns

#### Button Pattern
```vue
<button
  :aria-label="accessibleName"
  :aria-describedby="hasDescription ? descriptionId : undefined"
  :aria-expanded="isExpandable ? isExpanded : undefined"
  :aria-controls="controlsId"
  :aria-pressed="isToggle ? isPressed : undefined"
  :disabled="disabled"
>
  Button Content
</button>
```

#### Form Pattern
```vue
<div class="form-field">
  <label :for="inputId" class="form-label">
    {{ label }}
    <span v-if="required" aria-label="required">*</span>
  </label>
  
  <input
    :id="inputId"
    :aria-describedby="helpTextId"
    :aria-invalid="hasError"
    :aria-required="required"
    :required="required"
  />
  
  <p v-if="helpText" :id="helpTextId" class="form-help">
    {{ helpText }}
  </p>
  
  <p v-if="error" :id="errorId" class="form-error" role="alert">
    {{ error }}
  </p>
</div>
```

#### Modal Pattern
```vue
<div
  class="modal-backdrop"
  @click="closeOnBackdrop && $emit('close')"
>
  <div
    class="modal-content"
    role="dialog"
    :aria-modal="true"
    :aria-labelledby="titleId"
    :aria-describedby="descriptionId"
    @click.stop
  >
    <h2 :id="titleId" class="modal-title">{{ title }}</h2>
    <div :id="descriptionId" class="modal-body">
      <slot />
    </div>
    <div class="modal-footer">
      <slot name="footer" />
    </div>
  </div>
</div>
```

### Keyboard Navigation

#### Tab Order
- Logical tab sequence
- Skip links for main content
- Focus trapping in modals
- Visible focus indicators

#### Keyboard Shortcuts
- `Tab` / `Shift+Tab`: Navigate between focusable elements
- `Enter` / `Space`: Activate buttons and links
- `Escape`: Close modals and dropdowns
- `Arrow Keys`: Navigate within components (tabs, menus)
- `Home` / `End`: Jump to first/last item

### Screen Reader Support

#### Semantic HTML
```vue
<!-- Use semantic elements -->
<main>
  <article>
    <header>
      <h1>Page Title</h1>
    </header>
    <section>
      <h2>Section Title</h2>
      <p>Content...</p>
    </section>
  </article>
</main>
```

#### ARIA Live Regions
```vue
<!-- For dynamic content updates -->
<div aria-live="polite" aria-atomic="true">
  {{ statusMessage }}
</div>

<!-- For urgent announcements -->
<div aria-live="assertive" role="alert">
  {{ errorMessage }}
</div>
```

## Performance Guidelines

### Bundle Size
- Components should be tree-shakeable
- Use dynamic imports for large components
- Minimize external dependencies
- Optimize SVG icons

### Rendering Performance
- Use `v-memo` for expensive computations
- Implement virtual scrolling for large lists
- Lazy load images and components
- Debounce user inputs

### Memory Management
- Clean up event listeners
- Cancel pending requests
- Clear timers and intervals
- Remove DOM references

## Testing Standards

### Unit Tests
```javascript
// Component prop validation
test('validates required props', () => {
  expect(() => {
    mount(BaseButton, { props: {} })
  }).not.toThrow()
})

// Accessibility testing
test('has proper ARIA attributes', () => {
  const wrapper = mount(BaseButton, {
    props: { ariaLabel: 'Test button' }
  })
  expect(wrapper.attributes('aria-label')).toBe('Test button')
})

// Keyboard navigation
test('handles keyboard events', async () => {
  const wrapper = mount(BaseButton)
  await wrapper.trigger('keydown', { key: 'Enter' })
  expect(wrapper.emitted('click')).toBeTruthy()
})
```

### Accessibility Tests
```javascript
import { axe, toHaveNoViolations } from 'jest-axe'

expect.extend(toHaveNoViolations)

test('should not have accessibility violations', async () => {
  const wrapper = mount(Component)
  const results = await axe(wrapper.element)
  expect(results).toHaveNoViolations()
})
```

## Documentation Standards

### Component Documentation
Each component should include:
- Purpose and use cases
- Props interface with types
- Event emissions
- Slot definitions
- Usage examples
- Accessibility notes
- Browser support

### Code Comments
```vue
<template>
  <!-- Main container with proper ARIA role -->
  <div role="tablist" aria-label="Navigation tabs">
    <!-- Individual tab buttons -->
    <button
      v-for="tab in tabs"
      :key="tab.id"
      role="tab"
      :aria-selected="activeTab === tab.id"
      :aria-controls="`panel-${tab.id}`"
      @click="selectTab(tab.id)"
    >
      {{ tab.label }}
    </button>
  </div>
</template>
```

## Migration Guide

### From Legacy Components
1. Update prop names to match new interface
2. Add required accessibility attributes
3. Update styling to use design tokens
4. Add proper TypeScript types
5. Update tests for new behavior

### Breaking Changes
- Document all breaking changes
- Provide migration scripts where possible
- Maintain backward compatibility when feasible
- Clear deprecation timeline

This style guide ensures consistent, accessible, and maintainable components across the Modern Alumni Platform.
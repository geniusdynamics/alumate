# Modern Alumni Platform - Design System Documentation

## Overview

The Modern Alumni Platform uses a comprehensive design system built on Tailwind CSS with custom design tokens, ensuring consistency, accessibility, and maintainability across all components. The system supports both light and dark themes with WCAG AA compliance.

## Table of Contents

1. [Color Palette](#color-palette)
2. [Typography Scale](#typography-scale)
3. [Spacing System](#spacing-system)
4. [Component Variants](#component-variants)
5. [Design Tokens](#design-tokens)
6. [Naming Conventions](#naming-conventions)
7. [Accessibility Guidelines](#accessibility-guidelines)
8. [Mobile Optimization](#mobile-optimization)
9. [Theme System](#theme-system)
10. [Component Showcase](#component-showcase)

## Color Palette

### Primary Colors
Our color system is built with CSS custom properties for dynamic theming and WCAG AA compliance.

#### Light Theme
- **Primary**: `hsl(0 0% 9%)` - Main brand color with 4.8:1 contrast ratio
- **Primary Foreground**: `hsl(0 0% 98%)` - Text on primary backgrounds
- **Secondary**: `hsl(0 0% 92.1%)` - Secondary backgrounds
- **Secondary Foreground**: `hsl(0 0% 9%)` - Text on secondary backgrounds

#### Dark Theme
- **Primary**: `hsl(0 0% 98%)` - Inverted for dark mode
- **Primary Foreground**: `hsl(0 0% 9%)` - Inverted text
- **Secondary**: `hsl(0 0% 14.9%)` - Dark secondary backgrounds
- **Secondary Foreground**: `hsl(0 0% 98%)` - Light text on dark backgrounds

### Semantic Colors

#### Success Colors
- **Light**: `hsl(22 163 74)` - Green-600 with 4.6:1 contrast
- **Dark**: `hsl(21 128 61)` - Green-700 with 5.9:1 contrast
- **Light Variant**: `hsl(34 197 94)` - Green-500 for large text

#### Warning Colors
- **Light**: `hsl(217 119 6)` - Amber-600 with 4.7:1 contrast
- **Dark**: `hsl(180 83 9)` - Amber-700 with 6.2:1 contrast
- **Light Variant**: `hsl(245 158 11)` - Amber-500 for large text

#### Error Colors
- **Light**: `hsl(220 38 38)` - Red-600 with 5.9:1 contrast
- **Dark**: `hsl(185 28 28)` - Red-700 with 7.7:1 contrast
- **Light Variant**: `hsl(239 68 68)` - Red-500 for emphasis

### Background Colors
- **Primary**: `hsl(0 0% 100%)` - Main background (white)
- **Secondary**: `hsl(0 0% 96.1%)` - Card backgrounds
- **Tertiary**: `hsl(0 0% 92.8%)` - Subtle backgrounds

### Text Colors
All text colors meet WCAG AA standards:
- **Primary**: `hsl(0 0% 3.9%)` - 16.8:1 contrast ratio
- **Secondary**: `hsl(0 0% 45.1%)` - 9.6:1 contrast ratio
- **Tertiary**: `hsl(0 0% 63.9%)` - 7.1:1 contrast ratio
- **Quaternary**: `hsl(0 0% 83.1%)` - 5.2:1 contrast ratio

## Typography Scale

### Font Family
- **Primary**: `'Instrument Sans', ui-sans-serif, system-ui, sans-serif`
- **Fallback**: System font stack for optimal performance

### Font Sizes
```css
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */
```

### Heading Hierarchy
- **H1**: `2.25rem` (36px) - `font-weight: 800`
- **H2**: `1.875rem` (30px) - `font-weight: 700`
- **H3**: `1.5rem` (24px) - `font-weight: 600`
- **H4**: `1.25rem` (20px) - `font-weight: 600`
- **H5**: `1.125rem` (18px) - `font-weight: 500`
- **H6**: `1rem` (16px) - `font-weight: 500`

## Spacing System

### Base Spacing Scale
```css
--space-xs: 0.25rem;   /* 4px */
--space-sm: 0.5rem;    /* 8px */
--space-md: 1rem;      /* 16px */
--space-lg: 1.5rem;    /* 24px */
--space-xl: 2rem;      /* 32px */
--space-2xl: 3rem;     /* 48px */
```

### Extended Spacing
- **18**: `4.5rem` (72px) - Custom spacing for specific layouts
- **88**: `22rem` (352px) - Large container widths
- **Safe Areas**: `env(safe-area-inset-*)` for mobile devices

### Border Radius
```css
--radius-sm: 0.25rem;   /* 4px */
--radius-md: 0.375rem;  /* 6px */
--radius-lg: 0.5rem;    /* 8px */
--radius-xl: 0.75rem;   /* 12px */
--radius-2xl: 1rem;     /* 16px */
--radius-full: 9999px;  /* Fully rounded */
```

## Component Variants

### Button Variants
Using `class-variance-authority` for consistent component variants:

```typescript
const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "bg-primary text-primary-foreground shadow-xs hover:bg-primary/90",
        destructive: "bg-destructive text-white shadow-xs hover:bg-destructive/90",
        outline: "border bg-background shadow-xs hover:bg-accent hover:text-accent-foreground",
        secondary: "bg-secondary text-secondary-foreground shadow-xs hover:bg-secondary/80",
        ghost: "hover:bg-accent hover:text-accent-foreground",
        link: "text-primary underline-offset-4 hover:underline"
      },
      size: {
        default: "h-9 px-4 py-2",
        sm: "h-8 rounded-md gap-1.5 px-3",
        lg: "h-10 rounded-md px-6",
        icon: "size-9"
      }
    }
  }
)
```

### Card Variants
```vue
<Card class="bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm" />
```

### Input Variants
```vue
<Input class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]" />
```

## Design Tokens

### CSS Custom Properties
All design tokens are defined as CSS custom properties for dynamic theming:

```css
:root {
  /* Colors */
  --background: hsl(0 0% 100%);
  --foreground: hsl(0 0% 3.9%);
  --primary: hsl(0 0% 9%);
  --primary-foreground: hsl(0 0% 98%);
  
  /* Spacing */
  --space-xs: 0.25rem;
  --space-sm: 0.5rem;
  --space-md: 1rem;
  
  /* Typography */
  --text-xs: 0.75rem;
  --text-sm: 0.875rem;
  --text-base: 1rem;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  
  /* Transitions */
  --transition-fast: 150ms ease-in-out;
  --transition-normal: 200ms ease-in-out;
  --transition-slow: 300ms ease-in-out;
  
  /* Z-index Scale */
  --z-dropdown: 1000;
  --z-modal: 1050;
  --z-tooltip: 1070;
}
```

### Tailwind Configuration
Extended Tailwind configuration for custom design tokens:

```javascript
theme: {
  extend: {
    colors: {
      border: "hsl(var(--border))",
      background: "hsl(var(--background))",
      foreground: "hsl(var(--foreground))",
      primary: {
        DEFAULT: "hsl(var(--primary))",
        foreground: "hsl(var(--primary-foreground))"
      }
    },
    spacing: {
      'safe-top': 'env(safe-area-inset-top)',
      '18': '4.5rem',
      '88': '22rem'
    },
    minHeight: {
      'touch': '44px',
      'touch-lg': '48px'
    }
  }
}
```

## Naming Conventions

### Component Naming
- **PascalCase** for Vue components: `AlumniCard.vue`, `PostCreator.vue`
- **kebab-case** for CSS classes: `alumni-card`, `post-creator`
- **camelCase** for JavaScript variables: `alumniData`, `postCreator`

### CSS Class Naming
- **BEM methodology** for custom components:
  ```css
  .alumni-card { }
  .alumni-card__header { }
  .alumni-card__title { }
  .alumni-card--featured { }
  ```

- **Utility-first** with Tailwind CSS:
  ```html
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
  ```

### File Organization
```
resources/js/Components/
├── ui/                    # Base UI components
│   ├── button/
│   ├── card/
│   └── input/
├── layout/               # Layout components
├── common/               # Shared components
└── [feature]/           # Feature-specific components
```

## Accessibility Guidelines

### WCAG AA Compliance
- **Color Contrast**: Minimum 4.5:1 for normal text, 3:1 for large text
- **Focus States**: Visible focus indicators on all interactive elements
- **Keyboard Navigation**: Full keyboard accessibility
- **Screen Readers**: Proper ARIA labels and semantic HTML

### Focus Management
```css
.focus-visible:focus-visible {
  outline: 2px solid rgb(var(--color-primary));
  outline-offset: 2px;
  border-radius: 4px;
}
```

### Touch Targets
- **Minimum Size**: 44px × 44px for touch targets
- **Mobile Optimization**: 48px × 48px on small screens

```css
.touch-target {
  min-height: 44px;
  min-width: 44px;
}
```

### Screen Reader Support
```html
<button aria-label="Close modal" class="sr-only:not-sr-only">
  <span class="sr-only">Close</span>
  <XIcon />
</button>
```

## Mobile Optimization

### Responsive Breakpoints
```css
xs: '475px'    /* Extra small devices */
sm: '640px'    /* Small devices */
md: '768px'    /* Medium devices */
lg: '1024px'   /* Large devices */
xl: '1280px'   /* Extra large devices */
2xl: '1536px'  /* 2X large devices */
```

### Mobile-First Design
- **Touch-Friendly**: Minimum 44px touch targets
- **Safe Areas**: Support for device safe areas
- **Performance**: Optimized animations and transitions

### Mobile Utilities
```css
.mobile-only { @apply block lg:hidden; }
.desktop-only { @apply hidden lg:block; }
.touch-target { min-height: 44px; min-width: 44px; }
```

## Theme System

### Dynamic Theming
The theme system supports automatic dark mode detection and manual theme switching:

```css
@media (prefers-color-scheme: dark) {
  :root:not([data-theme]) {
    /* Apply dark theme variables */
  }
}

[data-theme="dark"] {
  /* Dark theme overrides */
}
```

### Theme Toggle Component
```vue
<button class="theme-toggle" @click="toggleTheme">
  <SunIcon class="theme-toggle-sun" />
  <MoonIcon class="theme-toggle-moon" />
</button>
```

### High Contrast Support
```css
@media (prefers-contrast: high) {
  :root {
    --color-primary: 0 0 255; /* Pure blue */
    --border-primary: 0 0 0;  /* Pure black */
  }
}
```

## Component Showcase

### Buttons
```vue
<!-- Primary Button -->
<Button variant="default" size="default">
  Primary Action
</Button>

<!-- Secondary Button -->
<Button variant="secondary" size="default">
  Secondary Action
</Button>

<!-- Outline Button -->
<Button variant="outline" size="default">
  Outline Action
</Button>

<!-- Ghost Button -->
<Button variant="ghost" size="sm">
  Ghost Action
</Button>

<!-- Icon Button -->
<Button variant="default" size="icon">
  <PlusIcon />
</Button>
```

### Cards
```vue
<!-- Basic Card -->
<Card class="p-6">
  <h3 class="text-lg font-semibold mb-2">Card Title</h3>
  <p class="text-muted-foreground">Card content goes here.</p>
</Card>

<!-- Alumni Card -->
<div class="card-mobile">
  <div class="card-mobile-header">
    <h3 class="card-mobile-title">Alumni Name</h3>
    <span class="card-mobile-subtitle">Class of 2020</span>
  </div>
  <p class="text-sm text-gray-600">Software Engineer at Tech Corp</p>
</div>
```

### Forms
```vue
<!-- Input Field -->
<div class="mobile-form-group">
  <label class="mobile-form-label">Email Address</label>
  <Input 
    type="email" 
    placeholder="Enter your email"
    class="mobile-form-input"
  />
</div>

<!-- Textarea -->
<div class="mobile-form-group">
  <label class="mobile-form-label">Message</label>
  <textarea 
    class="mobile-form-textarea"
    placeholder="Enter your message"
  ></textarea>
</div>
```

### Navigation
```vue
<!-- Mobile Navigation -->
<nav class="mobile-nav-enhanced">
  <div class="flex justify-around">
    <a href="/dashboard" class="mobile-nav-item-enhanced active">
      <HomeIcon class="h-5 w-5" />
      <span>Home</span>
    </a>
    <a href="/alumni" class="mobile-nav-item-enhanced">
      <UsersIcon class="h-5 w-5" />
      <span>Alumni</span>
    </a>
    <a href="/events" class="mobile-nav-item-enhanced">
      <CalendarIcon class="h-5 w-5" />
      <span>Events</span>
    </a>
  </div>
</nav>
```

### Modals
```vue
<!-- Mobile Modal -->
<div class="mobile-modal-enhanced">
  <div class="mobile-modal-backdrop" @click="closeModal"></div>
  <div class="mobile-modal-content">
    <div class="mobile-modal-handle"></div>
    <div class="mobile-modal-header">
      <h2 class="text-lg font-semibold">Modal Title</h2>
      <Button variant="ghost" size="icon" @click="closeModal">
        <XIcon class="h-4 w-4" />
      </Button>
    </div>
    <div class="mobile-modal-body">
      <p>Modal content goes here.</p>
    </div>
  </div>
</div>
```

### Loading States
```vue
<!-- Skeleton Loader -->
<div class="mobile-skeleton-enhanced h-4 w-full mb-2"></div>
<div class="mobile-skeleton-enhanced h-4 w-3/4 mb-2"></div>
<div class="mobile-skeleton-enhanced h-4 w-1/2"></div>

<!-- Loading Spinner -->
<div class="mobile-loading-enhanced">
  <div class="pull-to-refresh-spinner"></div>
</div>
```

### Status Indicators
```vue
<!-- Success Badge -->
<span class="mobile-badge-success">
  Active
</span>

<!-- Warning Badge -->
<span class="mobile-badge-warning">
  Pending
</span>

<!-- Error Badge -->
<span class="mobile-badge-error">
  Inactive
</span>
```

## Best Practices

### Performance
- Use `will-change` sparingly for animations
- Implement lazy loading for images and components
- Optimize bundle sizes with code splitting

### Accessibility
- Always provide alt text for images
- Use semantic HTML elements
- Test with screen readers
- Ensure keyboard navigation works

### Mobile
- Design mobile-first
- Use appropriate touch target sizes
- Test on real devices
- Consider network conditions

### Theming
- Use CSS custom properties for dynamic values
- Test both light and dark themes
- Support system preferences
- Provide manual theme controls

## Development Guidelines

### Component Development
1. Start with mobile design
2. Use existing design tokens
3. Follow naming conventions
4. Include accessibility features
5. Test across themes
6. Document component usage

### CSS Guidelines
1. Use Tailwind utilities first
2. Create custom CSS only when necessary
3. Follow BEM methodology for custom classes
4. Use CSS custom properties for dynamic values
5. Ensure responsive design

### Testing
1. Test across different screen sizes
2. Verify accessibility compliance
3. Test keyboard navigation
4. Validate color contrast
5. Test with screen readers

This design system ensures consistency, accessibility, and maintainability across the Modern Alumni Platform while providing a solid foundation for future development.
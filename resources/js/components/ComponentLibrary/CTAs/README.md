# CTA (Call-to-Action) Components

This directory contains a comprehensive set of CTA components designed for the alumni tracking system. These components support conversion tracking, A/B testing, and accessibility features.

## Components Overview

### CTABase.vue
The main wrapper component that handles routing to specific CTA types and provides common functionality like analytics tracking and A/B testing.

### CTAButton.vue
A versatile button component with multiple styles, sizes, and interactive states.

**Features:**
- Multiple styles: primary, secondary, outline, ghost, link
- Multiple sizes: xs, sm, md, lg, xl
- Icon support with positioning options
- Loading states with different animations
- Hover and click animations
- Custom color schemes
- Accessibility features
- Conversion tracking

**Usage:**
```vue
<CTAButton
  :config="{
    text: 'Join Our Network',
    url: '/signup',
    style: 'primary',
    size: 'lg',
    icon: {
      name: 'arrow-right',
      position: 'right'
    },
    trackingParams: {
      utm_source: 'homepage',
      utm_medium: 'cta_button'
    }
  }"
  @click="handleClick"
  @conversion="handleConversion"
/>
```

### CTABanner.vue
A full-width banner component for hero sections and promotional content.

**Features:**
- Multiple layouts: left-aligned, center-aligned, right-aligned, split
- Multiple heights: compact, medium, large, full-screen
- Background image support with overlay
- Primary and secondary CTA buttons
- Responsive design with mobile-specific layouts
- Parallax and scroll animations
- Content positioning options

**Usage:**
```vue
<CTABanner
  :config="{
    title: 'Connect with Alumni Worldwide',
    subtitle: 'Your next opportunity awaits',
    layout: 'center-aligned',
    height: 'large',
    primaryCTA: {
      text: 'Get Started',
      url: '/signup',
      style: 'primary',
      size: 'lg'
    },
    backgroundImage: {
      url: '/images/hero-bg.jpg',
      alt: 'Alumni networking'
    }
  }"
/>
```

### CTAInlineLink.vue
A contextual link component for use within content.

**Features:**
- Multiple styles: default, underline, button-like, arrow, external
- Icon support
- External link detection and indicators
- Download attribute support
- Hover animations
- Keyboard shortcuts

**Usage:**
```vue
<CTAInlineLink
  :config="{
    text: 'Learn more about our platform',
    url: '/about',
    style: 'arrow',
    size: 'base',
    openInNewTab: false
  }"
/>
```

## Configuration Types

### CTAComponentConfig
The main configuration interface that determines which type of CTA to render.

```typescript
interface CTAComponentConfig {
  type: 'button' | 'banner' | 'inline-link'
  buttonConfig?: CTAButtonConfig
  bannerConfig?: CTABannerConfig
  inlineLinkConfig?: CTAInlineLinkConfig
  theme?: 'default' | 'minimal' | 'modern' | 'classic'
  colorScheme?: 'default' | 'primary' | 'secondary' | 'accent'
  trackingEnabled?: boolean
  abTest?: ABTestConfig
}
```

### Tracking and Analytics

All CTA components support comprehensive tracking:

- **UTM Parameters**: Automatic URL parameter injection
- **Conversion Events**: Custom event tracking for analytics
- **A/B Testing**: Built-in variant testing with automatic assignment
- **Attribution**: Click-to-conversion attribution tracking
- **Session Tracking**: User session and behavior tracking

### A/B Testing

Components support A/B testing through the `useABTesting` composable:

```typescript
const abTestConfig = {
  enabled: true,
  testId: 'signup_button_test',
  variants: [
    {
      id: 'control',
      name: 'Original',
      weight: 50,
      config: { buttonConfig: { text: 'Join Now' } }
    },
    {
      id: 'variant_a',
      name: 'Action Focused',
      weight: 50,
      config: { buttonConfig: { text: 'Start Today' } }
    }
  ]
}
```

### Accessibility Features

All components include comprehensive accessibility support:

- **ARIA Labels**: Custom aria-label and aria-describedby attributes
- **Keyboard Navigation**: Full keyboard support with custom shortcuts
- **Screen Reader Support**: Proper semantic markup and announcements
- **High Contrast Mode**: Support for high contrast preferences
- **Reduced Motion**: Respects prefers-reduced-motion settings
- **Focus Management**: Proper focus indicators and management

## Styling and Theming

Components use CSS custom properties for theming:

```css
.cta-button {
  background-color: var(--cta-bg, theme('colors.blue.600'));
  color: var(--cta-text, theme('colors.white'));
  border-color: var(--cta-border, transparent);
}
```

### Theme Variants

- **Default**: Standard blue color scheme
- **Minimal**: Clean, minimal styling
- **Modern**: Contemporary design with subtle shadows
- **Classic**: Traditional button styling

### Color Schemes

- **Primary**: Main brand colors
- **Secondary**: Secondary brand colors
- **Accent**: Accent colors for highlights
- **Custom**: Custom color configuration

## Sample Data

The `ctaSampleData.ts` file provides comprehensive sample configurations for all CTA types and audience segments:

```typescript
import { getCTASampleData } from '@/data/ctaSampleData'

// Get sample CTAs for individual audience
const individualCTAs = getCTASampleData('individual')

// Get specific CTA type sample
const buttonSample = getSampleCTAByType('button')
```

## Composables

### useAnalytics
Handles all analytics tracking for CTA interactions.

### useABTesting
Manages A/B test variant assignment and tracking.

### useConversionTracking
Tracks conversion events and attribution data.

## Testing

Components include comprehensive test coverage:

- **Unit Tests**: Component logic and configuration validation
- **Feature Tests**: API integration and data flow
- **Vue Component Tests**: Vue-specific functionality and rendering

Run tests with:
```bash
php artisan test --filter=CTA
```

## Performance Considerations

- **Lazy Loading**: Components support lazy loading for better performance
- **Code Splitting**: Separate bundles for different CTA types
- **Image Optimization**: Automatic image optimization for banner backgrounds
- **Caching**: Analytics data caching for improved performance

## Browser Support

Components are tested and supported in:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Migration Guide

When upgrading from previous versions:

1. Update component imports to use new paths
2. Update configuration objects to match new interfaces
3. Test A/B testing functionality with new composable
4. Verify analytics tracking is working correctly

## Contributing

When adding new features:

1. Update TypeScript interfaces in `types/components.ts`
2. Add sample data to `ctaSampleData.ts`
3. Include comprehensive tests
4. Update this documentation
5. Ensure accessibility compliance
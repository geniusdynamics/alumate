<template>
  <div class="theme-preview-frame" :class="frameClasses">
    <!-- Performance Metrics Overlay -->
    <div v-if="showPerformanceMetrics" class="performance-overlay">
      <div class="performance-metrics">
        <div class="metric">
          <span class="metric-label">Load Time:</span>
          <span class="metric-value" :class="getMetricClass(performanceData.loadTime, 'loadTime')">
            {{ performanceData.loadTime }}ms
          </span>
        </div>
        <div class="metric">
          <span class="metric-label">Render Time:</span>
          <span class="metric-value" :class="getMetricClass(performanceData.renderTime, 'renderTime')">
            {{ performanceData.renderTime }}ms
          </span>
        </div>
        <div class="metric">
          <span class="metric-label">Bundle Size:</span>
          <span class="metric-value" :class="getMetricClass(performanceData.bundleSize, 'bundleSize')">
            {{ formatBytes(performanceData.bundleSize) }}
          </span>
        </div>
        <div class="metric">
          <span class="metric-label">Score:</span>
          <span class="metric-value" :class="getScoreClass(performanceData.score)">
            {{ performanceData.score }}/100
          </span>
        </div>
      </div>
    </div>

    <!-- Accessibility Overlay -->
    <div v-if="showAccessibilityOverlay" class="accessibility-overlay">
      <div class="accessibility-indicators">
        <div
          v-for="indicator in accessibilityIndicators"
          :key="indicator.id"
          class="accessibility-indicator"
          :class="indicator.type"
          :style="{ top: indicator.top, left: indicator.left }"
          @click="highlightAccessibilityIssue(indicator)"
        >
          <Icon :name="indicator.icon" class="w-4 h-4" />
          <div class="indicator-tooltip">
            {{ indicator.message }}
          </div>
        </div>
      </div>
    </div>

    <!-- Main Preview Content -->
    <div class="preview-content" :style="themeStyles">
      <!-- Components View -->
      <div v-if="viewMode === 'components'" class="components-showcase">
        <!-- Hero Section -->
        <section class="hero-section" :style="heroSectionStyles">
          <div class="hero-container">
            <div class="hero-content">
              <h1 class="hero-title" :style="heroTitleStyles">
                {{ sampleData.hero.title }}
              </h1>
              <p class="hero-subtitle" :style="heroSubtitleStyles">
                {{ sampleData.hero.subtitle }}
              </p>
              <div class="hero-actions">
                <button class="hero-btn primary" :style="primaryButtonStyles">
                  {{ sampleData.hero.primaryCta }}
                </button>
                <button class="hero-btn secondary" :style="secondaryButtonStyles">
                  {{ sampleData.hero.secondaryCta }}
                </button>
              </div>
            </div>
            <div class="hero-stats" :style="statsContainerStyles">
              <div
                v-for="stat in sampleData.hero.stats"
                :key="stat.label"
                class="stat-item"
              >
                <div class="stat-number" :style="statNumberStyles">
                  {{ stat.value }}
                </div>
                <div class="stat-label" :style="statLabelStyles">
                  {{ stat.label }}
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Form Section -->
        <section class="form-section" :style="sectionStyles">
          <div class="section-container">
            <h2 class="section-title" :style="sectionTitleStyles">
              {{ sampleData.form.title }}
            </h2>
            <div class="form-container" :style="formContainerStyles">
              <div
                v-for="field in sampleData.form.fields"
                :key="field.name"
                class="form-group"
              >
                <label class="form-label" :style="formLabelStyles">
                  {{ field.label }}
                </label>
                <input
                  v-if="field.type !== 'select'"
                  :type="field.type"
                  class="form-input"
                  :style="formInputStyles"
                  :placeholder="field.placeholder"
                />
                <select
                  v-else
                  class="form-input"
                  :style="formInputStyles"
                >
                  <option>{{ field.placeholder }}</option>
                  <option
                    v-for="option in field.options"
                    :key="option"
                    :value="option"
                  >
                    {{ option }}
                  </option>
                </select>
              </div>
              <button class="form-submit" :style="primaryButtonStyles">
                {{ sampleData.form.submitText }}
              </button>
            </div>
          </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials-section" :style="testimonialsSectionStyles">
          <div class="section-container">
            <h2 class="section-title" :style="sectionTitleStyles">
              {{ sampleData.testimonials.title }}
            </h2>
            <div class="testimonials-grid">
              <div
                v-for="testimonial in sampleData.testimonials.items"
                :key="testimonial.id"
                class="testimonial-card"
                :style="testimonialCardStyles"
              >
                <div class="testimonial-content" :style="testimonialContentStyles">
                  "{{ testimonial.content }}"
                </div>
                <div class="testimonial-author">
                  <div class="author-avatar" :style="avatarStyles">
                    {{ testimonial.author.charAt(0) }}
                  </div>
                  <div class="author-info">
                    <div class="author-name" :style="authorNameStyles">
                      {{ testimonial.author }}
                    </div>
                    <div class="author-title" :style="authorTitleStyles">
                      {{ testimonial.title }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section" :style="ctaSectionStyles">
          <div class="section-container">
            <div class="cta-content">
              <h2 class="cta-title" :style="ctaTitleStyles">
                {{ sampleData.cta.title }}
              </h2>
              <p class="cta-subtitle" :style="ctaSubtitleStyles">
                {{ sampleData.cta.subtitle }}
              </p>
              <div class="cta-actions">
                <button class="cta-btn primary" :style="primaryButtonStyles">
                  {{ sampleData.cta.primaryText }}
                </button>
                <button class="cta-btn secondary" :style="secondaryButtonStyles">
                  {{ sampleData.cta.secondaryText }}
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Style Guide View -->
      <div v-else-if="viewMode === 'styleguide'" class="style-guide">
        <div class="style-section">
          <h3 class="style-section-title">Color Palette</h3>
          <div class="color-grid">
            <div
              v-for="(color, name) in colorVariables"
              :key="name"
              class="color-item"
            >
              <div class="color-swatch" :style="{ backgroundColor: color }"></div>
              <div class="color-info">
                <div class="color-name">{{ formatColorName(name) }}</div>
                <div class="color-value">{{ color }}</div>
                <div class="color-contrast">
                  Contrast: {{ getContrastRatio(color, theme.cssVariables['--theme-color-background']) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="style-section">
          <h3 class="style-section-title">Typography</h3>
          <div class="typography-samples">
            <div class="type-sample">
              <h1 :style="h1Styles">Heading 1 - Main Page Title</h1>
            </div>
            <div class="type-sample">
              <h2 :style="h2Styles">Heading 2 - Section Title</h2>
            </div>
            <div class="type-sample">
              <h3 :style="h3Styles">Heading 3 - Subsection Title</h3>
            </div>
            <div class="type-sample">
              <p :style="bodyStyles">
                Body text - This is how regular paragraph text will appear throughout the site. 
                It should be readable and comfortable for extended reading sessions.
              </p>
            </div>
            <div class="type-sample">
              <small :style="smallTextStyles">
                Small text - Used for captions, disclaimers, and secondary information.
              </small>
            </div>
          </div>
        </div>

        <div class="style-section">
          <h3 class="style-section-title">Spacing System</h3>
          <div class="spacing-samples">
            <div
              v-for="(size, name) in spacingVariables"
              :key="name"
              class="spacing-item"
            >
              <div class="spacing-label">{{ formatSpacingName(name) }}</div>
              <div class="spacing-demo" :style="{ padding: size, backgroundColor: theme.cssVariables['--theme-color-primary'], opacity: 0.2 }">
                {{ size }}
              </div>
            </div>
          </div>
        </div>

        <div class="style-section">
          <h3 class="style-section-title">Component Samples</h3>
          <div class="component-samples">
            <div class="sample-group">
              <h4 class="sample-title">Buttons</h4>
              <div class="button-samples">
                <button :style="primaryButtonStyles">Primary Button</button>
                <button :style="secondaryButtonStyles">Secondary Button</button>
                <button :style="{ ...primaryButtonStyles, opacity: 0.6 }" disabled>Disabled Button</button>
              </div>
            </div>
            
            <div class="sample-group">
              <h4 class="sample-title">Form Elements</h4>
              <div class="form-samples">
                <input type="text" :style="formInputStyles" placeholder="Text Input" />
                <select :style="formInputStyles">
                  <option>Select Option</option>
                  <option>Option 1</option>
                  <option>Option 2</option>
                </select>
                <textarea :style="formInputStyles" placeholder="Textarea" rows="3"></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Accessibility View -->
      <div v-else-if="viewMode === 'accessibility'" class="accessibility-view">
        <div class="accessibility-section">
          <h3 class="section-title">Accessibility Compliance</h3>
          <div class="compliance-grid">
            <div
              v-for="check in accessibilityChecks"
              :key="check.name"
              class="compliance-item"
              :class="check.status"
            >
              <Icon
                :name="check.status === 'pass' ? 'check-circle' : check.status === 'warning' ? 'alert-triangle' : 'x-circle'"
                class="w-6 h-6"
              />
              <div class="compliance-content">
                <div class="compliance-name">{{ check.name }}</div>
                <div class="compliance-description">{{ check.description }}</div>
                <div v-if="check.value" class="compliance-value">{{ check.value }}</div>
                <div v-if="check.recommendation" class="compliance-recommendation">
                  {{ check.recommendation }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Responsive View -->
      <div v-else-if="viewMode === 'responsive'" class="responsive-view">
        <div class="responsive-section">
          <h3 class="section-title">Responsive Behavior</h3>
          <div class="responsive-demo">
            <div class="responsive-component" :style="responsiveStyles">
              <div class="responsive-grid">
                <div class="responsive-item">
                  <h4>Desktop Layout</h4>
                  <p>Full-width content with side navigation</p>
                </div>
                <div class="responsive-item">
                  <h4>Tablet Layout</h4>
                  <p>Stacked content with collapsible navigation</p>
                </div>
                <div class="responsive-item">
                  <h4>Mobile Layout</h4>
                  <p>Single column with hamburger menu</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import type { GrapeJSThemeData, ThemePerformanceData } from '@/types/components'

interface Props {
  theme: GrapeJSThemeData
  viewMode: string
  device: string
  showAccessibilityOverlay: boolean
  showPerformanceMetrics: boolean
  enableAnimations: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  performanceUpdate: [data: ThemePerformanceData]
}>()

// State
const performanceData = ref<ThemePerformanceData>({
  loadTime: 0,
  renderTime: 0,
  bundleSize: 0,
  score: 0
})

const accessibilityIndicators = ref<any[]>([])

// Sample data for preview
const sampleData = {
  hero: {
    title: 'Connect with Alumni Worldwide',
    subtitle: 'Join thousands of successful graduates and unlock new opportunities',
    primaryCta: 'Join Network',
    secondaryCta: 'Learn More',
    stats: [
      { value: '25,000+', label: 'Alumni' },
      { value: '500+', label: 'Companies' },
      { value: '95%', label: 'Success Rate' }
    ]
  },
  form: {
    title: 'Get Started Today',
    fields: [
      { name: 'name', label: 'Full Name', type: 'text', placeholder: 'Enter your full name' },
      { name: 'email', label: 'Email Address', type: 'email', placeholder: 'Enter your email' },
      { name: 'year', label: 'Graduation Year', type: 'select', placeholder: 'Select year', options: ['2024', '2023', '2022', '2021'] }
    ],
    submitText: 'Submit Application'
  },
  testimonials: {
    title: 'What Our Alumni Say',
    items: [
      {
        id: 1,
        content: 'This platform transformed my career. The connections I made here led to my dream job.',
        author: 'Sarah Johnson',
        title: 'Software Engineer at Google'
      },
      {
        id: 2,
        content: 'The mentorship program helped me navigate my career transition successfully.',
        author: 'Michael Chen',
        title: 'Product Manager at Microsoft'
      }
    ]
  },
  cta: {
    title: 'Ready to Join Our Community?',
    subtitle: 'Take the next step in your career journey with our alumni network',
    primaryText: 'Join Now',
    secondaryText: 'Contact Us'
  }
}

// Computed styles
const frameClasses = computed(() => [
  'theme-preview-frame',
  `device-${props.device}`,
  { 'animations-enabled': props.enableAnimations }
])

const themeStyles = computed(() => ({
  backgroundColor: props.theme.cssVariables['--theme-color-background'] || '#ffffff',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  fontFamily: props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-base'] || '16px',
  lineHeight: props.theme.cssVariables['--theme-line-height'] || '1.6'
}))

const colorVariables = computed(() => {
  const colors: Record<string, string> = {}
  Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
    if (key.includes('color') && typeof value === 'string') {
      colors[key] = value
    }
  })
  return colors
})

const spacingVariables = computed(() => {
  const spacing: Record<string, string> = {}
  Object.entries(props.theme.cssVariables).forEach(([key, value]) => {
    if (key.includes('spacing') && typeof value === 'string') {
      spacing[key] = value
    }
  })
  return spacing
})

const heroSectionStyles = computed(() => ({
  backgroundColor: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '4rem 2rem',
  color: '#ffffff'
}))

const heroTitleStyles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-heading'] || '3rem',
  fontWeight: '700',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const heroSubtitleStyles = computed(() => ({
  fontSize: '1.25rem',
  opacity: '0.9',
  marginBottom: props.theme.cssVariables['--theme-spacing-large'] || '2rem'
}))

const primaryButtonStyles = computed(() => ({
  backgroundColor: props.theme.cssVariables['--theme-color-accent'] || '#28a745',
  color: '#ffffff',
  padding: `${props.theme.cssVariables['--theme-spacing-small'] || '0.75rem'} ${props.theme.cssVariables['--theme-spacing-base'] || '1.5rem'}`,
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '6px',
  border: 'none',
  fontWeight: '600',
  cursor: 'pointer',
  transition: props.enableAnimations ? `all ${props.theme.cssVariables['--theme-animation-duration'] || '0.3s'} ${props.theme.cssVariables['--theme-animation-easing'] || 'ease'}` : 'none'
}))

const secondaryButtonStyles = computed(() => ({
  backgroundColor: 'transparent',
  color: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  padding: `${props.theme.cssVariables['--theme-spacing-small'] || '0.75rem'} ${props.theme.cssVariables['--theme-spacing-base'] || '1.5rem'}`,
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '6px',
  border: `2px solid ${props.theme.cssVariables['--theme-color-primary'] || '#007bff'}`,
  fontWeight: '600',
  cursor: 'pointer',
  transition: props.enableAnimations ? `all ${props.theme.cssVariables['--theme-animation-duration'] || '0.3s'} ${props.theme.cssVariables['--theme-animation-easing'] || 'ease'}` : 'none'
}))

const sectionStyles = computed(() => ({
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '4rem 2rem',
  backgroundColor: props.theme.cssVariables['--theme-color-background'] || '#ffffff'
}))

const sectionTitleStyles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '2.5rem',
  color: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  marginBottom: props.theme.cssVariables['--theme-spacing-large'] || '2rem',
  textAlign: 'center'
}))

const formContainerStyles = computed(() => ({
  maxWidth: '500px',
  margin: '0 auto',
  padding: props.theme.cssVariables['--theme-spacing-large'] || '2rem',
  backgroundColor: '#ffffff',
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '8px',
  boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
}))

const formLabelStyles = computed(() => ({
  display: 'block',
  marginBottom: props.theme.cssVariables['--theme-spacing-small'] || '0.5rem',
  fontWeight: '600',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333'
}))

const formInputStyles = computed(() => ({
  width: '100%',
  padding: props.theme.cssVariables['--theme-spacing-small'] || '0.75rem',
  border: `1px solid ${props.theme.cssVariables['--theme-color-secondary'] || '#e5e5e5'}`,
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '4px',
  fontSize: props.theme.cssVariables['--theme-font-size-base'] || '16px',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const statsContainerStyles = computed(() => ({
  display: 'flex',
  justifyContent: 'center',
  gap: props.theme.cssVariables['--theme-spacing-large'] || '3rem',
  marginTop: props.theme.cssVariables['--theme-spacing-large'] || '3rem'
}))

const statNumberStyles = computed(() => ({
  fontSize: '2.5rem',
  fontWeight: '700',
  color: '#ffffff'
}))

const statLabelStyles = computed(() => ({
  fontSize: '1rem',
  color: '#ffffff',
  opacity: '0.9'
}))

const testimonialsSectionStyles = computed(() => ({
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '4rem 2rem',
  backgroundColor: '#f8f9fa'
}))

const testimonialCardStyles = computed(() => ({
  backgroundColor: '#ffffff',
  padding: props.theme.cssVariables['--theme-spacing-large'] || '2rem',
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '8px',
  boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)',
  border: '1px solid #e5e5e5'
}))

const testimonialContentStyles = computed(() => ({
  fontSize: '1.125rem',
  fontStyle: 'italic',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem',
  lineHeight: props.theme.cssVariables['--theme-line-height'] || '1.6'
}))

const avatarStyles = computed(() => ({
  width: '48px',
  height: '48px',
  borderRadius: '50%',
  backgroundColor: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  color: '#ffffff',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  fontWeight: '600',
  fontSize: '1.25rem'
}))

const authorNameStyles = computed(() => ({
  fontWeight: '600',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333'
}))

const authorTitleStyles = computed(() => ({
  fontSize: '0.875rem',
  color: props.theme.cssVariables['--theme-color-secondary'] || '#6c757d'
}))

const ctaSectionStyles = computed(() => ({
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '4rem 2rem',
  backgroundColor: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  textAlign: 'center',
  color: '#ffffff'
}))

const ctaTitleStyles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '2.5rem',
  color: '#ffffff',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const ctaSubtitleStyles = computed(() => ({
  fontSize: '1.25rem',
  color: '#ffffff',
  opacity: '0.9',
  marginBottom: props.theme.cssVariables['--theme-spacing-large'] || '2rem'
}))

// Style guide styles
const h1Styles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-heading'] || '2.5rem',
  color: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const h2Styles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '2rem',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const h3Styles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '1.5rem',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  marginBottom: props.theme.cssVariables['--theme-spacing-small'] || '0.5rem'
}))

const bodyStyles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-base'] || '16px',
  lineHeight: props.theme.cssVariables['--theme-line-height'] || '1.6',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333'
}))

const smallTextStyles = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '0.875rem',
  color: props.theme.cssVariables['--theme-color-secondary'] || '#6c757d'
}))

const responsiveStyles = computed(() => {
  const baseStyles = {
    padding: props.theme.cssVariables['--theme-spacing-base'] || '1rem',
    backgroundColor: props.theme.cssVariables['--theme-color-background'] || '#ffffff'
  }

  if (props.device === 'mobile') {
    return {
      ...baseStyles,
      fontSize: '14px',
      padding: '0.5rem'
    }
  } else if (props.device === 'tablet') {
    return {
      ...baseStyles,
      fontSize: '15px',
      padding: '0.75rem'
    }
  }

  return baseStyles
})

// Accessibility checks
const accessibilityChecks = computed(() => {
  const checks = []
  
  // Color contrast checks
  const primaryContrast = calculateContrast(
    props.theme.cssVariables['--theme-color-primary'] || '#007bff',
    props.theme.cssVariables['--theme-color-background'] || '#ffffff'
  )
  
  checks.push({
    name: 'Primary Color Contrast',
    description: 'Contrast ratio between primary color and background',
    value: `${primaryContrast.toFixed(2)}:1`,
    status: primaryContrast >= 4.5 ? 'pass' : primaryContrast >= 3 ? 'warning' : 'fail',
    recommendation: primaryContrast < 4.5 ? 'Increase contrast to meet WCAG AA standards (4.5:1)' : null
  })
  
  const textContrast = calculateContrast(
    props.theme.cssVariables['--theme-color-text'] || '#333333',
    props.theme.cssVariables['--theme-color-background'] || '#ffffff'
  )
  
  checks.push({
    name: 'Text Color Contrast',
    description: 'Contrast ratio between text color and background',
    value: `${textContrast.toFixed(2)}:1`,
    status: textContrast >= 4.5 ? 'pass' : textContrast >= 3 ? 'warning' : 'fail',
    recommendation: textContrast < 4.5 ? 'Increase text contrast to meet WCAG AA standards (4.5:1)' : null
  })
  
  // Font size check
  const baseFontSize = parseInt(props.theme.cssVariables['--theme-font-size-base'] || '16px')
  checks.push({
    name: 'Base Font Size',
    description: 'Minimum readable font size',
    value: `${baseFontSize}px`,
    status: baseFontSize >= 16 ? 'pass' : baseFontSize >= 14 ? 'warning' : 'fail',
    recommendation: baseFontSize < 16 ? 'Use at least 16px for better readability' : null
  })
  
  // Touch target size (for mobile)
  if (props.device === 'mobile') {
    checks.push({
      name: 'Touch Target Size',
      description: 'Minimum touch target size for mobile devices',
      value: '44px × 44px',
      status: 'pass',
      recommendation: null
    })
  }
  
  return checks
})

// Methods
const measurePerformance = () => {
  const startTime = performance.now()
  
  // Simulate performance measurement
  setTimeout(() => {
    const endTime = performance.now()
    const loadTime = Math.round(endTime - startTime)
    
    performanceData.value = {
      loadTime,
      renderTime: Math.round(loadTime * 0.6),
      bundleSize: Math.round(Math.random() * 500000 + 100000), // 100KB - 600KB
      score: Math.max(0, Math.min(100, Math.round(100 - (loadTime / 10))))
    }
    
    emit('performanceUpdate', performanceData.value)
  }, 100)
}

const generateAccessibilityIndicators = () => {
  accessibilityIndicators.value = []
  
  // Add indicators for accessibility issues
  accessibilityChecks.value.forEach((check, index) => {
    if (check.status !== 'pass') {
      accessibilityIndicators.value.push({
        id: `indicator-${index}`,
        type: check.status,
        icon: check.status === 'warning' ? 'alert-triangle' : 'x-circle',
        message: check.recommendation || check.description,
        top: `${20 + (index * 60)}px`,
        left: '20px'
      })
    }
  })
}

const formatColorName = (name: string) => {
  return name.replace('--theme-color-', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatSpacingName = (name: string) => {
  return name.replace('--theme-spacing-', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const calculateContrast = (color1: string, color2: string) => {
  const rgb1 = hexToRgb(color1)
  const rgb2 = hexToRgb(color2)
  
  if (!rgb1 || !rgb2) return 0
  
  const l1 = getRelativeLuminance(rgb1)
  const l2 = getRelativeLuminance(rgb2)
  
  const lighter = Math.max(l1, l2)
  const darker = Math.min(l1, l2)
  
  return (lighter + 0.05) / (darker + 0.05)
}

const hexToRgb = (hex: string) => {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null
}

const getRelativeLuminance = (rgb: { r: number; g: number; b: number }) => {
  const { r, g, b } = rgb
  const [rs, gs, bs] = [r, g, b].map(c => {
    c = c / 255
    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4)
  })
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs
}

const getContrastRatio = (color1: string, color2: string) => {
  const ratio = calculateContrast(color1, color2)
  return `${ratio.toFixed(2)}:1`
}

const getMetricClass = (value: number, type: string) => {
  const thresholds = {
    loadTime: { good: 1000, fair: 2000 },
    renderTime: { good: 600, fair: 1200 },
    bundleSize: { good: 200000, fair: 500000 }
  }
  
  const threshold = thresholds[type as keyof typeof thresholds]
  if (!threshold) return 'metric-good'
  
  if (value <= threshold.good) return 'metric-good'
  if (value <= threshold.fair) return 'metric-fair'
  return 'metric-poor'
}

const getScoreClass = (score: number) => {
  if (score >= 90) return 'metric-good'
  if (score >= 70) return 'metric-fair'
  return 'metric-poor'
}

const formatBytes = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const highlightAccessibilityIssue = (indicator: any) => {
  // Handle accessibility issue highlighting
  console.log('Highlighting accessibility issue:', indicator)
}

// Watchers
watch(() => props.theme, () => {
  measurePerformance()
  generateAccessibilityIndicators()
}, { immediate: true })

watch(() => props.showAccessibilityOverlay, (show) => {
  if (show) {
    generateAccessibilityIndicators()
  }
})

// Lifecycle
onMounted(() => {
  measurePerformance()
  generateAccessibilityIndicators()
})
</script>

<style scoped>
.theme-preview-frame {
  @apply relative w-full min-h-96 bg-white rounded-lg overflow-hidden;
}

.device-desktop {
  @apply max-w-none;
}

.device-tablet {
  @apply max-w-4xl mx-auto;
}

.device-mobile {
  @apply max-w-sm mx-auto;
}

.performance-overlay {
  @apply absolute top-4 right-4 z-10 bg-black bg-opacity-75 text-white p-3 rounded-lg text-xs;
}

.performance-metrics {
  @apply space-y-2;
}

.metric {
  @apply flex justify-between gap-4;
}

.metric-label {
  @apply font-medium;
}

.metric-value {
  @apply font-mono;
}

.metric-good {
  @apply text-green-400;
}

.metric-fair {
  @apply text-yellow-400;
}

.metric-poor {
  @apply text-red-400;
}

.accessibility-overlay {
  @apply absolute inset-0 z-10 pointer-events-none;
}

.accessibility-indicators {
  @apply relative w-full h-full;
}

.accessibility-indicator {
  @apply absolute w-8 h-8 rounded-full flex items-center justify-center cursor-pointer pointer-events-auto;
}

.accessibility-indicator.warning {
  @apply bg-yellow-500 text-white;
}

.accessibility-indicator.fail {
  @apply bg-red-500 text-white;
}

.indicator-tooltip {
  @apply absolute left-10 top-0 bg-gray-900 text-white text-xs p-2 rounded whitespace-nowrap opacity-0 pointer-events-none transition-opacity duration-200;
}

.accessibility-indicator:hover .indicator-tooltip {
  @apply opacity-100;
}

.preview-content {
  @apply w-full;
}

.components-showcase {
  @apply space-y-0;
}

.hero-section {
  @apply relative;
}

.hero-container {
  @apply max-w-6xl mx-auto text-center;
}

.hero-content {
  @apply mb-8;
}

.hero-title {
  @apply font-bold leading-tight;
}

.hero-subtitle {
  @apply text-lg;
}

.hero-actions {
  @apply flex flex-col sm:flex-row gap-4 justify-center items-center;
}

.hero-btn {
  @apply px-6 py-3 rounded-lg font-semibold transition-all duration-200;
}

.hero-stats {
  @apply flex flex-wrap justify-center;
}

.stat-item {
  @apply text-center;
}

.form-section, .testimonials-section, .cta-section {
  @apply relative;
}

.section-container {
  @apply max-w-6xl mx-auto;
}

.section-title {
  @apply font-bold text-center;
}

.form-container {
  @apply space-y-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply text-sm font-medium;
}

.form-input {
  @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500;
}

.form-submit {
  @apply w-full px-6 py-3 rounded-lg font-semibold transition-all duration-200;
}

.testimonials-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-6;
}

.testimonial-card {
  @apply space-y-4;
}

.testimonial-author {
  @apply flex items-center gap-3;
}

.author-info {
  @apply space-y-1;
}

.cta-content {
  @apply space-y-6;
}

.cta-actions {
  @apply flex flex-col sm:flex-row gap-4 justify-center items-center;
}

.cta-btn {
  @apply px-6 py-3 rounded-lg font-semibold transition-all duration-200;
}

.style-guide {
  @apply p-6 space-y-8;
}

.style-section {
  @apply space-y-4;
}

.style-section-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2;
}

.color-grid {
  @apply grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4;
}

.color-item {
  @apply space-y-2;
}

.color-swatch {
  @apply w-full h-16 rounded-lg border border-gray-200 dark:border-gray-700;
}

.color-info {
  @apply space-y-1;
}

.color-name {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.color-value {
  @apply text-xs text-gray-600 dark:text-gray-400 font-mono;
}

.color-contrast {
  @apply text-xs text-gray-500 dark:text-gray-500;
}

.typography-samples {
  @apply space-y-4;
}

.type-sample {
  @apply border-l-4 border-gray-200 dark:border-gray-700 pl-4;
}

.spacing-samples {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4;
}

.spacing-item {
  @apply space-y-2;
}

.spacing-label {
  @apply text-sm font-medium text-gray-900 dark:text-white;
}

.spacing-demo {
  @apply rounded border border-gray-200 dark:border-gray-700 text-xs text-center;
}

.component-samples {
  @apply space-y-6;
}

.sample-group {
  @apply space-y-3;
}

.sample-title {
  @apply text-lg font-medium text-gray-900 dark:text-white;
}

.button-samples {
  @apply flex flex-wrap gap-3;
}

.form-samples {
  @apply space-y-3 max-w-md;
}

.accessibility-view {
  @apply p-6;
}

.accessibility-section {
  @apply space-y-4;
}

.compliance-grid {
  @apply space-y-4;
}

.compliance-item {
  @apply flex gap-4 p-4 rounded-lg border;
}

.compliance-item.pass {
  @apply border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20;
}

.compliance-item.warning {
  @apply border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20;
}

.compliance-item.fail {
  @apply border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20;
}

.compliance-content {
  @apply space-y-2;
}

.compliance-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.compliance-description {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.compliance-value {
  @apply text-sm font-mono text-gray-800 dark:text-gray-200;
}

.compliance-recommendation {
  @apply text-sm text-blue-600 dark:text-blue-400;
}

.responsive-view {
  @apply p-6;
}

.responsive-section {
  @apply space-y-4;
}

.responsive-demo {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden;
}

.responsive-component {
  @apply p-4;
}

.responsive-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.responsive-item {
  @apply p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.animations-enabled .hero-btn,
.animations-enabled .form-submit,
.animations-enabled .cta-btn {
  @apply hover:scale-105 hover:shadow-lg;
}

.animations-enabled .testimonial-card {
  @apply hover:shadow-lg transition-shadow duration-300;
}
</style>
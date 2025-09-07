<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
            Theme Preview: {{ theme.name }}
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Preview how this theme will look across different components
          </p>
        </div>
        <button @click="$emit('close')" class="btn-close">
          <Icon name="x" class="w-5 h-5" />
        </button>
      </div>

      <div class="modal-body">
        <div class="preview-controls">
          <div class="device-controls">
            <span class="control-label">Device:</span>
            <div class="device-buttons">
              <button
                v-for="device in devices"
                :key="device.name"
                @click="currentDevice = device.name"
                class="device-btn"
                :class="{ 'device-btn--active': currentDevice === device.name }"
                :title="device.label"
              >
                <Icon :name="device.icon" class="w-4 h-4" />
              </button>
            </div>
          </div>
          
          <div class="theme-controls">
            <span class="control-label">View:</span>
            <div class="view-buttons">
              <button
                v-for="view in previewViews"
                :key="view.id"
                @click="currentView = view.id"
                class="view-btn"
                :class="{ 'view-btn--active': currentView === view.id }"
              >
                {{ view.label }}
              </button>
            </div>
          </div>
        </div>

        <div class="preview-container" :class="`preview-${currentDevice}`">
          <div class="preview-frame" :style="frameStyle">
            <!-- Component Showcase View -->
            <div v-if="currentView === 'components'" class="preview-content">
              <!-- Hero Component Preview -->
              <section class="hero-preview" :style="heroStyle">
                <div class="hero-content">
                  <h1 class="hero-title" :style="heroTitleStyle">
                    Welcome to Our Alumni Network
                  </h1>
                  <p class="hero-subtitle" :style="heroSubtitleStyle">
                    Connect, grow, and succeed with thousands of alumni worldwide
                  </p>
                  <div class="hero-actions">
                    <button class="hero-button primary" :style="primaryButtonStyle">
                      Join Network
                    </button>
                    <button class="hero-button secondary" :style="secondaryButtonStyle">
                      Learn More
                    </button>
                  </div>
                  <div class="hero-stats" :style="statsStyle">
                    <div class="stat-item">
                      <div class="stat-number" :style="statNumberStyle">25,000+</div>
                      <div class="stat-label" :style="statLabelStyle">Alumni</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-number" :style="statNumberStyle">500+</div>
                      <div class="stat-label" :style="statLabelStyle">Companies</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-number" :style="statNumberStyle">95%</div>
                      <div class="stat-label" :style="statLabelStyle">Success Rate</div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Form Component Preview -->
              <section class="form-preview" :style="sectionStyle">
                <div class="section-container">
                  <h2 class="section-title" :style="sectionTitleStyle">
                    Get Started Today
                  </h2>
                  <div class="form-container" :style="formContainerStyle">
                    <div class="form-group">
                      <label class="form-label" :style="formLabelStyle">Full Name</label>
                      <input type="text" class="form-input" :style="formInputStyle" placeholder="Enter your full name" />
                    </div>
                    <div class="form-group">
                      <label class="form-label" :style="formLabelStyle">Email Address</label>
                      <input type="email" class="form-input" :style="formInputStyle" placeholder="Enter your email" />
                    </div>
                    <div class="form-group">
                      <label class="form-label" :style="formLabelStyle">Graduation Year</label>
                      <select class="form-input" :style="formInputStyle">
                        <option>Select year</option>
                        <option>2024</option>
                        <option>2023</option>
                        <option>2022</option>
                      </select>
                    </div>
                    <button class="form-submit" :style="primaryButtonStyle">
                      Submit Application
                    </button>
                  </div>
                </div>
              </section>

              <!-- Testimonial Component Preview -->
              <section class="testimonial-preview" :style="testimonialSectionStyle">
                <div class="section-container">
                  <h2 class="section-title" :style="sectionTitleStyle">
                    What Our Alumni Say
                  </h2>
                  <div class="testimonial-grid">
                    <div
                      v-for="testimonial in sampleTestimonials"
                      :key="testimonial.id"
                      class="testimonial-card"
                      :style="testimonialCardStyle"
                    >
                      <div class="testimonial-content" :style="testimonialContentStyle">
                        "{{ testimonial.content }}"
                      </div>
                      <div class="testimonial-author">
                        <div class="author-avatar" :style="avatarStyle">
                          {{ testimonial.author.charAt(0) }}
                        </div>
                        <div class="author-info">
                          <div class="author-name" :style="authorNameStyle">
                            {{ testimonial.author }}
                          </div>
                          <div class="author-title" :style="authorTitleStyle">
                            {{ testimonial.title }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- CTA Component Preview -->
              <section class="cta-preview" :style="ctaSectionStyle">
                <div class="section-container">
                  <div class="cta-content">
                    <h2 class="cta-title" :style="ctaTitleStyle">
                      Ready to Join Our Community?
                    </h2>
                    <p class="cta-subtitle" :style="ctaSubtitleStyle">
                      Take the next step in your career journey with our alumni network
                    </p>
                    <div class="cta-actions">
                      <button class="cta-button primary" :style="primaryButtonStyle">
                        Join Now
                      </button>
                      <button class="cta-button secondary" :style="secondaryButtonStyle">
                        Contact Us
                      </button>
                    </div>
                  </div>
                </div>
              </section>
            </div>

            <!-- Style Guide View -->
            <div v-else-if="currentView === 'styleguide'" class="style-guide">
              <div class="style-section">
                <h3 class="style-section-title">Colors</h3>
                <div class="color-palette">
                  <div
                    v-for="(color, name) in theme.cssVariables"
                    :key="name"
                    v-show="name.includes('color')"
                    class="color-item"
                  >
                    <div class="color-swatch" :style="{ backgroundColor: color }"></div>
                    <div class="color-info">
                      <div class="color-name">{{ formatColorName(name) }}</div>
                      <div class="color-value">{{ color }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="style-section">
                <h3 class="style-section-title">Typography</h3>
                <div class="typography-samples">
                  <div class="type-sample">
                    <h1 :style="h1Style">Heading 1 - Main Title</h1>
                  </div>
                  <div class="type-sample">
                    <h2 :style="h2Style">Heading 2 - Section Title</h2>
                  </div>
                  <div class="type-sample">
                    <h3 :style="h3Style">Heading 3 - Subsection</h3>
                  </div>
                  <div class="type-sample">
                    <p :style="bodyStyle">
                      Body text - This is how regular paragraph text will appear throughout the site. 
                      It should be readable and comfortable for extended reading.
                    </p>
                  </div>
                </div>
              </div>

              <div class="style-section">
                <h3 class="style-section-title">Spacing & Layout</h3>
                <div class="spacing-samples">
                  <div class="spacing-item">
                    <div class="spacing-label">Small Spacing</div>
                    <div class="spacing-demo small" :style="{ padding: theme.cssVariables['--theme-spacing-small'] || '0.5rem' }">
                      {{ theme.cssVariables['--theme-spacing-small'] || '0.5rem' }}
                    </div>
                  </div>
                  <div class="spacing-item">
                    <div class="spacing-label">Base Spacing</div>
                    <div class="spacing-demo base" :style="{ padding: theme.cssVariables['--theme-spacing-base'] || '1rem' }">
                      {{ theme.cssVariables['--theme-spacing-base'] || '1rem' }}
                    </div>
                  </div>
                  <div class="spacing-item">
                    <div class="spacing-label">Large Spacing</div>
                    <div class="spacing-demo large" :style="{ padding: theme.cssVariables['--theme-spacing-large'] || '2rem' }">
                      {{ theme.cssVariables['--theme-spacing-large'] || '2rem' }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="style-section">
                <h3 class="style-section-title">Components</h3>
                <div class="component-samples">
                  <div class="component-sample">
                    <div class="sample-label">Buttons</div>
                    <div class="button-group">
                      <button :style="primaryButtonStyle">Primary Button</button>
                      <button :style="secondaryButtonStyle">Secondary Button</button>
                    </div>
                  </div>
                  <div class="component-sample">
                    <div class="sample-label">Form Elements</div>
                    <div class="form-elements">
                      <input type="text" :style="formInputStyle" placeholder="Text Input" />
                      <select :style="formInputStyle">
                        <option>Select Option</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Accessibility View -->
            <div v-else-if="currentView === 'accessibility'" class="accessibility-report">
              <div class="accessibility-section">
                <h3 class="section-title">Accessibility Analysis</h3>
                
                <div class="accessibility-checks">
                  <div
                    v-for="check in accessibilityChecks"
                    :key="check.name"
                    class="accessibility-check"
                    :class="check.status"
                  >
                    <Icon
                      :name="check.status === 'pass' ? 'check-circle' : 'alert-triangle'"
                      class="w-5 h-5"
                      :class="check.status === 'pass' ? 'text-green-600' : 'text-yellow-600'"
                    />
                    <div class="check-content">
                      <div class="check-name">{{ check.name }}</div>
                      <div class="check-description">{{ check.description }}</div>
                      <div v-if="check.value" class="check-value">{{ check.value }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Theme Information -->
        <div class="theme-info">
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Theme Name:</span>
              <span class="info-value">{{ theme.name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Compatibility:</span>
              <span class="info-value" :class="compatibilityClass">
                {{ compatibilityStatus }}
              </span>
            </div>
            <div class="info-item">
              <span class="info-label">Components:</span>
              <span class="info-value">{{ componentCount }} supported</span>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="exportTheme" class="btn-secondary">
          <Icon name="download" class="w-4 h-4 mr-2" />
          Export Theme
        </button>
        <div class="flex gap-3">
          <button @click="$emit('close')" class="btn-secondary">
            Close
          </button>
          <button @click="$emit('apply', theme)" class="btn-primary">
            <Icon name="check" class="w-4 h-4 mr-2" />
            Apply Theme
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Icon from '@/components/Common/Icon.vue'
import type { GrapeJSThemeData } from '@/types/components'

interface Props {
  theme: GrapeJSThemeData
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  apply: [theme: GrapeJSThemeData]
}>()

// State
const currentDevice = ref('desktop')
const currentView = ref('components')

const devices = [
  { name: 'desktop', label: 'Desktop', icon: 'monitor' },
  { name: 'tablet', label: 'Tablet', icon: 'tablet' },
  { name: 'mobile', label: 'Mobile', icon: 'smartphone' }
]

const previewViews = [
  { id: 'components', label: 'Components' },
  { id: 'styleguide', label: 'Style Guide' },
  { id: 'accessibility', label: 'Accessibility' }
]

const sampleTestimonials = [
  {
    id: 1,
    content: "This platform transformed my career. The connections I made here led to my dream job.",
    author: "Sarah Johnson",
    title: "Software Engineer at Google"
  },
  {
    id: 2,
    content: "The mentorship program helped me navigate my career transition successfully.",
    author: "Michael Chen",
    title: "Product Manager at Microsoft"
  },
  {
    id: 3,
    content: "I found my co-founder through this network. We've built an amazing company together.",
    author: "Emily Rodriguez",
    title: "CEO at TechStart"
  }
]

// Computed styles
const frameStyle = computed(() => ({
  backgroundColor: props.theme.cssVariables['--theme-color-background'] || '#ffffff',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  fontFamily: props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-base'] || '16px',
  lineHeight: props.theme.cssVariables['--theme-line-height'] || '1.6'
}))

const heroStyle = computed(() => ({
  backgroundColor: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '3rem 1.5rem'
}))

const heroTitleStyle = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-heading'] || '2.5rem',
  color: '#ffffff',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const heroSubtitleStyle = computed(() => ({
  color: '#ffffff',
  opacity: '0.9',
  fontSize: '1.25rem',
  marginBottom: props.theme.cssVariables['--theme-spacing-large'] || '2rem'
}))

const primaryButtonStyle = computed(() => ({
  backgroundColor: props.theme.cssVariables['--theme-color-accent'] || '#28a745',
  color: '#ffffff',
  padding: `${props.theme.cssVariables['--theme-spacing-small'] || '0.75rem'} ${props.theme.cssVariables['--theme-spacing-base'] || '1.5rem'}`,
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '4px',
  border: 'none',
  fontWeight: '600',
  cursor: 'pointer',
  transition: `all ${props.theme.cssVariables['--theme-animation-duration'] || '0.3s'} ${props.theme.cssVariables['--theme-animation-easing'] || 'ease'}`
}))

const secondaryButtonStyle = computed(() => ({
  backgroundColor: 'transparent',
  color: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  padding: `${props.theme.cssVariables['--theme-spacing-small'] || '0.75rem'} ${props.theme.cssVariables['--theme-spacing-base'] || '1.5rem'}`,
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '4px',
  border: `${props.theme.cssVariables['--theme-border-width'] || '2px'} solid ${props.theme.cssVariables['--theme-color-primary'] || '#007bff'}`,
  fontWeight: '600',
  cursor: 'pointer',
  transition: `all ${props.theme.cssVariables['--theme-animation-duration'] || '0.3s'} ${props.theme.cssVariables['--theme-animation-easing'] || 'ease'}`
}))

const sectionStyle = computed(() => ({
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '3rem 1.5rem',
  backgroundColor: props.theme.cssVariables['--theme-color-background'] || '#ffffff'
}))

const sectionTitleStyle = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '2rem',
  color: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  marginBottom: props.theme.cssVariables['--theme-spacing-large'] || '2rem',
  textAlign: 'center'
}))

const formContainerStyle = computed(() => ({
  maxWidth: '500px',
  margin: '0 auto',
  padding: props.theme.cssVariables['--theme-spacing-large'] || '2rem',
  backgroundColor: '#ffffff',
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '8px',
  boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
}))

const formLabelStyle = computed(() => ({
  display: 'block',
  marginBottom: props.theme.cssVariables['--theme-spacing-small'] || '0.5rem',
  fontWeight: '600',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333'
}))

const formInputStyle = computed(() => ({
  width: '100%',
  padding: props.theme.cssVariables['--theme-spacing-small'] || '0.75rem',
  border: `${props.theme.cssVariables['--theme-border-width'] || '1px'} solid ${props.theme.cssVariables['--theme-color-secondary'] || '#e5e5e5'}`,
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '4px',
  fontSize: props.theme.cssVariables['--theme-font-size-base'] || '16px',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const testimonialSectionStyle = computed(() => ({
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '3rem 1.5rem',
  backgroundColor: '#f8f9fa'
}))

const testimonialCardStyle = computed(() => ({
  backgroundColor: '#ffffff',
  padding: props.theme.cssVariables['--theme-spacing-large'] || '2rem',
  borderRadius: props.theme.cssVariables['--theme-border-radius'] || '8px',
  boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)',
  border: `${props.theme.cssVariables['--theme-border-width'] || '1px'} solid #e5e5e5`
}))

const testimonialContentStyle = computed(() => ({
  fontSize: '1.125rem',
  fontStyle: 'italic',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem',
  lineHeight: props.theme.cssVariables['--theme-line-height'] || '1.6'
}))

const avatarStyle = computed(() => ({
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

const authorNameStyle = computed(() => ({
  fontWeight: '600',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333'
}))

const authorTitleStyle = computed(() => ({
  fontSize: '0.875rem',
  color: props.theme.cssVariables['--theme-color-secondary'] || '#6c757d'
}))

const ctaSectionStyle = computed(() => ({
  padding: props.theme.cssVariables['--theme-spacing-section-padding'] || '3rem 1.5rem',
  backgroundColor: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  textAlign: 'center'
}))

const ctaTitleStyle = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '2.5rem',
  color: '#ffffff',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const ctaSubtitleStyle = computed(() => ({
  fontSize: '1.25rem',
  color: '#ffffff',
  opacity: '0.9',
  marginBottom: props.theme.cssVariables['--theme-spacing-large'] || '2rem'
}))

const statsStyle = computed(() => ({
  display: 'flex',
  justifyContent: 'center',
  gap: props.theme.cssVariables['--theme-spacing-large'] || '3rem',
  marginTop: props.theme.cssVariables['--theme-spacing-large'] || '3rem'
}))

const statNumberStyle = computed(() => ({
  fontSize: '2.5rem',
  fontWeight: '700',
  color: '#ffffff'
}))

const statLabelStyle = computed(() => ({
  fontSize: '1rem',
  color: '#ffffff',
  opacity: '0.9'
}))

// Style guide styles
const h1Style = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-heading'] || '2.5rem',
  color: props.theme.cssVariables['--theme-color-primary'] || '#007bff',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const h2Style = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '2rem',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  marginBottom: props.theme.cssVariables['--theme-spacing-base'] || '1rem'
}))

const h3Style = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-heading-font'] || props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: '1.5rem',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333',
  marginBottom: props.theme.cssVariables['--theme-spacing-small'] || '0.5rem'
}))

const bodyStyle = computed(() => ({
  fontFamily: props.theme.cssVariables['--theme-font-family'] || 'Arial, sans-serif',
  fontSize: props.theme.cssVariables['--theme-font-size-base'] || '16px',
  lineHeight: props.theme.cssVariables['--theme-line-height'] || '1.6',
  color: props.theme.cssVariables['--theme-color-text'] || '#333333'
}))

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
    value: `${primaryContrast.toFixed(2)}:1 ${primaryContrast >= 4.5 ? '(WCAG AA)' : '(Below WCAG AA)'}`,
    status: primaryContrast >= 4.5 ? 'pass' : 'warning'
  })
  
  const textContrast = calculateContrast(
    props.theme.cssVariables['--theme-color-text'] || '#333333',
    props.theme.cssVariables['--theme-color-background'] || '#ffffff'
  )
  
  checks.push({
    name: 'Text Color Contrast',
    description: 'Contrast ratio between text color and background',
    value: `${textContrast.toFixed(2)}:1 ${textContrast >= 4.5 ? '(WCAG AA)' : '(Below WCAG AA)'}`,
    status: textContrast >= 4.5 ? 'pass' : 'warning'
  })
  
  // Font size check
  const baseFontSize = parseInt(props.theme.cssVariables['--theme-font-size-base'] || '16px')
  checks.push({
    name: 'Base Font Size',
    description: 'Minimum readable font size',
    value: `${baseFontSize}px ${baseFontSize >= 16 ? '(Recommended)' : '(Below recommended)'}`,
    status: baseFontSize >= 16 ? 'pass' : 'warning'
  })
  
  return checks
})

// Computed properties
const compatibilityStatus = computed(() => {
  const issues = props.theme.accessibility || []
  if (issues.length === 0) return 'Fully Compatible'
  if (issues.length <= 2) return 'Minor Issues'
  return 'Needs Attention'
})

const compatibilityClass = computed(() => {
  const issues = props.theme.accessibility || []
  if (issues.length === 0) return 'text-green-600'
  if (issues.length <= 2) return 'text-yellow-600'
  return 'text-red-600'
})

const componentCount = computed(() => {
  // Count supported component types
  return 6 // Hero, Forms, Testimonials, Statistics, CTAs, Media
})

// Methods
const handleOverlayClick = () => {
  emit('close')
}

const formatColorName = (name: string) => {
  return name.replace('--theme-color-', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
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

const exportTheme = () => {
  const themeData = {
    name: props.theme.name,
    version: '1.0.0',
    exported: new Date().toISOString(),
    config: props.theme.styleManager,
    cssVariables: props.theme.cssVariables,
    tailwindMappings: props.theme.tailwindMappings
  }
  
  const blob = new Blob([JSON.stringify(themeData, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${props.theme.slug || 'theme'}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-7xl w-full max-h-[95vh] overflow-hidden flex flex-col;
}

.modal-header {
  @apply flex items-start justify-between p-6 border-b border-gray-200 dark:border-gray-700;
}

.modal-body {
  @apply flex-1 overflow-y-auto p-6 space-y-6;
}

.modal-footer {
  @apply flex items-center justify-between p-6 border-t border-gray-200 dark:border-gray-700;
}

.preview-controls {
  @apply flex items-center justify-between mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.device-controls, .theme-controls {
  @apply flex items-center gap-3;
}

.control-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.device-buttons, .view-buttons {
  @apply flex gap-1;
}

.device-btn, .view-btn {
  @apply px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200;
}

.device-btn {
  @apply text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-600;
}

.device-btn--active {
  @apply text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.view-btn {
  @apply text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-600;
}

.view-btn--active {
  @apply text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20;
}

.preview-container {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden;
}

.preview-desktop {
  @apply w-full;
}

.preview-tablet {
  @apply w-3/4 mx-auto;
}

.preview-mobile {
  @apply w-1/2 mx-auto;
}

.preview-frame {
  @apply w-full min-h-96 overflow-y-auto;
}

.preview-content {
  @apply space-y-0;
}

/* Hero Preview Styles */
.hero-preview {
  @apply text-center text-white;
}

.hero-content {
  @apply max-w-4xl mx-auto;
}

.hero-title {
  @apply font-bold;
}

.hero-subtitle {
  @apply text-lg;
}

.hero-actions {
  @apply flex justify-center gap-4 flex-wrap;
}

.hero-button {
  @apply px-6 py-3 font-semibold rounded-md;
}

.hero-stats {
  @apply text-center;
}

.stat-item {
  @apply text-center;
}

/* Form Preview Styles */
.form-preview {
  @apply py-16;
}

.section-container {
  @apply max-w-6xl mx-auto px-4;
}

.form-container {
  @apply space-y-4;
}

.form-group {
  @apply text-left;
}

.form-label {
  @apply text-sm font-medium;
}

.form-input {
  @apply border focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.form-submit {
  @apply w-full font-semibold;
}

/* Testimonial Preview Styles */
.testimonial-preview {
  @apply py-16;
}

.testimonial-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-6;
}

.testimonial-author {
  @apply flex items-center gap-3 mt-4;
}

.author-info {
  @apply text-left;
}

/* CTA Preview Styles */
.cta-preview {
  @apply py-16;
}

.cta-content {
  @apply max-w-4xl mx-auto text-center;
}

.cta-actions {
  @apply flex justify-center gap-4 flex-wrap;
}

.cta-button {
  @apply px-8 py-4 font-semibold rounded-md;
}

/* Style Guide Styles */
.style-guide {
  @apply p-8 space-y-8;
}

.style-section {
  @apply space-y-4;
}

.style-section-title {
  @apply text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2;
}

.color-palette {
  @apply grid grid-cols-2 md:grid-cols-4 gap-4;
}

.color-item {
  @apply flex items-center gap-3;
}

.color-swatch {
  @apply w-12 h-12 rounded border border-gray-300 dark:border-gray-600;
}

.color-info {
  @apply text-sm;
}

.color-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.color-value {
  @apply text-gray-600 dark:text-gray-400;
}

.typography-samples {
  @apply space-y-4;
}

.type-sample {
  @apply border-b border-gray-200 dark:border-gray-700 pb-4;
}

.spacing-samples {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.spacing-item {
  @apply text-center;
}

.spacing-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
}

.spacing-demo {
  @apply bg-blue-100 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-600 rounded text-sm font-mono;
}

.component-samples {
  @apply space-y-6;
}

.component-sample {
  @apply space-y-3;
}

.sample-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.button-group {
  @apply flex gap-3;
}

.form-elements {
  @apply space-y-3;
}

/* Accessibility Report Styles */
.accessibility-report {
  @apply p-8;
}

.accessibility-section {
  @apply space-y-6;
}

.accessibility-checks {
  @apply space-y-4;
}

.accessibility-check {
  @apply flex items-start gap-3 p-4 rounded-lg;
}

.accessibility-check.pass {
  @apply bg-green-50 dark:bg-green-900/20;
}

.accessibility-check.warning {
  @apply bg-yellow-50 dark:bg-yellow-900/20;
}

.check-content {
  @apply flex-1;
}

.check-name {
  @apply font-medium text-gray-900 dark:text-white;
}

.check-description {
  @apply text-sm text-gray-600 dark:text-gray-400;
}

.check-value {
  @apply text-sm font-mono text-gray-800 dark:text-gray-200 mt-1;
}

/* Theme Info */
.theme-info {
  @apply mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg;
}

.info-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.info-item {
  @apply flex justify-between items-center;
}

.info-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.info-value {
  @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.btn-primary {
  @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.btn-secondary {
  @apply bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center;
}

.btn-close {
  @apply p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}
</style>
<template>
  <div class="landing-page-container">
    <Head>
      <title>{{ meta.title }}</title>
      <meta name="description" :content="meta.description" />

      <!-- Open Graph meta tags -->
      <meta property="og:title" :content="meta.og_title" />
      <meta property="og:description" :content="meta.og_description" />
      <meta property="og:image" :content="meta.og_image" />

      <!-- CSRF Token -->
      <meta name="csrf-token" :content="csrfToken" />

      <!-- Analytics tracking code injection -->
      <script v-if="analyticsTrackingEnabled" v-html="decodedAnalyticsCode"></script>
    </Head>

    <!-- Main landing page content -->
    <div class="landing-page-wrapper" :id="`lp-${landingPage.id}`">
      <!-- Landing Page Title -->
      <div v-if="landingPage.title" class="landing-page-header">
        <h1 class="landing-page-title">{{ landingPage.title }}</h1>
        <p v-if="landingPage.description" class="landing-page-description">{{ landingPage.description }}</p>
      </div>

      <!-- Landing Page Content -->
      <div v-if="landingPage.content" class="landing-page-content">
        <!-- Dynamic content rendering based on landing page structure -->
        <div class="lp-content-sections" v-html="renderContentSections()"></div>
      </div>

      <!-- Form Section -->
      <div v-if="landingPage.form_config" class="landing-page-form">
        <ContactForm
          :config="landingPage.form_config"
          :settings="landingPage.settings"
          @submitted="handleFormSubmission"
        />
      </div>
    </div>

    <!-- Additional tracking script for form interactions -->
    <script v-if="analyticsTrackingEnabled" v-html="formInteractionTrackingScript"></script>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import ContactForm from '@/components/ContactForm.vue'

interface LandingPage {
  id: number
  name: string
  title: string
  description: string
  content?: any
  settings?: any
  form_config?: any
  target_audience: string
  campaign_type: string
  campaign_name?: string
}

interface MetaData {
  title: string
  description: string
  og_title: string
  og_description: string
  og_image?: string
}

interface AnalyticsTracking {
  enabled: boolean
  code: string
  last_updated: string
}

const props = defineProps<{
  landingPage: LandingPage
  meta: MetaData
  analytics_tracking?: AnalyticsTracking
}>()

const csrfToken = computed(() => {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
})

const analyticsTrackingEnabled = computed(() => {
  return props.analytics_tracking?.enabled || false
})

const decodedAnalyticsCode = computed(() => {
  if (!analyticsTrackingEnabled.value || !props.analytics_tracking?.code) {
    return ''
  }

  try {
    // Decode the base64 encoded analytics code
    return atob(props.analytics_tracking.code)
  } catch (error) {
    console.error('Failed to decode analytics tracking code:', error)
    return ''
  }
})

const formInteractionTrackingScript = computed(() => {
  if (!analyticsTrackingEnabled.value) {
    return ''
  }

  return `<script>(function(){document.addEventListener('DOMContentLoaded',function(){const forms=document.querySelectorAll('form');forms.forEach(function(form,index){form.addEventListener('focusin',function(e){if(window._etTrack){window._etTrack('focusin',{form_index:index,element:e.target.tagName,field_name:e.target.name||e.target.id})}})});const buttons=document.querySelectorAll('button,.btn,.cta-button');buttons.forEach(function(button,index){button.addEventListener('click',function(){if(window._etTrack){window._etTrack('cta_click',{button_index:index,button_text:button.textContent?.trim()||'',button_type:button.type||'button',button_class:button.className})}})});let startTime=Date.now();window.addEventListener('beforeunload',function(){let duration=Math.round((Date.now()-startTime)/1000);if(window._etTrack){window._etTrack('time_on_page',{duration_seconds:duration,page_url:window.location.href})}});setTimeout(function(){const documentHeight=document.documentElement.scrollHeight;const windowHeight=window.innerHeight;if(documentHeight>windowHeight*2){let maxScroll=0;const checkScroll=function(){const scrollTop=window.scrollY;const documentHeight=document.documentElement.scrollHeight;const windowHeight=window.innerHeight;const scrollPercent=Math.round((scrollTop/(documentHeight-windowHeight))*100);if(scrollPercent>maxScroll&&window._etTrack){maxScroll=scrollPercent;if(scrollPercent>=25||scrollPercent>=50||scrollPercent>=75){window._etTrack('scroll_depth',{depth_percent:scrollPercent,max_depth:maxScroll})}}};window.addEventListener('scroll',checkScroll);window.addEventListener('beforeunload',function(){const scrollTop=window.scrollY;const documentHeight=document.documentElement.scrollHeight;const windowHeight=window.innerHeight;const finalScrollPercent=Math.round((scrollTop/(documentHeight-windowHeight))*100);if(window._etTrack){window._etTrack('final_scroll_depth',{depth_percent:finalScrollPercent})}})}},1000)})()})</script>`
})

const handleFormSubmission = (formData: any) => {
  // Emit form submission event to parent component or handle here
  // You can add additional analytics tracking or processing here
  console.log('Form submitted:', formData)

  if (window._etTrack) {
    window._etTrack('form_submit_success', {
      form_name: formData.form_name || 'default',
      submission_count: 1,
      timestamp: new Date().toISOString(),
    });
  }
}

const renderContentSections = () => {
  // Render landing page content sections
  // This would typically use the landing page structure to render dynamic content
  if (!props.landingPage.content) {
    return '<div class="lp-section lp-hero"><h1>Welcome to our Landing Page</h1></div>'
  }

  const content = props.landingPage.content

  // Simple content rendering - can be enhanced based on content structure
  let html = ''

  if (content.sections) {
    content.sections.forEach((section: any, index: number) => {
      html += renderSection(section, index)
    })
  }

  return html
}

const renderSection = (section: any, index: number) => {
  const sectionClass = `lp-section lp-section-${section.type}`
  const sectionId = `section-${index}`

  let html = `<div class="${sectionClass}" id="${sectionId}">`

  switch (section.type) {
    case 'hero':
      html += renderHeroSection(section.config, index)
      break
    case 'feature':
    case 'features':
      html += renderFeaturesSection(section.config, index)
      break
    case 'testimonial':
    case 'testimonials':
      html += renderTestimonialsSection(section.config, index)
      break
    case 'cta':
      html += renderCTASection(section.config, index)
      break
    case 'text':
      html += renderTextSection(section.config, index)
      break
    default:
      html += renderDefaultSection(section.config, index)
  }

  html += '</div>'
  return html
}

const renderHeroSection = (config: any, index: number) => {
  return `
    <div class="lp-hero">
      <div class="lp-hero-content">
        <h1 class="lp-hero-title">${config.title || 'Hero Title'}</h1>
        <p class="lp-hero-subtitle">${config.subtitle || ''}</p>
        ${config.cta_text ? `<a href="#" class="lp-hero-cta lp-cta-button" data-cta="hero" data-section="${index}">${config.cta_text}</a>` : ''}
      </div>
      ${config.background_url ? `<div class="lp-hero-background"><img src="${config.background_url}" alt="Hero background"></div>` : ''}
    </div>
  `
}

const renderFeaturesSection = (config: any, index: number) => {
  const features = config.features || []
  let html = '<div class="lp-features">'

  if (config.title) {
    html += `<h2 class="lp-features-title">${config.title}</h2>`
  }

  features.forEach((feature: any, featureIndex: number) => {
    html += `
      <div class="lp-feature" data-feature-index="${featureIndex}" data-section="${index}">
        ${feature.icon ? `<div class="lp-feature-icon">${feature.icon}</div>` : ''}
        <h3 class="lp-feature-title">${feature.title || ''}</h3>
        <p class="lp-feature-description">${feature.description || ''}</p>
      </div>
    `
  })

  html += '</div>'
  return html
}

const renderTestimonialsSection = (config: any, index: number) => {
  const testimonials = config.testimonials || []
  let html = '<div class="lp-testimonials">'

  if (config.title) {
    html += `<h2 class="lp-testimonials-title">${config.title}</h2>`
  }

  testimonials.forEach((testimonial: any, testimonialIndex: number) => {
    html += `
      <div class="lp-testimonial" data-testimonial-index="${testimonialIndex}" data-section="${index}">
        ${testimonial.image ? `<div class="lp-testimonial-image"><img src="${testimonial.image}" alt="${testimonial.author}"></div>` : ''}
        <blockquote class="lp-testimonial-text">${testimonial.text || ''}</blockquote>
        <cite class="lp-testimonial-author">${testimonial.author || ''}</cite>
        <span class="lp-testimonial-company">${testimonial.company || ''}</span>
      </div>
    `
  })

  html += '</div>'
  return html
}

const renderCTASection = (config: any, index: number) => {
  return `
    <div class="lp-cta-section">
      <h2 class="lp-cta-title">${config.title || 'Ready to Get Started?'}</h2>
      <p class="lp-cta-description">${config.description || ''}</p>
      <a href="#" class="lp-cta-button" data-cta="section" data-section="${index}">${config.button_text || 'Get Started'}</a>
    </div>
  `
}

const renderTextSection = (config: any, index: number) => {
  return `
    <div class="lp-text-section" data-section="${index}">
      <h2 class="lp-text-title">${config.title || ''}</h2>
      <div class="lp-text-content">${config.content || ''}</div>
    </div>
  `
}

const renderDefaultSection = (config: any, index: number) => {
  return `
    <div class="lp-default-section" data-section="${index}">
      <h2>${config.title || 'Section'}</h2>
      <p>${config.description || 'Default section content'}</p>
    </div>
  `
}

onMounted(() => {
  // Track page ready event
  if (window._etTrack) {
    window._etTrack('page_ready', {
      landing_page_id: props.landingPage.id,
      timestamp: new Date().toISOString(),
      viewport_width: window.innerWidth,
      viewport_height: window.innerHeight,
    });
  }

  // Expose tracking for external use
  (window as any).landingPageTracking = {
    trackEvent: (eventType: string, eventData: any) => {
      if (window._etTrack) {
        window._etTrack(eventType, eventData);
      }
    },
    landingPageId: props.landingPage.id,
  };
})

// Type definitions for global window object
declare global {
  interface Window {
    _etTrack?: (eventType: string, eventData: any) => void
  }
}
</script>

<style scoped>
.landing-page-container {
  min-height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.landing-page-wrapper {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.landing-page-header {
  text-align: center;
  margin-bottom: 40px;
}

.landing-page-title {
  font-size: 2.5rem;
  font-weight: bold;
  color: #333;
  margin-bottom: 20px;
  line-height: 1.2;
}

.landing-page-description {
  font-size: 1.1rem;
  color: #666;
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.6;
}

.lp-content-sections {
  margin-bottom: 60px;
}

/* Landing Page Section Styles */
.lp-section {
  margin-bottom: 60px;
  padding: 40px 0;
  border-bottom: 1px solid #eee;
}

.lp-section:last-of-type {
  border-bottom: none;
}

/* Hero Section */
.lp-hero {
  display: flex;
  align-items: center;
  min-height: 60vh;
  text-align: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.lp-hero-content {
  max-width: 800px;
  margin: 0 auto;
  padding: 60px 20px;
  position: relative;
  z-index: 2;
}

.lp-hero-title {
  font-size: 3.5rem;
  font-weight: bold;
  margin-bottom: 20px;
  line-height: 1.1;
}

.lp-hero-subtitle {
  font-size: 1.3rem;
  margin-bottom: 30px;
  opacity: 0.9;
}

.lp-hero-cta {
  display: inline-block;
  padding: 15px 40px;
  background: #ff6b6b;
  color: white;
  text-decoration: none;
  border-radius: 50px;
  font-weight: bold;
  transition: all 0.3s ease;
  cursor: pointer;
}

.lp-hero-cta:hover {
  background: #ff5252;
  transform: translateY(-2px);
}

.lp-hero-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: hidden;
}

.lp-hero-background img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.2;
}

/* Features Section */
.lp-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 40px;
  padding: 20px 0;
}

.lp-features-title {
  text-align: center;
  font-size: 2.5rem;
  color: #333;
  margin-bottom: 60px;
  grid-column: 1 / -1;
}

.lp-feature {
  text-align: center;
  padding: 20px;
}

.lp-feature-icon {
  font-size: 3rem;
  margin-bottom: 20px;
  display: block;
}

.lp-feature-title {
  font-size: 1.5rem;
  color: #333;
  margin-bottom: 15px;
  font-weight: bold;
}

.lp-feature-description {
  color: #666;
  line-height: 1.6;
}

/* Testimonials Section */
.lp-testimonials {
  padding: 40px 0;
}

.lp-testimonials-title {
  text-align: center;
  font-size: 2.5rem;
  color: #333;
  margin-bottom: 60px;
}

.lp-testimonial {
  max-width: 600px;
  margin: 40px auto;
  text-align: center;
}

.lp-testimonial-image {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  margin: 0 auto 20px;
  overflow: hidden;
}

.lp-testimonial-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lp-testimonial-text {
  font-size: 1.2rem;
  font-style: italic;
  color: #555;
  margin-bottom: 20px;
  position: relative;
}

.lp-testimonial-text::before {
  content: '"';
  font-size: 4rem;
  color: #ddd;
  position: absolute;
  top: -30px;
  left: -20px;
}

.lp-testimonial-text::after {
  content: '"';
  font-size: 4rem;
  color: #ddd;
  position: absolute;
  bottom: -50px;
  right: -20px;
}

.lp-testimonial-author {
  font-weight: bold;
  color: #333;
  display: block;
  font-size: 1.1rem;
}

.lp-testimonial-company {
  color: #666;
  font-size: 0.9rem;
  display: block;
}

/* CTA Section */
.lp-cta-section {
  text-align: center;
  background: #f8f9fa;
  padding: 60px 20px;
  border-radius: 10px;
}

.lp-cta-title {
  font-size: 2.5rem;
  color: #333;
  margin-bottom: 20px;
}

.lp-cta-description {
  font-size: 1.2rem;
  color: #666;
  margin-bottom: 30px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.lp-cta-button {
  display: inline-block;
  padding: 15px 40px;
  background: #007bff;
  color: white;
  text-decoration: none;
  border-radius: 50px;
  font-weight: bold;
  transition: all 0.3s ease;
  cursor: pointer;
}

.lp-cta-button:hover {
  background: #0056b3;
  transform: translateY(-2px);
}

/* Text Section */
.lp-text-section {
  max-width: 800px;
  margin: 0 auto;
}

.lp-text-title {
  font-size: 2rem;
  color: #333;
  margin-bottom: 30px;
  text-align: center;
}

.lp-text-content {
  line-height: 1.8;
  color: #555;
  font-size: 1.1rem;
}

.lp-text-content h3 {
  color: #333;
  margin: 30px 0 15px 0;
}

.lp-text-content p {
  margin-bottom: 20px;
}

/* Default Section */
.lp-default-section {
  text-align: center;
  padding: 60px 20px;
  background: #f8f9fa;
  border-radius: 10px;
}

/* Landing Page Form */
.landing-page-form {
  margin-top: 60px;
}

/* CTA Buttons */
.lp-cta-button {
  text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
  .landing-page-container {
    padding: 10px;
  }

  .lp-hero-title {
    font-size: 2.5rem;
  }

  .lp-hero-content {
    padding: 40px 20px;
  }

  .lp-features {
    grid-template-columns: 1fr;
    gap: 30px;
  }

  .lp-features-title,
  .lp-cta-title {
    font-size: 2rem;
  }

  .lp-testimonials-title {
    font-size: 2rem;
  }

  .landing-page-title {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  .landing-page-container {
    padding: 5px;
  }

  .lp-hero-title {
    font-size: 2rem;
  }

  .lp-hero-subtitle {
    font-size: 1.1rem;
  }

  .landing-page-title {
    font-size: 1.5rem;
  }

  .landing-page-description {
    font-size: 1rem;
  }

  .lp-text-title {
    font-size: 1.5rem;
  }
}
</style>
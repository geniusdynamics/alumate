<template>
  <div class="homepage-layout">
    <!-- Enhanced SEO Head -->
    <Head>
      <title>{{ pageTitle }}</title>
      <meta name="description" :content="pageDescription">
      <meta name="keywords" :content="pageKeywords">
      <meta name="author" content="Alumni Platform">
      <meta name="robots" content="index, follow">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="canonical" :href="canonicalUrl">
      
      <!-- Open Graph tags -->
      <meta property="og:title" :content="pageTitle">
      <meta property="og:description" :content="pageDescription">
      <meta property="og:type" content="website">
      <meta property="og:url" :content="canonicalUrl">
      <meta property="og:image" :content="ogImage">
      <meta property="og:site_name" content="Alumni Platform">
      
      <!-- Twitter Card tags -->
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" :content="pageTitle">
      <meta name="twitter:description" :content="pageDescription">
      <meta name="twitter:image" :content="twitterImage">
      <meta name="twitter:site" content="@AlumniPlatform">
      
      <!-- Preload critical resources -->
      <link rel="preload" href="/fonts/inter-var.woff2" as="font" type="font/woff2" crossorigin>
      <link rel="preload" href="/images/hero-background.webp" as="image">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      
      <!-- DNS prefetch for external resources -->
      <link rel="dns-prefetch" href="//analytics.google.com">
      <link rel="dns-prefetch" href="//www.googletagmanager.com">
      
      <!-- Structured Data -->
      <script type="application/ld+json" v-html="organizationSchema"></script>
      <script type="application/ld+json" v-html="websiteSchema"></script>
      <script type="application/ld+json" v-html="breadcrumbSchema"></script>
    </Head>
    
    <!-- Accessibility enhancements -->
    <div id="skip-links" class="skip-links" aria-label="Skip navigation links">
      <a href="#main-content" class="skip-link">Skip to main content</a>
      <a href="#navigation" class="skip-link">Skip to navigation</a>
      <a href="#footer" class="skip-link">Skip to footer</a>
    </div>
    
    <!-- ARIA live regions for screen reader announcements -->
    <div id="sr-polite" aria-live="polite" aria-atomic="false" class="sr-only"></div>
    <div id="sr-assertive" aria-live="assertive" aria-atomic="false" class="sr-only"></div>
    <div id="sr-status" aria-live="polite" aria-atomic="true" class="sr-only"></div>
    
    <!-- Navigation Header -->
    <HomepageNavigation id="navigation" />
    
    <!-- Main content with proper semantic structure -->
    <main id="main-content" class="homepage-main" role="main" tabindex="-1">
      <h1 class="sr-only">{{ pageTitle }}</h1>
      <slot />
    </main>
    <!-- Enhanced footer with proper navigation -->
    <footer id="footer" class="homepage-footer" role="contentinfo">
      <div class="homepage-container-inner">
        <div class="footer-content">
          <nav class="footer-navigation" role="navigation" aria-label="Footer navigation">
            <div class="footer-links">
              <a href="/privacy" class="footer-link">Privacy Policy</a>
              <a href="/terms" class="footer-link">Terms of Service</a>
              <a href="/contact" class="footer-link">Contact</a>
              <a href="/accessibility" class="footer-link">Accessibility</a>
              <a href="/sitemap" class="footer-link">Sitemap</a>
            </div>
          </nav>
          <div class="footer-copyright">
            <p>&copy; {{ currentYear }} Alumni Platform. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { accessibilityService } from '@/services/AccessibilityService'
import { seoService } from '@/services/SEOService'
import HomepageNavigation from '@/components/navigation/HomepageNavigation.vue'
interface Props {
  title?: string
  description?: string
  keywords?: string
}
const props = withDefaults(defineProps<Props>(), {
  title: 'Connect with Your Alumni Network - Professional Networking Platform',
  description: 'Join thousands of alumni advancing their careers through meaningful connections, mentorship, and professional opportunities.',
  keywords: 'alumni network, professional networking, career advancement, mentorship'
})
const currentYear = computed(() => new Date().getFullYear())
// SEO computed properties
const pageTitle = computed(() => props.title)
const pageDescription = computed(() => props.description)
const pageKeywords = computed(() => props.keywords)
const canonicalUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.href
  }
  return 'https://alumniplatform.com'
})
const ogImage = computed(() => 'https://alumniplatform.com/images/og-homepage.jpg')
const twitterImage = computed(() => 'https://alumniplatform.com/images/twitter-homepage.jpg')
// Structured Data
const organizationSchema = computed(() => JSON.stringify({
  '@context': 'https://schema.org',
  '@type': 'Organization',
  name: 'Alumni Platform',
  url: 'https://alumniplatform.com',
  logo: 'https://alumniplatform.com/images/logo.png',
  description: 'Professional alumni networking platform connecting graduates worldwide',
  sameAs: [
    'https://twitter.com/AlumniPlatform',
    'https://linkedin.com/company/alumni-platform',
    'https://facebook.com/AlumniPlatform'
  ],
  contactPoint: {
    '@type': 'ContactPoint',
    telephone: '+1-555-0123',
    contactType: 'customer service',
    email: 'support@alumniplatform.com'
  }
}, null, 2))
const websiteSchema = computed(() => JSON.stringify({
  '@context': 'https://schema.org',
  '@type': 'WebSite',
  name: 'Alumni Platform',
  url: 'https://alumniplatform.com',
  description: props.description,
  potentialAction: {
    '@type': 'SearchAction',
    target: {
      '@type': 'EntryPoint',
      urlTemplate: 'https://alumniplatform.com/search?q={search_term_string}'
    },
    'query-input': 'required name=search_term_string'
  }
}, null, 2))
const breadcrumbSchema = computed(() => JSON.stringify({
  '@context': 'https://schema.org',
  '@type': 'BreadcrumbList',
  itemListElement: [
    {
      '@type': 'ListItem',
      position: 1,
      name: 'Home',
      item: 'https://alumniplatform.com'
    }
  ]
}, null, 2))
onMounted(() => {
  // Initialize accessibility features
  accessibilityService.announce('Alumni Platform homepage loaded', 'polite')
  
  // Update SEO metadata
  seoService.updateMetadata({
    title: props.title,
    description: props.description,
    keywords: props.keywords.split(', '),
    canonical: canonicalUrl.value,
    ogTitle: props.title,
    ogDescription: props.description,
    ogImage: ogImage.value,
    ogUrl: canonicalUrl.value,
    ogType: 'website',
    twitterCard: 'summary_large_image',
    twitterTitle: props.title,
    twitterDescription: props.description,
    twitterImage: twitterImage.value,
    twitterSite: '@AlumniPlatform'
  })
  
  // Validate SEO and accessibility
  setTimeout(() => {
    const seoValidation = seoService.validateSEO()
    if (!seoValidation.valid) {
      console.warn('SEO issues found:', seoValidation.issues)
    }
    
    const headingValidation = accessibilityService.validateHeadingHierarchy()
    if (!headingValidation.valid) {
      console.warn('Heading hierarchy issues:', headingValidation.issues)
    }
  }, 1000)
})
onUnmounted(() => {
  // Cleanup accessibility features
  accessibilityService.cleanup()
  seoService.clearStructuredData()
})
</script>
<style scoped>
.homepage-layout {
  @apply min-h-screen flex flex-col;
}
.skip-link {
  @apply absolute -top-10 left-4 bg-blue-600 text-white px-4 py-2 rounded;
  @apply focus:top-4 transition-all duration-200 z-50;
}
.homepage-main {
  @apply flex-1;
}
.homepage-footer {
  @apply bg-gray-900 text-white py-8;
}
.footer-content {
  @apply flex flex-col md:flex-row justify-between items-center gap-4;
}
.footer-links {
  @apply flex gap-6;
}
.footer-link {
  @apply text-gray-300 hover:text-white transition-colors duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900;
}
.footer-copyright {
  @apply text-gray-400 text-sm;
}
/* Mobile optimizations */
@media (max-width: 767px) {
  .footer-content {
    @apply text-center;
  }
  
  .footer-links {
    @apply flex-wrap justify-center gap-4;
  }
}
</style>
<style scoped>
/* Accessibility styles */
.sr-only {
  position: absolute !important;
  width: 1px !important;
  height: 1px !important;
  padding: 0 !important;
  margin: -1px !important;
  overflow: hidden !important;
  clip: rect(0, 0, 0, 0) !important;
  white-space: nowrap !important;
  border: 0 !important;
}
.skip-links {
  position: absolute;
  top: -40px;
  left: 6px;
  z-index: 1000;
}
.skip-link {
  position: absolute;
  left: -10000px;
  top: auto;
  width: 1px;
  height: 1px;
  overflow: hidden;
  background: #000;
  color: #fff;
  padding: 8px 16px;
  text-decoration: none;
  border-radius: 4px;
  font-weight: bold;
  font-size: 14px;
  transition: all 0.2s ease;
}
.skip-link:focus {
  position: absolute;
  left: 6px;
  top: 6px;
  width: auto;
  height: auto;
  overflow: visible;
  z-index: 1001;
  outline: 2px solid #4f46e5;
  outline-offset: 2px;
}
/* Layout styles */
.homepage-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.homepage-main {
  flex: 1;
  outline: none;
}
.homepage-main:focus {
  outline: none;
}
/* Footer styles */
.homepage-footer {
  background-color: #f8fafc;
  border-top: 1px solid #e2e8f0;
  padding: 2rem 0;
  margin-top: auto;
}
.homepage-container-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}
.footer-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  text-align: center;
}
.footer-navigation {
  margin-bottom: 1rem;
}
.footer-links {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 2rem;
}
.footer-link {
  color: #64748b;
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.2s ease;
}
.footer-link:hover,
.footer-link:focus {
  color: #4f46e5;
  text-decoration: underline;
}
.footer-link:focus {
  outline: 2px solid #4f46e5;
  outline-offset: 2px;
  border-radius: 2px;
}
.footer-copyright {
  color: #64748b;
  font-size: 0.875rem;
}
.footer-copyright p {
  margin: 0;
}
/* Responsive design */
@media (min-width: 768px) {
  .footer-content {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    text-align: left;
  }
  
  .footer-navigation {
    margin-bottom: 0;
  }
  
  .footer-links {
    justify-content: flex-start;
  }
}
/* High contrast mode support */
@media (prefers-contrast: high) {
  .skip-link {
    background: #000;
    color: #fff;
    border: 2px solid #fff;
  }
  
  .footer-link {
    color: #000;
  }
  
  .footer-link:hover,
  .footer-link:focus {
    color: #4f46e5;
    background: #fff;
  }
}
/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .skip-link,
  .footer-link {
    transition: none;
  }
}
/* Focus visible support */
.skip-link:focus-visible,
.footer-link:focus-visible {
  outline: 2px solid #4f46e5;
  outline-offset: 2px;
}
/* Print styles */
@media print {
  .skip-links,
  .homepage-footer {
    display: none;
  }
}
</style>

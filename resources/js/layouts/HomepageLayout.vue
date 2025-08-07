<template>
  <div class="homepage-layout">
    <!-- SEO Head -->
    <Head>
      <title>{{ pageTitle }}</title>
      <meta name="description" :content="pageDescription">
      <meta name="keywords" :content="pageKeywords">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta property="og:title" :content="pageTitle">
      <meta property="og:description" :content="pageDescription">
      <meta property="og:type" content="website">
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" :content="pageTitle">
      <meta name="twitter:description" :content="pageDescription">
      
      <!-- Preload critical resources -->
      <link rel="preload" href="/fonts/instrument-sans.woff2" as="font" type="font/woff2" crossorigin>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    </Head>

    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Main content -->
    <main id="main-content" class="homepage-main" role="main">
      <slot />
    </main>

    <!-- Footer (minimal for homepage) -->
    <footer class="homepage-footer" role="contentinfo">
      <div class="homepage-container-inner">
        <div class="footer-content">
          <div class="footer-links">
            <a href="/privacy" class="footer-link">Privacy Policy</a>
            <a href="/terms" class="footer-link">Terms of Service</a>
            <a href="/contact" class="footer-link">Contact</a>
          </div>
          <div class="footer-copyright">
            <p>&copy; {{ currentYear }} Alumni Platform. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

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

const pageTitle = computed(() => props.title)
const pageDescription = computed(() => props.description)
const pageKeywords = computed(() => props.keywords)
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
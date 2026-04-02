<template>
  <footer class="mega-footer" role="contentinfo">
    <!-- Main Footer Content -->
    <div class="mega-footer__main">
      <div class="mega-footer__container">
        <!-- Company Branding Section -->
        <div class="mega-footer__brand">
          <div class="mega-footer__logo">
            <MainLogoIcon class="h-8 w-auto" />
            <span class="mega-footer__brand-name">Alumate</span>
          </div>
          <p class="mega-footer__mission">
            Committed to higher education's success, Alumate is on a mission to ensure 
            every learner has access to the people and networks needed to succeed.
          </p>
          
          <!-- Newsletter Signup -->
          <div class="mega-footer__newsletter">
            <h3 class="mega-footer__newsletter-title">Get research &amp; insights delivered monthly.</h3>
            <form class="mega-footer__newsletter-form" @submit.prevent="handleNewsletterSubmit">
              <div class="mega-footer__newsletter-input-group">
                <input 
                  v-model="newsletterEmail"
                  type="email" 
                  placeholder="Enter your email"
                  class="mega-footer__newsletter-input"
                  required
                  aria-label="Email address for newsletter"
                />
                <button 
                  type="submit" 
                  class="mega-footer__newsletter-button"
                  :disabled="isSubmittingNewsletter"
                  aria-label="Subscribe to newsletter"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                  </svg>
                </button>
              </div>
            </form>
          </div>

          <!-- Social Media Links -->
          <div class="mega-footer__social">
            <a href="https://linkedin.com/company/alumate" class="mega-footer__social-link" aria-label="LinkedIn">
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
              </svg>
            </a>
            <a href="https://twitter.com/alumate" class="mega-footer__social-link" aria-label="Twitter">
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
            </a>
            <a href="https://facebook.com/alumate" class="mega-footer__social-link" aria-label="Facebook">
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>
            <a href="https://instagram.com/alumate" class="mega-footer__social-link" aria-label="Instagram">
              <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.297-3.323C5.902 8.198 7.053 7.708 8.35 7.708s2.448.49 3.323 1.297c.897.875 1.387 2.026 1.387 3.323s-.49 2.448-1.297 3.323c-.875.897-2.026 1.387-3.323 1.387zm7.718 0c-1.297 0-2.448-.49-3.323-1.297-.897-.875-1.387-2.026-1.387-3.323s.49-2.448 1.297-3.323c.875-.897 2.026-1.387 3.323-1.387s2.448.49 3.323 1.297c.897.875 1.387 2.026 1.387 3.323s-.49 2.448-1.297 3.323c-.875.897-2.026 1.387-3.323 1.387z"/>
              </svg>
            </a>
          </div>
        </div>

        <!-- Navigation Columns -->
        <div class="mega-footer__nav">
          <!-- Solutions Column -->
          <div class="mega-footer__column">
            <h3 class="mega-footer__column-title">Solutions</h3>
            <ul class="mega-footer__links">
              <li><a href="/solutions/career-services" class="mega-footer__link">Career Services</a></li>
              <li><a href="/solutions/alumni-relations" class="mega-footer__link">Alumni Relations</a></li>
              <li><a href="/solutions/employer-relations" class="mega-footer__link">Employer Relations</a></li>
              <li><a href="/solutions/outcome-analytics" class="mega-footer__link">Outcome Data Analytics</a></li>
              <li><a href="/solutions/experiential-learning" class="mega-footer__link">Experiential Learning</a></li>
              <li><a href="/solutions/student-alumni-network" class="mega-footer__link">Student-Alumni Network</a></li>
            </ul>
          </div>

          <!-- Use Cases Column -->
          <div class="mega-footer__column">
            <h3 class="mega-footer__column-title">Use Cases</h3>
            <ul class="mega-footer__links">
              <li><a href="/use-cases/university-career-centers" class="mega-footer__link">University Career Centers</a></li>
              <li><a href="/use-cases/alumni-relations-offices" class="mega-footer__link">Alumni Relations Offices</a></li>
              <li><a href="/use-cases/institutional-research" class="mega-footer__link">Institutional Research</a></li>
              <li><a href="/use-cases/hiring-mba-talent" class="mega-footer__link">Hiring MBA Talent</a></li>
              <li><a href="/use-cases/hiring-law-school-talent" class="mega-footer__link">Hiring Law School Talent</a></li>
              <li><a href="/use-cases/hiring-university-wide" class="mega-footer__link">Hiring University-wide</a></li>
            </ul>
          </div>

          <!-- Products Column -->
          <div class="mega-footer__column">
            <h3 class="mega-footer__column-title">Products</h3>
            <ul class="mega-footer__links">
              <li><a href="/products/career-cloud" class="mega-footer__link">Career Cloud</a></li>
              <li><a href="/products/alumni-cloud" class="mega-footer__link">Alumni Cloud</a></li>
              <li><a href="/products/hiring-cloud" class="mega-footer__link">Hiring Cloud</a></li>
              <li><a href="/products/virtual-events" class="mega-footer__link">Virtual Events</a></li>
              <li><a href="/products/recruiting-intelligence" class="mega-footer__link">Recruiting Intelligence</a></li>
              <li><a href="/products/multischool-interviews" class="mega-footer__link">Multischool Interviews</a></li>
            </ul>
          </div>

          <!-- Resources Column -->
          <div class="mega-footer__column">
            <h3 class="mega-footer__column-title">Resources</h3>
            <ul class="mega-footer__links">
              <li><a href="/resources/success-stories" class="mega-footer__link">Success Stories</a></li>
              <li><a href="/resources/blog" class="mega-footer__link">Blog</a></li>
              <li><a href="/resources/webinars" class="mega-footer__link">Webinars</a></li>
              <li><a href="/resources/events-calendar" class="mega-footer__link">Events Calendar</a></li>
              <li><a href="/resources/research-library" class="mega-footer__link">Research Library</a></li>
              <li><a href="/resources/integrations" class="mega-footer__link">Add-ons &amp; Integrations</a></li>
            </ul>
          </div>

          <!-- Company Column -->
          <div class="mega-footer__column">
            <h3 class="mega-footer__column-title">Company</h3>
            <ul class="mega-footer__links">
              <li><a href="/company/about" class="mega-footer__link">About Alumate</a></li>
              <li><a href="/company/careers" class="mega-footer__link">Careers</a></li>
              <li><a href="/company/contact" class="mega-footer__link">Contact</a></li>
              <li><a href="/company/support" class="mega-footer__link">Support</a></li>
              <li><a href="/company/work-for-us" class="mega-footer__link">Work for Us</a></li>
            </ul>
          </div>

          <!-- Get Started Column -->
          <div class="mega-footer__column">
            <h3 class="mega-footer__column-title">Get Started</h3>
            <ul class="mega-footer__links">
              <li><a href="/get-started/speak-with-expert" class="mega-footer__link mega-footer__link--cta">Speak with an Expert</a></li>
              <li><a href="/get-started/download-impact-report" class="mega-footer__link">Download Impact Report</a></li>
              <li><a href="/get-started/university-demo" class="mega-footer__link">University Demo</a></li>
              <li><a href="/get-started/employer-demo" class="mega-footer__link">Employer Demo</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Bottom -->
    <div class="mega-footer__bottom">
      <div class="mega-footer__container">
        <div class="mega-footer__bottom-content">
          <div class="mega-footer__copyright">
            <p>&copy; {{ currentYear }} Alumate. All rights reserved.</p>
          </div>
          <nav class="mega-footer__legal" role="navigation" aria-label="Legal navigation">
            <a href="/legal/privacy-policy" class="mega-footer__legal-link">Privacy Policy</a>
            <a href="/legal/terms-of-service" class="mega-footer__legal-link">Terms of Service</a>
            <a href="/legal/terms-of-use" class="mega-footer__legal-link">Terms of Use</a>
            <a href="/accessibility" class="mega-footer__legal-link">Accessibility</a>
            <a href="/sitemap" class="mega-footer__legal-link">Sitemap</a>
          </nav>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import MainLogoIcon from '@/Components/MainLogoIcon.vue';
import { computed, ref } from 'vue';

// Newsletter functionality
const newsletterEmail = ref('');
const isSubmittingNewsletter = ref(false);

const currentYear = computed(() => new Date().getFullYear());

const handleNewsletterSubmit = async () => {
  if (!newsletterEmail.value || isSubmittingNewsletter.value) return;
  
  isSubmittingNewsletter.value = true;
  
  try {
    // TODO: Implement newsletter subscription API call
    logger.log('Newsletter subscription:', newsletterEmail.value);
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Reset form on success
    newsletterEmail.value = '';
    
    // TODO: Show success message
    // TODO-toast: alert('Thank you for subscribing to our newsletter!');
  } catch (error) {
    console.error('Newsletter subscription failed:', error);
    // TODO: Show error message
    // TODO-toast: alert('Failed to subscribe. Please try again.');
  } finally {
    isSubmittingNewsletter.value = false;
  }
};
</script>

<style scoped>
.mega-footer {
  @apply bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-900 text-white relative overflow-hidden;
}

.mega-footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at top right, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
              radial-gradient(circle at bottom left, rgba(99, 102, 241, 0.15) 0%, transparent 40%);
  z-index: 0;
}

.mega-footer__main {
  @apply py-16 relative z-10;
}

.mega-footer__container {
  @apply mx-auto max-w-7xl px-4 sm:px-6 lg:px-8;
}

.mega-footer__main .mega-footer__container {
  @apply grid gap-12 lg:grid-cols-12;
}

.mega-footer__brand {
  @apply lg:col-span-4;
}

.mega-footer__logo {
  @apply mb-6 flex items-center gap-3;
}

.mega-footer__brand-name {
  @apply text-2xl font-bold bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent;
  font-weight: 800;
  letter-spacing: -0.025em;
}

.mega-footer__mission {
  @apply mb-8 text-base leading-relaxed text-gray-300;
}

.mega-footer__newsletter {
  @apply mb-8 relative;
}

.mega-footer__newsletter-title {
  @apply mb-4 text-lg font-semibold text-white relative;
}

.mega-footer__newsletter-title::after {
  content: '';
  @apply absolute bottom-0 left-0 h-0.5 w-1/2 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full;
  transition: width 0.3s ease;
}

.mega-footer__newsletter-title:hover::after {
  @apply w-full;
}

.mega-footer__newsletter-form {
  @apply w-full;
}

.mega-footer__newsletter-input-group {
  @apply flex rounded-2xl bg-white/10 backdrop-blur-xl p-1 border border-white/20 shadow-lg transition-all duration-300 hover:shadow-xl hover:border-white/30;
}

.mega-footer__newsletter-input {
  @apply flex-1 bg-transparent px-4 py-3 text-white placeholder-gray-400 focus:outline-none;
  font-weight: 500;
}

.mega-footer__newsletter-button {
  @apply rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 px-4 py-3 text-white transition-all duration-300 hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-slate-900 disabled:opacity-50;
  font-weight: 600;
  letter-spacing: 0.025em;
  text-transform: uppercase;
}

.mega-footer__social {
  @apply flex gap-4;
}

.mega-footer__social-link {
  @apply rounded-2xl bg-white/10 backdrop-blur-xl p-3 text-gray-300 transition-all duration-300 hover:bg-white/20 hover:text-white hover:scale-110 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900;
}

.mega-footer__nav {
  @apply grid gap-8 sm:grid-cols-2 lg:col-span-8 lg:grid-cols-6 relative z-10;
}

.mega-footer__column {
  @apply space-y-4;
}

.mega-footer__column-title {
  @apply text-lg font-semibold text-white relative;
}

.mega-footer__column-title::after {
  content: '';
  @apply absolute bottom-0 left-0 h-0.5 w-1/3 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full;
  transition: width 0.3s ease;
}

.mega-footer__column-title:hover::after {
  @apply w-full;
}

.mega-footer__links {
  @apply space-y-3;
}

.mega-footer__link {
  @apply block text-gray-300 transition-all duration-300 hover:text-white hover:translate-x-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900 relative;
  font-weight: 500;
}

.mega-footer__link--cta {
  @apply rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-3 font-medium text-white transition-all duration-300 hover:shadow-lg hover:scale-105 hover:translate-x-0;
  font-weight: 600;
  letter-spacing: 0.025em;
  text-transform: uppercase;
}

.mega-footer__bottom {
  @apply border-t border-white/20 py-8 relative z-10;
  background: linear-gradient(to right, transparent, rgba(255,255,255,0.05), transparent);
}

.mega-footer__bottom-content {
  @apply flex flex-col items-center justify-between gap-6 md:flex-row;
}

.mega-footer__copyright {
  @apply text-sm text-gray-400 font-medium;
}

.mega-footer__legal {
  @apply flex flex-wrap justify-center gap-6;
}

.mega-footer__legal-link {
  @apply text-sm text-gray-400 transition-colors duration-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900 relative;
}

.mega-footer__legal-link::after {
  content: '';
  @apply absolute bottom-0 left-0 h-0.5 w-0 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full;
  transition: width 0.3s ease;
}

.mega-footer__legal-link:hover::after {
  @apply w-full;
}

/* Mobile optimizations */
@media (max-width: 1023px) {
  .mega-footer__main .mega-footer__container {
    @apply grid-cols-1;
  }
  
  .mega-footer__brand {
    @apply mb-8;
  }
  
  .mega-footer__nav {
    @apply grid-cols-2;
  }
}

@media (max-width: 639px) {
  .mega-footer__nav {
    @apply grid-cols-1;
  }
  
  .mega-footer__bottom-content {
    @apply text-center;
  }
  
  .mega-footer__legal {
    @apply justify-center;
  }
}

/* Enhanced hover effects */
.mega-footer__link {
  @apply relative;
}

.mega-footer__link::after {
  content: '';
  @apply absolute bottom-0 left-0 h-0.5 w-0 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full;
  transition: width 0.3s ease;
}

.mega-footer__link:hover::after {
  @apply w-full;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .mega-footer__link::after,
  .mega-footer__social-link,
  .mega-footer__newsletter-button,
  .mega-footer__legal-link,
  .mega-footer__column-title::after,
  .mega-footer__newsletter-title::after {
    @apply transition-none;
  }
  
  .mega-footer__link:hover::after {
    @apply w-0;
  }
}

/* Focus improvements for better accessibility */
.mega-footer__link:focus,
.mega-footer__social-link:focus,
.mega-footer__newsletter-button:focus,
.mega-footer__legal-link:focus {
  @apply ring-2 ring-white ring-offset-2 ring-offset-slate-900;
}

/* Glassmorphism enhancements */
.glass-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
}

/* Animation for floating elements */
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.floating-element {
  animation: float 6s ease-in-out infinite;
}

/* Gradient text utility */
.gradient-text {
  background: linear-gradient(135deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
</style>
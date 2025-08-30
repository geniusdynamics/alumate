<template>
  <div class="cta-examples">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          CTA Component Examples
        </h1>
        <p class="text-xl text-gray-600 max-w-3xl mx-auto">
          Explore our comprehensive collection of call-to-action components with conversion tracking, 
          A/B testing, and accessibility features.
        </p>
      </div>

      <!-- CTA Buttons Section -->
      <section class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">CTA Buttons</h2>
        
        <!-- Button Styles -->
        <div class="mb-8">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Button Styles</h3>
          <div class="flex flex-wrap gap-4">
            <CTABase
              v-for="style in buttonStyles"
              :key="style"
              :config="{
                type: 'button',
                buttonConfig: {
                  text: `${style.charAt(0).toUpperCase() + style.slice(1)} Button`,
                  url: '/demo',
                  style: style,
                  size: 'md'
                },
                trackingEnabled: true
              }"
              @click="handleCTAClick"
              @conversion="handleConversion"
            />
          </div>
        </div>

        <!-- Button Sizes -->
        <div class="mb-8">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Button Sizes</h3>
          <div class="flex flex-wrap items-center gap-4">
            <CTABase
              v-for="size in buttonSizes"
              :key="size"
              :config="{
                type: 'button',
                buttonConfig: {
                  text: `${size.toUpperCase()} Button`,
                  url: '/demo',
                  style: 'primary',
                  size: size
                },
                trackingEnabled: true
              }"
              @click="handleCTAClick"
            />
          </div>
        </div>

        <!-- Buttons with Icons -->
        <div class="mb-8">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Buttons with Icons</h3>
          <div class="flex flex-wrap gap-4">
            <CTABase
              :config="{
                type: 'button',
                buttonConfig: {
                  text: 'Download App',
                  url: '/download',
                  style: 'primary',
                  size: 'lg',
                  icon: {
                    name: 'download',
                    position: 'left'
                  }
                }
              }"
              @click="handleCTAClick"
            />
            <CTABase
              :config="{
                type: 'button',
                buttonConfig: {
                  text: 'Learn More',
                  url: '/about',
                  style: 'outline',
                  size: 'md',
                  icon: {
                    name: 'arrow-right',
                    position: 'right'
                  }
                }
              }"
              @click="handleCTAClick"
            />
            <CTABase
              :config="{
                type: 'button',
                buttonConfig: {
                  text: 'Play Video',
                  url: '/video',
                  style: 'secondary',
                  size: 'md',
                  icon: {
                    name: 'play',
                    position: 'only'
                  }
                }
              }"
              @click="handleCTAClick"
            />
          </div>
        </div>

        <!-- Loading States -->
        <div class="mb-8">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Loading States</h3>
          <div class="flex flex-wrap gap-4">
            <CTABase
              :config="{
                type: 'button',
                buttonConfig: {
                  text: 'Spinner Loading',
                  url: '/demo',
                  style: 'primary',
                  size: 'md',
                  loading: true,
                  animation: {
                    loading: 'spinner'
                  }
                }
              }"
            />
            <CTABase
              :config="{
                type: 'button',
                buttonConfig: {
                  text: 'Dots Loading',
                  url: '/demo',
                  style: 'secondary',
                  size: 'md',
                  loading: true,
                  animation: {
                    loading: 'dots'
                  }
                }
              }"
            />
            <CTABase
              :config="{
                type: 'button',
                buttonConfig: {
                  text: 'Pulse Loading',
                  url: '/demo',
                  style: 'outline',
                  size: 'md',
                  loading: true,
                  animation: {
                    loading: 'pulse'
                  }
                }
              }"
            />
          </div>
        </div>
      </section>

      <!-- CTA Banners Section -->
      <section class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">CTA Banners</h2>
        
        <!-- Center Aligned Banner -->
        <div class="mb-8">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Center Aligned Banner</h3>
          <CTABase
            :config="{
              type: 'banner',
              bannerConfig: {
                title: 'Join 50,000+ Alumni Worldwide',
                subtitle: 'Connect, Network, and Advance Your Career',
                description: 'Access exclusive job opportunities, mentorship programs, and professional development resources.',
                layout: 'center-aligned',
                height: 'medium',
                backgroundColor: '#1e40af',
                textColor: '#ffffff',
                primaryCTA: {
                  text: 'Get Started Free',
                  url: '/signup',
                  style: 'primary',
                  size: 'lg'
                },
                secondaryCTA: {
                  text: 'Watch Demo',
                  url: '/demo',
                  style: 'outline',
                  size: 'md'
                }
              },
              trackingEnabled: true
            }"
            @click="handleCTAClick"
            @conversion="handleConversion"
          />
        </div>

        <!-- Split Layout Banner -->
        <div class="mb-8">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Split Layout Banner</h3>
          <CTABase
            :config="{
              type: 'banner',
              bannerConfig: {
                title: 'For Universities & Institutions',
                subtitle: 'Enhance Alumni Engagement',
                description: 'Provide your graduates with powerful networking tools and career advancement opportunities.',
                layout: 'split',
                height: 'medium',
                backgroundColor: '#059669',
                textColor: '#ffffff',
                primaryCTA: {
                  text: 'Schedule Consultation',
                  url: '/consultation',
                  style: 'primary',
                  size: 'lg'
                },
                secondaryCTA: {
                  text: 'View Case Studies',
                  url: '/case-studies',
                  style: 'outline',
                  size: 'md'
                }
              }
            }"
            @click="handleCTAClick"
          />
        </div>

        <!-- Compact Banner -->
        <div class="mb-8">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">Compact Banner</h3>
          <CTABase
            :config="{
              type: 'banner',
              bannerConfig: {
                title: 'Limited Time Offer',
                description: 'Get 3 months free when you sign up today!',
                layout: 'center-aligned',
                height: 'compact',
                backgroundColor: '#7c3aed',
                textColor: '#ffffff',
                primaryCTA: {
                  text: 'Claim Offer',
                  url: '/offer',
                  style: 'primary',
                  size: 'md'
                }
              }
            }"
            @click="handleCTAClick"
          />
        </div>
      </section>

      <!-- Inline Links Section -->
      <section class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Inline Links</h2>
        
        <div class="prose max-w-4xl">
          <p class="text-lg text-gray-700 mb-6">
            Our platform connects alumni from leading universities worldwide. 
            <CTABase
              :config="{
                type: 'inline-link',
                inlineLinkConfig: {
                  text: 'Learn more about our mission',
                  url: '/about',
                  style: 'arrow',
                  size: 'base'
                }
              }"
              @click="handleCTAClick"
            />
            and discover how we're transforming career development for graduates.
          </p>

          <p class="text-lg text-gray-700 mb-6">
            Ready to get started? 
            <CTABase
              :config="{
                type: 'inline-link',
                inlineLinkConfig: {
                  text: 'Create your free account',
                  url: '/signup',
                  style: 'button-like',
                  size: 'base'
                }
              }"
              @click="handleCTAClick"
            />
            or 
            <CTABase
              :config="{
                type: 'inline-link',
                inlineLinkConfig: {
                  text: 'browse our resources',
                  url: '/resources',
                  style: 'underline',
                  size: 'base'
                }
              }"
              @click="handleCTAClick"
            />
            to learn more.
          </p>

          <p class="text-lg text-gray-700 mb-6">
            For detailed information, 
            <CTABase
              :config="{
                type: 'inline-link',
                inlineLinkConfig: {
                  text: 'download our comprehensive guide',
                  url: '/downloads/guide.pdf',
                  style: 'default',
                  size: 'base',
                  downloadAttribute: 'alumni-guide.pdf',
                  icon: {
                    name: 'download',
                    position: 'left'
                  }
                }
              }"
              @click="handleCTAClick"
            />
            or visit our 
            <CTABase
              :config="{
                type: 'inline-link',
                inlineLinkConfig: {
                  text: 'external research portal',
                  url: 'https://research.example.com',
                  style: 'external',
                  size: 'base',
                  openInNewTab: true
                }
              }"
              @click="handleCTAClick"
            />
            for the latest industry insights.
          </p>
        </div>
      </section>

      <!-- A/B Testing Demo -->
      <section class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">A/B Testing Demo</h2>
        <div class="bg-gray-50 p-6 rounded-lg">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">
            Signup Button Test ({{ currentVariant?.name || 'Loading...' }})
          </h3>
          <p class="text-gray-600 mb-6">
            This button is part of an A/B test. Refresh the page to see different variants.
          </p>
          
          <CTABase
            v-if="abTestConfig"
            :config="abTestConfig"
            @click="handleCTAClick"
            @conversion="handleConversion"
          />
          
          <div class="mt-4 text-sm text-gray-500">
            <p>Test ID: signup_button_test</p>
            <p>Variant: {{ currentVariant?.id || 'N/A' }}</p>
            <p>Session ID: {{ sessionId }}</p>
          </div>
        </div>
      </section>

      <!-- Analytics Dashboard -->
      <section class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Analytics Dashboard</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Clicks</h3>
            <p class="text-3xl font-bold text-blue-600">{{ analytics.totalClicks }}</p>
          </div>
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Conversions</h3>
            <p class="text-3xl font-bold text-green-600">{{ analytics.totalConversions }}</p>
          </div>
          <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Conversion Rate</h3>
            <p class="text-3xl font-bold text-purple-600">{{ analytics.conversionRate }}%</p>
          </div>
        </div>
        
        <div class="mt-6">
          <button
            @click="clearAnalytics"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
          >
            Clear Analytics Data
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import CTABase from './CTABase.vue'
import { useABTesting, createABTest, createEqualWeightVariants } from '@/composables/useABTesting'
import { useConversionTracking } from '@/composables/useConversionTracking'
import type { CTAComponentConfig } from '@/types/components'

// Component data
const buttonStyles = ['primary', 'secondary', 'outline', 'ghost', 'link']
const buttonSizes = ['xs', 'sm', 'md', 'lg', 'xl']

// A/B Testing
const { getVariant, trackImpression } = useABTesting()
const { trackCTAClick, trackConversion, getConversionMetrics, clearConversionData, sessionId } = useConversionTracking()

const currentVariant = ref<any>(null)
const abTestConfig = ref<CTAComponentConfig | null>(null)

// Analytics
const analytics = ref({
  totalClicks: 0,
  totalConversions: 0,
  conversionRate: 0
})

// Set up A/B test
onMounted(() => {
  const testConfig = createABTest('signup_button_test', createEqualWeightVariants([
    {
      id: 'control',
      name: 'Original Button',
      config: {
        buttonConfig: {
          text: 'Join Our Network',
          url: '/signup',
          style: 'primary',
          size: 'lg'
        }
      }
    },
    {
      id: 'variant_a',
      name: 'Action-Focused',
      config: {
        buttonConfig: {
          text: 'Start Networking Today',
          url: '/signup',
          style: 'primary',
          size: 'lg',
          animation: {
            hover: 'glow'
          }
        }
      }
    },
    {
      id: 'variant_b',
      name: 'Benefit-Focused',
      config: {
        buttonConfig: {
          text: 'Unlock Career Opportunities',
          url: '/signup',
          style: 'primary',
          size: 'lg',
          icon: {
            name: 'star',
            position: 'left'
          }
        }
      }
    }
  ]))

  const result = getVariant(testConfig, 'demo-user')
  if (result) {
    currentVariant.value = result.variant
    abTestConfig.value = {
      type: 'button',
      ...result.variant.config,
      trackingEnabled: true,
      abTest: testConfig
    }

    // Track impression
    trackImpression(result.testId, result.variantId, {
      page: 'cta-examples',
      section: 'ab-test-demo'
    })
  }

  // Load analytics
  updateAnalytics()
})

// Event handlers
const handleCTAClick = (event: MouseEvent, config: any) => {
  console.log('CTA clicked:', config)
  
  // Track click
  trackCTAClick(
    `cta-${Date.now()}`,
    config.type || 'button',
    config.text || 'Unknown',
    config.url || '#',
    config.trackingParams,
    config.abTestVariant
  )
  
  // Update analytics
  analytics.value.totalClicks++
  updateConversionRate()
}

const handleConversion = (data: any) => {
  console.log('Conversion tracked:', data)
  
  // Track conversion
  trackConversion('cta_conversion', {
    eventName: 'cta_conversion',
    category: 'engagement',
    action: 'convert',
    label: data.button_text || 'cta',
    value: 100
  }, data)
  
  // Update analytics
  analytics.value.totalConversions++
  updateConversionRate()
}

const updateAnalytics = () => {
  const metrics = getConversionMetrics()
  analytics.value = {
    totalClicks: analytics.value.totalClicks, // Keep current click count
    totalConversions: metrics.totalConversions,
    conversionRate: parseFloat((metrics.conversionRate * 100).toFixed(2))
  }
}

const updateConversionRate = () => {
  if (analytics.value.totalClicks > 0) {
    analytics.value.conversionRate = parseFloat(
      ((analytics.value.totalConversions / analytics.value.totalClicks) * 100).toFixed(2)
    )
  }
}

const clearAnalytics = () => {
  clearConversionData()
  analytics.value = {
    totalClicks: 0,
    totalConversions: 0,
    conversionRate: 0
  }
}
</script>

<style scoped>
.cta-examples {
  @apply min-h-screen bg-gray-50;
}

.prose p {
  @apply inline;
}
</style>
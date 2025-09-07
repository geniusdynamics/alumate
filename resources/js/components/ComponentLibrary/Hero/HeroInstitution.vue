<template>
  <HeroBase :config="finalConfig" />
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import HeroBase from './HeroBase.vue'
import { getHeroConfigForAudience } from '@/data/heroSampleData'
import { useABTest, heroABTestConfigs, abTestingService } from '@/utils/abTesting'
import type { HeroComponentConfig } from '@/types/components'

interface Props {
  config?: Partial<HeroComponentConfig>
  sampleData?: boolean
  userId?: string
  enableABTest?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  sampleData: false,
  enableABTest: true
})

const baseConfig = computed(() => {
  const config = getHeroConfigForAudience('institution')
  
  // Add A/B test configuration for institution audience
  if (props.enableABTest) {
    config.abTest = heroABTestConfigs.institution
  }
  
  // Add variant-specific styling based on partnership focus
  config.variantStyling = {
    colorScheme: 'professional',
    typography: 'classic',
    spacing: 'spacious'
  }
  
  return config
})

const { variant, trackEvent, trackConversion } = useABTest(
  baseConfig.value.abTest || { enabled: false },
  props.userId
)

const finalConfig = computed(() => {
  let config = { ...baseConfig.value }
  
  // Apply A/B test variant if enabled
  if (props.enableABTest && config.abTest?.enabled) {
    config = abTestingService.applyVariant(config, variant)
  }
  
  // Merge with custom config if provided
  if (props.config) {
    config = {
      ...config,
      ...props.config,
      audienceType: 'institution' as const
    }
  }
  
  // Enhance CTAs with partnership messaging
  config.ctaButtons = config.ctaButtons.map(cta => ({
    ...cta,
    abTestVariant: variant,
    trackingParams: {
      ...cta.trackingParams,
      variant,
      audience_focus: 'institutional_partnership'
    }
  }))
  
  // Add partnership-focused statistics if not overridden
  if (!props.config?.statistics) {
    config.statistics = [
      {
        id: 'partner-institutions',
        value: 500,
        label: 'Partner Institutions',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'engagement-growth',
        value: 85,
        label: 'Engagement Growth',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'donation-increase',
        value: 120,
        label: 'Donation Increase',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'network-value',
        value: 2.5,
        label: 'Network Value',
        prefix: '$',
        suffix: 'M',
        animated: true,
        source: 'manual'
      }
    ]
  }
  
  return config
})

// Track component view
onMounted(() => {
  if (props.enableABTest) {
    trackEvent('hero_view', {
      audience_type: 'institution',
      variant_focus: 'partnership_benefits'
    })
  }
})

// Expose tracking methods for parent components
defineExpose({
  trackEvent,
  trackConversion,
  variant
})
</script>
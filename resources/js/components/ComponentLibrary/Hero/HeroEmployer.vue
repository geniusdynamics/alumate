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
  const config = getHeroConfigForAudience('employer')
  
  // Add A/B test configuration for employer audience
  if (props.enableABTest) {
    config.abTest = heroABTestConfigs.employer
  }
  
  // Add variant-specific styling based on efficiency focus
  config.variantStyling = {
    colorScheme: 'energetic',
    typography: 'bold',
    spacing: 'default'
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
      audienceType: 'employer' as const
    }
  }
  
  // Enhance CTAs with recruitment efficiency messaging
  config.ctaButtons = config.ctaButtons.map(cta => ({
    ...cta,
    abTestVariant: variant,
    trackingParams: {
      ...cta.trackingParams,
      variant,
      audience_focus: 'recruitment_efficiency'
    }
  }))
  
  // Add efficiency-focused statistics if not overridden
  if (!props.config?.statistics) {
    config.statistics = [
      {
        id: 'time-to-hire-reduction',
        value: 40,
        label: 'Faster Hiring',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'qualified-candidates',
        value: 50000,
        label: 'Qualified Candidates',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'successful-placements',
        value: 12000,
        label: 'Successful Hires',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'retention-rate',
        value: 92,
        label: 'Retention Rate',
        suffix: '%',
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
      audience_type: 'employer',
      variant_focus: 'recruitment_efficiency'
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
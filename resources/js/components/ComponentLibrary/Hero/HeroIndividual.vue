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
  const config = getHeroConfigForAudience('individual')
  
  // Add A/B test configuration for individual audience
  if (props.enableABTest) {
    config.abTest = heroABTestConfigs.individual
  }
  
  // Add variant-specific styling based on success story focus
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
      audienceType: 'individual' as const
    }
  }
  
  // Enhance CTAs with success story messaging
  config.ctaButtons = config.ctaButtons.map(cta => ({
    ...cta,
    abTestVariant: variant,
    trackingParams: {
      ...cta.trackingParams,
      variant,
      audience_focus: 'personal_success'
    }
  }))
  
  // Add success story specific statistics if not overridden
  if (!props.config?.statistics) {
    config.statistics = [
      {
        id: 'career-advancement',
        value: 78,
        label: 'Career Advancement',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'salary-increase',
        value: 35,
        label: 'Avg Salary Boost',
        suffix: '%',
        animated: true,
        source: 'manual'
      },
      {
        id: 'success-stories',
        value: 2500,
        label: 'Success Stories',
        suffix: '+',
        animated: true,
        source: 'api'
      },
      {
        id: 'mentor-connections',
        value: 15000,
        label: 'Mentor Matches',
        suffix: '+',
        animated: true,
        source: 'api'
      }
    ]
  }
  
  return config
})

// Track component view
onMounted(() => {
  if (props.enableABTest) {
    trackEvent('hero_view', {
      audience_type: 'individual',
      variant_focus: 'success_story'
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
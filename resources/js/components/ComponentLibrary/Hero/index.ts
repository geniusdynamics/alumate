export { default as HeroBase } from './HeroBase.vue'
export { default as HeroIndividual } from './HeroIndividual.vue'
export { default as HeroInstitution } from './HeroInstitution.vue'
export { default as HeroEmployer } from './HeroEmployer.vue'
export { default as AnimatedCounter } from './AnimatedCounter.vue'

// Re-export types for convenience
export type {
  HeroComponentConfig,
  CTAButton,
  StatisticCounter,
  BackgroundMedia,
  MediaAsset,
  GradientConfig,
  AudienceType
} from '@/types/components'

// Re-export sample data
export {
  heroSampleData,
  getHeroConfigForAudience,
  defaultHeroConfig
} from '@/data/heroSampleData'

// Re-export A/B testing utilities
export {
  abTestingService,
  useABTest,
  heroABTestConfigs
} from '@/utils/abTesting'

// Re-export variant styling utilities
export {
  getVariantStyleClasses,
  getBackgroundGradient
} from '@/utils/variantStyling'
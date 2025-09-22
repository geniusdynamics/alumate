export { default as AnimatedCounter } from './AnimatedCounter.vue';
export { default as HeroBase } from './HeroBase.vue';
export { default as HeroEmployer } from './HeroEmployer.vue';
export { default as HeroIndividual } from './HeroIndividual.vue';
export { default as HeroInstitution } from './HeroInstitution.vue';

// Re-export types for convenience
export type { AudienceType, BackgroundMedia, CTAButton, GradientConfig, HeroComponentConfig, MediaAsset, StatisticCounter } from '@/Types/components';

// Re-export sample data
export { defaultHeroConfig, getHeroConfigForAudience, heroSampleData } from '@/Data/heroSampleData';

// Re-export A/B testing utilities
export { abTestingService, heroABTestConfigs, useABTest } from '@/Utils/abTesting';

// Re-export variant styling utilities
export { getBackgroundGradient, getVariantStyleClasses } from '@/Utils/variantStyling';


















// Main Component Library Interface
export { default as ComponentLibrary } from './ComponentLibrary.vue'
export { default as ComponentCard } from './ComponentCard.vue'
export { default as ComponentSkeleton } from './ComponentSkeleton.vue'
export { default as ComponentPreviewModal } from './ComponentPreviewModal.vue'
export { default as ComponentConfigurator } from './ComponentConfigurator.vue'
export { default as ComponentBrowser } from './ComponentBrowser.vue'
export { default as ComponentPreview } from './ComponentPreview.vue'

// Hero Components
export { default as HeroBase } from './Hero/HeroBase.vue'
export { default as HeroIndividual } from './Hero/HeroIndividual.vue'
export { default as HeroInstitution } from './Hero/HeroInstitution.vue'
export { default as HeroEmployer } from './Hero/HeroEmployer.vue'

// Form Components
export { default as FormBase } from './Forms/FormBase.vue'
export { default as FormBuilder } from './Forms/FormBuilder.vue'
export { default as FormFieldRenderer } from './Forms/FormFieldRenderer.vue'

// Testimonial Components
export { default as TestimonialBase } from './Testimonials/TestimonialBase.vue'
export { default as TestimonialCard } from './Testimonials/TestimonialCard.vue'
export { default as TestimonialCarousel } from './Testimonials/TestimonialCarousel.vue'
export { default as TestimonialSingle } from './Testimonials/TestimonialSingle.vue'

// Statistics Components
export { default as StatisticsBase } from './Statistics/StatisticsBase.vue'
export { default as AnimatedCounter } from './Statistics/AnimatedCounter.vue'
export { default as ProgressBar } from './Statistics/ProgressBar.vue'
export { default as ComparisonChart } from './Statistics/ComparisonChart.vue'

// CTA Components
export { default as CTABase } from './CTAs/CTABase.vue'
export { default as CTAButton } from './CTAs/CTAButton.vue'
export { default as CTABanner } from './CTAs/CTABanner.vue'
export { default as CTAInlineLink } from './CTAs/CTAInlineLink.vue'

// Media Components
export { default as MediaBase } from './Media/MediaBase.vue'
export { default as ImageGallery } from './Media/ImageGallery.vue'
export { default as VideoEmbed } from './Media/VideoEmbed.vue'
export { default as InteractiveDemo } from './Media/InteractiveDemo.vue'

// Sample Data
export { 
  sampleComponents, 
  sampleComponentsByCategory, 
  getComponentsByCategory,
  getComponentById,
  searchComponents,
  getPopularComponents,
  getRecentComponents,
  componentMetadata
} from '../../data/componentLibrarySampleData'

// Types
export type {
  Component,
  ComponentCategory,
  ComponentInstance,
  HeroComponentConfig,
  FormComponentConfig,
  TestimonialComponentConfig,
  StatisticsComponentConfig,
  CTAComponentConfig,
  MediaComponentConfig
} from '../../types/components'
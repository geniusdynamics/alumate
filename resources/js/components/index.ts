// Manual component exports for components excluded from auto-import
// This prevents naming conflicts while still allowing manual imports

// Analytics Components
export { default as OverviewMetrics } from './Analytics/OverviewMetrics.vue'

// Form Components  
export { default as InputError } from './InputError.vue'

// Layout Components
export { default as AppHeader } from './layout/AppHeader.vue'

// Onboarding Components
export { default as GuidedTour } from './onboarding/GuidedTour.vue'

// Success Stories Components
export { default as SuccessStoryCard } from './SuccessStories/SuccessStoryCard.vue'

// Testing Components
export { default as ABTestManager } from './Testing/ABTestManager.vue'

// UI Components
export { default as LoadingSpinner } from './ui/LoadingSpinner.vue'
export { default as Modal } from './ui/Modal.vue'
export { default as Skeleton } from './ui/skeleton/Skeleton.vue'

// Layout Exports
export { default as AdminLayout } from '../layouts/AdminLayout.vue'

// Type definitions for better TypeScript support
export type {
  // Add any type exports if needed
}
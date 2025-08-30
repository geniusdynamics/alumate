// Media Components
export { default as MediaBase } from './MediaBase.vue'
export { default as MediaComponent } from './MediaComponent.vue'
export { default as ImageGallery } from './ImageGallery.vue'
export { default as VideoEmbed } from './VideoEmbed.vue'
export { default as InteractiveDemo } from './InteractiveDemo.vue'

// Re-export types for convenience
export type {
  MediaComponentConfig,
  MediaType,
  MediaLayout,
  MediaOptimization,
  MediaAccessibility,
  MediaPerformance,
  LightboxConfig,
  TouchGestureConfig
} from '@/types/components'
// Media Components
export { default as ImageGallery } from './ImageGallery.vue';
export { default as InteractiveDemo } from './InteractiveDemo.vue';
export { default as MediaBase } from './MediaBase.vue';
export { default as MediaComponent } from './MediaComponent.vue';
export { default as VideoEmbed } from './VideoEmbed.vue';

// Re-export types for convenience
export type {
    LightboxConfig,
    MediaAccessibility,
    MediaComponentConfig,
    MediaLayout,
    MediaOptimization,
    MediaPerformance,
    MediaType,
    TouchGestureConfig,
} from '@/types/Components';

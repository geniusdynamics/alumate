export { default as TemplateCustomizer } from './TemplateCustomizer.vue'
export { default as ColorPicker } from './ColorPicker.vue'
export { default as FontSelector } from './FontSelector.vue'
export { default as ContentEditor } from './ContentEditor.vue'

export { templateCustomizationService } from '@/services/TemplateCustomizationService'

// Re-export types for convenience
export type {
  TemplateCustomizationConfig,
  BrandCustomization,
  ContentCustomization,
  ColorCustomization,
  FontCustomization,
  ContentBlock,
  TextBlock,
  ImageBlock,
  ButtonBlock,
  CustomizationAPIResponse
} from '@/types/components'

export {
  ColorPicker as TemplateColorPicker,
  FontSelector as TemplateFontSelector,
  ContentEditor as TemplateContentEditor,
  TemplateCustomizer as TemplateCustomizationInterface
} from '.'
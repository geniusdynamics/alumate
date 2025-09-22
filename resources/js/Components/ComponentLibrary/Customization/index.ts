export { default as ColorPicker } from './ColorPicker.vue';
export { default as ContentEditor } from './ContentEditor.vue';
export { default as FontSelector } from './FontSelector.vue';
export { default as TemplateCustomizer } from './TemplateCustomizer.vue';

export { templateCustomizationService } from '@/Services/TemplateCustomizationService';

// Re-export types for convenience
export type {
    BrandCustomization,
    ButtonBlock,
    ColorCustomization,
    ContentBlock,
    ContentCustomization,
    CustomizationAPIResponse,
    FontCustomization,
    ImageBlock,
    TemplateCustomizationConfig,
    TextBlock,
<<<<<<< HEAD:resources/js/components/ComponentLibrary/Customization/index.ts
} from '@/Types/components';
=======
} from '@/types/Components';
>>>>>>> origin/db1:resources/js/Components/ComponentLibrary/Customization/index.ts

export {
    ColorPicker as TemplateColorPicker,
    ContentEditor as TemplateContentEditor,
    TemplateCustomizer as TemplateCustomizationInterface,
    FontSelector as TemplateFontSelector,
} from '.';
















<template>
    <div class="property-panel">
        <!-- Panel Header -->
        <div class="panel-header">
            <div class="header-content">
                <h3 class="panel-title">
                    {{ selectedComponent ? selectedComponent.name : 'Properties' }}
                </h3>
                <p v-if="selectedComponent" class="panel-subtitle">Configure {{ selectedComponent.category }} component</p>
                <p v-else class="panel-subtitle">Select a component to edit its properties</p>
            </div>
            <button @click="$emit('close')" class="close-btn" aria-label="Close properties panel">
                <Icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="!selectedComponent" class="empty-state">
            <div class="empty-icon">
                <Icon name="settings" class="h-12 w-12 text-gray-400" />
            </div>
            <h4 class="empty-title">No Component Selected</h4>
            <p class="empty-description">Click on a component in the editor to configure its properties</p>
        </div>

        <!-- Component Properties -->
        <div v-else class="properties-content">
            <!-- Component Info -->
            <div class="component-info">
                <div class="component-icon">
                    <Icon :name="getComponentIcon(selectedComponent.category)" class="h-6 w-6" />
                </div>
                <div class="component-details">
                    <h4 class="component-name">{{ selectedComponent.name }}</h4>
                    <span class="component-type">{{ selectedComponent.category }}</span>
                </div>
            </div>

            <!-- Property Sections -->
            <div class="property-sections">
                <!-- General Properties -->
                <div class="property-section">
                    <h5 class="section-title">
                        <Icon name="settings" class="h-4 w-4" />
                        General
                    </h5>
                    <div class="property-group">
                        <!-- Component Name -->
                        <div class="property-field">
                            <label class="property-label">Component Name</label>
                            <input
                                v-model="componentConfig.name"
                                type="text"
                                class="property-input"
                                placeholder="Enter component name"
                                @input="updateProperty('name', $event.target.value)"
                            />
                        </div>

                        <!-- Component ID -->
                        <div class="property-field">
                            <label class="property-label">Component ID</label>
                            <input
                                v-model="componentConfig.id"
                                type="text"
                                class="property-input"
                                placeholder="component-id"
                                @input="updateProperty('id', $event.target.value)"
                            />
                        </div>

                        <!-- CSS Classes -->
                        <div class="property-field">
                            <label class="property-label">CSS Classes</label>
                            <input
                                v-model="componentConfig.classes"
                                type="text"
                                class="property-input"
                                placeholder="custom-class another-class"
                                @input="updateProperty('classes', $event.target.value)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Style Properties -->
                <div class="property-section">
                    <h5 class="section-title">
                        <Icon name="palette" class="h-4 w-4" />
                        Styling
                    </h5>
                    <div class="property-group">
                        <!-- Background Color -->
                        <div class="property-field">
                            <label class="property-label">Background Color</label>
                            <div class="color-input-group">
                                <input
                                    v-model="componentConfig.backgroundColor"
                                    type="color"
                                    class="color-picker"
                                    @input="updateProperty('backgroundColor', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.backgroundColor"
                                    type="text"
                                    class="color-text-input"
                                    placeholder="#ffffff"
                                    @input="updateProperty('backgroundColor', $event.target.value)"
                                />
                            </div>
                        </div>

                        <!-- Text Color -->
                        <div class="property-field">
                            <label class="property-label">Text Color</label>
                            <div class="color-input-group">
                                <input
                                    v-model="componentConfig.textColor"
                                    type="color"
                                    class="color-picker"
                                    @input="updateProperty('textColor', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.textColor"
                                    type="text"
                                    class="color-text-input"
                                    placeholder="#000000"
                                    @input="updateProperty('textColor', $event.target.value)"
                                />
                            </div>
                        </div>

                        <!-- Padding -->
                        <div class="property-field">
                            <label class="property-label">Padding</label>
                            <div class="spacing-inputs">
                                <input
                                    v-model="componentConfig.paddingTop"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Top"
                                    @input="updateProperty('paddingTop', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.paddingRight"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Right"
                                    @input="updateProperty('paddingRight', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.paddingBottom"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Bottom"
                                    @input="updateProperty('paddingBottom', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.paddingLeft"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Left"
                                    @input="updateProperty('paddingLeft', $event.target.value)"
                                />
                            </div>
                        </div>

                        <!-- Margin -->
                        <div class="property-field">
                            <label class="property-label">Margin</label>
                            <div class="spacing-inputs">
                                <input
                                    v-model="componentConfig.marginTop"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Top"
                                    @input="updateProperty('marginTop', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.marginRight"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Right"
                                    @input="updateProperty('marginRight', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.marginBottom"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Bottom"
                                    @input="updateProperty('marginBottom', $event.target.value)"
                                />
                                <input
                                    v-model="componentConfig.marginLeft"
                                    type="number"
                                    class="spacing-input"
                                    placeholder="Left"
                                    @input="updateProperty('marginLeft', $event.target.value)"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Component-Specific Properties -->
                <div v-if="selectedComponent.category === 'hero'" class="property-section">
                    <h5 class="section-title">
                        <Icon name="star" class="h-4 w-4" />
                        Hero Settings
                    </h5>
                    <div class="property-group">
                        <!-- Headline -->
                        <div class="property-field">
                            <label class="property-label">Headline</label>
                            <textarea
                                v-model="componentConfig.headline"
                                class="property-textarea"
                                placeholder="Enter hero headline"
                                rows="2"
                                @input="updateProperty('headline', $event.target.value)"
                            ></textarea>
                        </div>

                        <!-- Subheading -->
                        <div class="property-field">
                            <label class="property-label">Subheading</label>
                            <textarea
                                v-model="componentConfig.subheading"
                                class="property-textarea"
                                placeholder="Enter hero subheading"
                                rows="2"
                                @input="updateProperty('subheading', $event.target.value)"
                            ></textarea>
                        </div>

                        <!-- CTA Button -->
                        <div class="property-field">
                            <label class="property-label">CTA Button Text</label>
                            <input
                                v-model="componentConfig.ctaText"
                                type="text"
                                class="property-input"
                                placeholder="Get Started"
                                @input="updateProperty('ctaText', $event.target.value)"
                            />
                        </div>

                        <!-- CTA URL -->
                        <div class="property-field">
                            <label class="property-label">CTA URL</label>
                            <input
                                v-model="componentConfig.ctaUrl"
                                type="url"
                                class="property-input"
                                placeholder="https://example.com"
                                @input="updateProperty('ctaUrl', $event.target.value)"
                            />
                        </div>

                        <!-- Background Image -->
                        <div class="property-field">
                            <label class="property-label">Background Image</label>
                            <div class="file-input-group">
                                <input
                                    v-model="componentConfig.backgroundImage"
                                    type="url"
                                    class="property-input"
                                    placeholder="Image URL or upload"
                                    @input="updateProperty('backgroundImage', $event.target.value)"
                                />
                                <button @click="uploadImage" class="upload-btn">
                                    <Icon name="upload" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Component Properties -->
                <div v-if="selectedComponent.category === 'forms'" class="property-section">
                    <h5 class="section-title">
                        <Icon name="form" class="h-4 w-4" />
                        Form Settings
                    </h5>
                    <div class="property-group">
                        <!-- Form Title -->
                        <div class="property-field">
                            <label class="property-label">Form Title</label>
                            <input
                                v-model="componentConfig.formTitle"
                                type="text"
                                class="property-input"
                                placeholder="Contact Us"
                                @input="updateProperty('formTitle', $event.target.value)"
                            />
                        </div>

                        <!-- Submit Button Text -->
                        <div class="property-field">
                            <label class="property-label">Submit Button Text</label>
                            <input
                                v-model="componentConfig.submitText"
                                type="text"
                                class="property-input"
                                placeholder="Submit"
                                @input="updateProperty('submitText', $event.target.value)"
                            />
                        </div>

                        <!-- Form Action -->
                        <div class="property-field">
                            <label class="property-label">Form Action URL</label>
                            <input
                                v-model="componentConfig.formAction"
                                type="url"
                                class="property-input"
                                placeholder="/submit-form"
                                @input="updateProperty('formAction', $event.target.value)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Responsive Settings -->
                <div class="property-section">
                    <h5 class="section-title">
                        <Icon name="device-mobile" class="h-4 w-4" />
                        Responsive
                    </h5>
                    <div class="property-group">
                        <!-- Device Visibility -->
                        <div class="property-field">
                            <label class="property-label">Visible On</label>
                            <div class="checkbox-group">
                                <label class="checkbox-item">
                                    <input
                                        v-model="componentConfig.visibleOnDesktop"
                                        type="checkbox"
                                        @change="updateProperty('visibleOnDesktop', $event.target.checked)"
                                    />
                                    <span class="checkbox-label">Desktop</span>
                                </label>
                                <label class="checkbox-item">
                                    <input
                                        v-model="componentConfig.visibleOnTablet"
                                        type="checkbox"
                                        @change="updateProperty('visibleOnTablet', $event.target.checked)"
                                    />
                                    <span class="checkbox-label">Tablet</span>
                                </label>
                                <label class="checkbox-item">
                                    <input
                                        v-model="componentConfig.visibleOnMobile"
                                        type="checkbox"
                                        @change="updateProperty('visibleOnMobile', $event.target.checked)"
                                    />
                                    <span class="checkbox-label">Mobile</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="property-section">
                    <h5 class="section-title">
                        <Icon name="cog" class="h-4 w-4" />
                        Advanced
                    </h5>
                    <div class="property-group">
                        <!-- Custom CSS -->
                        <div class="property-field">
                            <label class="property-label">Custom CSS</label>
                            <textarea
                                v-model="componentConfig.customCss"
                                class="property-textarea code-textarea"
                                placeholder="/* Custom CSS styles */"
                                rows="4"
                                @input="updateProperty('customCss', $event.target.value)"
                            ></textarea>
                        </div>

                        <!-- Animation -->
                        <div class="property-field">
                            <label class="property-label">Animation</label>
                            <select
                                v-model="componentConfig.animation"
                                class="property-select"
                                @change="updateProperty('animation', $event.target.value)"
                            >
                                <option value="">No Animation</option>
                                <option value="fadeIn">Fade In</option>
                                <option value="slideUp">Slide Up</option>
                                <option value="slideDown">Slide Down</option>
                                <option value="slideLeft">Slide Left</option>
                                <option value="slideRight">Slide Right</option>
                                <option value="zoomIn">Zoom In</option>
                                <option value="bounce">Bounce</option>
                            </select>
                        </div>

                        <!-- Animation Delay -->
                        <div class="property-field">
                            <label class="property-label">Animation Delay (ms)</label>
                            <input
                                v-model="componentConfig.animationDelay"
                                type="number"
                                class="property-input"
                                placeholder="0"
                                min="0"
                                step="100"
                                @input="updateProperty('animationDelay', $event.target.value)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="panel-actions">
                <button @click="resetComponent" class="action-btn secondary">
                    <Icon name="refresh" class="h-4 w-4" />
                    Reset
                </button>
                <button @click="duplicateComponent" class="action-btn secondary">
                    <Icon name="copy" class="h-4 w-4" />
                    Duplicate
                </button>
                <button @click="deleteComponent" class="action-btn danger">
                    <Icon name="trash" class="h-4 w-4" />
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Icon } from '@/components/ui';
import { computed, onMounted, ref, watch } from 'vue';

// Props
interface Props {
    selectedComponent?: any;
    grapeJSEditor?: any;
}

const props = withDefaults(defineProps<Props>(), {
    selectedComponent: null,
    grapeJSEditor: null,
});

// Emits
const emit = defineEmits<{
    close: [];
    updateComponent: [componentId: string, properties: any];
    deleteComponent: [componentId: string];
    duplicateComponent: [componentId: string];
}>();

// Reactive data
const componentConfig = ref({
    name: '',
    id: '',
    classes: '',
    backgroundColor: '#ffffff',
    textColor: '#000000',
    paddingTop: 0,
    paddingRight: 0,
    paddingBottom: 0,
    paddingLeft: 0,
    marginTop: 0,
    marginRight: 0,
    marginBottom: 0,
    marginLeft: 0,
    headline: '',
    subheading: '',
    ctaText: '',
    ctaUrl: '',
    backgroundImage: '',
    formTitle: '',
    submitText: '',
    formAction: '',
    visibleOnDesktop: true,
    visibleOnTablet: true,
    visibleOnMobile: true,
    customCss: '',
    animation: '',
    animationDelay: 0,
});

// Computed properties
const componentIcon = computed(() => {
    return getComponentIcon(props.selectedComponent?.category || 'default');
});

// Methods
const getComponentIcon = (category: string): string => {
    const iconMap: Record<string, string> = {
        hero: 'star',
        forms: 'form',
        testimonials: 'quote',
        statistics: 'chart',
        ctas: 'cursor-click',
        media: 'photo',
        default: 'component',
    };
    return iconMap[category] || iconMap.default;
};

const updateProperty = (key: string, value: any) => {
    if (props.selectedComponent && props.grapeJSEditor) {
        // Update GrapeJS component
        const component = props.grapeJSEditor.getSelected();
        if (component) {
            // Update component attributes or styles based on property type
            if (
                [
                    'backgroundColor',
                    'textColor',
                    'paddingTop',
                    'paddingRight',
                    'paddingBottom',
                    'paddingLeft',
                    'marginTop',
                    'marginRight',
                    'marginBottom',
                    'marginLeft',
                ].includes(key)
            ) {
                // Style properties
                const styleKey = key.replace(/([A-Z])/g, '-$1').toLowerCase();
                component.addStyle({ [styleKey]: value });
            } else if (['name', 'id', 'classes'].includes(key)) {
                // Attribute properties
                if (key === 'name') {
                    component.set('name', value);
                } else if (key === 'id') {
                    component.addAttributes({ id: value });
                } else if (key === 'classes') {
                    component.setClass(value);
                }
            } else {
                // Component-specific properties
                component.set(key, value);
            }

            // Emit update event
            emit('updateComponent', props.selectedComponent.id, { [key]: value });
        }
    }
};

const uploadImage = () => {
    // Create file input
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (event: any) => {
        const file = event.target.files[0];
        if (file) {
            // Handle file upload (implement your upload logic here)
            const reader = new FileReader();
            reader.onload = (e) => {
                const imageUrl = e.target?.result as string;
                componentConfig.value.backgroundImage = imageUrl;
                updateProperty('backgroundImage', imageUrl);
            };
            reader.readAsDataURL(file);
        }
    };
    input.click();
};

const resetComponent = () => {
    if (props.selectedComponent && props.grapeJSEditor) {
        const component = props.grapeJSEditor.getSelected();
        if (component) {
            // Reset component to default state
            component.set('style', {});
            component.set('attributes', {});
            loadComponentConfig();
        }
    }
};

const duplicateComponent = () => {
    if (props.selectedComponent) {
        emit('duplicateComponent', props.selectedComponent.id);
    }
};

const deleteComponent = () => {
    if (props.selectedComponent) {
        if (confirm('Are you sure you want to delete this component?')) {
            emit('deleteComponent', props.selectedComponent.id);
        }
    }
};

const loadComponentConfig = () => {
    if (props.selectedComponent && props.grapeJSEditor) {
        const component = props.grapeJSEditor.getSelected();
        if (component) {
            // Load component properties into form
            const styles = component.getStyle();
            const attributes = component.getAttributes();

            componentConfig.value = {
                name: component.get('name') || '',
                id: attributes.id || '',
                classes: component.getClasses().join(' ') || '',
                backgroundColor: styles['background-color'] || '#ffffff',
                textColor: styles.color || '#000000',
                paddingTop: parseInt(styles['padding-top']) || 0,
                paddingRight: parseInt(styles['padding-right']) || 0,
                paddingBottom: parseInt(styles['padding-bottom']) || 0,
                paddingLeft: parseInt(styles['padding-left']) || 0,
                marginTop: parseInt(styles['margin-top']) || 0,
                marginRight: parseInt(styles['margin-right']) || 0,
                marginBottom: parseInt(styles['margin-bottom']) || 0,
                marginLeft: parseInt(styles['margin-left']) || 0,
                headline: component.get('headline') || '',
                subheading: component.get('subheading') || '',
                ctaText: component.get('ctaText') || '',
                ctaUrl: component.get('ctaUrl') || '',
                backgroundImage: styles['background-image'] || '',
                formTitle: component.get('formTitle') || '',
                submitText: component.get('submitText') || '',
                formAction: attributes.action || '',
                visibleOnDesktop: component.get('visibleOnDesktop') !== false,
                visibleOnTablet: component.get('visibleOnTablet') !== false,
                visibleOnMobile: component.get('visibleOnMobile') !== false,
                customCss: component.get('customCss') || '',
                animation: component.get('animation') || '',
                animationDelay: component.get('animationDelay') || 0,
            };
        }
    }
};

// Watchers
watch(
    () => props.selectedComponent,
    () => {
        if (props.selectedComponent) {
            loadComponentConfig();
        }
    },
    { immediate: true },
);

// Lifecycle
onMounted(() => {
    if (props.selectedComponent) {
        loadComponentConfig();
    }
});
</script>

<style scoped>
.property-panel {
    @apply flex h-full w-80 flex-col border-l border-gray-200 bg-white;
}

.panel-header {
    @apply flex items-center justify-between border-b border-gray-200 bg-gray-50 p-4;
}

.header-content {
    @apply flex-1;
}

.panel-title {
    @apply mb-1 text-lg font-semibold text-gray-900;
}

.panel-subtitle {
    @apply text-sm text-gray-600;
}

.close-btn {
    @apply p-1 text-gray-400 transition-colors hover:text-gray-600;
}

.empty-state {
    @apply flex flex-1 flex-col items-center justify-center p-8 text-center;
}

.empty-icon {
    @apply mb-4;
}

.empty-title {
    @apply mb-2 text-lg font-medium text-gray-900;
}

.empty-description {
    @apply text-gray-600;
}

.properties-content {
    @apply flex-1 overflow-y-auto;
}

.component-info {
    @apply flex items-center border-b border-gray-200 bg-gray-50 p-4;
}

.component-icon {
    @apply mr-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100;
}

.component-details {
    @apply flex-1;
}

.component-name {
    @apply font-medium text-gray-900;
}

.component-type {
    @apply text-sm capitalize text-gray-600;
}

.property-sections {
    @apply divide-y divide-gray-200;
}

.property-section {
    @apply p-4;
}

.section-title {
    @apply mb-3 flex items-center text-sm font-medium text-gray-900;
}

.section-title svg {
    @apply mr-2;
}

.property-group {
    @apply space-y-4;
}

.property-field {
    @apply space-y-1;
}

.property-label {
    @apply block text-sm font-medium text-gray-700;
}

.property-input {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.property-textarea {
    @apply w-full resize-none rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.code-textarea {
    @apply font-mono text-xs;
}

.property-select {
    @apply w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.color-input-group {
    @apply flex space-x-2;
}

.color-picker {
    @apply h-10 w-12 cursor-pointer rounded border border-gray-300;
}

.color-text-input {
    @apply flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.spacing-inputs {
    @apply grid grid-cols-4 gap-2;
}

.spacing-input {
    @apply rounded border border-gray-300 px-2 py-2 text-center text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.file-input-group {
    @apply flex space-x-2;
}

.upload-btn {
    @apply rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm transition-colors hover:bg-gray-200;
}

.checkbox-group {
    @apply space-y-2;
}

.checkbox-item {
    @apply flex items-center;
}

.checkbox-item input[type='checkbox'] {
    @apply mr-2;
}

.checkbox-label {
    @apply text-sm text-gray-700;
}

.panel-actions {
    @apply flex space-x-2 border-t border-gray-200 bg-gray-50 p-4;
}

.action-btn {
    @apply flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors;
}

.action-btn svg {
    @apply mr-1;
}

.action-btn.secondary {
    @apply border border-gray-300 bg-white text-gray-700 hover:bg-gray-50;
}

.action-btn.danger {
    @apply border border-red-300 bg-red-50 text-red-700 hover:bg-red-100;
}
</style>












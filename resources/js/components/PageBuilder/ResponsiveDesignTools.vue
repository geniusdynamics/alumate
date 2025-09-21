<template>
    <div class="responsive-design-tools">
        <!-- Device Switcher -->
        <div class="device-switcher">
            <div class="device-switcher-header">
                <h3 class="switcher-title">Device Preview</h3>
                <button
                    @click="toggleDeviceFrame"
                    :class="['frame-toggle', { active: showDeviceFrame }]"
                    :title="showDeviceFrame ? 'Hide device frame' : 'Show device frame'"
                >
                    <Icon name="smartphone" class="h-4 w-4" />
                </button>
            </div>

            <div class="device-buttons">
                <button
                    v-for="device in devices"
                    :key="device.id"
                    @click="switchDevice(device.id)"
                    :class="['device-btn', { active: selectedDevice === device.id }]"
                    :title="`${device.name} (${device.width}×${device.height})`"
                >
                    <Icon :name="device.icon" class="device-icon" />
                    <span class="device-name">{{ device.name }}</span>
                    <span class="device-size">{{ device.width }}×{{ device.height }}</span>
                </button>
            </div>

            <!-- Custom Device Size -->
            <div class="custom-device">
                <button @click="showCustomDevice = !showCustomDevice" :class="['custom-device-btn', { active: showCustomDevice }]">
                    <Icon name="settings" class="h-4 w-4" />
                    Custom Size
                </button>

                <div v-if="showCustomDevice" class="custom-device-controls">
                    <div class="size-inputs">
                        <div class="input-group">
                            <label for="custom-width">Width</label>
                            <input
                                id="custom-width"
                                v-model.number="customDevice.width"
                                type="number"
                                min="200"
                                max="2560"
                                @input="updateCustomDevice"
                                class="size-input"
                            />
                            <span class="unit">px</span>
                        </div>
                        <div class="input-group">
                            <label for="custom-height">Height</label>
                            <input
                                id="custom-height"
                                v-model.number="customDevice.height"
                                type="number"
                                min="200"
                                max="1600"
                                @input="updateCustomDevice"
                                class="size-input"
                            />
                            <span class="unit">px</span>
                        </div>
                    </div>
                    <button @click="applyCustomDevice" class="apply-custom-btn">Apply</button>
                </div>
            </div>
        </div>

        <!-- Responsive Breakpoints -->
        <div class="breakpoint-manager">
            <h4 class="breakpoint-title">Tailwind Breakpoints</h4>
            <div class="breakpoint-list">
                <div
                    v-for="breakpoint in tailwindBreakpoints"
                    :key="breakpoint.name"
                    :class="['breakpoint-item', { active: isBreakpointActive(breakpoint) }]"
                    @click="switchToBreakpoint(breakpoint)"
                >
                    <span class="breakpoint-name">{{ breakpoint.name }}</span>
                    <span class="breakpoint-size">{{ breakpoint.minWidth }}px+</span>
                </div>
            </div>
        </div>

        <!-- Responsive Utilities -->
        <div class="responsive-utilities">
            <h4 class="utilities-title">Responsive Tools</h4>

            <div class="utility-buttons">
                <button @click="toggleResponsiveMode" :class="['utility-btn', { active: responsiveMode }]" title="Toggle responsive editing mode">
                    <Icon name="layers" class="h-4 w-4" />
                    Responsive Mode
                </button>

                <button
                    @click="showBreakpointOverlay = !showBreakpointOverlay"
                    :class="['utility-btn', { active: showBreakpointOverlay }]"
                    title="Show breakpoint overlay"
                >
                    <Icon name="grid" class="h-4 w-4" />
                    Breakpoint Grid
                </button>

                <button @click="copyResponsiveStyles" class="utility-btn" title="Copy styles to other breakpoints">
                    <Icon name="copy" class="h-4 w-4" />
                    Copy Styles
                </button>

                <button @click="resetResponsiveStyles" class="utility-btn danger" title="Reset responsive styles">
                    <Icon name="refresh" class="h-4 w-4" />
                    Reset
                </button>
            </div>
        </div>

        <!-- Responsive Style Editor -->
        <div v-if="responsiveMode && selectedComponent" class="responsive-style-editor">
            <h4 class="editor-title">Responsive Styles for {{ selectedComponent.getName() }}</h4>

            <div class="responsive-tabs">
                <button
                    v-for="device in devices"
                    :key="`tab-${device.id}`"
                    @click="activeResponsiveTab = device.id"
                    :class="['responsive-tab', { active: activeResponsiveTab === device.id }]"
                >
                    <Icon :name="device.icon" class="h-3 w-3" />
                    {{ device.name }}
                </button>
            </div>

            <div class="responsive-style-controls">
                <div v-for="property in responsiveProperties" :key="property.name" class="style-property">
                    <label :for="`${property.name}-${activeResponsiveTab}`" class="property-label">
                        {{ property.label }}
                    </label>

                    <div class="property-input-group">
                        <input
                            :id="`${property.name}-${activeResponsiveTab}`"
                            v-model="responsiveStyles[activeResponsiveTab][property.name]"
                            :type="property.type"
                            :placeholder="property.placeholder"
                            @input="updateResponsiveStyle(property.name, $event.target.value)"
                            class="property-input"
                        />
                        <span v-if="property.unit" class="property-unit">{{ property.unit }}</span>
                    </div>
                </div>
            </div>

            <div class="responsive-actions">
                <button @click="applyResponsiveStyles" class="apply-styles-btn">Apply Styles</button>
                <button @click="previewResponsiveStyles" class="preview-styles-btn">Preview</button>
            </div>
        </div>

        <!-- Breakpoint Overlay -->
        <div v-if="showBreakpointOverlay" class="breakpoint-overlay">
            <div class="overlay-content">
                <div class="overlay-header">
                    <h4>Breakpoint Information</h4>
                    <button @click="showBreakpointOverlay = false" class="close-overlay-btn">
                        <Icon name="x" class="h-4 w-4" />
                    </button>
                </div>
                <div class="current-breakpoint">
                    <p><strong>Current:</strong> {{ currentDevice.name }}</p>
                    <p><strong>Size:</strong> {{ currentDevice.width }}×{{ currentDevice.height }}px</p>
                    <p><strong>Breakpoint:</strong> {{ getCurrentBreakpoint() }}</p>
                </div>
                <div class="breakpoint-guide">
                    <h5>Tailwind CSS Breakpoints:</h5>
                    <ul>
                        <li v-for="bp in tailwindBreakpoints" :key="bp.name">
                            <code>{{ bp.name }}</code
                            >: {{ bp.minWidth }}px and up
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { realTimeEditingService } from '../../services/RealTimeEditingService';
import Icon from '../ui/Icon.vue';

// Props
interface Props {
    grapeJSEditor?: any;
    selectedComponent?: any;
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
    deviceChange: [device: string];
    responsiveStylesUpdate: [styles: any];
}>();

// Reactive state
const selectedDevice = ref('desktop');
const showDeviceFrame = ref(true);
const showCustomDevice = ref(false);
const responsiveMode = ref(false);
const showBreakpointOverlay = ref(false);
const activeResponsiveTab = ref('desktop');

const customDevice = reactive({
    width: 1024,
    height: 768,
});

const responsiveStyles = reactive({
    desktop: {},
    tablet: {},
    mobile: {},
});

// Device configurations
const devices = realTimeEditingService.devices;

const tailwindBreakpoints = [
    { name: 'sm', minWidth: 640 },
    { name: 'md', minWidth: 768 },
    { name: 'lg', minWidth: 1024 },
    { name: 'xl', minWidth: 1280 },
    { name: '2xl', minWidth: 1536 },
];

const responsiveProperties = [
    { name: 'width', label: 'Width', type: 'text', placeholder: 'auto', unit: '' },
    { name: 'height', label: 'Height', type: 'text', placeholder: 'auto', unit: '' },
    { name: 'margin', label: 'Margin', type: 'text', placeholder: '0', unit: '' },
    { name: 'padding', label: 'Padding', type: 'text', placeholder: '0', unit: '' },
    { name: 'fontSize', label: 'Font Size', type: 'text', placeholder: '16px', unit: '' },
    { name: 'display', label: 'Display', type: 'select', options: ['block', 'inline', 'flex', 'grid', 'none'] },
];

// Computed
const currentDevice = computed(() => devices.find((d) => d.id === selectedDevice.value) || devices[0]);

// Methods
const switchDevice = (deviceId: string) => {
    selectedDevice.value = deviceId;
    realTimeEditingService.switchDevice(deviceId);
    emit('deviceChange', deviceId);

    // Update active responsive tab
    activeResponsiveTab.value = deviceId;
};

const toggleDeviceFrame = () => {
    showDeviceFrame.value = !showDeviceFrame.value;
};

const updateCustomDevice = () => {
    // Validate custom device dimensions
    if (customDevice.width < 200) customDevice.width = 200;
    if (customDevice.width > 2560) customDevice.width = 2560;
    if (customDevice.height < 200) customDevice.height = 200;
    if (customDevice.height > 1600) customDevice.height = 1600;
};

const applyCustomDevice = () => {
    if (!props.grapeJSEditor) return;

    // Create custom device configuration
    const customDeviceConfig = {
        id: 'custom',
        name: 'Custom',
        width: customDevice.width,
        height: customDevice.height,
        icon: 'monitor',
    };

    // Update canvas size
    const canvas = props.grapeJSEditor.Canvas;
    const canvasEl = canvas.getElement();

    if (canvasEl) {
        canvasEl.style.width = `${customDevice.width}px`;
        canvasEl.style.height = `${customDevice.height}px`;
    }

    selectedDevice.value = 'custom';
    showCustomDevice.value = false;
};

const isBreakpointActive = (breakpoint: any) => {
    return currentDevice.value.width >= breakpoint.minWidth;
};

const switchToBreakpoint = (breakpoint: any) => {
    // Find the closest device for this breakpoint
    let targetDevice = devices[0]; // default to desktop

    for (const device of devices) {
        if (device.width >= breakpoint.minWidth) {
            targetDevice = device;
            break;
        }
    }

    switchDevice(targetDevice.id);
};

const getCurrentBreakpoint = () => {
    const width = currentDevice.value.width;

    for (let i = tailwindBreakpoints.length - 1; i >= 0; i--) {
        if (width >= tailwindBreakpoints[i].minWidth) {
            return tailwindBreakpoints[i].name;
        }
    }

    return 'base';
};

const toggleResponsiveMode = () => {
    responsiveMode.value = !responsiveMode.value;

    if (responsiveMode.value && props.selectedComponent) {
        loadResponsiveStyles();
    }
};

const loadResponsiveStyles = () => {
    if (!props.selectedComponent || !props.grapeJSEditor) return;

    // Load existing responsive styles for the selected component
    const component = props.selectedComponent;
    const styles = component.getStyle();

    // Parse responsive styles from Tailwind classes
    devices.forEach((device) => {
        responsiveStyles[device.id] = extractResponsiveStyles(styles, device.id);
    });
};

const extractResponsiveStyles = (styles: any, deviceId: string) => {
    // Extract device-specific styles from Tailwind classes
    const deviceStyles = {};

    // This would parse Tailwind responsive classes like 'md:w-1/2', 'lg:text-xl', etc.
    // Implementation depends on how styles are stored in GrapeJS

    return deviceStyles;
};

const updateResponsiveStyle = (property: string, value: string) => {
    if (!props.selectedComponent) return;

    responsiveStyles[activeResponsiveTab.value][property] = value;
};

const applyResponsiveStyles = () => {
    if (!props.selectedComponent || !props.grapeJSEditor) return;

    const component = props.selectedComponent;
    const deviceStyles = responsiveStyles[activeResponsiveTab.value];

    // Convert to Tailwind responsive classes
    const tailwindClasses = convertToTailwindClasses(deviceStyles, activeResponsiveTab.value);

    // Apply classes to component
    const currentClasses = component.getClasses();
    const newClasses = mergeTailwindClasses(currentClasses, tailwindClasses);

    component.setClass(newClasses);

    emit('responsiveStylesUpdate', responsiveStyles);
};

const convertToTailwindClasses = (styles: any, device: string) => {
    const classes = [];
    const prefix = device === 'desktop' ? '' : `${getDevicePrefix(device)}:`;

    Object.entries(styles).forEach(([property, value]) => {
        if (value) {
            const tailwindClass = convertPropertyToTailwind(property, value as string, prefix);
            if (tailwindClass) {
                classes.push(tailwindClass);
            }
        }
    });

    return classes;
};

const getDevicePrefix = (device: string) => {
    const prefixMap = {
        mobile: 'sm',
        tablet: 'md',
        desktop: 'lg',
    };
    return prefixMap[device] || '';
};

const convertPropertyToTailwind = (property: string, value: string, prefix: string) => {
    // Convert CSS properties to Tailwind classes
    const propertyMap = {
        width: (val: string) => `${prefix}w-${val}`,
        height: (val: string) => `${prefix}h-${val}`,
        margin: (val: string) => `${prefix}m-${val}`,
        padding: (val: string) => `${prefix}p-${val}`,
        fontSize: (val: string) => `${prefix}text-${val}`,
        display: (val: string) => `${prefix}${val}`,
    };

    const converter = propertyMap[property];
    return converter ? converter(value) : null;
};

const mergeTailwindClasses = (currentClasses: string[], newClasses: string[]) => {
    // Remove conflicting classes and add new ones
    const merged = [...currentClasses];

    newClasses.forEach((newClass) => {
        // Remove existing classes that conflict with the new class
        const property = newClass.split('-')[0].replace(/^(sm|md|lg|xl|2xl):/, '');
        const conflictPattern = new RegExp(`(^|\\s)(sm:|md:|lg:|xl:|2xl:)?${property}-\\S+`, 'g');

        for (let i = merged.length - 1; i >= 0; i--) {
            if (conflictPattern.test(merged[i])) {
                merged.splice(i, 1);
            }
        }

        merged.push(newClass);
    });

    return merged;
};

const previewResponsiveStyles = () => {
    // Temporarily apply styles for preview
    applyResponsiveStyles();

    // Show preview notification
    setTimeout(() => {
        // Could show a toast or highlight the changes
    }, 100);
};

const copyResponsiveStyles = () => {
    if (!props.selectedComponent) return;

    const sourceDevice = activeResponsiveTab.value;
    const sourceStyles = responsiveStyles[sourceDevice];

    // Copy to all other devices
    devices.forEach((device) => {
        if (device.id !== sourceDevice) {
            responsiveStyles[device.id] = { ...sourceStyles };
        }
    });
};

const resetResponsiveStyles = () => {
    if (!props.selectedComponent) return;

    // Reset styles for current device
    responsiveStyles[activeResponsiveTab.value] = {};

    // Remove responsive classes from component
    const component = props.selectedComponent;
    const currentClasses = component.getClasses();
    const devicePrefix = getDevicePrefix(activeResponsiveTab.value);

    const filteredClasses = currentClasses.filter((cls) => !cls.startsWith(`${devicePrefix}:`));

    component.setClass(filteredClasses);
};

// Watch for device changes from the editing service
watch(
    () => realTimeEditingService.editingState.deviceMode,
    (newDevice) => {
        selectedDevice.value = newDevice;
    },
);

// Watch for selected component changes
watch(
    () => props.selectedComponent,
    (newComponent) => {
        if (newComponent && responsiveMode.value) {
            loadResponsiveStyles();
        }
    },
);

// Lifecycle
onMounted(() => {
    // Initialize with current device from editing service
    selectedDevice.value = realTimeEditingService.editingState.deviceMode;
});
</script>

<style scoped>
.responsive-design-tools {
    @apply flex flex-col space-y-6 border-l border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800;
}

.device-switcher {
    @apply space-y-3;
}

.device-switcher-header {
    @apply flex items-center justify-between;
}

.switcher-title {
    @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.frame-toggle {
    @apply rounded p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
}

.frame-toggle.active {
    @apply text-blue-600 dark:text-blue-400;
}

.device-buttons {
    @apply space-y-2;
}

.device-btn {
    @apply flex w-full items-center justify-between rounded-lg border border-gray-200 p-3 text-left text-sm transition-colors hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700;
}

.device-btn.active {
    @apply border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-600 dark:bg-blue-900 dark:text-blue-300;
}

.device-icon {
    @apply mr-2 h-4 w-4;
}

.device-name {
    @apply font-medium;
}

.device-size {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.custom-device {
    @apply border-t border-gray-200 pt-3 dark:border-gray-600;
}

.custom-device-btn {
    @apply flex w-full items-center justify-center rounded-lg border border-dashed border-gray-300 p-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700;
}

.custom-device-btn.active {
    @apply border-gray-400 bg-gray-50 dark:border-gray-500 dark:bg-gray-700;
}

.custom-device-controls {
    @apply mt-3 space-y-3;
}

.size-inputs {
    @apply grid grid-cols-2 gap-3;
}

.input-group {
    @apply space-y-1;
}

.input-group label {
    @apply block text-xs font-medium text-gray-700 dark:text-gray-300;
}

.size-input {
    @apply w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.unit {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.apply-custom-btn {
    @apply w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700;
}

.breakpoint-manager {
    @apply space-y-3;
}

.breakpoint-title {
    @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.breakpoint-list {
    @apply space-y-1;
}

.breakpoint-item {
    @apply flex cursor-pointer items-center justify-between rounded p-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700;
}

.breakpoint-item.active {
    @apply bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-300;
}

.breakpoint-name {
    @apply font-mono font-medium;
}

.breakpoint-size {
    @apply text-xs text-gray-500 dark:text-gray-400;
}

.responsive-utilities {
    @apply space-y-3;
}

.utilities-title {
    @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.utility-buttons {
    @apply grid grid-cols-2 gap-2;
}

.utility-btn {
    @apply flex items-center justify-center rounded bg-gray-100 p-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.utility-btn.active {
    @apply bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300;
}

.utility-btn.danger {
    @apply bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800;
}

.responsive-style-editor {
    @apply space-y-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-900;
}

.editor-title {
    @apply text-sm font-semibold text-gray-900 dark:text-white;
}

.responsive-tabs {
    @apply flex space-x-1;
}

.responsive-tab {
    @apply flex items-center rounded-t-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700;
}

.responsive-tab.active {
    @apply border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-600 dark:bg-blue-900 dark:text-blue-300;
}

.responsive-style-controls {
    @apply space-y-3;
}

.style-property {
    @apply space-y-1;
}

.property-label {
    @apply block text-xs font-medium text-gray-700 dark:text-gray-300;
}

.property-input-group {
    @apply flex items-center;
}

.property-input {
    @apply flex-1 rounded-l border border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.property-unit {
    @apply rounded-r border border-l-0 border-gray-300 bg-gray-100 px-2 py-1 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400;
}

.responsive-actions {
    @apply flex space-x-2;
}

.apply-styles-btn {
    @apply flex-1 rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700;
}

.preview-styles-btn {
    @apply flex-1 rounded bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500;
}

.breakpoint-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50;
}

.overlay-content {
    @apply mx-4 w-full max-w-md rounded-lg bg-white p-6 dark:bg-gray-800;
}

.overlay-header {
    @apply mb-4 flex items-center justify-between;
}

.close-overlay-btn {
    @apply p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200;
}

.current-breakpoint {
    @apply mb-4 space-y-2;
}

.breakpoint-guide h5 {
    @apply mb-2 text-sm font-semibold text-gray-900 dark:text-white;
}

.breakpoint-guide ul {
    @apply space-y-1;
}

.breakpoint-guide li {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

.breakpoint-guide code {
    @apply rounded bg-gray-100 px-1 py-0.5 font-mono text-xs dark:bg-gray-700;
}
</style>

<template>
    <div class="preview-modes">
        <!-- Device Selector -->
        <div class="device-selector">
            <button
                v-for="device in devices"
                :key="device.id"
                @click="selectDevice(device.id)"
                :class="['device-btn', { active: selectedDevice === device.id }]"
                :title="`Preview in ${device.name}`"
            >
                <Icon :name="device.icon" class="h-4 w-4" />
                <span>{{ device.name }}</span>
            </button>
        </div>

        <!-- Preview Frame -->
        <div class="preview-container">
            <div :class="['preview-frame', `device-${selectedDevice}`]" :style="frameStyles">
                <div class="device-frame" v-if="showDeviceFrame">
                    <div class="device-screen">
                        <iframe ref="previewIframe" :src="previewUrl" class="preview-iframe" @load="onIframeLoad"></iframe>
                    </div>
                </div>
                <iframe v-else ref="previewIframe" :src="previewUrl" class="preview-iframe" @load="onIframeLoad"></iframe>
            </div>
        </div>

        <!-- Preview Controls -->
        <div class="preview-controls">
            <button @click="refreshPreview" class="control-btn" title="Refresh preview">
                <Icon name="refresh" class="h-4 w-4" />
            </button>
            <button @click="toggleDeviceFrame" class="control-btn" title="Toggle device frame">
                <Icon name="device-mobile" class="h-4 w-4" />
            </button>
            <button @click="openInNewTab" class="control-btn" title="Open in new tab">
                <Icon name="external-link" class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Icon } from '@/Components/ui';
import { computed, onMounted, ref, watch } from 'vue';

// Props
interface Props {
    pageId?: string;
    grapeJSEditor?: any;
    autoRefresh?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    pageId: '',
    grapeJSEditor: null,
    autoRefresh: true,
});

// Emits
const emit = defineEmits<{
    deviceChange: [device: string];
}>();

// Reactive data
const selectedDevice = ref('desktop');
const showDeviceFrame = ref(true);
const previewIframe = ref<HTMLIFrameElement | null>(null);
const isLoading = ref(false);

const devices = [
    {
        id: 'desktop',
        name: 'Desktop',
        icon: 'monitor',
        width: 1200,
        height: 800,
    },
    {
        id: 'tablet',
        name: 'Tablet',
        icon: 'tablet',
        width: 768,
        height: 1024,
    },
    {
        id: 'mobile',
        name: 'Mobile',
        icon: 'smartphone',
        width: 375,
        height: 667,
    },
];

// Computed properties
const currentDevice = computed(() => {
    return devices.find((d) => d.id === selectedDevice.value) || devices[0];
});

const frameStyles = computed(() => {
    const device = currentDevice.value;
    return {
        width: `${device.width}px`,
        height: `${device.height}px`,
        maxWidth: '100%',
        maxHeight: '100%',
    };
});

const previewUrl = computed(() => {
    if (props.pageId) {
        return `/preview/${props.pageId}?device=${selectedDevice.value}&timestamp=${Date.now()}`;
    }
    return 'about:blank';
});

// Methods
const selectDevice = (deviceId: string) => {
    selectedDevice.value = deviceId;
    emit('deviceChange', deviceId);

    // Update GrapeJS device manager if available
    if (props.grapeJSEditor) {
        props.grapeJSEditor.setDevice(deviceId);
    }
};

const refreshPreview = () => {
    if (previewIframe.value) {
        isLoading.value = true;
        previewIframe.value.src = previewUrl.value;
    }
};

const toggleDeviceFrame = () => {
    showDeviceFrame.value = !showDeviceFrame.value;
};

const openInNewTab = () => {
    if (previewUrl.value && previewUrl.value !== 'about:blank') {
        window.open(previewUrl.value, '_blank');
    }
};

const onIframeLoad = () => {
    isLoading.value = false;
};

// Watchers
watch(
    () => props.grapeJSEditor,
    (editor) => {
        if (editor && props.autoRefresh) {
            // Listen for editor changes and refresh preview
            editor.on('component:update', () => {
                setTimeout(refreshPreview, 500); // Debounce refresh
            });
        }
    },
);

// Lifecycle
onMounted(() => {
    if (props.grapeJSEditor) {
        // Set initial device
        props.grapeJSEditor.setDevice(selectedDevice.value);
    }
});
</script>

<style scoped>
.preview-modes {
    @apply flex h-full flex-col bg-gray-50;
}

.device-selector {
    @apply flex items-center justify-center border-b border-gray-200 bg-white p-4;
}

.device-btn {
    @apply mx-1 flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50;
}

.device-btn.active {
    @apply border-blue-300 bg-blue-50 text-blue-700;
}

.device-btn svg {
    @apply mr-2;
}

.preview-container {
    @apply flex flex-1 items-center justify-center overflow-auto p-4;
}

.preview-frame {
    @apply relative overflow-hidden rounded-lg bg-white shadow-lg;
    transition: all 0.3s ease;
}

.device-frame {
    @apply relative;
}

.device-frame::before {
    content: '';
    @apply pointer-events-none absolute inset-0 rounded-xl border-8 border-gray-800;
}

.device-screen {
    @apply relative overflow-hidden rounded-lg;
}

.preview-iframe {
    @apply h-full w-full border-0;
}

.preview-controls {
    @apply flex items-center justify-center border-t border-gray-200 bg-white p-4;
}

.control-btn {
    @apply mx-1 rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900;
}

/* Device-specific styles */
.device-desktop {
    @apply max-w-full;
}

.device-tablet .device-frame::before {
    @apply rounded-lg border-4;
}

.device-mobile .device-frame::before {
    @apply rounded-md border-2;
}
</style>


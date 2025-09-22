<template>
    <div class="heat-map-viewer">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading heat map data...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
        </div>

        <!-- Heat Map Container -->
        <div v-else class="heat-map-container relative">
            <!-- Controls -->
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h3 class="text-lg font-medium text-gray-900">Heat Map: {{ pageUrl }}</h3>
                    <span class="text-sm text-gray-500">Total Clicks: {{ heatMapData?.totalClicks || 0 }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <button @click="resetView" class="rounded bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200" :disabled="!isZoomed">
                        Reset View
                    </button>
                    <button @click="toggleFullscreen" class="rounded bg-blue-100 px-3 py-1 text-sm text-blue-700 hover:bg-blue-200">
                        {{ isFullscreen ? 'Exit Fullscreen' : 'Fullscreen' }}
                    </button>
                </div>
            </div>

            <!-- Canvas Container -->
            <div
                ref="containerRef"
                class="canvas-container relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                :class="{ fullscreen: isFullscreen }"
                :style="{ height: canvasHeight + 'px' }"
                @keydown="handleKeydown"
                @wheel="handleWheel"
                tabindex="0"
                role="img"
                :aria-label="accessibility.ariaLabel"
                :aria-describedby="accessibility.screenReaderDescription"
            >
                <!-- Canvas -->
                <canvas
                    ref="canvasRef"
                    :width="canvasWidth"
                    :height="canvasHeight"
                    class="cursor-crosshair"
                    @mousedown="handleMouseDown"
                    @mousemove="handleMouseMove"
                    @mouseup="handleMouseUp"
                    @mouseleave="handleMouseLeave"
                ></canvas>

                <!-- Tooltip -->
                <div
                    v-if="tooltip.visible"
                    class="pointer-events-none absolute z-10 rounded bg-black px-2 py-1 text-sm text-white"
                    :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
                >
                    {{ tooltip.content }}
                </div>

                <!-- Zoom Controls -->
                <div class="absolute right-4 top-4 flex flex-col space-y-2">
                    <button
                        @click="zoomIn"
                        class="flex h-8 w-8 items-center justify-center rounded border border-gray-300 bg-white shadow hover:bg-gray-50"
                        :disabled="interaction.zoomLevel >= 3"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </button>
                    <button
                        @click="zoomOut"
                        class="flex h-8 w-8 items-center justify-center rounded border border-gray-300 bg-white shadow hover:bg-gray-50"
                        :disabled="interaction.zoomLevel <= 0.5"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </button>
                </div>

                <!-- Legend -->
                <div class="absolute bottom-4 left-4 rounded bg-white p-3 shadow">
                    <div class="mb-2 text-sm font-medium">Intensity</div>
                    <div class="flex items-center space-x-2">
                        <div class="h-4 w-4 rounded" :style="{ backgroundColor: config.colorGradient.low }"></div>
                        <span class="text-xs text-gray-600">Low</span>
                        <div class="h-2 w-8 rounded bg-gradient-to-r from-blue-400 to-red-500"></div>
                        <span class="text-xs text-gray-600">High</span>
                        <div class="h-4 w-4 rounded" :style="{ backgroundColor: config.colorGradient.high }"></div>
                    </div>
                </div>
            </div>

            <!-- Screen Reader Description -->
            <div id="heatmap-description" class="sr-only">
                {{ accessibility.screenReaderDescription }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import type {
    HeatMapAccessibility,
    HeatMapApiResponse,
    HeatMapConfig,
    HeatMapData,
    HeatMapInteraction,
    HeatMapPoint,
    HeatMapViewerProps,
} from '../../Types/analytics';

// Props
const props = withDefaults(defineProps<HeatMapViewerProps>(), {
    dateRange: () => ({}),
});

// Refs
const containerRef = ref<HTMLDivElement>();
const canvasRef = ref<HTMLCanvasElement>();
const isLoading = ref(false);
const error = ref<string | null>(null);
const heatMapData = ref<HeatMapData | null>(null);
const isFullscreen = ref(false);

// Configuration
const config: HeatMapConfig = {
    canvasWidth: 800,
    canvasHeight: 600,
    minIntensity: 0,
    maxIntensity: 100,
    colorGradient: {
        low: '#3b82f6', // Blue
        high: '#ef4444', // Red
    },
    pointRadius: 20,
    blurRadius: 15,
};

// Interaction state
const interaction: HeatMapInteraction = {
    isZoomed: false,
    zoomLevel: 1,
    panOffset: { x: 0, y: 0 },
    hoveredPoint: null,
    tooltipPosition: null,
};

// Accessibility
const accessibility: HeatMapAccessibility = {
    ariaLabel: 'Interactive heat map showing user click patterns',
    keyboardNavigation: true,
    screenReaderDescription: 'Heat map visualization of user interactions. Use mouse to hover for details, scroll to zoom, and drag to pan.',
};

// Tooltip state
const tooltip = ref({
    visible: false,
    x: 0,
    y: 0,
    content: '',
});

// Computed properties
const canvasWidth = computed(() => config.canvasWidth * interaction.zoomLevel);
const canvasHeight = computed(() => config.canvasHeight * interaction.zoomLevel);

// Methods
const fetchHeatMapData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (props.dateRange?.from) params.append('date_from', props.dateRange.from);
        if (props.dateRange?.to) params.append('date_to', props.dateRange.to);

        const response = await axios.get<HeatMapApiResponse>(`/api/analytics/heatmaps/${encodeURIComponent(props.pageUrl)}?${params}`);
        heatMapData.value = response.data.data;
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Failed to load heat map data';
        console.error('Heat map fetch error:', err);
    } finally {
        isLoading.value = false;
    }
};

const drawHeatMap = () => {
    const canvas = canvasRef.value;
    if (!canvas || !heatMapData.value) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Draw background
    ctx.fillStyle = '#f9fafb';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw heat map points
    heatMapData.value.heatMapData.forEach((point) => {
        drawHeatPoint(ctx, point);
    });
};

const drawHeatPoint = (ctx: CanvasRenderingContext2D, point: HeatMapPoint) => {
    const x = (point.x / 100) * canvasWidth.value + interaction.panOffset.x;
    const y = (point.y / 100) * canvasHeight.value + interaction.panOffset.y;

    // Create radial gradient for heat effect
    const gradient = ctx.createRadialGradient(x, y, 0, x, y, config.pointRadius);
    const intensity = Math.min(point.intensity / config.maxIntensity, 1);
    const color = interpolateColor(config.colorGradient.low, config.colorGradient.high, intensity);

    gradient.addColorStop(0, color + '80'); // Semi-transparent center
    gradient.addColorStop(0.7, color + '40'); // More transparent
    gradient.addColorStop(1, color + '00'); // Fully transparent

    ctx.fillStyle = gradient;
    ctx.beginPath();
    ctx.arc(x, y, config.pointRadius, 0, Math.PI * 2);
    ctx.fill();
};

const interpolateColor = (color1: string, color2: string, factor: number): string => {
    const c1 = hexToRgb(color1);
    const c2 = hexToRgb(color2);

    if (!c1 || !c2) return color1;

    const r = Math.round(c1.r + (c2.r - c1.r) * factor);
    const g = Math.round(c1.g + (c2.g - c1.g) * factor);
    const b = Math.round(c1.b + (c2.b - c1.b) * factor);

    return rgbToHex(r, g, b);
};

const hexToRgb = (hex: string) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result
        ? {
              r: parseInt(result[1], 16),
              g: parseInt(result[2], 16),
              b: parseInt(result[3], 16),
          }
        : null;
};

const rgbToHex = (r: number, g: number, b: number): string => {
    return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
};

// Interaction handlers
const handleMouseDown = () => {
    interaction.isZoomed = true;
    // Implement pan start
};

const handleMouseMove = (event: MouseEvent) => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const rect = canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    // Find hovered point
    const hoveredPoint = findPointAt(x, y);
    if (hoveredPoint) {
        tooltip.value = {
            visible: true,
            x: event.clientX - rect.left + 10,
            y: event.clientY - rect.top - 10,
            content: `Clicks: ${hoveredPoint.intensity}`,
        };
    } else {
        tooltip.value.visible = false;
    }
};

const handleMouseUp = () => {
    // Implement pan end
};

const handleMouseLeave = () => {
    tooltip.value.visible = false;
};

const handleWheel = (event: WheelEvent) => {
    event.preventDefault();
    const delta = event.deltaY > 0 ? 0.9 : 1.1;
    zoom(delta, event.clientX, event.clientY);
};

const handleKeydown = (event: KeyboardEvent) => {
    switch (event.key) {
        case 'r':
        case 'R':
            resetView();
            break;
        case '+':
        case '=':
            zoomIn();
            break;
        case '-':
            zoomOut();
            break;
    }
};

const findPointAt = (x: number, y: number): HeatMapPoint | null => {
    if (!heatMapData.value) return null;

    return (
        heatMapData.value.heatMapData.find((point) => {
            const pointX = (point.x / 100) * canvasWidth.value + interaction.panOffset.x;
            const pointY = (point.y / 100) * canvasHeight.value + interaction.panOffset.y;
            const distance = Math.sqrt((x - pointX) ** 2 + (y - pointY) ** 2);
            return distance <= config.pointRadius;
        }) || null
    );
};

// Zoom and pan methods
const zoom = (factor: number, centerX?: number, centerY?: number) => {
    const newZoom = Math.max(0.5, Math.min(3, interaction.zoomLevel * factor));
    if (newZoom === interaction.zoomLevel) return;

    interaction.zoomLevel = newZoom;
    interaction.isZoomed = newZoom !== 1;

    // Adjust pan offset to zoom towards cursor
    if (centerX !== undefined && centerY !== undefined) {
        const canvas = canvasRef.value;
        if (canvas) {
            const rect = canvas.getBoundingClientRect();
            const canvasX = centerX - rect.left;
            const canvasY = centerY - rect.top;

            interaction.panOffset.x = canvasX - canvasX / factor;
            interaction.panOffset.y = canvasY - canvasY / factor;
        }
    }

    drawHeatMap();
};

const zoomIn = () => zoom(1.2);
const zoomOut = () => zoom(0.8);

const resetView = () => {
    interaction.zoomLevel = 1;
    interaction.panOffset = { x: 0, y: 0 };
    interaction.isZoomed = false;
    drawHeatMap();
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
};

// Watchers
watch(
    () => props.pageUrl,
    () => {
        fetchHeatMapData();
    },
);

watch(
    () => props.dateRange,
    () => {
        fetchHeatMapData();
    },
    { deep: true },
);

watch(heatMapData, () => {
    nextTick(() => drawHeatMap());
});

// Lifecycle
onMounted(() => {
    fetchHeatMapData();
});

// Cleanup
onUnmounted(() => {
    // Cleanup if needed
});
</script>

<style scoped>
.canvas-container {
    @apply transition-all duration-200;
}

.canvas-container.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    background: white;
}

.heat-map-viewer {
    @apply w-full;
}

/* Focus styles for accessibility */
.canvas-container:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Hide tooltip when not visible */
.tooltip {
    @apply opacity-0 transition-opacity duration-200;
}

.tooltip.visible {
    @apply opacity-100;
}
</style>

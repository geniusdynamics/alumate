<template>
    <div class="geographic-map">
        <div class="chart-header">
            <h4 class="chart-title">Geographic Distribution</h4>
            <div class="map-controls">
                <button @click="viewMode = 'list'" class="control-button" :class="{ active: viewMode === 'list' }">List View</button>
                <button @click="viewMode = 'map'" class="control-button" :class="{ active: viewMode === 'map' }">Map View</button>
            </div>
        </div>

        <div class="map-container">
            <div v-if="viewMode === 'list'" class="location-list">
                <div v-for="(location, index) in topLocations" :key="index" class="location-item">
                    <div class="location-info">
                        <span class="location-name">{{ location.location }}</span>
                        <span class="location-count">{{ location.count }} alumni</span>
                    </div>
                    <div class="location-bar">
                        <div class="location-fill" :style="{ width: `${getLocationPercentage(location.count)}%` }"></div>
                    </div>
                </div>
            </div>

            <div v-else class="map-placeholder">
                <div class="placeholder-content">
                    <Icon name="map" class="h-16 w-16 text-gray-400" />
                    <p class="placeholder-text">Interactive map would be displayed here</p>
                    <p class="placeholder-subtext">Integration with mapping library (Leaflet, Google Maps, etc.) required</p>
                </div>
            </div>
        </div>

        <div class="map-summary">
            <div class="summary-stats">
                <div class="stat-item">
                    <span class="stat-label">Total Locations</span>
                    <span class="stat-value">{{ totalLocations }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Top Location</span>
                    <span class="stat-value">{{ topLocation }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Countries</span>
                    <span class="stat-value">{{ estimatedCountries }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import { computed, ref } from 'vue';

interface LocationData {
    location: string;
    count: number;
}

interface Props {
    data: LocationData[];
}

const props = defineProps<Props>();
const viewMode = ref<'list' | 'map'>('list');

const topLocations = computed(() => {
    if (!props.data || !Array.isArray(props.data)) {
        return [];
    }
    return props.data.slice(0, 10);
});

const maxCount = computed(() => {
    return Math.max(...topLocations.value.map((l) => l.count), 1);
});

const totalLocations = computed(() => {
    return props.data?.length || 0;
});

const topLocation = computed(() => {
    if (topLocations.value.length === 0) return 'N/A';
    return topLocations.value[0].location;
});

const estimatedCountries = computed(() => {
    // Simple estimation based on location names
    const uniqueCountries = new Set();
    props.data?.forEach((location) => {
        const parts = location.location.split(',');
        if (parts.length > 1) {
            uniqueCountries.add(parts[parts.length - 1].trim());
        }
    });
    return uniqueCountries.size || 'N/A';
});

const getLocationPercentage = (count: number): number => {
    return (count / maxCount.value) * 100;
};
</script>

<style scoped>
.geographic-map {
    @apply h-full w-full;
}

.chart-header {
    @apply mb-4 flex items-center justify-between;
}

.chart-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.map-controls {
    @apply flex items-center space-x-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-700;
}

.control-button {
    @apply rounded-md px-3 py-1 text-sm font-medium transition-colors;
    @apply text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white;
}

.control-button.active {
    @apply bg-white text-gray-900 shadow-sm dark:bg-gray-600 dark:text-white;
}

.map-container {
    @apply mb-4 h-64;
}

.location-list {
    @apply h-full space-y-3 overflow-y-auto;
}

.location-item {
    @apply space-y-2;
}

.location-info {
    @apply flex items-center justify-between;
}

.location-name {
    @apply text-sm font-medium text-gray-900 dark:text-white;
}

.location-count {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

.location-bar {
    @apply h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700;
}

.location-fill {
    @apply h-2 rounded-full bg-blue-500 transition-all duration-300;
}

.map-placeholder {
    @apply flex h-full items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-800;
}

.placeholder-content {
    @apply space-y-2 text-center;
}

.placeholder-text {
    @apply font-medium text-gray-600 dark:text-gray-400;
}

.placeholder-subtext {
    @apply text-sm text-gray-500 dark:text-gray-500;
}

.map-summary {
    @apply border-t border-gray-200 pt-4 dark:border-gray-600;
}

.summary-stats {
    @apply grid grid-cols-3 gap-4;
}

.stat-item {
    @apply text-center;
}

.stat-label {
    @apply mb-1 block text-xs text-gray-600 dark:text-gray-400;
}

.stat-value {
    @apply block text-lg font-semibold text-gray-900 dark:text-white;
}
</style>


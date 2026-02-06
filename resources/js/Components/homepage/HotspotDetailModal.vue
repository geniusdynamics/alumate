<template>
    <div class="modal-overlay" @click="handleOverlayClick">
        <div class="modal-content" @click.stop>
            <div class="modal-header">
                <h3 class="modal-title">{{ hotspot.title || 'Feature Detail' }}</h3>
                <button @click="$emit('close')" class="close-button" aria-label="Close modal">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <div class="modal-body">
                <div class="hotspot-detail">
                    <!-- Feature Image/Screenshot -->
                    <div v-if="hotspot.image" class="feature-image">
                        <img :src="hotspot.image" :alt="hotspot.title" class="h-auto w-full rounded-lg shadow-lg" />
                    </div>

                    <!-- Feature Description -->
                    <div class="feature-description">
                        <p class="description-text">{{ hotspot.description }}</p>

                        <!-- Detailed Information -->
                        <div v-if="hotspot.details" class="feature-details">
                            <h4 class="details-title">Key Features:</h4>
                            <ul class="details-list">
                                <li v-for="detail in hotspot.details" :key="detail" class="detail-item">
                                    <CheckIcon class="mr-2 h-5 w-5 flex-shrink-0 text-green-500" />
                                    {{ detail }}
                                </li>
                            </ul>
                        </div>

                        <!-- Benefits Section -->
                        <div v-if="hotspot.benefits" class="feature-benefits">
                            <h4 class="benefits-title">Benefits:</h4>
                            <div class="benefits-grid">
                                <div v-for="benefit in hotspot.benefits" :key="benefit.title" class="benefit-card">
                                    <div class="benefit-icon">
                                        <component :is="benefit.icon" class="h-6 w-6" />
                                    </div>
                                    <div class="benefit-content">
                                        <h5 class="benefit-title">{{ benefit.title }}</h5>
                                        <p class="benefit-description">{{ benefit.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Usage Statistics -->
                        <div v-if="hotspot.stats" class="feature-stats">
                            <h4 class="stats-title">Usage Statistics:</h4>
                            <div class="stats-grid">
                                <div v-for="stat in hotspot.stats" :key="stat.label" class="stat-item">
                                    <div class="stat-value">{{ stat.value }}</div>
                                    <div class="stat-label">{{ stat.label }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Technical Details -->
                        <div v-if="hotspot.technical" class="technical-details">
                            <h4 class="technical-title">Technical Information:</h4>
                            <div class="technical-content">
                                <div v-if="hotspot.technical.integrations" class="tech-section">
                                    <h5 class="tech-subtitle">Integrations:</h5>
                                    <div class="integration-tags">
                                        <span v-for="integration in hotspot.technical.integrations" :key="integration" class="integration-tag">
                                            {{ integration }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="hotspot.technical.requirements" class="tech-section">
                                    <h5 class="tech-subtitle">Requirements:</h5>
                                    <ul class="requirements-list">
                                        <li v-for="requirement in hotspot.technical.requirements" :key="requirement" class="requirement-item">
                                            {{ requirement }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="modal-actions">
                    <button @click="requestDemo" class="demo-button">
                        <PlayIcon class="mr-2 h-5 w-5" />
                        See Live Demo
                    </button>
                    <button @click="learnMore" class="learn-more-button">
                        <DocumentTextIcon class="mr-2 h-5 w-5" />
                        Learn More
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { CheckIcon, DocumentTextIcon, PlayIcon, XMarkIcon } from '@heroicons/vue/24/outline';

interface HotspotDetail {
    id: string;
    title?: string;
    description: string;
    image?: string;
    details?: string[];
    benefits?: Array<{
        title: string;
        description: string;
        icon: any;
    }>;
    stats?: Array<{
        label: string;
        value: string;
    }>;
    technical?: {
        integrations?: string[];
        requirements?: string[];
    };
}

interface Props {
    hotspot: HotspotDetail;
}

defineProps<Props>();

const emit = defineEmits<{
    close: [];
    'demo-request': [hotspotId: string];
    'learn-more': [hotspotId: string];
}>();

// Methods
const handleOverlayClick = () => {
    emit('close');
};

const requestDemo = () => {
    emit('demo-request', props.hotspot.id);
};

const learnMore = () => {
    emit('learn-more', props.hotspot.id);
};
</script>

<style scoped>
.modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4;
}

.modal-content {
    @apply max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-lg bg-white shadow-xl;
}

.modal-header {
    @apply flex items-center justify-between border-b border-gray-200 p-6;
}

.modal-title {
    @apply text-2xl font-bold text-gray-900;
}

.close-button {
    @apply text-gray-400 transition-colors hover:text-gray-600;
}

.modal-body {
    @apply p-6;
}

.hotspot-detail {
    @apply space-y-6;
}

.feature-image {
    @apply mb-6;
}

.feature-description {
    @apply space-y-6;
}

.description-text {
    @apply text-lg leading-relaxed text-gray-700;
}

.feature-details {
    @apply rounded-lg bg-gray-50 p-4;
}

.details-title {
    @apply mb-3 text-lg font-semibold text-gray-900;
}

.details-list {
    @apply space-y-2;
}

.detail-item {
    @apply flex items-start;
}

.feature-benefits {
    @apply space-y-4;
}

.benefits-title {
    @apply text-lg font-semibold text-gray-900;
}

.benefits-grid {
    @apply grid grid-cols-1 gap-4 md:grid-cols-2;
}

.benefit-card {
    @apply flex items-start space-x-3 rounded-lg bg-blue-50 p-4;
}

.benefit-icon {
    @apply flex-shrink-0 text-blue-600;
}

.benefit-content {
    @apply space-y-1;
}

.benefit-title {
    @apply font-medium text-gray-900;
}

.benefit-description {
    @apply text-sm text-gray-600;
}

.feature-stats {
    @apply space-y-4;
}

.stats-title {
    @apply text-lg font-semibold text-gray-900;
}

.stats-grid {
    @apply grid grid-cols-2 gap-4 md:grid-cols-4;
}

.stat-item {
    @apply rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 p-4 text-center;
}

.stat-value {
    @apply text-2xl font-bold text-blue-600;
}

.stat-label {
    @apply mt-1 text-sm text-gray-600;
}

.technical-details {
    @apply space-y-4;
}

.technical-title {
    @apply text-lg font-semibold text-gray-900;
}

.technical-content {
    @apply space-y-4;
}

.tech-section {
    @apply space-y-2;
}

.tech-subtitle {
    @apply font-medium text-gray-900;
}

.integration-tags {
    @apply flex flex-wrap gap-2;
}

.integration-tag {
    @apply rounded-full bg-green-100 px-3 py-1 text-sm text-green-800;
}

.requirements-list {
    @apply ml-4 space-y-1;
}

.requirement-item {
    @apply list-disc text-sm text-gray-600;
}

.modal-actions {
    @apply flex justify-center space-x-4 border-t border-gray-200 pt-6;
}

.demo-button {
    @apply flex items-center rounded-lg bg-blue-600 px-6 py-3 text-white transition-colors hover:bg-blue-700;
}

.learn-more-button {
    @apply flex items-center rounded-lg bg-gray-100 px-6 py-3 text-gray-700 transition-colors hover:bg-gray-200;
}

@media (max-width: 640px) {
    .modal-content {
        @apply mx-2 max-h-[95vh];
    }

    .benefits-grid {
        @apply grid-cols-1;
    }

    .stats-grid {
        @apply grid-cols-2;
    }

    .modal-actions {
        @apply flex-col space-x-0 space-y-3;
    }
}
</style>

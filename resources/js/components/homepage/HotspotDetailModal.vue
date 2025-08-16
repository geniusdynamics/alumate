<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">{{ hotspot.title || 'Feature Detail' }}</h3>
        <button 
          @click="$emit('close')"
          class="close-button"
          aria-label="Close modal"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <div class="modal-body">
        <div class="hotspot-detail">
          <!-- Feature Image/Screenshot -->
          <div v-if="hotspot.image" class="feature-image">
            <img 
              :src="hotspot.image" 
              :alt="hotspot.title"
              class="w-full h-auto rounded-lg shadow-lg"
            />
          </div>

          <!-- Feature Description -->
          <div class="feature-description">
            <p class="description-text">{{ hotspot.description }}</p>
            
            <!-- Detailed Information -->
            <div v-if="hotspot.details" class="feature-details">
              <h4 class="details-title">Key Features:</h4>
              <ul class="details-list">
                <li 
                  v-for="detail in hotspot.details" 
                  :key="detail"
                  class="detail-item"
                >
                  <CheckIcon class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" />
                  {{ detail }}
                </li>
              </ul>
            </div>

            <!-- Benefits Section -->
            <div v-if="hotspot.benefits" class="feature-benefits">
              <h4 class="benefits-title">Benefits:</h4>
              <div class="benefits-grid">
                <div 
                  v-for="benefit in hotspot.benefits"
                  :key="benefit.title"
                  class="benefit-card"
                >
                  <div class="benefit-icon">
                    <component :is="benefit.icon" class="w-6 h-6" />
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
                <div 
                  v-for="stat in hotspot.stats"
                  :key="stat.label"
                  class="stat-item"
                >
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
                    <span 
                      v-for="integration in hotspot.technical.integrations"
                      :key="integration"
                      class="integration-tag"
                    >
                      {{ integration }}
                    </span>
                  </div>
                </div>
                
                <div v-if="hotspot.technical.requirements" class="tech-section">
                  <h5 class="tech-subtitle">Requirements:</h5>
                  <ul class="requirements-list">
                    <li 
                      v-for="requirement in hotspot.technical.requirements"
                      :key="requirement"
                      class="requirement-item"
                    >
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
          <button
            @click="requestDemo"
            class="demo-button"
          >
            <PlayIcon class="w-5 h-5 mr-2" />
            See Live Demo
          </button>
          <button
            @click="learnMore"
            class="learn-more-button"
          >
            <DocumentTextIcon class="w-5 h-5 mr-2" />
            Learn More
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { 
  XMarkIcon, 
  CheckIcon, 
  PlayIcon, 
  DocumentTextIcon 
} from '@heroicons/vue/24/outline'

interface HotspotDetail {
  id: string
  title?: string
  description: string
  image?: string
  details?: string[]
  benefits?: Array<{
    title: string
    description: string
    icon: any
  }>
  stats?: Array<{
    label: string
    value: string
  }>
  technical?: {
    integrations?: string[]
    requirements?: string[]
  }
}

interface Props {
  hotspot: HotspotDetail
}

defineProps<Props>()

const emit = defineEmits<{
  'close': []
  'demo-request': [hotspotId: string]
  'learn-more': [hotspotId: string]
}>()

// Methods
const handleOverlayClick = () => {
  emit('close')
}

const requestDemo = () => {
  emit('demo-request', props.hotspot.id)
}

const learnMore = () => {
  emit('learn-more', props.hotspot.id)
}
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-content {
  @apply bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto;
}

.modal-header {
  @apply flex justify-between items-center p-6 border-b border-gray-200;
}

.modal-title {
  @apply text-2xl font-bold text-gray-900;
}

.close-button {
  @apply text-gray-400 hover:text-gray-600 transition-colors;
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
  @apply text-lg text-gray-700 leading-relaxed;
}

.feature-details {
  @apply bg-gray-50 rounded-lg p-4;
}

.details-title {
  @apply text-lg font-semibold text-gray-900 mb-3;
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
  @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.benefit-card {
  @apply flex items-start space-x-3 p-4 bg-blue-50 rounded-lg;
}

.benefit-icon {
  @apply text-blue-600 flex-shrink-0;
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
  @apply grid grid-cols-2 md:grid-cols-4 gap-4;
}

.stat-item {
  @apply text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg;
}

.stat-value {
  @apply text-2xl font-bold text-blue-600;
}

.stat-label {
  @apply text-sm text-gray-600 mt-1;
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
  @apply px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full;
}

.requirements-list {
  @apply space-y-1 ml-4;
}

.requirement-item {
  @apply text-sm text-gray-600 list-disc;
}

.modal-actions {
  @apply flex justify-center space-x-4 pt-6 border-t border-gray-200;
}

.demo-button {
  @apply flex items-center px-6 py-3 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition-colors;
}

.learn-more-button {
  @apply flex items-center px-6 py-3 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors;
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
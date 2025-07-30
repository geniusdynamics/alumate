<template>
  <div class="connection-insights">
    <div class="insights-header">
      <h3 class="insights-title">
        <Icon name="users" class="title-icon" />
        Your Network at {{ companyName }}
      </h3>
      <p class="insights-subtitle">
        {{ connections.length }} mutual connection{{ connections.length !== 1 ? 's' : '' }} 
        can help you with this opportunity
      </p>
    </div>

    <div v-if="connections.length > 0" class="connections-list">
      <div
        v-for="connection in connections"
        :key="connection.id"
        class="connection-card"
      >
        <!-- Avatar and Basic Info -->
        <div class="connection-header">
          <div class="avatar-container">
            <img
              v-if="connection.avatar_url"
              :src="connection.avatar_url"
              :alt="connection.name"
              class="avatar"
            />
            <div v-else class="avatar-placeholder">
              {{ connection.name.charAt(0) }}
            </div>
          </div>
          
          <div class="connection-info">
            <h4 class="connection-name">{{ connection.name }}</h4>
            <p class="connection-title">{{ connection.title }}</p>
            <p v-if="connection.department" class="connection-department">
              {{ connection.department }}
            </p>
          </div>
        </div>

        <!-- Connection Details -->
        <div class="connection-details">
          <div v-if="connection.tenure" class="detail-item">
            <Icon name="calendar" class="detail-icon" />
            <span>{{ connection.tenure }}</span>
          </div>
          
          <div v-if="connection.mutual_circles.length > 0" class="detail-item">
            <Icon name="graduation-cap" class="detail-icon" />
            <span>{{ formatMutualCircles(connection.mutual_circles) }}</span>
          </div>
        </div>

        <!-- Action Button -->
        <div class="connection-actions">
          <button
            v-if="connection.can_request_introduction"
            @click="requestIntroduction(connection)"
            class="introduction-btn"
          >
            <Icon name="message" class="btn-icon" />
            Request Introduction
          </button>
          
          <div v-else class="introduction-disabled">
            <Icon name="lock" class="btn-icon" />
            Introduction not available
          </div>
        </div>
      </div>
    </div>

    <div v-else class="no-connections">
      <div class="no-connections-icon">🤝</div>
      <h4>No direct connections found</h4>
      <p>
        You don't have any direct connections at {{ companyName }} yet, 
        but you can still apply directly or expand your network.
      </p>
    </div>

    <!-- Introduction Request Modal -->
    <IntroductionRequestModal
      v-if="selectedConnection"
      :connection="selectedConnection"
      :company="companyName"
      :job-title="jobTitle"
      :show="showIntroductionModal"
      @close="closeIntroductionModal"
      @submit="submitIntroductionRequest"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import Icon from './Icon.vue'
import IntroductionRequestModal from './IntroductionRequestModal.vue'

const props = defineProps({
  connections: {
    type: Array,
    required: true
  },
  companyName: {
    type: String,
    required: true
  },
  jobTitle: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['request-introduction'])

const selectedConnection = ref(null)
const showIntroductionModal = ref(false)

const formatMutualCircles = (circles) => {
  if (circles.length === 0) return ''
  if (circles.length === 1) return `Alumni: ${circles[0]}`
  if (circles.length === 2) return `Alumni: ${circles.join(' & ')}`
  return `Alumni: ${circles[0]} & ${circles.length - 1} more`
}

const requestIntroduction = (connection) => {
  selectedConnection.value = connection
  showIntroductionModal.value = true
}

const closeIntroductionModal = () => {
  showIntroductionModal.value = false
  selectedConnection.value = null
}

const submitIntroductionRequest = (message) => {
  emit('request-introduction', selectedConnection.value.id, message)
  closeIntroductionModal()
}
</script>

<style scoped>
.connection-insights {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
}

.insights-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.insights-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.5rem 0;
}

.title-icon {
  width: 20px;
  height: 20px;
  color: #3b82f6;
}

.insights-subtitle {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.connections-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.connection-card {
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  background: #fafafa;
  transition: all 0.2s ease;
}

.connection-card:hover {
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.connection-header {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.avatar-container {
  flex-shrink: 0;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 40px;
  height: 40px;
  background: #e5e7eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: #6b7280;
}

.connection-info {
  flex: 1;
  min-width: 0;
}

.connection-name {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.connection-title {
  font-size: 0.8125rem;
  color: #374151;
  margin: 0 0 0.125rem 0;
  font-weight: 500;
}

.connection-department {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0;
}

.connection-details {
  margin-bottom: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.detail-icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.connection-actions {
  display: flex;
  justify-content: flex-end;
}

.introduction-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.introduction-btn:hover {
  background: #2563eb;
}

.introduction-disabled {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  background: #f3f4f6;
  color: #6b7280;
  border-radius: 0.375rem;
  font-size: 0.8125rem;
}

.btn-icon {
  width: 14px;
  height: 14px;
}

.no-connections {
  text-align: center;
  padding: 2rem 1rem;
}

.no-connections-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.no-connections h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.5rem 0;
}

.no-connections p {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
}

@media (max-width: 640px) {
  .connection-insights {
    padding: 1rem;
  }
  
  .connection-card {
    padding: 0.75rem;
  }
  
  .connection-header {
    gap: 0.5rem;
  }
  
  .avatar,
  .avatar-placeholder {
    width: 36px;
    height: 36px;
  }
  
  .connection-name {
    font-size: 0.875rem;
  }
  
  .connection-title {
    font-size: 0.75rem;
  }
  
  .introduction-btn {
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
  }
}
</style>
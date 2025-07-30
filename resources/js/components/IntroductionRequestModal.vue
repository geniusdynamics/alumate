<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">Request Introduction</h2>
        <button @click="$emit('close')" class="close-button">
          <Icon name="x" />
        </button>
      </div>

      <form @submit.prevent="submitRequest" class="introduction-form">
        <div class="modal-content">
          <!-- Connection Info -->
          <div class="connection-section">
            <div class="connection-card">
              <div class="connection-avatar">
                <img
                  v-if="connection.avatar_url"
                  :src="connection.avatar_url"
                  :alt="connection.name"
                  class="avatar-image"
                />
                <div v-else class="avatar-placeholder">
                  {{ connection.name.charAt(0) }}
                </div>
              </div>
              <div class="connection-info">
                <h3 class="connection-name">{{ connection.name }}</h3>
                <p class="connection-title">{{ connection.title }}</p>
                <p class="connection-company">{{ company }}</p>
              </div>
            </div>
          </div>

          <!-- Job Context -->
          <div class="job-context">
            <p class="context-text">
              You're requesting an introduction to help with your application for 
              <strong>{{ jobTitle }}</strong> at <strong>{{ company }}</strong>.
            </p>
          </div>

          <!-- Message -->
          <div class="form-section">
            <label class="form-label" for="message">
              Personal Message *
            </label>
            <textarea
              id="message"
              v-model="form.message"
              class="form-textarea"
              rows="6"
              placeholder="Hi [Name], I hope you're doing well! I noticed you work at [Company] and I'm very interested in the [Job Title] position. I'd really appreciate if you could provide some insights about the role and the company culture. Would you be open to a brief chat or introduction to the hiring team? Thanks so much!"
              required
            ></textarea>
            <div class="character-count">
              {{ form.message.length }}/500 characters
            </div>
          </div>

          <!-- Tips -->
          <div class="tips-section">
            <h4 class="tips-title">
              <Icon name="lightbulb" class="tips-icon" />
              Tips for a great introduction request:
            </h4>
            <ul class="tips-list">
              <li>Be specific about the role you're interested in</li>
              <li>Mention your shared connection or background</li>
              <li>Ask for insights, not just a referral</li>
              <li>Keep it concise and professional</li>
              <li>Offer to provide your resume or portfolio</li>
            </ul>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="modal-actions">
          <button type="button" @click="$emit('close')" class="btn btn-secondary">
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!isFormValid || submitting"
            class="btn btn-primary"
          >
            <span v-if="submitting">Sending...</span>
            <span v-else>Send Request</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  connection: {
    type: Object,
    required: true
  },
  company: {
    type: String,
    required: true
  },
  jobTitle: {
    type: String,
    required: true
  },
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'submit'])

const submitting = ref(false)

const form = reactive({
  message: ''
})

const isFormValid = computed(() => {
  return form.message.trim().length > 0 && 
         form.message.length <= 500
})

// Generate a default message when the modal opens
watch(() => props.show, (show) => {
  if (show && !form.message) {
    form.message = `Hi ${props.connection.name}, I hope you're doing well! I noticed you work at ${props.company} and I'm very interested in the ${props.jobTitle} position. I'd really appreciate if you could provide some insights about the role and the company culture. Would you be open to a brief chat or introduction to the hiring team? Thanks so much!`
  }
})

const closeModal = () => {
  emit('close')
}

const submitRequest = async () => {
  if (!isFormValid.value || submitting.value) return
  
  submitting.value = true
  
  try {
    await emit('submit', form.message.trim())
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 0.75rem;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.close-button {
  background: none;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: #6b7280;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.close-button:hover {
  background: #f3f4f6;
}

.introduction-form {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.modal-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.connection-section {
  margin-bottom: 1.5rem;
}

.connection-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.connection-avatar .avatar-image {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
}

.connection-avatar .avatar-placeholder {
  width: 48px;
  height: 48px;
  background: #e5e7eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: #6b7280;
}

.connection-name {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.connection-title {
  font-size: 0.875rem;
  color: #374151;
  margin: 0 0 0.125rem 0;
}

.connection-company {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.job-context {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #eff6ff;
  border-radius: 0.5rem;
  border-left: 4px solid #3b82f6;
}

.context-text {
  color: #1e40af;
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
}

.form-section {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  resize: vertical;
  min-height: 120px;
  line-height: 1.5;
}

.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.character-count {
  text-align: right;
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.tips-section {
  background: #f0fdf4;
  border-radius: 0.5rem;
  padding: 1rem;
  border-left: 4px solid #10b981;
}

.tips-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #065f46;
  margin: 0 0 0.75rem 0;
}

.tips-icon {
  width: 16px;
  height: 16px;
}

.tips-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.tips-list li {
  font-size: 0.8125rem;
  color: #047857;
  margin-bottom: 0.375rem;
  position: relative;
  padding-left: 1rem;
}

.tips-list li::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #10b981;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 0.375rem;
  font-weight: 500;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: #f9fafb;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #f3f4f6;
}

@media (max-width: 768px) {
  .modal-overlay {
    padding: 0.5rem;
  }
  
  .modal-container {
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-content,
  .modal-actions {
    padding: 1rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
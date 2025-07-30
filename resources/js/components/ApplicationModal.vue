<template>
  <div v-if="show" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h2 class="modal-title">Apply for {{ job.title }}</h2>
        <button @click="$emit('close')" class="close-button">
          <Icon name="x" />
        </button>
      </div>

      <form @submit.prevent="submitApplication" class="application-form">
        <div class="modal-content">
          <!-- Job Summary -->
          <div class="job-summary">
            <div class="company-info">
              <div class="company-logo">
                <img
                  v-if="job.company?.logo_url"
                  :src="job.company.logo_url"
                  :alt="job.company.name"
                  class="logo-image"
                />
                <div v-else class="logo-placeholder">
                  {{ job.company?.name?.charAt(0) || 'C' }}
                </div>
              </div>
              <div>
                <h3 class="job-title">{{ job.title }}</h3>
                <p class="company-name">{{ job.company?.name }}</p>
              </div>
            </div>
          </div>

          <!-- Cover Letter -->
          <div class="form-section">
            <label class="form-label" for="cover-letter">
              Cover Letter *
            </label>
            <textarea
              id="cover-letter"
              v-model="form.coverLetter"
              class="form-textarea"
              rows="8"
              placeholder="Tell the employer why you're interested in this role and how your experience makes you a great fit..."
              required
            ></textarea>
            <div class="character-count">
              {{ form.coverLetter.length }}/2000 characters
            </div>
          </div>

          <!-- Resume Upload -->
          <div class="form-section">
            <label class="form-label" for="resume">
              Resume (Optional)
            </label>
            <div class="file-upload-area">
              <input
                id="resume"
                ref="resumeInput"
                type="file"
                accept=".pdf,.doc,.docx"
                @change="handleResumeUpload"
                class="file-input"
              />
              <div v-if="!form.resume" class="upload-placeholder">
                <Icon name="upload" class="upload-icon" />
                <p class="upload-text">
                  Click to upload your resume or drag and drop
                </p>
                <p class="upload-hint">
                  PDF, DOC, or DOCX (max 5MB)
                </p>
              </div>
              <div v-else class="uploaded-file">
                <Icon name="file" class="file-icon" />
                <span class="file-name">{{ form.resume.name }}</span>
                <button
                  type="button"
                  @click="removeResume"
                  class="remove-file-btn"
                >
                  <Icon name="x" />
                </button>
              </div>
            </div>
          </div>

          <!-- Network Connections -->
          <div v-if="job.mutual_connections && job.mutual_connections.length > 0" class="form-section">
            <label class="form-label">
              Request Introduction (Optional)
            </label>
            <p class="form-hint">
              You have {{ job.mutual_connections.length }} mutual connection{{ job.mutual_connections.length > 1 ? 's' : '' }} 
              at {{ job.company?.name }}. Would you like to request an introduction?
            </p>
            
            <div class="connections-list">
              <div
                v-for="connection in job.mutual_connections.slice(0, 3)"
                :key="connection.id"
                class="connection-option"
                :class="{ active: form.introductionContactId === connection.id }"
                @click="selectConnection(connection.id)"
              >
                <input
                  type="radio"
                  :value="connection.id"
                  v-model="form.introductionContactId"
                  class="connection-radio"
                />
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
                  <h4 class="connection-name">{{ connection.name }}</h4>
                  <p class="connection-title">{{ connection.title }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Application Notes -->
          <div class="form-section">
            <label class="form-label">
              Additional Notes (Optional)
            </label>
            <textarea
              v-model="form.notes"
              class="form-textarea"
              rows="3"
              placeholder="Any additional information you'd like to include..."
            ></textarea>
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
            <span v-if="submitting">Submitting...</span>
            <span v-else>Submit Application</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
  job: {
    type: Object,
    required: true
  },
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'submit'])

const resumeInput = ref(null)
const submitting = ref(false)

const form = reactive({
  coverLetter: '',
  resume: null,
  introductionContactId: null,
  notes: ''
})

const isFormValid = computed(() => {
  return form.coverLetter.trim().length > 0 && 
         form.coverLetter.length <= 2000
})

const closeModal = () => {
  emit('close')
}

const handleResumeUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB')
      return
    }
    
    // Validate file type
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    if (!allowedTypes.includes(file.type)) {
      alert('Please upload a PDF, DOC, or DOCX file')
      return
    }
    
    form.resume = file
  }
}

const removeResume = () => {
  form.resume = null
  if (resumeInput.value) {
    resumeInput.value.value = ''
  }
}

const selectConnection = (connectionId) => {
  form.introductionContactId = form.introductionContactId === connectionId ? null : connectionId
}

const submitApplication = async () => {
  if (!isFormValid.value || submitting.value) return
  
  submitting.value = true
  
  try {
    const applicationData = {
      coverLetter: form.coverLetter.trim(),
      resume: form.resume,
      introductionContactId: form.introductionContactId,
      notes: form.notes.trim()
    }
    
    await emit('submit', applicationData)
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
  max-width: 600px;
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

.application-form {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.modal-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.job-summary {
  margin-bottom: 2rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.company-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.company-logo .logo-image {
  width: 48px;
  height: 48px;
  border-radius: 0.375rem;
  object-fit: cover;
}

.company-logo .logo-placeholder {
  width: 48px;
  height: 48px;
  background: #e5e7eb;
  border-radius: 0.375rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: #6b7280;
}

.job-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.company-name {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
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
  min-height: 100px;
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

.file-upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s;
  position: relative;
}

.file-upload-area:hover {
  border-color: #3b82f6;
}

.file-input {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.upload-placeholder {
  pointer-events: none;
}

.upload-icon {
  width: 48px;
  height: 48px;
  color: #9ca3af;
  margin: 0 auto 1rem;
}

.upload-text {
  font-weight: 500;
  color: #374151;
  margin: 0 0 0.5rem 0;
}

.upload-hint {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.uploaded-file {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: #f3f4f6;
  padding: 0.75rem;
  border-radius: 0.375rem;
}

.file-icon {
  width: 20px;
  height: 20px;
  color: #3b82f6;
}

.file-name {
  font-weight: 500;
  color: #374151;
}

.remove-file-btn {
  background: none;
  border: none;
  padding: 0.25rem;
  cursor: pointer;
  color: #ef4444;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.remove-file-btn:hover {
  background: #fee2e2;
}

.form-hint {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 1rem;
}

.connections-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.connection-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.connection-option:hover {
  border-color: #3b82f6;
  background: #f8fafc;
}

.connection-option.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.connection-radio {
  margin: 0;
}

.connection-avatar .avatar-image {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.connection-avatar .avatar-placeholder {
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

.connection-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #111827;
  margin: 0 0 0.125rem 0;
}

.connection-title {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0;
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
  
  .file-upload-area {
    padding: 1.5rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
<!-- ABOUTME: Contact form component for user inquiries and communication -->
<!-- ABOUTME: Provides form interface for users to submit contact requests with validation and submission handling -->
<template>
  <div class="contact-form-container">
    <div class="form-header">
      <h2 class="form-title">{{ title || 'Get in Touch' }}</h2>
      <p v-if="subtitle" class="form-subtitle">{{ subtitle }}</p>
    </div>

    <form @submit.prevent="handleSubmit" class="contact-form">
      <!-- Name Field -->
      <div class="form-group">
        <label for="name" class="form-label">Full Name *</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="form-input"
          :class="{ 'error': errors.name }"
          placeholder="Enter your full name"
          required
        />
        <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
      </div>

      <!-- Email Field -->
      <div class="form-group">
        <label for="email" class="form-label">Email Address *</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          class="form-input"
          :class="{ 'error': errors.email }"
          placeholder="Enter your email address"
          required
        />
        <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
      </div>

      <!-- Phone Field -->
      <div class="form-group">
        <label for="phone" class="form-label">Phone Number</label>
        <input
          id="phone"
          v-model="form.phone"
          type="tel"
          class="form-input"
          :class="{ 'error': errors.phone }"
          placeholder="Enter your phone number"
        />
        <span v-if="errors.phone" class="error-message">{{ errors.phone }}</span>
      </div>

      <!-- Subject Field -->
      <div class="form-group">
        <label for="subject" class="form-label">Subject *</label>
        <select
          id="subject"
          v-model="form.subject"
          class="form-select"
          :class="{ 'error': errors.subject }"
          required
        >
          <option value="">Select a subject</option>
          <option value="general">General Inquiry</option>
          <option value="support">Technical Support</option>
          <option value="sales">Sales Question</option>
          <option value="partnership">Partnership Opportunity</option>
          <option value="feedback">Feedback</option>
          <option value="other">Other</option>
        </select>
        <span v-if="errors.subject" class="error-message">{{ errors.subject }}</span>
      </div>

      <!-- Message Field -->
      <div class="form-group">
        <label for="message" class="form-label">Message *</label>
        <textarea
          id="message"
          v-model="form.message"
          class="form-textarea"
          :class="{ 'error': errors.message }"
          placeholder="Enter your message"
          rows="5"
          required
        ></textarea>
        <span v-if="errors.message" class="error-message">{{ errors.message }}</span>
      </div>

      <!-- Privacy Consent -->
      <div class="form-group">
        <label class="checkbox-label">
          <input
            v-model="form.consent"
            type="checkbox"
            class="form-checkbox"
            required
          />
          <span class="checkbox-text">
            I agree to the <a href="/privacy" target="_blank" class="privacy-link">Privacy Policy</a> and consent to being contacted.
          </span>
        </label>
        <span v-if="errors.consent" class="error-message">{{ errors.consent }}</span>
      </div>

      <!-- Submit Button -->
      <div class="form-actions">
        <button
          type="submit"
          class="submit-button"
          :disabled="isSubmitting || !isFormValid"
        >
          <svg v-if="isSubmitting" class="loading-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <span>{{ isSubmitting ? 'Sending...' : 'Send Message' }}</span>
        </button>
      </div>
    </form>

    <!-- Success Message -->
    <div v-if="showSuccess" class="success-message">
      <svg class="success-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <div class="success-content">
        <h3 class="success-title">Message Sent Successfully!</h3>
        <p class="success-text">Thank you for contacting us. We'll get back to you within 24 hours.</p>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="showError" class="error-banner">
      <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <div class="error-content">
        <h3 class="error-title">Failed to Send Message</h3>
        <p class="error-text">{{ errorMessage || 'Please try again later or contact us directly.' }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'

interface Props {
  title?: string
  subtitle?: string
  endpoint?: string
}

interface Emits {
  'submit': [formData: ContactFormData]
  'success': []
  'error': [error: string]
}

interface ContactFormData {
  name: string
  email: string
  phone: string
  subject: string
  message: string
  consent: boolean
}

const props = withDefaults(defineProps<Props>(), {
  endpoint: '/api/contact'
})

const emit = defineEmits<Emits>()

// Reactive form data
const form = reactive<ContactFormData>({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
  consent: false
})

// Form state
const isSubmitting = ref(false)
const showSuccess = ref(false)
const showError = ref(false)
const errorMessage = ref('')

// Form validation errors
const errors = reactive({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
  consent: ''
})

// Computed
const isFormValid = computed(() => {
  return form.name.trim() !== '' &&
         form.email.trim() !== '' &&
         form.subject !== '' &&
         form.message.trim() !== '' &&
         form.consent &&
         isValidEmail(form.email)
})

// Methods
const isValidEmail = (email: string): boolean => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

const validateForm = (): boolean => {
  // Clear previous errors
  Object.keys(errors).forEach(key => {
    errors[key as keyof typeof errors] = ''
  })

  let isValid = true

  // Name validation
  if (!form.name.trim()) {
    errors.name = 'Name is required'
    isValid = false
  }

  // Email validation
  if (!form.email.trim()) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!isValidEmail(form.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }

  // Subject validation
  if (!form.subject) {
    errors.subject = 'Please select a subject'
    isValid = false
  }

  // Message validation
  if (!form.message.trim()) {
    errors.message = 'Message is required'
    isValid = false
  } else if (form.message.trim().length < 10) {
    errors.message = 'Message must be at least 10 characters long'
    isValid = false
  }

  // Consent validation
  if (!form.consent) {
    errors.consent = 'You must agree to the privacy policy'
    isValid = false
  }

  return isValid
}

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.phone = ''
  form.subject = ''
  form.message = ''
  form.consent = false
  
  Object.keys(errors).forEach(key => {
    errors[key as keyof typeof errors] = ''
  })
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true
  showSuccess.value = false
  showError.value = false
  errorMessage.value = ''

  try {
    // Emit the form data to parent component
    emit('submit', { ...form })

    // Simulate API call
    const response = await fetch(props.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form)
    })

    if (response.ok) {
      showSuccess.value = true
      resetForm()
      emit('success')
      
      // Hide success message after 5 seconds
      setTimeout(() => {
        showSuccess.value = false
      }, 5000)
    } else {
      throw new Error('Failed to send message')
    }
  } catch (error) {
    console.error('Contact form submission error:', error)
    errorMessage.value = error instanceof Error ? error.message : 'An unexpected error occurred'
    showError.value = true
    emit('error', errorMessage.value)
    
    // Hide error message after 5 seconds
    setTimeout(() => {
      showError.value = false
    }, 5000)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.contact-form-container {
  @apply max-w-2xl mx-auto p-6;
}

.form-header {
  @apply text-center mb-8;
}

.form-title {
  @apply text-3xl font-bold text-gray-900 dark:text-white mb-4;
}

.form-subtitle {
  @apply text-lg text-gray-600 dark:text-gray-400;
}

.contact-form {
  @apply space-y-6;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
}

.form-input,
.form-select,
.form-textarea {
  @apply w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg;
  @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-white;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
  @apply transition-colors duration-200;
}

.form-input.error,
.form-select.error,
.form-textarea.error {
  @apply border-red-500 ring-red-500;
}

.form-textarea {
  resize: vertical;
}

.checkbox-label {
  @apply flex items-start gap-3 cursor-pointer;
}

.form-checkbox {
  @apply w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded;
  @apply focus:ring-2 focus:ring-blue-500;
  @apply bg-white dark:bg-gray-800;
  margin-top: 2px;
}

.checkbox-text {
  @apply text-sm text-gray-700 dark:text-gray-300;
}

.privacy-link {
  @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300;
  @apply underline transition-colors duration-200;
}

.error-message {
  @apply text-sm text-red-600 dark:text-red-400;
}

.form-actions {
  @apply pt-4;
}

.submit-button {
  @apply w-full flex items-center justify-center gap-2;
  @apply bg-blue-600 text-white font-medium py-3 px-6 rounded-lg;
  @apply hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
  @apply transition-all duration-200;
}

.loading-icon {
  @apply w-5 h-5 animate-spin;
}

.success-message {
  @apply mt-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg;
  @apply flex items-start gap-3;
}

.success-icon {
  @apply w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5;
}

.success-content {
  @apply flex-grow;
}

.success-title {
  @apply text-lg font-medium text-green-800 dark:text-green-400 mb-1;
}

.success-text {
  @apply text-sm text-green-700 dark:text-green-300;
}

.error-banner {
  @apply mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg;
  @apply flex items-start gap-3;
}

.error-icon {
  @apply w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5;
}

.error-content {
  @apply flex-grow;
}

.error-title {
  @apply text-lg font-medium text-red-800 dark:text-red-400 mb-1;
}

.error-text {
  @apply text-sm text-red-700 dark:text-red-300;
}

/* Responsive Design */
@media (max-width: 640px) {
  .contact-form-container {
    @apply p-4;
  }
  
  .form-title {
    @apply text-2xl;
  }
  
  .form-subtitle {
    @apply text-base;
  }
}

/* Focus visible for better accessibility */
.form-input:focus-visible,
.form-select:focus-visible,
.form-textarea:focus-visible,
.form-checkbox:focus-visible,
.submit-button:focus-visible {
  @apply outline-2 outline-offset-2 outline-blue-500;
}
</style>
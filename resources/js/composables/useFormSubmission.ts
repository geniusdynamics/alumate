import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import type { FormComponentConfig } from '@/types/components'

export function useFormSubmission(config: FormComponentConfig) {
  const isSubmitting = ref(false)
  const submissionError = ref<string | null>(null)
  const submissionSuccess = ref(false)

  const submitForm = async (formData: Record<string, any>) => {
    isSubmitting.value = true
    submissionError.value = null
    submissionSuccess.value = false

    try {
      // Prepare submission data
      const submissionData = {
        ...formData,
        _form_config: {
          title: config.title,
          fields: config.fields.map(field => ({
            name: field.name,
            label: field.label,
            type: field.type,
            required: field.required
          }))
        }
      }

      // Add CRM integration data if configured
      if (config.submission.crmIntegration?.enabled) {
        submissionData._crm_config = {
          provider: config.submission.crmIntegration.provider,
          mapping: config.submission.crmIntegration.mapping,
          leadScore: config.submission.crmIntegration.leadScore,
          tags: config.submission.crmIntegration.tags
        }
      }

      // Submit using Inertia
      await new Promise<void>((resolve, reject) => {
        router.post(config.submission.action, submissionData, {
          onSuccess: () => {
            submissionSuccess.value = true
            
            // Show success message if configured
            if (config.submission.successMessage) {
              showNotification(config.submission.successMessage, 'success')
            }
            
            // Redirect if configured
            if (config.submission.redirectUrl) {
              router.visit(config.submission.redirectUrl)
            }
            
            resolve()
          },
          onError: (errors) => {
            const errorMessage = config.submission.errorMessage || 
              'There was an error submitting the form. Please try again.'
            
            submissionError.value = errorMessage
            showNotification(errorMessage, 'error')
            
            reject(new Error(errorMessage))
          },
          onFinish: () => {
            isSubmitting.value = false
          }
        })
      })

      // Send notifications if configured
      if (config.submission.notifications?.enabled) {
        await sendNotifications(formData, config.submission.notifications)
      }

      // Track successful submission
      trackFormSubmission(formData, config)

    } catch (error) {
      isSubmitting.value = false
      
      if (error instanceof Error) {
        submissionError.value = error.message
      } else {
        submissionError.value = 'An unexpected error occurred'
      }
      
      throw error
    }
  }

  const sendNotifications = async (formData: Record<string, any>, notifications: any) => {
    try {
      await fetch('/api/forms/notifications', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
          formData,
          notifications
        })
      })
    } catch (error) {
      console.warn('Failed to send notifications:', error)
      // Don't fail the form submission if notifications fail
    }
  }

  const trackFormSubmission = (formData: Record<string, any>, config: FormComponentConfig) => {
    if (!config.trackingEnabled) return

    // Google Analytics tracking
    if (typeof window !== 'undefined' && (window as any).gtag) {
      (window as any).gtag('event', 'form_submit', {
        form_name: config.title || 'Form',
        form_fields: config.fields.length,
        form_layout: config.layout,
        submission_method: config.submission.method,
        ...(config.trackingEvents || []).reduce((acc, event) => {
          acc[event] = true
          return acc
        }, {} as Record<string, boolean>)
      })
    }

    // Custom tracking events
    const trackingEvent = new CustomEvent('form-submitted', {
      detail: {
        formData,
        config,
        timestamp: new Date().toISOString()
      }
    })
    window.dispatchEvent(trackingEvent)
  }

  const showNotification = (message: string, type: 'success' | 'error' | 'info' = 'info') => {
    // Create a simple notification
    const notification = document.createElement('div')
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 ${
      type === 'success' ? 'bg-green-500 text-white' :
      type === 'error' ? 'bg-red-500 text-white' :
      'bg-blue-500 text-white'
    }`
    notification.textContent = message
    notification.setAttribute('role', 'alert')
    notification.setAttribute('aria-live', 'polite')

    document.body.appendChild(notification)

    // Auto-remove after 5 seconds
    setTimeout(() => {
      notification.style.opacity = '0'
      notification.style.transform = 'translateX(100%)'
      
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification)
        }
      }, 300)
    }, 5000)

    // Allow manual dismissal
    notification.addEventListener('click', () => {
      notification.style.opacity = '0'
      notification.style.transform = 'translateX(100%)'
      
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification)
        }
      }, 300)
    })
  }

  const resetSubmission = () => {
    isSubmitting.value = false
    submissionError.value = null
    submissionSuccess.value = false
  }

  return {
    isSubmitting,
    submissionError,
    submissionSuccess,
    submitForm,
    resetSubmission
  }
}
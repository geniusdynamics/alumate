import { ref, computed, reactive, watch } from 'vue'
import type { FormComponentConfig, FormField } from '@/types/components'

export interface FormErrorState {
  hasErrors: boolean
  errorCount: number
  firstErrorField: string | null
  errorsByField: Record<string, string[]>
  errorSummary: string[]
  criticalErrors: string[]
  warningsByField: Record<string, string[]>
  hasWarnings: boolean
  warningCount: number
}

export interface ErrorDisplayOptions {
  showInline: boolean
  showSummary: boolean
  showTooltips: boolean
  showIcons: boolean
  groupByField: boolean
  maxDisplayedErrors: number
  autoScroll: boolean
  announceToScreenReader: boolean
}

export function useFormErrorState(config: FormComponentConfig) {
  const errors = ref<Record<string, string[]>>({})
  const warnings = ref<Record<string, string[]>>({})
  const fieldTouched = ref<Record<string, boolean>>({})
  const errorHistory = ref<Array<{ field: string; error: string; timestamp: Date }>>([])
  const displayOptions = reactive<ErrorDisplayOptions>({
    showInline: true,
    showSummary: true,
    showTooltips: false,
    showIcons: true,
    groupByField: true,
    maxDisplayedErrors: 5,
    autoScroll: true,
    announceToScreenReader: true
  })

  const errorState = computed<FormErrorState>(() => {
    const errorsByField = { ...errors.value }
    const warningsByField = { ...warnings.value }
    const allErrors = Object.values(errorsByField).flat()
    const allWarnings = Object.values(warningsByField).flat()
    
    return {
      hasErrors: allErrors.length > 0,
      errorCount: allErrors.length,
      firstErrorField: Object.keys(errorsByField)[0] || null,
      errorsByField,
      errorSummary: allErrors.slice(0, displayOptions.maxDisplayedErrors),
      criticalErrors: allErrors.filter(error => 
        error.toLowerCase().includes('required') || 
        error.toLowerCase().includes('invalid')
      ),
      warningsByField,
      hasWarnings: allWarnings.length > 0,
      warningCount: allWarnings.length
    }
  })

  const fieldErrorState = computed(() => {
    const state: Record<string, {
      hasError: boolean
      hasWarning: boolean
      isTouched: boolean
      errors: string[]
      warnings: string[]
      displayError: string | null
      displayWarning: string | null
      errorClass: string
      ariaDescribedBy: string
    }> = {}

    config.fields.forEach(field => {
      const fieldName = field.name
      const fieldErrors = errors.value[fieldName] || []
      const fieldWarnings = warnings.value[fieldName] || []
      const isTouched = fieldTouched.value[fieldName] || false

      state[fieldName] = {
        hasError: fieldErrors.length > 0,
        hasWarning: fieldWarnings.length > 0,
        isTouched,
        errors: fieldErrors,
        warnings: fieldWarnings,
        displayError: isTouched && fieldErrors.length > 0 ? fieldErrors[0] : null,
        displayWarning: isTouched && fieldWarnings.length > 0 ? fieldWarnings[0] : null,
        errorClass: getErrorClass(fieldErrors.length > 0, fieldWarnings.length > 0, isTouched),
        ariaDescribedBy: getAriaDescribedBy(fieldName, fieldErrors.length > 0, fieldWarnings.length > 0)
      }
    })

    return state
  })

  const addError = (fieldName: string, error: string | string[]) => {
    const errorArray = Array.isArray(error) ? error : [error]
    errors.value[fieldName] = [...(errors.value[fieldName] || []), ...errorArray]
    
    // Add to error history
    errorArray.forEach(err => {
      errorHistory.value.push({
        field: fieldName,
        error: err,
        timestamp: new Date()
      })
    })

    // Announce to screen reader if enabled
    if (displayOptions.announceToScreenReader) {
      announceError(fieldName, errorArray[0])
    }

    // Auto-scroll to first error if enabled
    if (displayOptions.autoScroll && !errorState.value.firstErrorField) {
      nextTick(() => scrollToError(fieldName))
    }
  }

  const addWarning = (fieldName: string, warning: string | string[]) => {
    const warningArray = Array.isArray(warning) ? warning : [warning]
    warnings.value[fieldName] = [...(warnings.value[fieldName] || []), ...warningArray]
  }

  const removeError = (fieldName: string, error?: string) => {
    if (!errors.value[fieldName]) return

    if (error) {
      errors.value[fieldName] = errors.value[fieldName].filter(e => e !== error)
      if (errors.value[fieldName].length === 0) {
        delete errors.value[fieldName]
      }
    } else {
      delete errors.value[fieldName]
    }
  }

  const removeWarning = (fieldName: string, warning?: string) => {
    if (!warnings.value[fieldName]) return

    if (warning) {
      warnings.value[fieldName] = warnings.value[fieldName].filter(w => w !== warning)
      if (warnings.value[fieldName].length === 0) {
        delete warnings.value[fieldName]
      }
    } else {
      delete warnings.value[fieldName]
    }
  }

  const clearFieldErrors = (fieldName: string) => {
    delete errors.value[fieldName]
  }

  const clearFieldWarnings = (fieldName: string) => {
    delete warnings.value[fieldName]
  }

  const clearAllErrors = () => {
    errors.value = {}
    errorHistory.value = []
  }

  const clearAllWarnings = () => {
    warnings.value = {}
  }

  const clearAll = () => {
    clearAllErrors()
    clearAllWarnings()
    fieldTouched.value = {}
  }

  const markFieldTouched = (fieldName: string) => {
    fieldTouched.value[fieldName] = true
  }

  const markAllFieldsTouched = () => {
    config.fields.forEach(field => {
      fieldTouched.value[field.name] = true
    })
  }

  const setFieldErrors = (fieldName: string, errors: string[]) => {
    if (errors.length > 0) {
      this.errors.value[fieldName] = errors
    } else {
      delete this.errors.value[fieldName]
    }
  }

  const setFieldWarnings = (fieldName: string, warnings: string[]) => {
    if (warnings.length > 0) {
      this.warnings.value[fieldName] = warnings
    } else {
      delete this.warnings.value[fieldName]
    }
  }

  const getErrorClass = (hasError: boolean, hasWarning: boolean, isTouched: boolean): string => {
    const classes: string[] = []
    
    if (isTouched) {
      if (hasError) {
        classes.push('field-error', 'border-red-500', 'focus:border-red-500', 'focus:ring-red-500')
      } else if (hasWarning) {
        classes.push('field-warning', 'border-yellow-500', 'focus:border-yellow-500', 'focus:ring-yellow-500')
      } else {
        classes.push('field-valid', 'border-green-500', 'focus:border-green-500', 'focus:ring-green-500')
      }
    }
    
    return classes.join(' ')
  }

  const getAriaDescribedBy = (fieldName: string, hasError: boolean, hasWarning: boolean): string => {
    const ids: string[] = []
    
    if (hasError) {
      ids.push(`${fieldName}-error`)
    }
    
    if (hasWarning) {
      ids.push(`${fieldName}-warning`)
    }
    
    return ids.join(' ')
  }

  const announceError = (fieldName: string, error: string) => {
    const field = config.fields.find(f => f.name === fieldName)
    const fieldLabel = field?.label || fieldName
    const message = `Error in ${fieldLabel}: ${error}`
    
    // Create a live region announcement
    const announcement = document.createElement('div')
    announcement.setAttribute('aria-live', 'assertive')
    announcement.setAttribute('aria-atomic', 'true')
    announcement.className = 'sr-only'
    announcement.textContent = message
    
    document.body.appendChild(announcement)
    
    // Remove after announcement
    setTimeout(() => {
      document.body.removeChild(announcement)
    }, 1000)
  }

  const scrollToError = (fieldName: string) => {
    nextTick(() => {
      const element = document.querySelector(`[name="${fieldName}"], #${fieldName}`)
      if (element) {
        element.scrollIntoView({ 
          behavior: 'smooth', 
          block: 'center' 
        })
        
        // Focus the element for keyboard users
        if (element instanceof HTMLElement) {
          element.focus()
        }
      }
    })
  }

  const scrollToFirstError = () => {
    if (errorState.value.firstErrorField) {
      scrollToError(errorState.value.firstErrorField)
    }
  }

  const getErrorSummaryForScreenReader = (): string => {
    const { errorCount, criticalErrors } = errorState.value
    
    if (errorCount === 0) {
      return 'No errors found'
    }
    
    if (errorCount === 1) {
      return `1 error found: ${criticalErrors[0] || 'Please check your input'}`
    }
    
    return `${errorCount} errors found. Please review and correct the highlighted fields.`
  }

  const exportErrorState = () => {
    return {
      errors: { ...errors.value },
      warnings: { ...warnings.value },
      fieldTouched: { ...fieldTouched.value },
      errorHistory: [...errorHistory.value],
      timestamp: new Date().toISOString()
    }
  }

  const importErrorState = (state: any) => {
    if (state.errors) errors.value = state.errors
    if (state.warnings) warnings.value = state.warnings
    if (state.fieldTouched) fieldTouched.value = state.fieldTouched
    if (state.errorHistory) errorHistory.value = state.errorHistory
  }

  const updateDisplayOptions = (options: Partial<ErrorDisplayOptions>) => {
    Object.assign(displayOptions, options)
  }

  // Watch for error changes to provide additional functionality
  watch(
    () => errorState.value.errorCount,
    (newCount, oldCount) => {
      if (newCount > oldCount && displayOptions.announceToScreenReader) {
        const message = getErrorSummaryForScreenReader()
        announceError('form', message)
      }
    }
  )

  return {
    // State
    errors: readonly(errors),
    warnings: readonly(warnings),
    fieldTouched: readonly(fieldTouched),
    errorHistory: readonly(errorHistory),
    errorState,
    fieldErrorState,
    displayOptions,

    // Methods
    addError,
    addWarning,
    removeError,
    removeWarning,
    clearFieldErrors,
    clearFieldWarnings,
    clearAllErrors,
    clearAllWarnings,
    clearAll,
    markFieldTouched,
    markAllFieldsTouched,
    setFieldErrors,
    setFieldWarnings,
    scrollToError,
    scrollToFirstError,
    getErrorSummaryForScreenReader,
    exportErrorState,
    importErrorState,
    updateDisplayOptions
  }
}
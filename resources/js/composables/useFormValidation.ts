import { ref, computed, reactive, watch, nextTick } from 'vue'
import type { FormComponentConfig, FormField, FormValidationConfig } from '@/types/components'

export function useFormValidation(config: FormComponentConfig, formData: Record<string, any>) {
  const fieldErrors = ref<Record<string, string>>({})
  const fieldWarnings = ref<Record<string, string>>({})
  const validationState = ref<Record<string, 'idle' | 'validating' | 'valid' | 'invalid'>>({})
  const touchedFields = ref<Set<string>>(new Set())
  const isRealTimeValidationEnabled = ref(true)
  const validationDebounceTimeout = ref<Record<string, NodeJS.Timeout>>({})
  
  const validationErrors = computed(() => {
    return Object.entries(fieldErrors.value).map(([field, message]) => ({
      field,
      message
    }))
  })

  const validationWarnings = computed(() => {
    return Object.entries(fieldWarnings.value).map(([field, message]) => ({
      field,
      message
    }))
  })

  const hasErrors = computed(() => {
    return Object.keys(fieldErrors.value).length > 0
  })

  const hasWarnings = computed(() => {
    return Object.keys(fieldWarnings.value).length > 0
  })

  const isFormValid = computed(() => {
    // Check if all required fields are filled and have no errors
    const requiredFields = config.fields.filter(field => field.required)
    
    for (const field of requiredFields) {
      const value = formData[field.name]
      
      // Check if required field is empty
      if (value === undefined || value === null || value === '' || 
          (Array.isArray(value) && value.length === 0) ||
          (typeof value === 'boolean' && !value && field.type === 'checkbox')) {
        return false
      }
    }
    
    // Check if there are any validation errors
    return !hasErrors.value
  })

  const formCompleteness = computed(() => {
    const totalFields = config.fields.length
    const completedFields = config.fields.filter(field => {
      const value = formData[field.name]
      return value !== undefined && value !== null && value !== '' && 
             (!Array.isArray(value) || value.length > 0)
    }).length
    
    return totalFields > 0 ? Math.round((completedFields / totalFields) * 100) : 0
  })

  const fieldValidationState = computed(() => {
    const state: Record<string, { isValid: boolean; isTouched: boolean; hasError: boolean; hasWarning: boolean; isValidating: boolean }> = {}
    
    config.fields.forEach(field => {
      const fieldName = field.name
      state[fieldName] = {
        isValid: !fieldErrors.value[fieldName] && touchedFields.value.has(fieldName),
        isTouched: touchedFields.value.has(fieldName),
        hasError: !!fieldErrors.value[fieldName],
        hasWarning: !!fieldWarnings.value[fieldName],
        isValidating: validationState.value[fieldName] === 'validating'
      }
    })
    
    return state
  })

  const validateField = async (field: FormField, value: any, options: { 
    showWarnings?: boolean; 
    debounce?: boolean; 
    immediate?: boolean 
  } = {}): Promise<boolean> => {
    const { showWarnings = true, debounce = true, immediate = false } = options
    const fieldName = field.name
    
    // Mark field as touched
    touchedFields.value.add(fieldName)
    
    // Clear existing timeout for this field
    if (validationDebounceTimeout.value[fieldName]) {
      clearTimeout(validationDebounceTimeout.value[fieldName])
    }
    
    // Set validation state
    validationState.value[fieldName] = 'validating'
    
    const performValidation = async () => {
      const errors: string[] = []
      const warnings: string[] = []
      
      // Required validation
      if (field.required) {
        if (value === undefined || value === null || value === '' ||
            (Array.isArray(value) && value.length === 0) ||
            (typeof value === 'boolean' && !value && field.type === 'checkbox')) {
          errors.push(`${field.label} is required`)
        }
      }
      
      // Skip other validations if field is empty and not required
      if (!field.required && (value === undefined || value === null || value === '')) {
        clearFieldError(field.name)
        clearFieldWarning(field.name)
        validationState.value[fieldName] = 'idle'
        return true
      }
      
      // Type-specific validations
      switch (field.type) {
        case 'email':
          if (value) {
            if (!isValidEmail(value)) {
              errors.push(`${field.label} must be a valid email address`)
            } else {
              // Check for common email issues as warnings
              if (showWarnings) {
                const emailWarnings = getEmailWarnings(value)
                warnings.push(...emailWarnings.map(w => `${field.label}: ${w}`))
              }
            }
          }
          break
          
        case 'phone':
          if (value) {
            const phoneValidation = validatePhoneNumber(value)
            if (!phoneValidation.isValid) {
              errors.push(`${field.label} ${phoneValidation.error}`)
            } else if (showWarnings && phoneValidation.warnings.length > 0) {
              warnings.push(...phoneValidation.warnings.map(w => `${field.label}: ${w}`))
            }
          }
          break
          
        case 'url':
          if (value && !isValidUrl(value)) {
            errors.push(`${field.label} must be a valid URL`)
          }
          break
          
        case 'number':
          if (value !== undefined && value !== '') {
            const numValue = Number(value)
            if (isNaN(numValue)) {
              errors.push(`${field.label} must be a valid number`)
            } else {
              if (field.min !== undefined && numValue < field.min) {
                errors.push(`${field.label} must be at least ${field.min}`)
              }
              if (field.max !== undefined && numValue > field.max) {
                errors.push(`${field.label} must be no more than ${field.max}`)
              }
              
              // Add warnings for numbers close to limits
              if (showWarnings) {
                if (field.max !== undefined && numValue > field.max * 0.9) {
                  warnings.push(`${field.label} is approaching the maximum value`)
                }
              }
            }
          }
          break
          
        case 'password':
          if (value) {
            const passwordStrength = checkPasswordStrength(value)
            if (passwordStrength.score < 2) {
              errors.push(`${field.label} is too weak`)
            } else if (showWarnings && passwordStrength.score < 4) {
              warnings.push(`${field.label}: ${passwordStrength.feedback}`)
            }
          }
          break
          
        case 'date':
          if (value) {
            const dateValidation = validateDate(value, field)
            if (!dateValidation.isValid) {
              errors.push(`${field.label} ${dateValidation.error}`)
            }
          }
          break
      }
      
      // Custom validation rules
      if (field.validation) {
        for (const rule of field.validation) {
          const ruleResult = await validateRule(rule, value, field.label)
          if (typeof ruleResult === 'string') {
            errors.push(ruleResult)
          } else if (ruleResult && typeof ruleResult === 'object') {
            if (ruleResult.error) {
              errors.push(ruleResult.error)
            }
            if (ruleResult.warning && showWarnings) {
              warnings.push(ruleResult.warning)
            }
          }
        }
      }
      
      // Pattern validation
      if (field.pattern && value) {
        try {
          const regex = new RegExp(field.pattern)
          if (!regex.test(value)) {
            errors.push(`${field.label} format is invalid`)
          }
        } catch (e) {
          console.warn(`Invalid regex pattern for field ${field.name}:`, field.pattern)
        }
      }
      
      // Accessibility validation
      if (field.accessibility?.required && value) {
        const accessibilityIssues = validateAccessibility(field, value)
        if (accessibilityIssues.length > 0 && showWarnings) {
          warnings.push(...accessibilityIssues.map(issue => `${field.label}: ${issue}`))
        }
      }
      
      // Set validation results
      if (errors.length > 0) {
        fieldErrors.value[fieldName] = errors[0] // Show first error
        validationState.value[fieldName] = 'invalid'
        clearFieldWarning(fieldName) // Clear warnings when there are errors
        return false
      } else {
        clearFieldError(fieldName)
        validationState.value[fieldName] = 'valid'
        
        if (warnings.length > 0 && showWarnings) {
          fieldWarnings.value[fieldName] = warnings[0] // Show first warning
        } else {
          clearFieldWarning(fieldName)
        }
        
        return true
      }
    }
    
    if (debounce && !immediate) {
      return new Promise((resolve) => {
        validationDebounceTimeout.value[fieldName] = setTimeout(async () => {
          const result = await performValidation()
          resolve(result)
        }, 300) // 300ms debounce
      })
    } else {
      return await performValidation()
    }
  }

  const validateRule = async (rule: FormValidationConfig, value: any, fieldLabel: string): Promise<string | { error?: string; warning?: string } | null> => {
    switch (rule.rule) {
      case 'required':
        if (value === undefined || value === null || value === '' ||
            (Array.isArray(value) && value.length === 0)) {
          return rule.message || `${fieldLabel} is required`
        }
        break
        
      case 'email':
        if (value && !isValidEmail(value)) {
          return rule.message || `${fieldLabel} must be a valid email address`
        }
        break
        
      case 'phone':
        if (value) {
          const phoneValidation = validatePhoneNumber(value)
          if (!phoneValidation.isValid) {
            return rule.message || `${fieldLabel} ${phoneValidation.error}`
          }
        }
        break
        
      case 'url':
        if (value && !isValidUrl(value)) {
          return rule.message || `${fieldLabel} must be a valid URL`
        }
        break
        
      case 'min':
        if (rule.value !== undefined && Number(value) < Number(rule.value)) {
          return rule.message || `${fieldLabel} must be at least ${rule.value}`
        }
        break
        
      case 'max':
        if (rule.value !== undefined && Number(value) > Number(rule.value)) {
          return rule.message || `${fieldLabel} must be no more than ${rule.value}`
        }
        break
        
      case 'minLength':
        if (rule.value !== undefined && String(value).length < Number(rule.value)) {
          return rule.message || `${fieldLabel} must be at least ${rule.value} characters`
        }
        break
        
      case 'maxLength':
        if (rule.value !== undefined && String(value).length > Number(rule.value)) {
          return rule.message || `${fieldLabel} must be no more than ${rule.value} characters`
        }
        break
        
      case 'pattern':
        if (rule.value && value) {
          try {
            const regex = new RegExp(rule.value)
            if (!regex.test(value)) {
              return rule.message || `${fieldLabel} format is invalid`
            }
          } catch (e) {
            console.warn(`Invalid regex pattern in validation rule:`, rule.value)
          }
        }
        break
        
      case 'unique':
        if (value && rule.endpoint) {
          try {
            const response = await fetch(rule.endpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({ value, field: rule.field || 'email' })
            })
            const result = await response.json()
            if (!result.available) {
              return rule.message || `${fieldLabel} is already taken`
            }
          } catch (e) {
            console.warn('Unique validation failed:', e)
            return { warning: 'Unable to verify uniqueness' }
          }
        }
        break
        
      case 'custom':
        if (rule.customValidator) {
          const result = await rule.customValidator(value)
          if (result === false) {
            return rule.message || `${fieldLabel} is invalid`
          } else if (typeof result === 'string') {
            return result
          } else if (typeof result === 'object' && result !== null) {
            return result
          }
        }
        break
        
      case 'spam_protection':
        if (value) {
          const spamScore = calculateSpamScore(value)
          if (spamScore > 0.8) {
            return rule.message || `${fieldLabel} appears to contain spam content`
          } else if (spamScore > 0.5) {
            return { warning: `${fieldLabel} may contain suspicious content` }
          }
        }
        break
        
      case 'profanity_filter':
        if (value && containsProfanity(value)) {
          return rule.message || `${fieldLabel} contains inappropriate language`
        }
        break
    }
    
    return null
  }

  const validateForm = async (options: { showWarnings?: boolean } = {}): Promise<boolean> => {
    const { showWarnings = true } = options
    let isValid = true
    
    // Clear previous errors and warnings
    fieldErrors.value = {}
    if (showWarnings) {
      fieldWarnings.value = {}
    }
    
    // Validate each field
    const validationPromises = config.fields.map(async (field) => {
      const fieldValue = formData[field.name]
      const fieldValid = await validateField(field, fieldValue, { 
        showWarnings, 
        debounce: false, 
        immediate: true 
      })
      
      if (!fieldValid) {
        isValid = false
      }
      
      return fieldValid
    })
    
    await Promise.all(validationPromises)
    
    return isValid
  }

  const validateFieldOnBlur = async (fieldName: string) => {
    const field = config.fields.find(f => f.name === fieldName)
    if (field) {
      const value = formData[fieldName]
      await validateField(field, value, { debounce: false, immediate: true })
    }
  }

  const validateFieldOnInput = async (fieldName: string) => {
    if (!isRealTimeValidationEnabled.value) return
    
    const field = config.fields.find(f => f.name === fieldName)
    if (field) {
      const value = formData[fieldName]
      await validateField(field, value, { debounce: true })
    }
  }

  const clearFieldError = (fieldName: string) => {
    delete fieldErrors.value[fieldName]
    if (validationState.value[fieldName] === 'invalid') {
      validationState.value[fieldName] = 'idle'
    }
  }

  const clearFieldWarning = (fieldName: string) => {
    delete fieldWarnings.value[fieldName]
  }

  const clearAllErrors = () => {
    fieldErrors.value = {}
    fieldWarnings.value = {}
    Object.keys(validationState.value).forEach(key => {
      validationState.value[key] = 'idle'
    })
  }

  const markFieldAsTouched = (fieldName: string) => {
    touchedFields.value.add(fieldName)
  }

  const resetValidation = () => {
    fieldErrors.value = {}
    fieldWarnings.value = {}
    validationState.value = {}
    touchedFields.value.clear()
    Object.values(validationDebounceTimeout.value).forEach(timeout => {
      clearTimeout(timeout)
    })
    validationDebounceTimeout.value = {}
  }

  const setRealTimeValidation = (enabled: boolean) => {
    isRealTimeValidationEnabled.value = enabled
  }

  // Enhanced validation helper functions
  const isValidEmail = (email: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }

  const getEmailWarnings = (email: string): string[] => {
    const warnings: string[] = []
    
    // Check for common typos in domains
    const commonDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com']
    const domain = email.split('@')[1]?.toLowerCase()
    
    if (domain) {
      // Check for similar domains (typos)
      const similarDomains = {
        'gmial.com': 'gmail.com',
        'gmai.com': 'gmail.com',
        'yahooo.com': 'yahoo.com',
        'hotmial.com': 'hotmail.com',
        'outlok.com': 'outlook.com'
      }
      
      if (similarDomains[domain]) {
        warnings.push(`Did you mean ${similarDomains[domain]}?`)
      }
      
      // Check for disposable email domains
      const disposableDomains = ['10minutemail.com', 'tempmail.org', 'guerrillamail.com']
      if (disposableDomains.includes(domain)) {
        warnings.push('Disposable email addresses may not receive important communications')
      }
    }
    
    return warnings
  }

  const validatePhoneNumber = (phone: string): { isValid: boolean; error?: string; warnings: string[] } => {
    const warnings: string[] = []
    const cleanPhone = phone.replace(/[\s\-\(\)\.]/g, '')
    
    // Basic validation
    if (cleanPhone.length < 7) {
      return { isValid: false, error: 'is too short', warnings: [] }
    }
    
    if (cleanPhone.length > 15) {
      return { isValid: false, error: 'is too long', warnings: [] }
    }
    
    // Check for valid patterns
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/
    if (!phoneRegex.test(cleanPhone)) {
      return { isValid: false, error: 'contains invalid characters', warnings: [] }
    }
    
    // Check for obviously fake numbers
    if (/^0+$|^1+$|^123456|^654321/.test(cleanPhone)) {
      return { isValid: false, error: 'appears to be invalid', warnings: [] }
    }
    
    // Warnings for potential issues
    if (!cleanPhone.startsWith('+') && cleanPhone.length > 10) {
      warnings.push('Consider adding country code for international numbers')
    }
    
    return { isValid: true, warnings }
  }

  const isValidUrl = (url: string): boolean => {
    try {
      new URL(url)
      return true
    } catch {
      return false
    }
  }

  const checkPasswordStrength = (password: string): { score: number; feedback: string } => {
    let score = 0
    const feedback: string[] = []
    
    if (password.length >= 8) score++
    else feedback.push('Use at least 8 characters')
    
    if (/[a-z]/.test(password)) score++
    else feedback.push('Include lowercase letters')
    
    if (/[A-Z]/.test(password)) score++
    else feedback.push('Include uppercase letters')
    
    if (/\d/.test(password)) score++
    else feedback.push('Include numbers')
    
    if (/[^a-zA-Z\d]/.test(password)) score++
    else feedback.push('Include special characters')
    
    return {
      score,
      feedback: feedback.length > 0 ? feedback.join(', ') : 'Strong password'
    }
  }

  const validateDate = (dateValue: string, field: FormField): { isValid: boolean; error?: string } => {
    const date = new Date(dateValue)
    
    if (isNaN(date.getTime())) {
      return { isValid: false, error: 'is not a valid date' }
    }
    
    const now = new Date()
    
    if (field.minDate) {
      const minDate = new Date(field.minDate)
      if (date < minDate) {
        return { isValid: false, error: `must be after ${field.minDate}` }
      }
    }
    
    if (field.maxDate) {
      const maxDate = new Date(field.maxDate)
      if (date > maxDate) {
        return { isValid: false, error: `must be before ${field.maxDate}` }
      }
    }
    
    return { isValid: true }
  }

  const validateAccessibility = (field: FormField, value: string): string[] => {
    const issues: string[] = []
    
    // Check for screen reader friendly content
    if (field.type === 'text' && value.length > 0) {
      // Check for all caps (hard to read with screen readers)
      if (value === value.toUpperCase() && value.length > 10) {
        issues.push('Avoid using all capital letters for better accessibility')
      }
      
      // Check for special characters that might be confusing
      if (/[^\w\s\-.,!?]/.test(value)) {
        issues.push('Special characters may be difficult for screen readers')
      }
    }
    
    return issues
  }

  const calculateSpamScore = (text: string): number => {
    let score = 0
    const lowerText = text.toLowerCase()
    
    // Common spam indicators
    const spamKeywords = ['free', 'urgent', 'act now', 'limited time', 'click here', 'guaranteed']
    const keywordMatches = spamKeywords.filter(keyword => lowerText.includes(keyword)).length
    score += keywordMatches * 0.2
    
    // Excessive punctuation
    const punctuationCount = (text.match(/[!?]{2,}/g) || []).length
    score += punctuationCount * 0.1
    
    // All caps words
    const capsWords = (text.match(/\b[A-Z]{3,}\b/g) || []).length
    score += capsWords * 0.1
    
    // Excessive links
    const linkCount = (text.match(/https?:\/\/\S+/g) || []).length
    if (linkCount > 2) score += 0.3
    
    return Math.min(score, 1)
  }

  const containsProfanity = (text: string): boolean => {
    // Basic profanity filter - in production, use a more comprehensive solution
    const profanityWords = ['spam', 'scam', 'fake'] // Add more as needed
    const lowerText = text.toLowerCase()
    return profanityWords.some(word => lowerText.includes(word))
  }

  // Watch for form data changes to trigger real-time validation
  if (isRealTimeValidationEnabled.value) {
    Object.keys(formData).forEach(fieldName => {
      watch(() => formData[fieldName], () => {
        validateFieldOnInput(fieldName)
      })
    })
  }

  return {
    // State
    fieldErrors,
    fieldWarnings,
    validationErrors,
    validationWarnings,
    hasErrors,
    hasWarnings,
    isFormValid,
    formCompleteness,
    fieldValidationState,
    touchedFields,
    
    // Methods
    validateField,
    validateForm,
    validateFieldOnBlur,
    validateFieldOnInput,
    clearFieldError,
    clearFieldWarning,
    clearAllErrors,
    markFieldAsTouched,
    resetValidation,
    setRealTimeValidation,
    
    // Utilities
    isValidEmail,
    validatePhoneNumber,
    isValidUrl,
    checkPasswordStrength
  }
}
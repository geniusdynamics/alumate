import { ref, computed } from 'vue'
import { useAnalytics } from './useAnalytics'

interface ABTestVariant {
  id: string
  name: string
  weight: number
  config: any
  conversionRate?: number
  impressions?: number
  conversions?: number
}

interface ABTestConfig {
  enabled: boolean
  testId?: string
  variants?: ABTestVariant[]
  trafficSplit?: Record<string, number>
}

interface ABTestResult {
  testId: string
  variantId: string
  variant: ABTestVariant
  isControl: boolean
}

const activeTests = ref<Map<string, ABTestResult>>(new Map())
const testResults = ref<Map<string, any>>(new Map())

export function useABTesting() {
  const { trackEvent } = useAnalytics()

  /**
   * Get or assign a variant for an A/B test
   */
  const getVariant = (testConfig: ABTestConfig, userId?: string): ABTestResult | null => {
    if (!testConfig.enabled || !testConfig.testId || !testConfig.variants?.length) {
      return null
    }

    const testId = testConfig.testId
    
    // Check if we already have a variant assigned for this test
    if (activeTests.value.has(testId)) {
      return activeTests.value.get(testId)!
    }

    // Assign a new variant
    const variant = assignVariant(testConfig, userId)
    const result: ABTestResult = {
      testId,
      variantId: variant.id,
      variant,
      isControl: variant.id === 'control' || variant.id === testConfig.variants[0]?.id
    }

    // Store the assignment
    activeTests.value.set(testId, result)
    
    // Store in localStorage for persistence
    try {
      const stored = JSON.parse(localStorage.getItem('ab_tests') || '{}')
      stored[testId] = {
        variantId: variant.id,
        assignedAt: Date.now()
      }
      localStorage.setItem('ab_tests', JSON.stringify(stored))
    } catch (error) {
      console.warn('Failed to store A/B test assignment:', error)
    }

    // Track the assignment
    trackEvent('ab_test_assigned', {
      test_id: testId,
      variant_id: variant.id,
      variant_name: variant.name,
      user_id: userId
    })

    return result
  }

  /**
   * Assign a variant based on weights and user ID
   */
  const assignVariant = (testConfig: ABTestConfig, userId?: string): ABTestVariant => {
    const variants = testConfig.variants!
    
    // Use deterministic assignment if userId is provided
    if (userId) {
      const hash = hashString(`${testConfig.testId}-${userId}`)
      const totalWeight = variants.reduce((sum, v) => sum + v.weight, 0)
      const threshold = (hash % 100) / 100 * totalWeight
      
      let currentWeight = 0
      for (const variant of variants) {
        currentWeight += variant.weight
        if (threshold <= currentWeight) {
          return variant
        }
      }
    }

    // Random assignment
    const totalWeight = variants.reduce((sum, v) => sum + v.weight, 0)
    const random = Math.random() * totalWeight
    
    let currentWeight = 0
    for (const variant of variants) {
      currentWeight += variant.weight
      if (random <= currentWeight) {
        return variant
      }
    }

    // Fallback to first variant
    return variants[0]
  }

  /**
   * Track an impression for an A/B test
   */
  const trackImpression = (testId: string, variantId: string, context?: any) => {
    trackEvent('ab_test_impression', {
      test_id: testId,
      variant_id: variantId,
      ...context
    })

    // Update impression count
    const result = activeTests.value.get(testId)
    if (result) {
      result.variant.impressions = (result.variant.impressions || 0) + 1
    }
  }

  /**
   * Track a conversion for an A/B test
   */
  const trackConversion = (testId: string, variantId: string, conversionData?: any) => {
    trackEvent('ab_test_conversion', {
      test_id: testId,
      variant_id: variantId,
      ...conversionData
    })

    // Update conversion count
    const result = activeTests.value.get(testId)
    if (result) {
      result.variant.conversions = (result.variant.conversions || 0) + 1
      
      // Calculate conversion rate
      if (result.variant.impressions && result.variant.impressions > 0) {
        result.variant.conversionRate = result.variant.conversions / result.variant.impressions
      }
    }
  }

  /**
   * Get test results and statistics
   */
  const getTestResults = (testId: string) => {
    return testResults.value.get(testId)
  }

  /**
   * Check if a user is in a specific variant
   */
  const isInVariant = (testId: string, variantId: string): boolean => {
    const result = activeTests.value.get(testId)
    return result?.variantId === variantId
  }

  /**
   * Load stored A/B test assignments from localStorage
   */
  const loadStoredAssignments = () => {
    try {
      const stored = JSON.parse(localStorage.getItem('ab_tests') || '{}')
      const now = Date.now()
      const maxAge = 30 * 24 * 60 * 60 * 1000 // 30 days

      Object.entries(stored).forEach(([testId, data]: [string, any]) => {
        // Check if assignment is not too old
        if (now - data.assignedAt < maxAge) {
          // We would need the test config to fully restore, so this is a simplified version
          // In a real implementation, you might store more data or fetch test configs
        }
      })
    } catch (error) {
      console.warn('Failed to load stored A/B test assignments:', error)
    }
  }

  /**
   * Clear all A/B test assignments
   */
  const clearAssignments = () => {
    activeTests.value.clear()
    try {
      localStorage.removeItem('ab_tests')
    } catch (error) {
      console.warn('Failed to clear A/B test assignments:', error)
    }
  }

  /**
   * Get all active test assignments
   */
  const getActiveTests = computed(() => {
    return Array.from(activeTests.value.values())
  })

  return {
    getVariant,
    trackImpression,
    trackConversion,
    getTestResults,
    isInVariant,
    loadStoredAssignments,
    clearAssignments,
    activeTests: getActiveTests
  }
}

/**
 * Simple hash function for deterministic variant assignment
 */
function hashString(str: string): number {
  let hash = 0
  for (let i = 0; i < str.length; i++) {
    const char = str.charCodeAt(i)
    hash = ((hash << 5) - hash) + char
    hash = hash & hash // Convert to 32-bit integer
  }
  return Math.abs(hash)
}

/**
 * Utility function to create A/B test configuration
 */
export function createABTest(
  testId: string,
  variants: Omit<ABTestVariant, 'impressions' | 'conversions' | 'conversionRate'>[]
): ABTestConfig {
  return {
    enabled: true,
    testId,
    variants: variants.map(v => ({
      ...v,
      impressions: 0,
      conversions: 0,
      conversionRate: 0
    }))
  }
}

/**
 * Utility function to create equal-weight variants
 */
export function createEqualWeightVariants(
  variantConfigs: Array<{ id: string; name: string; config: any }>
): ABTestVariant[] {
  const weight = 100 / variantConfigs.length
  
  return variantConfigs.map(variant => ({
    ...variant,
    weight,
    impressions: 0,
    conversions: 0,
    conversionRate: 0
  }))
}
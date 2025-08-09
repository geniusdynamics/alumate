import type { 
  ABTest, 
  ABVariant, 
  ComponentOverride, 
  ConversionGoal, 
  AudienceType,
  ABTestResult,
  ABTestStatistics
} from '@/types/homepage'

export class ABTestingService {
  private activeTests: Map<string, ABTest> = new Map()
  private userAssignments: Map<string, Map<string, string>> = new Map() // userId -> testId -> variantId
  private sessionAssignments: Map<string, string> = new Map() // testId -> variantId
  private userId?: string
  private sessionId: string
  private audience: AudienceType

  constructor(userId: string | undefined, sessionId: string, audience: AudienceType) {
    this.userId = userId
    this.sessionId = sessionId
    this.audience = audience
    this.loadActiveTests()
    this.loadUserAssignments()
  }

  private async loadActiveTests(): Promise<void> {
    try {
      const response = await fetch('/api/ab-tests/active', {
        headers: {
          'X-Audience': this.audience
        }
      })
      
      if (response.ok) {
        const tests: ABTest[] = await response.json()
        tests.forEach(test => {
          if (this.isTestApplicable(test)) {
            this.activeTests.set(test.id, test)
          }
        })
      }
    } catch (error) {
      console.error('Failed to load active A/B tests:', error)
    }
  }

  private loadUserAssignments(): void {
    if (this.userId) {
      const stored = localStorage.getItem(`ab_assignments_${this.userId}`)
      if (stored) {
        try {
          const assignments = JSON.parse(stored)
          this.userAssignments.set(this.userId, new Map(Object.entries(assignments)))
        } catch (error) {
          console.error('Failed to parse stored A/B test assignments:', error)
        }
      }
    }

    // Load session assignments
    const sessionStored = sessionStorage.getItem(`ab_session_assignments_${this.sessionId}`)
    if (sessionStored) {
      try {
        const assignments = JSON.parse(sessionStored)
        this.sessionAssignments = new Map(Object.entries(assignments))
      } catch (error) {
        console.error('Failed to parse session A/B test assignments:', error)
      }
    }
  }

  private saveUserAssignments(): void {
    if (this.userId && this.userAssignments.has(this.userId)) {
      const assignments = Object.fromEntries(this.userAssignments.get(this.userId)!)
      localStorage.setItem(`ab_assignments_${this.userId}`, JSON.stringify(assignments))
    }

    // Save session assignments
    const sessionAssignments = Object.fromEntries(this.sessionAssignments)
    sessionStorage.setItem(`ab_session_assignments_${this.sessionId}`, JSON.stringify(sessionAssignments))
  }

  private isTestApplicable(test: ABTest): boolean {
    // Check if test is for current audience
    if (test.audience !== this.audience && test.audience !== 'both') {
      return false
    }

    // Check if test is currently running
    const now = new Date()
    if (test.startDate > now || (test.endDate && test.endDate < now)) {
      return false
    }

    // Check test status
    if (test.status !== 'running') {
      return false
    }

    return true
  }

  private hashUserId(userId: string, testId: string): number {
    // Simple hash function for consistent variant assignment
    let hash = 0
    const str = `${userId}_${testId}`
    
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i)
      hash = ((hash << 5) - hash) + char
      hash = hash & hash // Convert to 32-bit integer
    }
    
    return Math.abs(hash)
  }

  private assignVariant(test: ABTest): ABVariant {
    const identifier = this.userId || this.sessionId
    const hash = this.hashUserId(identifier, test.id)
    
    // Calculate cumulative weights
    let totalWeight = 0
    const cumulativeWeights: Array<{ variant: ABVariant; weight: number }> = []
    
    test.variants.forEach(variant => {
      totalWeight += variant.weight
      cumulativeWeights.push({ variant, weight: totalWeight })
    })

    // Normalize hash to 0-100 range based on traffic allocation
    const normalizedHash = (hash % 100) + 1
    
    // Check if user should be included in test based on traffic allocation
    if (normalizedHash > test.trafficAllocation) {
      // Return control variant (first variant) for users not in test
      return test.variants[0]
    }

    // Assign variant based on weights
    const targetWeight = (hash % totalWeight) + 1
    
    for (const { variant, weight } of cumulativeWeights) {
      if (targetWeight <= weight) {
        return variant
      }
    }

    // Fallback to first variant
    return test.variants[0]
  }

  // Public Methods

  public getVariant(testId: string): ABVariant | null {
    const test = this.activeTests.get(testId)
    if (!test) return null

    // Check existing assignment
    let assignedVariantId: string | undefined

    if (this.userId && this.userAssignments.has(this.userId)) {
      assignedVariantId = this.userAssignments.get(this.userId)!.get(testId)
    }

    if (!assignedVariantId) {
      assignedVariantId = this.sessionAssignments.get(testId)
    }

    if (assignedVariantId) {
      const variant = test.variants.find(v => v.id === assignedVariantId)
      if (variant) return variant
    }

    // Assign new variant
    const variant = this.assignVariant(test)
    
    // Store assignment
    if (this.userId) {
      if (!this.userAssignments.has(this.userId)) {
        this.userAssignments.set(this.userId, new Map())
      }
      this.userAssignments.get(this.userId)!.set(testId, variant.id)
    }
    
    this.sessionAssignments.set(testId, variant.id)
    this.saveUserAssignments()

    // Track assignment
    this.trackAssignment(testId, variant.id)

    return variant
  }

  public getComponentOverrides(testId: string): ComponentOverride[] {
    const variant = this.getVariant(testId)
    return variant?.componentOverrides || []
  }

  public isInTest(testId: string): boolean {
    return this.getVariant(testId) !== null
  }

  public isInVariant(testId: string, variantId: string): boolean {
    const variant = this.getVariant(testId)
    return variant?.id === variantId
  }

  public trackConversion(testId: string, goalId: string, value?: number): void {
    const variant = this.getVariant(testId)
    if (!variant) return

    const test = this.activeTests.get(testId)
    if (!test) return

    const goal = test.conversionGoals.find(g => g.id === goalId)
    if (!goal) return

    // Send conversion event
    this.sendConversionEvent(testId, variant.id, goalId, value || goal.value)
  }

  public getAllActiveTests(): ABTest[] {
    return Array.from(this.activeTests.values())
  }

  public getTestAssignments(): Record<string, string> {
    const assignments: Record<string, string> = {}
    
    this.activeTests.forEach((test, testId) => {
      const variant = this.getVariant(testId)
      if (variant) {
        assignments[testId] = variant.id
      }
    })

    return assignments
  }

  public async getTestResults(testId: string): Promise<ABTestResult | null> {
    try {
      const response = await fetch(`/api/ab-tests/${testId}/results`)
      if (response.ok) {
        return await response.json()
      }
    } catch (error) {
      console.error(`Failed to get results for test ${testId}:`, error)
    }
    return null
  }

  public async getTestStatistics(testId: string): Promise<ABTestStatistics | null> {
    try {
      const response = await fetch(`/api/ab-tests/${testId}/statistics`)
      if (response.ok) {
        return await response.json()
      }
    } catch (error) {
      console.error(`Failed to get statistics for test ${testId}:`, error)
    }
    return null
  }

  // Test Management Methods (for admin use)

  public async createTest(test: Omit<ABTest, 'id'>): Promise<string | null> {
    try {
      const response = await fetch('/api/ab-tests', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(test)
      })

      if (response.ok) {
        const result = await response.json()
        return result.id
      }
    } catch (error) {
      console.error('Failed to create A/B test:', error)
    }
    return null
  }

  public async updateTest(testId: string, updates: Partial<ABTest>): Promise<boolean> {
    try {
      const response = await fetch(`/api/ab-tests/${testId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(updates)
      })

      return response.ok
    } catch (error) {
      console.error(`Failed to update test ${testId}:`, error)
      return false
    }
  }

  public async startTest(testId: string): Promise<boolean> {
    return this.updateTest(testId, { status: 'running', startDate: new Date() })
  }

  public async pauseTest(testId: string): Promise<boolean> {
    return this.updateTest(testId, { status: 'paused' })
  }

  public async endTest(testId: string): Promise<boolean> {
    return this.updateTest(testId, { status: 'completed', endDate: new Date() })
  }

  // Utility Methods

  public calculateStatisticalSignificance(
    controlConversions: number,
    controlSamples: number,
    variantConversions: number,
    variantSamples: number
  ): { significant: boolean; pValue: number; confidenceLevel: number } {
    // Simplified statistical significance calculation
    // In production, you'd want to use a proper statistical library
    
    const controlRate = controlConversions / controlSamples
    const variantRate = variantConversions / variantSamples
    
    const pooledRate = (controlConversions + variantConversions) / (controlSamples + variantSamples)
    const standardError = Math.sqrt(pooledRate * (1 - pooledRate) * (1/controlSamples + 1/variantSamples))
    
    const zScore = Math.abs(controlRate - variantRate) / standardError
    
    // Approximate p-value calculation (simplified)
    const pValue = 2 * (1 - this.normalCDF(Math.abs(zScore)))
    
    return {
      significant: pValue < 0.05,
      pValue,
      confidenceLevel: (1 - pValue) * 100
    }
  }

  private normalCDF(x: number): number {
    // Approximation of the cumulative distribution function for standard normal distribution
    return 0.5 * (1 + this.erf(x / Math.sqrt(2)))
  }

  private erf(x: number): number {
    // Approximation of the error function
    const a1 =  0.254829592
    const a2 = -0.284496736
    const a3 =  1.421413741
    const a4 = -1.453152027
    const a5 =  1.061405429
    const p  =  0.3275911

    const sign = x >= 0 ? 1 : -1
    x = Math.abs(x)

    const t = 1.0 / (1.0 + p * x)
    const y = 1.0 - (((((a5 * t + a4) * t) + a3) * t + a2) * t + a1) * t * Math.exp(-x * x)

    return sign * y
  }

  // Private Helper Methods

  private trackAssignment(testId: string, variantId: string): void {
    // Send assignment tracking event
    fetch('/api/ab-tests/assignments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Session-ID': this.sessionId
      },
      body: JSON.stringify({
        testId,
        variantId,
        userId: this.userId,
        sessionId: this.sessionId,
        audience: this.audience,
        timestamp: new Date().toISOString()
      })
    }).catch(error => {
      console.error('Failed to track A/B test assignment:', error)
    })
  }

  private sendConversionEvent(testId: string, variantId: string, goalId: string, value: number): void {
    fetch('/api/ab-tests/conversions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Session-ID': this.sessionId
      },
      body: JSON.stringify({
        testId,
        variantId,
        goalId,
        value,
        userId: this.userId,
        sessionId: this.sessionId,
        audience: this.audience,
        timestamp: new Date().toISOString()
      })
    }).catch(error => {
      console.error('Failed to track A/B test conversion:', error)
    })
  }

  // Cleanup

  public destroy(): void {
    this.saveUserAssignments()
    this.activeTests.clear()
    this.userAssignments.clear()
    this.sessionAssignments.clear()
  }
}
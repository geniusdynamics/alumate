import axios, { AxiosInstance } from 'axios'
import { io, type Socket } from 'socket.io-client'
import type { AnalyticsIntegrationService } from './AnalyticsIntegrationService'

// Type definitions based on design document
export interface ExperimentConfig {
  name: string
  description?: string
  hypothesis: string
  goal: TestGoal
  variants: VariantConfig[]
  trafficAllocation: number[]
  duration: number // in days
  startDate?: Date
  endDate?: Date
  targeting?: TargetingRules
  status: ExperimentStatus
}

export interface Experiment {
  id: string
  config: ExperimentConfig
  status: ExperimentStatus
  variants: Variant[]
  results?: TestResults
  createdAt: Date
  updatedAt: Date
  createdBy: string
  tenantId?: string
}

export type ExperimentStatus = 'draft' | 'scheduled' | 'running' | 'paused' | 'completed' | 'archived'

export interface VariantConfig {
  id?: string
  name: string
  description?: string
  changes: VariantChange[]
  weight: number
}

export interface VariantChange {
  type: ChangeType
  targetId: string
  property: string
  value: any
}

export type ChangeType = 'content' | 'style' | 'component' | 'layout'

export interface Variant {
  id: string
  experimentId: string
  config: VariantConfig
  visitors: number
  conversions: number
  conversionRate: number
  createdAt: Date
  updatedAt: Date
}

export interface TestGoal {
  type: GoalType
  selector?: string
  eventName?: string
  customFunction?: string
  value?: number
}

export type GoalType = 'conversion' | 'engagement' | 'clicks' | 'custom'

export interface TargetingRules {
  audienceSegments?: string[]
  geolocation?: GeolocationRule[]
  deviceTypes?: string[]
  browsers?: string[]
  customRules?: CustomRule[]
}

export interface GeolocationRule {
  country?: string
  region?: string
  city?: string
  include: boolean
}

export interface CustomRule {
  condition: string
  value: any
}

export interface TestResults {
  experimentId: string
  startDate: Date
  endDate: Date
  totalVisitors: number
  variantResults: VariantResult[]
  winner?: string
  confidence: number
  statisticalSignificance: boolean
  updatedAt: Date
}

export interface VariantResult {
  variantId: string
  name: string
  visitors: number
  conversions: number
  conversionRate: number
  engagement: number
  revenue?: number
  statisticalSignificance: boolean
}

export interface RealTimeResults {
  experimentId: string
  currentVisitors: number
  currentConversions: number
  variantMetrics: RealTimeMetric[]
  lastUpdated: Date
}

export interface RealTimeMetric {
  variantId: string
  visitorsPerMinute: number
  conversionsPerMinute: number
  currentConversionRate: number
}

export interface StatisticalAnalysis {
  pValue: number
  confidenceInterval: ConfidenceInterval
  effectSize: number
  power: number
  significance: boolean
}

export interface ConfidenceInterval {
  lowerBound: number
  upperBound: number
  confidenceLevel: number
}

export interface ExperimentQueryOptions {
  status?: ExperimentStatus
  limit?: number
  offset?: number
  sortBy?: 'createdAt' | 'updatedAt' | 'name'
  sortOrder?: 'asc' | 'desc'
  startDate?: Date
  endDate?: Date
}

export interface TrafficAssignment {
  experimentId: string
  variantId: string
  userId: string
  sessionId: string
  timestamp: Date
  tenantId?: string
}

/**
 * A/B Testing Service for managing experiments, variants, and traffic allocation
 * Integrates with analytics service for performance tracking and statistical analysis
 */
export class ABTestingService {
  private http: AxiosInstance
  private socket: Socket | null = null
  private analyticsService: AnalyticsIntegrationService
  private experiments: Map<string, Experiment> = new Map()
  private trafficAssignments: Map<string, TrafficAssignment> = new Map()
  private cacheTimeout = 5 * 60 * 1000 // 5 minutes
  private tenantId?: string

  constructor(
    analyticsService: AnalyticsIntegrationService,
    tenantId?: string,
    baseURL: string = '/api/ab-testing'
  ) {
    this.analyticsService = analyticsService
    this.tenantId = tenantId

    this.http = axios.create({
      baseURL,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': this.getCsrfToken()
      }
    })

    // Add tenant header if available
    if (tenantId) {
      this.http.defaults.headers.common['X-Tenant-ID'] = tenantId
    }

    this.setupInterceptors()
  }

  /**
   * Initialize the service with socket connection for real-time updates
   */
  async initialize(socket?: Socket): Promise<void> {
    if (socket) {
      this.socket = socket
      this.setupSocketListeners()
    }

    // Load initial experiments
    await this.loadExperiments()
  }

  // Experiment Management Methods

  /**
   * Create a new A/B testing experiment
   */
  async createExperiment(config: ExperimentConfig): Promise<Experiment> {
    this.validateExperimentConfig(config)

    try {
      const response = await this.http.post('/experiments', {
        ...config,
        tenantId: this.tenantId
      })

      const experiment: Experiment = {
        ...response.data,
        createdAt: new Date(response.data.createdAt),
        updatedAt: new Date(response.data.updatedAt)
      }

      this.experiments.set(experiment.id, experiment)

      // Track experiment creation
      await this.analyticsService.trackEvent('experiment_created', {
        experimentId: experiment.id,
        experimentName: experiment.config.name,
        variantCount: experiment.variants.length,
        tenantId: this.tenantId
      })

      // Notify via socket
      if (this.socket) {
        this.socket.emit('experiment:created', experiment)
      }

      return experiment
    } catch (error) {
      console.error('Failed to create experiment:', error)
      throw new Error('Failed to create experiment')
    }
  }

  /**
   * Update an existing experiment
   */
  async updateExperiment(experimentId: string, config: Partial<ExperimentConfig>): Promise<Experiment> {
    const experiment = this.experiments.get(experimentId)
    if (!experiment) {
      throw new Error(`Experiment with ID ${experimentId} not found`)
    }

    try {
      const response = await this.http.put(`/experiments/${experimentId}`, config)

      const updatedExperiment: Experiment = {
        ...experiment,
        config: { ...experiment.config, ...config },
        updatedAt: new Date()
      }

      this.experiments.set(experimentId, updatedExperiment)

      // Notify via socket
      if (this.socket) {
        this.socket.emit('experiment:updated', updatedExperiment)
      }

      return updatedExperiment
    } catch (error) {
      console.error('Failed to update experiment:', error)
      throw new Error('Failed to update experiment')
    }
  }

  /**
   * Delete an experiment
   */
  async deleteExperiment(experimentId: string): Promise<void> {
    try {
      await this.http.delete(`/experiments/${experimentId}`)

      this.experiments.delete(experimentId)

      // Clean up traffic assignments
      this.cleanupTrafficAssignments(experimentId)

      // Notify via socket
      if (this.socket) {
        this.socket.emit('experiment:deleted', { experimentId })
      }
    } catch (error) {
      console.error('Failed to delete experiment:', error)
      throw new Error('Failed to delete experiment')
    }
  }

  /**
   * Get a specific experiment
   */
  async getExperiment(experimentId: string): Promise<Experiment> {
    // Check cache first
    const cached = this.experiments.get(experimentId)
    if (cached) {
      return cached
    }

    try {
      const response = await this.http.get(`/experiments/${experimentId}`)
      const experiment: Experiment = {
        ...response.data,
        createdAt: new Date(response.data.createdAt),
        updatedAt: new Date(response.data.updatedAt)
      }

      this.experiments.set(experimentId, experiment)
      return experiment
    } catch (error) {
      console.error('Failed to fetch experiment:', error)
      throw new Error('Failed to fetch experiment')
    }
  }

  /**
   * Get experiments with optional filtering
   */
  async getExperiments(options?: ExperimentQueryOptions): Promise<Experiment[]> {
    try {
      const params = new URLSearchParams()
      if (options?.status) params.append('status', options.status)
      if (options?.limit) params.append('limit', options.limit.toString())
      if (options?.offset) params.append('offset', options.offset.toString())
      if (options?.sortBy) params.append('sortBy', options.sortBy)
      if (options?.sortOrder) params.append('sortOrder', options.sortOrder)
      if (options?.startDate) params.append('startDate', options.startDate.toISOString())
      if (options?.endDate) params.append('endDate', options.endDate.toISOString())

      const response = await this.http.get(`/experiments?${params.toString()}`)
      const experiments: Experiment[] = response.data.map((exp: any) => ({
        ...exp,
        createdAt: new Date(exp.createdAt),
        updatedAt: new Date(exp.updatedAt)
      }))

      // Cache experiments
      experiments.forEach(exp => {
        this.experiments.set(exp.id, exp)
      })

      return experiments
    } catch (error) {
      console.error('Failed to fetch experiments:', error)
      throw new Error('Failed to fetch experiments')
    }
  }

  // Variant Management Methods

  /**
   * Create a new variant for an experiment
   */
  async createVariant(experimentId: string, config: VariantConfig): Promise<Variant> {
    try {
      const response = await this.http.post(`/experiments/${experimentId}/variants`, config)

      const variant: Variant = {
        ...response.data,
        createdAt: new Date(response.data.createdAt),
        updatedAt: new Date(response.data.updatedAt)
      }

      // Update experiment in cache
      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        experiment.variants.push(variant)
        experiment.updatedAt = new Date()
      }

      // Notify via socket
      if (this.socket) {
        this.socket.emit('variant:created', { experimentId, variant })
      }

      return variant
    } catch (error) {
      console.error('Failed to create variant:', error)
      throw new Error('Failed to create variant')
    }
  }

  /**
   * Update an existing variant
   */
  async updateVariant(variantId: string, config: VariantConfig): Promise<Variant> {
    try {
      const response = await this.http.put(`/variants/${variantId}`, config)

      const updatedVariant: Variant = {
        ...response.data,
        updatedAt: new Date()
      }

      // Update variant in experiment cache
      for (const experiment of this.experiments.values()) {
        const variantIndex = experiment.variants.findIndex(v => v.id === variantId)
        if (variantIndex !== -1) {
          experiment.variants[variantIndex] = updatedVariant
          experiment.updatedAt = new Date()
          break
        }
      }

      // Notify via socket
      if (this.socket) {
        this.socket.emit('variant:updated', { variantId, variant: updatedVariant })
      }

      return updatedVariant
    } catch (error) {
      console.error('Failed to update variant:', error)
      throw new Error('Failed to update variant')
    }
  }

  /**
   * Delete a variant
   */
  async deleteVariant(variantId: string): Promise<void> {
    try {
      await this.http.delete(`/variants/${variantId}`)

      // Remove variant from experiment cache
      for (const experiment of this.experiments.values()) {
        experiment.variants = experiment.variants.filter(v => v.id !== variantId)
        experiment.updatedAt = new Date()
      }

      // Notify via socket
      if (this.socket) {
        this.socket.emit('variant:deleted', { variantId })
      }
    } catch (error) {
      console.error('Failed to delete variant:', error)
      throw new Error('Failed to delete variant')
    }
  }

  /**
   * Get variants for an experiment
   */
  async getVariants(experimentId: string): Promise<Variant[]> {
    const experiment = this.experiments.get(experimentId)
    if (experiment) {
      return experiment.variants
    }

    try {
      const response = await this.http.get(`/experiments/${experimentId}/variants`)
      const variants: Variant[] = response.data.map((v: any) => ({
        ...v,
        createdAt: new Date(v.createdAt),
        updatedAt: new Date(v.updatedAt)
      }))

      return variants
    } catch (error) {
      console.error('Failed to fetch variants:', error)
      throw new Error('Failed to fetch variants')
    }
  }

  // Traffic Assignment Methods

  /**
   * Assign a user to a variant based on traffic allocation
   */
  assignVariant(experimentId: string, userId: string, sessionId: string): string | null {
    const experiment = this.experiments.get(experimentId)
    if (!experiment || experiment.status !== 'running') {
      return null
    }

    // Check if user is already assigned
    const existingAssignment = Array.from(this.trafficAssignments.values())
      .find(assignment =>
        assignment.experimentId === experimentId &&
        assignment.userId === userId
      )

    if (existingAssignment) {
      return existingAssignment.variantId
    }

    // Assign based on traffic allocation
    const variantId = this.selectVariantByTrafficAllocation(experiment)

    if (variantId) {
      const assignment: TrafficAssignment = {
        experimentId,
        variantId,
        userId,
        sessionId,
        timestamp: new Date(),
        tenantId: this.tenantId
      }

      this.trafficAssignments.set(`${experimentId}-${userId}`, assignment)

      // Track assignment
      this.analyticsService.trackEvent('variant_assigned', {
        experimentId,
        variantId,
        userId,
        tenantId: this.tenantId
      }).catch(console.error)
    }

    return variantId
  }

  /**
   * Get the assigned variant for a user
   */
  getAssignedVariant(experimentId: string, userId: string): string | null {
    const assignment = this.trafficAssignments.get(`${experimentId}-${userId}`)
    return assignment?.variantId || null
  }

  /**
   * Select variant based on traffic allocation using weighted random selection
   */
  private selectVariantByTrafficAllocation(experiment: Experiment): string | null {
    const { variants, config } = experiment
    const { trafficAllocation } = config

    if (variants.length === 0 || trafficAllocation.length !== variants.length) {
      return null
    }

    const random = Math.random() * 100
    let cumulativeWeight = 0

    for (let i = 0; i < variants.length; i++) {
      cumulativeWeight += trafficAllocation[i]
      if (random <= cumulativeWeight) {
        return variants[i].id
      }
    }

    return null
  }

  // Analytics Integration Methods

  /**
   * Track a conversion event for a variant
   */
  async trackConversion(experimentId: string, variantId: string, userId: string, conversionType: string): Promise<void> {
    try {
      // Update local variant metrics
      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        const variant = experiment.variants.find(v => v.id === variantId)
        if (variant) {
          variant.conversions++
          variant.conversionRate = variant.visitors > 0 ? variant.conversions / variant.visitors : 0
          experiment.updatedAt = new Date()
        }
      }

      // Track via analytics service
      await this.analyticsService.trackEvent('conversion', {
        experimentId,
        variantId,
        userId,
        conversionType,
        tenantId: this.tenantId
      })

      // Send to backend
      await this.http.post('/conversions', {
        experimentId,
        variantId,
        userId,
        conversionType,
        tenantId: this.tenantId
      })

      // Notify via socket
      if (this.socket) {
        this.socket.emit('conversion:tracked', {
          experimentId,
          variantId,
          userId,
          conversionType
        })
      }
    } catch (error) {
      console.error('Failed to track conversion:', error)
      throw new Error('Failed to track conversion')
    }
  }

  /**
   * Track a visitor for a variant
   */
  async trackVisitor(experimentId: string, variantId: string, userId: string): Promise<void> {
    try {
      // Update local variant metrics
      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        const variant = experiment.variants.find(v => v.id === variantId)
        if (variant) {
          variant.visitors++
          variant.conversionRate = variant.conversions > 0 ? variant.conversions / variant.visitors : 0
          experiment.updatedAt = new Date()
        }
      }

      // Track via analytics service
      await this.analyticsService.trackPageView(window.location.href, {
        experimentId,
        variantId,
        userId,
        tenantId: this.tenantId
      })

      // Send to backend
      await this.http.post('/visitors', {
        experimentId,
        variantId,
        userId,
        tenantId: this.tenantId
      })
    } catch (error) {
      console.error('Failed to track visitor:', error)
      throw new Error('Failed to track visitor')
    }
  }

  /**
   * Get test results for an experiment
   */
  async getTestResults(experimentId: string): Promise<TestResults> {
    try {
      const response = await this.http.get(`/experiments/${experimentId}/results`)
      const results: TestResults = {
        ...response.data,
        startDate: new Date(response.data.startDate),
        endDate: new Date(response.data.endDate),
        updatedAt: new Date(response.data.updatedAt)
      }

      // Update experiment with results
      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        experiment.results = results
      }

      return results
    } catch (error) {
      console.error('Failed to fetch test results:', error)
      throw new Error('Failed to fetch test results')
    }
  }

  /**
   * Get real-time results for an experiment
   */
  async getRealTimeResults(experimentId: string): Promise<RealTimeResults> {
    try {
      const response = await this.http.get(`/experiments/${experimentId}/real-time-results`)
      const results: RealTimeResults = {
        ...response.data,
        lastUpdated: new Date(response.data.lastUpdated)
      }

      return results
    } catch (error) {
      console.error('Failed to fetch real-time results:', error)
      throw new Error('Failed to fetch real-time results')
    }
  }

  /**
   * Calculate statistical significance for test results
   */
  async calculateStatisticalSignificance(results: TestResults): Promise<StatisticalAnalysis> {
    try {
      const analysis = this.performStatisticalAnalysis(results)

      // Cache analysis result
      if (results.experimentId) {
        const experiment = this.experiments.get(results.experimentId)
        if (experiment) {
          experiment.results = {
            ...results,
            statisticalSignificance: analysis.significance
          }
        }
      }

      return analysis
    } catch (error) {
      console.error('Failed to calculate statistical significance:', error)
      throw new Error('Failed to calculate statistical significance')
    }
  }

  /**
   * Perform statistical analysis on test results
   */
  private performStatisticalAnalysis(results: TestResults): StatisticalAnalysis {
    if (results.variantResults.length < 2) {
      return {
        pValue: 1,
        confidenceInterval: { lowerBound: 0, upperBound: 0, confidenceLevel: 0.95 },
        effectSize: 0,
        power: 0,
        significance: false
      }
    }

    const controlVariant = results.variantResults[0]
    const treatmentVariant = results.variantResults[1]

    // Calculate p-value using chi-square approximation
    const pValue = this.calculatePValue(controlVariant, treatmentVariant)

    // Calculate confidence interval
    const confidenceInterval = this.calculateConfidenceInterval(controlVariant, treatmentVariant)

    // Calculate effect size (Cohen's h)
    const effectSize = this.calculateEffectSize(controlVariant, treatmentVariant)

    // Calculate statistical power
    const power = this.calculatePower(controlVariant, treatmentVariant)

    const significance = pValue < 0.05

    return {
      pValue,
      confidenceInterval,
      effectSize,
      power,
      significance
    }
  }

  /**
   * Calculate p-value using chi-square test approximation
   */
  private calculatePValue(control: VariantResult, treatment: VariantResult): number {
    const n1 = control.visitors
    const n2 = treatment.visitors
    const p1 = control.conversionRate
    const p2 = treatment.conversionRate

    if (n1 === 0 || n2 === 0) return 1

    const pooledP = (control.conversions + treatment.conversions) / (n1 + n2)
    const se = Math.sqrt(pooledP * (1 - pooledP) * (1/n1 + 1/n2))
    const z = Math.abs(p1 - p2) / se

    // Approximate p-value from z-score
    return 2 * (1 - this.normalCDF(z))
  }

  /**
   * Calculate confidence interval for conversion rate difference
   */
  private calculateConfidenceInterval(control: VariantResult, treatment: VariantResult, confidenceLevel: number = 0.95): ConfidenceInterval {
    const n1 = control.visitors
    const n2 = treatment.visitors
    const p1 = control.conversionRate
    const p2 = treatment.conversionRate

    if (n1 === 0 || n2 === 0) {
      return { lowerBound: 0, upperBound: 0, confidenceLevel }
    }

    const diff = p2 - p1
    const se = Math.sqrt((p1 * (1 - p1) / n1) + (p2 * (1 - p2) / n2))

    const z = 1.96 // For 95% confidence level
    const marginOfError = z * se

    return {
      lowerBound: diff - marginOfError,
      upperBound: diff + marginOfError,
      confidenceLevel
    }
  }

  /**
   * Calculate effect size using Cohen's h
   */
  private calculateEffectSize(control: VariantResult, treatment: VariantResult): number {
    const p1 = control.conversionRate
    const p2 = treatment.conversionRate

    return 2 * (Math.asin(Math.sqrt(p2)) - Math.asin(Math.sqrt(p1)))
  }

  /**
   * Calculate statistical power
   */
  private calculatePower(control: VariantResult, treatment: VariantResult): number {
    const n1 = control.visitors
    const n2 = treatment.visitors
    const p1 = control.conversionRate
    const p2 = treatment.conversionRate

    if (n1 === 0 || n2 === 0) return 0

    const effectSize = Math.abs(p1 - p2)
    const pooledP = (p1 + p2) / 2
    const se = Math.sqrt(pooledP * (1 - pooledP) * (1/n1 + 1/n2))

    if (se === 0) return 0

    const z = effectSize / se
    return this.normalCDF(z - 1.96) // Power for 95% significance level
  }

  /**
   * Standard normal cumulative distribution function approximation
   */
  private normalCDF(x: number): number {
    const a1 = 0.254829592
    const a2 = -0.284496736
    const a3 = 1.421413741
    const a4 = -1.453152027
    const a5 = 1.061405429
    const p = 0.3275911

    const sign = x < 0 ? -1 : 1
    x = Math.abs(x) / Math.sqrt(2.0)

    const t = 1.0 / (1.0 + p * x)
    const y = 1.0 - (((((a5 * t + a4) * t) + a3) * t + a2) * t + a1) * t * Math.exp(-x * x)

    return 0.5 * (1.0 + sign * y)
  }

  // Experiment Lifecycle Methods

  /**
   * Start an experiment
   */
  async startExperiment(experimentId: string): Promise<void> {
    try {
      await this.http.post(`/experiments/${experimentId}/start`)

      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        experiment.status = 'running'
        experiment.config.startDate = new Date()
        experiment.updatedAt = new Date()
      }

      // Notify via socket
      if (this.socket) {
        this.socket.emit('experiment:started', { experimentId })
      }
    } catch (error) {
      console.error('Failed to start experiment:', error)
      throw new Error('Failed to start experiment')
    }
  }

  /**
   * Pause an experiment
   */
  async pauseExperiment(experimentId: string): Promise<void> {
    try {
      await this.http.post(`/experiments/${experimentId}/pause`)

      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        experiment.status = 'paused'
        experiment.updatedAt = new Date()
      }

      // Notify via socket
      if (this.socket) {
        this.socket.emit('experiment:paused', { experimentId })
      }
    } catch (error) {
      console.error('Failed to pause experiment:', error)
      throw new Error('Failed to pause experiment')
    }
  }

  /**
   * Resume a paused experiment
   */
  async resumeExperiment(experimentId: string): Promise<void> {
    try {
      await this.http.post(`/experiments/${experimentId}/resume`)

      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        experiment.status = 'running'
        experiment.updatedAt = new Date()
      }

      // Notify via socket
      if (this.socket) {
        this.socket.emit('experiment:resumed', { experimentId })
      }
    } catch (error) {
      console.error('Failed to resume experiment:', error)
      throw new Error('Failed to resume experiment')
    }
  }

  /**
   * End an experiment
   */
  async endExperiment(experimentId: string): Promise<void> {
    try {
      await this.http.post(`/experiments/${experimentId}/end`)

      const experiment = this.experiments.get(experimentId)
      if (experiment) {
        experiment.status = 'completed'
        experiment.config.endDate = new Date()
        experiment.updatedAt = new Date()
      }

      // Notify via socket
      if (this.socket) {
        this.socket.emit('experiment:ended', { experimentId })
      }
    } catch (error) {
      console.error('Failed to end experiment:', error)
      throw new Error('Failed to end experiment')
    }
  }

  // Utility Methods

  /**
   * Validate experiment configuration
   */
  private validateExperimentConfig(config: ExperimentConfig): void {
    if (!config.name || config.name.trim() === '') {
      throw new Error('Experiment name is required')
    }

    if (!config.hypothesis || config.hypothesis.trim() === '') {
      throw new Error('Experiment hypothesis is required')
    }

    if (!config.goal) {
      throw new Error('Experiment goal is required')
    }

    if (!config.variants || config.variants.length < 2) {
      throw new Error('At least 2 variants are required for A/B testing')
    }

    // Validate traffic allocation
    if (!config.trafficAllocation || config.trafficAllocation.length !== config.variants.length) {
      throw new Error('Traffic allocation must match number of variants')
    }

    const totalAllocation = config.trafficAllocation.reduce((sum, alloc) => sum + alloc, 0)
    if (Math.abs(totalAllocation - 100) > 0.01) {
      throw new Error('Traffic allocation must sum to 100%')
    }

    // Validate variant weights
    const totalWeight = config.variants.reduce((sum, variant) => sum + variant.weight, 0)
    if (Math.abs(totalWeight - 100) > 0.01) {
      throw new Error('Variant weights must sum to 100%')
    }
  }

  /**
   * Set up HTTP interceptors for error handling
   */
  private setupInterceptors(): void {
    this.http.interceptors.response.use(
      response => response,
      error => {
        if (error.response?.status === 401) {
          // Handle unauthorized access
          console.error('Unauthorized access to A/B testing API')
        } else if (error.response?.status === 403) {
          // Handle forbidden access (tenant isolation)
          console.error('Access forbidden - tenant isolation violation')
        } else if (error.response?.status >= 500) {
          // Handle server errors
          console.error('A/B testing service server error:', error.response.data)
        }

        return Promise.reject(error)
      }
    )
  }

  /**
   * Set up socket listeners for real-time updates
   */
  private setupSocketListeners(): void {
    if (!this.socket) return

    this.socket.on('experiment:updated', (data: Experiment) => {
      this.experiments.set(data.id, data)
    })

    this.socket.on('experiment:deleted', (data: { experimentId: string }) => {
      this.experiments.delete(data.experimentId)
    })

    this.socket.on('variant:updated', (data: { variantId: string, variant: Variant }) => {
      // Update variant in experiments
      for (const experiment of this.experiments.values()) {
        const variantIndex = experiment.variants.findIndex(v => v.id === data.variantId)
        if (variantIndex !== -1) {
          experiment.variants[variantIndex] = data.variant
          experiment.updatedAt = new Date()
          break
        }
      }
    })

    this.socket.on('conversion:tracked', (data: any) => {
      // Update local metrics
      const experiment = this.experiments.get(data.experimentId)
      if (experiment) {
        const variant = experiment.variants.find(v => v.id === data.variantId)
        if (variant) {
          variant.conversions++
          variant.conversionRate = variant.visitors > 0 ? variant.conversions / variant.visitors : 0
        }
      }
    })
  }

  /**
   * Load experiments from backend
   */
  private async loadExperiments(): Promise<void> {
    try {
      const experiments = await this.getExperiments()
      experiments.forEach(exp => {
        this.experiments.set(exp.id, exp)
      })
    } catch (error) {
      console.error('Failed to load experiments:', error)
    }
  }

  /**
   * Clean up traffic assignments for deleted experiment
   */
  private cleanupTrafficAssignments(experimentId: string): void {
    for (const [key, assignment] of this.trafficAssignments.entries()) {
      if (assignment.experimentId === experimentId) {
        this.trafficAssignments.delete(key)
      }
    }
  }

  /**
   * Get CSRF token from meta tag
   */
  private getCsrfToken(): string {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token || ''
  }

  /**
   * Generate a unique ID for experiments/variants
   */
  private generateId(): string {
    return Math.random().toString(36).substr(2, 9)
  }
}

// Export singleton instance factory
export const createABTestingService = (
  analyticsService: AnalyticsIntegrationService,
  tenantId?: string
): ABTestingService => {
  return new ABTestingService(analyticsService, tenantId)
}
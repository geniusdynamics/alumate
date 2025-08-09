// Homepage TypeScript Interfaces

export type AudienceType = 'individual' | 'institutional'

export interface HomepageData {
  audience: AudienceType
  meta: PageMeta
}

// Audience Detection and Personalization Interfaces
export interface AudiencePreference {
  type: AudienceType
  timestamp: Date
  source: 'manual' | 'auto_detected' | 'url_param'
  sessionId: string
}

export interface AudienceContent {
  audience: AudienceType
  hero: HeroContent
  features: ContentSection
  testimonials: ContentSection
  pricing: ContentSection
  cta: CTAContent
}

export interface HeroContent {
  headline: string
  subtitle: string
  description?: string
  backgroundImage?: string
  backgroundVideo?: string
}

export interface ContentSection {
  title: string
  subtitle?: string
  items: ContentItem[]
}

export interface ContentItem {
  id: string
  title: string
  description: string
  image?: string
  video?: string
  metadata?: Record<string, any>
}

export interface CTAContent {
  primary: CTAButton
  secondary?: CTAButton
  tertiary?: CTAButton
}

export interface AudienceDetectionResult {
  detectedAudience: AudienceType
  confidence: number
  factors: DetectionFactor[]
  fallback: AudienceType
}

export interface DetectionFactor {
  type: 'url_param' | 'referrer' | 'user_agent' | 'session_history' | 'cookie'
  value: string
  weight: number
  contribution: number
}

export interface PageMeta {
  title: string
  description: string
  keywords: string
}

// Hero Section Interfaces
export interface HeroSectionProps {
  headline: string
  subtitle: string
  primaryCTA: CTAButton
  secondaryCTA?: CTAButton
  backgroundVideo?: string
  backgroundImage?: string
  testimonialRotation?: Testimonial[]
  statisticsHighlight?: Statistic[]
}

export interface CTAButton {
  text: string
  action: 'register' | 'trial' | 'demo' | 'contact' | 'learn-more'
  variant: 'primary' | 'secondary' | 'outline'
  trackingEvent: string
  href?: string
}

export interface CTAClickEvent {
  action: string
  section: string
  audience: AudienceType
  additionalData?: any
}

// Statistics and Metrics
export interface PlatformStatistic {
  key: string
  value: number
  label: string
  icon: string
  animateOnScroll: boolean
  format: 'number' | 'percentage' | 'currency'
  suffix?: string
}

export interface Statistic {
  value: number
  label: string
  icon: string
  animateOnScroll: boolean
}

// Alumni Profile Interfaces
export interface AlumniProfile {
  id: string
  name: string
  graduationYear: number
  degree: string
  currentRole: string
  currentCompany: string
  industry: string
  location: string
  profileImage: string
  linkedinUrl?: string
  careerStage: 'recent_grad' | 'mid_career' | 'senior' | 'executive'
  specialties: string[]
  mentorshipAvailable: boolean
}

// Testimonials
export interface Testimonial {
  id: string
  quote: string
  author: AlumniProfile
  beforeAfter?: CareerProgression
  videoTestimonial?: string
  metrics?: SuccessMetric[]
  featured: boolean
}

export interface InstitutionTestimonial {
  id: string
  quote: string
  institution: Institution
  administrator: AdminProfile
  results: InstitutionalResult[]
  videoTestimonial?: string
  featured: boolean
}

export interface Institution {
  id: string
  name: string
  type: 'university' | 'college' | 'corporate' | 'nonprofit'
  logo: string
  website: string
  alumniCount: number
  establishedYear: number
  location: string
  tier: 'professional' | 'enterprise' | 'custom'
}

export interface AdminProfile {
  id: string
  name: string
  title: string
  institution: string
  email: string
  phone?: string
  profileImage: string
  responsibilities: string[]
  experience: number
}

// Career Progression
export interface CareerProgression {
  before: CareerStage
  after: CareerStage
  timeframe: string
  keyMilestones: Milestone[]
}

export interface CareerStage {
  role: string
  company: string
  salary?: number
  level: string
  responsibilities: string[]
}

export interface Milestone {
  date: Date
  title: string
  description: string
  type: 'promotion' | 'job_change' | 'skill_acquisition' | 'achievement'
}

// Success Metrics
export interface SuccessMetric {
  type: 'salary_increase' | 'job_placement' | 'promotion' | 'business_growth' | 'network_expansion'
  value: number
  unit: 'percentage' | 'dollar' | 'count' | 'days'
  timeframe: string
  verified: boolean
}

export interface InstitutionalResult {
  metric: 'engagement' | 'alumni_participation' | 'event_attendance' | 'donations' | 'job_placements' | 'app_downloads'
  beforeValue: number
  afterValue: number
  improvementPercentage: number
  timeframe: string
  verified: boolean
}

// Platform Features
export interface PlatformFeature {
  id: string
  title: string
  description: string
  benefits: string[]
  screenshot: string
  demoVideo?: string
  usageStats: FeatureStatistic[]
  targetPersona: AlumniPersona[]
  category: 'networking' | 'mentorship' | 'jobs' | 'events' | 'analytics' | 'admin'
}

export interface InstitutionalFeature {
  id: string
  title: string
  description: string
  benefits: string[]
  targetInstitution: 'university' | 'college' | 'corporate' | 'nonprofit'
  screenshot: string
  demoVideo?: string
  pricingTier: 'professional' | 'enterprise' | 'custom'
  customizationLevel: 'basic' | 'advanced' | 'full'
}

export interface FeatureStatistic {
  metric: string
  value: number
  label: string
  trend: 'up' | 'down' | 'stable'
}

export interface AlumniPersona {
  id: string
  name: string
  description: string
  careerStage: string
  primaryGoals: string[]
  painPoints: string[]
}

// Success Stories
export interface SuccessStory {
  id: string
  title: string
  summary: string
  alumniProfile: AlumniProfile
  careerProgression: CareerProgression
  platformImpact: PlatformImpact
  testimonialVideo?: string
  metrics: SuccessMetric[]
  industry: string
  graduationYear: number
  featured: boolean
  tags: string[]
}

export interface InstitutionalCaseStudy {
  id: string
  title: string
  institutionName: string
  institutionType: 'university' | 'college' | 'corporate' | 'nonprofit'
  challenge: string
  solution: string
  implementation: ImplementationDetail[]
  results: InstitutionalResult[]
  timeline: string
  alumniCount: number
  engagementIncrease: number
  featured: boolean
}

export interface PlatformImpact {
  connectionsMade: number
  mentorsWorkedWith: number
  referralsReceived: number
  eventsAttended: number
  skillsAcquired: string[]
  networkGrowth: number
}

export interface ImplementationDetail {
  phase: string
  duration: string
  activities: string[]
  deliverables: string[]
  milestones: string[]
}

// Value Calculator
export interface CalculatorInput {
  currentRole: string
  industry: string
  experienceYears: number
  careerGoals: string[]
  location?: string
  educationLevel?: string
  currentSalary?: number
  targetRole?: string
  preferredCompanySize?: string
  workStyle?: string
  skillsToLearn?: string
  goalTimeline?: string
  primaryChallenge?: string
  networkingLevel?: number
  timeInvestment?: string
  additionalInfo?: string
}

export interface CalculationResult {
  projectedSalaryIncrease: number
  networkingValue: string
  careerAdvancementTimeline: string
  personalizedRecommendations: Recommendation[]
  successProbability: number
  roiEstimate: number
}

export interface Recommendation {
  category: string
  action: string
  priority: 'high' | 'medium' | 'low'
  timeframe: string
  expectedOutcome: string
}

// Pricing and Plans
export interface PricingTier {
  id: string
  name: string
  audience: AudienceType
  price: number
  billingPeriod: 'monthly' | 'yearly'
  features: PricingFeature[]
  limitations: PricingLimitation[]
  popular: boolean
  trialAvailable: boolean
  customQuote: boolean
}

export interface EnterprisePricing {
  tier: 'professional' | 'enterprise' | 'custom'
  basePrice: number
  pricePerAlumni?: number
  features: EnterpriseFeature[]
  support: SupportLevel
  customization: CustomizationLevel
  brandedApp: boolean
  analytics: AnalyticsLevel
  integrations: Integration[]
  sla: ServiceLevelAgreement
}

export interface PricingFeature {
  name: string
  description?: string
  included: boolean
  limit?: string
}

export interface PricingPlan {
  id: string
  name: string
  description: string
  price: number | null
  originalPrice?: number
  billingPeriod: string
  ctaText: string
  featured: boolean
  features: PricingFeature[]
  additionalInfo?: string
}

export interface ComparisonFeature {
  name: string
  key: string
  description?: string
}

export interface PricingLimitation {
  feature: string
  limit: number
  unit: string
}

export interface EnterpriseFeature {
  name: string
  description: string
  included: boolean
  additionalCost?: number
  requiresCustom: boolean
}

// Form Data Interfaces
export interface DemoRequestData {
  institutionName: string
  contactName: string
  email: string
  title?: string
  phone?: string
  alumniCount?: string
  currentSolution?: string
  interests?: string[]
  preferredTime?: string
  message?: string
  timestamp?: string
}

export interface TrialSignupData {
  name: string
  email: string
  graduationYear?: number
  institution?: string
  currentRole?: string
  industry?: string
  referralSource?: string
}

export interface LeadCaptureData {
  email: string
  source: string
  audience: AudienceType
  interestLevel?: string
  additionalData?: Record<string, any>
}

// Component Props Interfaces
export interface SocialProofProps {
  audience: AudienceType
  keyStatistics: PlatformStatistic[]
  featuredTestimonials: Testimonial[] | InstitutionTestimonial[]
  trustBadges: TrustBadge[]
  companyLogos: CompanyLogo[]
}

export interface FeaturesShowcaseProps {
  audience: AudienceType
  features: PlatformFeature[] | InstitutionalFeature[]
  demoMode: 'carousel' | 'tabs' | 'accordion'
  interactiveDemo: boolean
}

export interface SuccessStoriesProps {
  audience: AudienceType
  stories: SuccessStory[] | InstitutionalCaseStudy[]
  filters: StoryFilter[]
  pagination: PaginationConfig
  featuredStory?: SuccessStory | InstitutionalCaseStudy
}

export interface ValueCalculatorProps {
  calculatorSteps: CalculatorStep[]
  industryData: IndustryBenchmark[]
  resultTemplate: CalculationResult
}

// Supporting Interfaces
export interface TrustBadge {
  id: string
  name: string
  image: string
  description: string
  verificationUrl?: string
}

export interface CompanyLogo {
  id: string
  name: string
  logo: string
  website?: string
  category: string
}

export interface StoryFilter {
  key: string
  label: string
  options: FilterOption[]
  type: 'select' | 'multiselect' | 'range'
}

export interface FilterOption {
  value: string
  label: string
  count?: number
}

export interface PaginationConfig {
  currentPage: number
  totalPages: number
  itemsPerPage: number
  totalItems: number
}

export interface CalculatorStep {
  stepNumber: number
  title: string
  question: string
  inputType: 'select' | 'range' | 'text' | 'multiselect' | 'radio'
  options?: SelectOption[]
  validation: ValidationRule[]
  helpText?: string
}

export interface SelectOption {
  value: string
  label: string
  description?: string
}

export interface ValidationRule {
  type: 'required' | 'min' | 'max' | 'email' | 'pattern'
  value?: any
  message: string
}

export interface IndustryBenchmark {
  industry: string
  averageSalary: number
  salaryGrowthRate: number
  networkingValue: number
  jobPlacementRate: number
}

// Analytics and Tracking
export interface AnalyticsEvent {
  eventName: string
  audience: AudienceType
  section: string
  action: string
  value?: number
  customData?: Record<string, any>
  timestamp: Date
}

export interface ConversionGoal {
  id: string
  name: string
  audience: AudienceType
  type: 'trial_signup' | 'demo_request' | 'contact_form' | 'calculator_completion'
  value: number
  trackingCode: string
}

// A/B Testing
export interface ABTest {
  id: string
  name: string
  audience: AudienceType
  variants: ABVariant[]
  trafficAllocation: number
  conversionGoals: ConversionGoal[]
  startDate: Date
  endDate?: Date
  status: 'draft' | 'running' | 'paused' | 'completed'
}

export interface ABVariant {
  id: string
  name: string
  weight: number
  componentOverrides: ComponentOverride[]
  conversionRate?: number
  sampleSize?: number
}

export interface ComponentOverride {
  component: string
  props: Record<string, any>
  content?: string
}

// Additional Supporting Types
export interface SupportLevel {
  type: 'basic' | 'priority' | 'dedicated'
  responseTime: string
  channels: string[]
  availability: string
}

export interface CustomizationLevel {
  type: 'basic' | 'advanced' | 'full'
  options: string[]
  additionalCost?: number
}

export interface AnalyticsLevel {
  type: 'basic' | 'advanced' | 'enterprise'
  features: string[]
  dataRetention: string
  exportOptions: string[]
}

export interface Integration {
  name: string
  type: 'crm' | 'email' | 'analytics' | 'sso' | 'payment'
  description: string
  setupComplexity: 'easy' | 'medium' | 'complex'
  additionalCost?: number
}

export interface ServiceLevelAgreement {
  uptime: number
  responseTime: string
  supportLevel: string
  penalties: string[]
}

// Admin Dashboard Interfaces
export interface AdminDashboard {
  features: AdminFeature[]
  analytics: DashboardAnalytics
  managementTools: ManagementTool[]
  customization: CustomizationOption[]
}

export interface AdminFeature {
  id: string
  name: string
  description: string
  category: 'analytics' | 'management' | 'customization' | 'integrations'
  screenshot: string
  hotspots: FeatureHotspot[]
  benefits: string[]
}

export interface DashboardAnalytics {
  totalAlumni: number
  activeUsers: number
  engagementRate: number
  eventsThisMonth: number
}

export interface ManagementTool {
  id: string
  name: string
  description: string
  capabilities: string[]
}

export interface CustomizationOption {
  id: string
  name: string
  description: string
  level: 'basic' | 'advanced' | 'enterprise'
}

export interface FeatureHotspot {
  id: string
  x: number
  y: number
  title?: string
  description: string
  image?: string
  details?: string[]
  benefits?: Array<{
    title: string
    description: string
    icon: any
  }>
  stats?: Array<{
    label: string
    value: string
  }>
  technical?: {
    integrations?: string[]
    requirements?: string[]
  }
}

// Branded Apps Interfaces
export interface BrandedApp {
  id: string
  institutionName: string
  institutionType: 'university' | 'college' | 'corporate' | 'nonprofit'
  logo: string
  appIcon: string
  appStoreUrl?: string
  playStoreUrl?: string
  screenshots: AppScreenshot[]
  customizations: AppCustomization[]
  userCount: number
  engagementStats: EngagementMetric[]
  launchDate: Date
  featured: boolean
}

export interface AppScreenshot {
  id: string
  url: string
  title: string
  description: string
  device: 'iphone' | 'android' | 'tablet'
  category: 'home' | 'profile' | 'networking' | 'events' | 'messaging'
}

export interface AppCustomization {
  category: 'branding' | 'features' | 'integrations' | 'analytics'
  name: string
  description: string
  implemented: boolean
  complexity: 'basic' | 'advanced' | 'custom'
}

export interface EngagementMetric {
  metric: 'daily_active_users' | 'session_duration' | 'feature_usage' | 'retention_rate'
  value: number
  unit: 'count' | 'minutes' | 'percentage'
  trend: 'up' | 'down' | 'stable'
  period: string
}

export interface CustomizationOption {
  id: string
  category: 'branding' | 'features' | 'integrations' | 'analytics'
  name: string
  description: string
  options: CustomizationDetail[]
  examples: CustomizationExample[]
  level: 'basic' | 'advanced' | 'enterprise'
}

export interface CustomizationDetail {
  id: string
  name: string
  description: string
  type: 'color' | 'logo' | 'text' | 'feature' | 'integration'
  defaultValue?: string
  options?: string[]
  required: boolean
}

export interface CustomizationExample {
  id: string
  name: string
  description: string
  beforeImage: string
  afterImage: string
  institutionType: string
}

export interface AppStoreIntegration {
  appleAppStore: boolean
  googlePlayStore: boolean
  customDomain: boolean
  whiteLabel: boolean
  institutionBranding: boolean
  reviewManagement: boolean
  analyticsIntegration: boolean
}

export interface DevelopmentTimeline {
  phases: DevelopmentPhase[]
  totalDuration: string
  estimatedCost: string
  maintenanceCost: string
}

export interface DevelopmentPhase {
  id: string
  name: string
  description: string
  duration: string
  deliverables: string[]
  dependencies: string[]
  milestones: PhaseMilestone[]
}

export interface PhaseMilestone {
  id: string
  name: string
  description: string
  dueDate: string
  status: 'pending' | 'in_progress' | 'completed' | 'delayed'
}

export interface BrandedAppsShowcaseProps {
  featuredApps: BrandedApp[]
  customizationOptions: CustomizationOption[]
  appStoreIntegration: AppStoreIntegration
  developmentTimeline: DevelopmentTimeline
  audience: AudienceType
}

// Security and Privacy Interfaces
export interface SecurityPrivacyProps {
  audience: AudienceType
  privacyHighlights: PrivacyHighlight[]
  securityCertifications: SecurityCertification[]
  verificationProcess: VerificationProcess
  dataProtection: DataProtectionInfo
  complianceInfo: ComplianceInfo[]
}

export interface PrivacyHighlight {
  id: string
  title: string
  description: string
  icon: string
  details: string[]
  learnMoreUrl?: string
}

export interface SecurityCertification {
  id: string
  name: string
  badge: string
  description: string
  verificationUrl?: string
  expiryDate?: Date
  category: 'security' | 'privacy' | 'compliance' | 'quality'
}

export interface VerificationProcess {
  title: string
  description: string
  steps: VerificationStep[]
  benefits: string[]
  requirements: string[]
}

export interface VerificationStep {
  id: string
  stepNumber: number
  title: string
  description: string
  icon: string
  estimatedTime: string
  required: boolean
}

export interface DataProtectionInfo {
  title: string
  description: string
  principles: DataProtectionPrinciple[]
  userRights: UserRight[]
  contactInfo: ContactInfo
}

export interface DataProtectionPrinciple {
  id: string
  title: string
  description: string
  icon: string
  implementation: string[]
}

export interface UserRight {
  id: string
  right: string
  description: string
  howToExercise: string
  responseTime: string
}

export interface ComplianceInfo {
  id: string
  standard: string
  description: string
  badge?: string
  certificationDate?: Date
  scope: string[]
  auditFrequency: string
}

export interface ContactInfo {
  email: string
  phone?: string
  address?: string
  hours: string
}

// Integration and Ecosystem Interfaces
export interface IntegrationEcosystemProps {
  audience: AudienceType
  integrations: PlatformIntegration[]
  apiDocumentation: APIDocumentation
  migrationSupport: MigrationSupport
  trainingPrograms: TrainingProgram[]
  scalabilityInfo: ScalabilityInfo[]
}

export interface PlatformIntegration {
  id: string
  name: string
  category: 'crm' | 'email' | 'events' | 'analytics' | 'sso' | 'payment' | 'communication'
  logo: string
  description: string
  features: string[]
  setupComplexity: 'easy' | 'medium' | 'complex'
  documentation: string
  supportLevel: 'community' | 'standard' | 'premium'
  pricing: IntegrationPricing
  screenshots?: string[]
}

export interface IntegrationPricing {
  type: 'free' | 'paid' | 'enterprise'
  cost?: number
  billingPeriod?: 'monthly' | 'yearly'
  setupFee?: number
  notes?: string
}

export interface APIDocumentation {
  title: string
  description: string
  version: string
  baseUrl: string
  authentication: AuthenticationMethod[]
  endpoints: APIEndpoint[]
  sdks: SDK[]
  examples: CodeExample[]
  rateLimits: RateLimit[]
}

export interface AuthenticationMethod {
  type: 'api_key' | 'oauth2' | 'jwt' | 'basic'
  description: string
  implementation: string
  security: string[]
}

export interface APIEndpoint {
  id: string
  method: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
  path: string
  description: string
  parameters: APIParameter[]
  responses: APIResponse[]
  examples: string[]
}

export interface APIParameter {
  name: string
  type: string
  required: boolean
  description: string
  example?: string
}

export interface APIResponse {
  status: number
  description: string
  schema: string
  example: string
}

export interface SDK {
  language: string
  name: string
  version: string
  documentation: string
  repository: string
  examples: string[]
}

export interface CodeExample {
  id: string
  title: string
  description: string
  language: string
  code: string
  explanation: string[]
}

export interface RateLimit {
  endpoint: string
  limit: number
  period: string
  headers: string[]
}

export interface MigrationSupport {
  title: string
  description: string
  supportedPlatforms: SupportedPlatform[]
  migrationProcess: MigrationStep[]
  timeline: string
  support: MigrationSupportLevel
  tools: MigrationTool[]
}

export interface SupportedPlatform {
  id: string
  name: string
  logo: string
  description: string
  migrationComplexity: 'low' | 'medium' | 'high'
  dataMapping: DataMapping[]
  estimatedTime: string
}

export interface DataMapping {
  sourceField: string
  targetField: string
  transformation?: string
  notes?: string
}

export interface MigrationStep {
  id: string
  stepNumber: number
  title: string
  description: string
  duration: string
  deliverables: string[]
  prerequisites: string[]
}

export interface MigrationSupportLevel {
  type: 'self_service' | 'assisted' | 'full_service'
  description: string
  included: string[]
  additionalCost?: number
  timeline: string
}

export interface MigrationTool {
  id: string
  name: string
  description: string
  type: 'automated' | 'semi_automated' | 'manual'
  supportedFormats: string[]
  limitations: string[]
}

export interface TrainingProgram {
  id: string
  title: string
  description: string
  audience: 'administrators' | 'end_users' | 'developers' | 'all'
  format: 'online' | 'in_person' | 'hybrid' | 'self_paced'
  duration: string
  modules: TrainingModule[]
  certification: boolean
  cost: TrainingCost
  schedule: TrainingSchedule[]
}

export interface TrainingModule {
  id: string
  title: string
  description: string
  duration: string
  topics: string[]
  materials: string[]
  assessment: boolean
}

export interface TrainingCost {
  type: 'free' | 'paid' | 'included'
  amount?: number
  currency?: string
  notes?: string
}

export interface TrainingSchedule {
  id: string
  date: Date
  time: string
  timezone: string
  capacity: number
  registrationUrl: string
}

export interface ScalabilityInfo {
  id: string
  institutionSize: 'small' | 'medium' | 'large' | 'enterprise'
  alumniRange: string
  features: ScalabilityFeature[]
  performance: PerformanceMetric[]
  support: ScalabilitySupportLevel
  pricing: ScalabilityPricing
  caseStudies: string[]
}

export interface ScalabilityFeature {
  name: string
  description: string
  availability: boolean
  limitations?: string[]
  additionalCost?: number
}

export interface PerformanceMetric {
  metric: string
  value: string
  description: string
  benchmark: string
}

export interface ScalabilitySupportLevel {
  type: string
  description: string
  responseTime: string
  channels: string[]
  dedicatedManager: boolean
}

export interface ScalabilityPricing {
  model: 'per_user' | 'tiered' | 'custom'
  basePrice?: number
  additionalUserCost?: number
  volumeDiscounts: VolumeDiscount[]
  customQuoteThreshold?: number
}

export interface VolumeDiscount {
  minUsers: number
  maxUsers?: number
  discountPercentage: number
  description: string
}

// Strategic CTA and Conversion System Interfaces
export interface StrategicCTA {
  id: string
  type: 'contextual' | 'section' | 'sticky-header' | 'floating'
  placement: 'hero' | 'features' | 'testimonials' | 'pricing' | 'footer' | 'inline'
  audience: AudienceType | 'both'
  text: string
  action: string
  section: string
  mobileOptimized: boolean
  contextual: boolean
  priority: number
  conditions?: CTACondition[]
}

export interface CTACondition {
  type: 'scroll_depth' | 'time_on_page' | 'section_viewed' | 'engagement_level'
  operator: 'greater_than' | 'less_than' | 'equals'
  value: number | string
}

export interface ExitIntentOffer {
  badge: string
  title: string
  description: string
  details?: string[]
  countdown?: number
  countdownLabel?: string
}

export type EngagementLevel = 'low' | 'medium' | 'high'

export interface ProgressiveCTA {
  id: string
  label: string
  message: string
  text: string
  action: string
  section: string
  triggerDepth: number
}

export interface MicroCTA {
  id: string
  text: string
  icon: string
  tooltip: string
  action: string
  section: string
  position: { top: number; left: number }
}

export interface UrgencyIndicator {
  icon: string
  text: string
}

export interface FloatingCTAContent {
  title: string
  subtitle: string
  primaryCTA: {
    text: string
    description: string
    action: string
    icon: string
  }
  secondaryCTA?: {
    text: string
    description: string
    action: string
    icon: string
  }
  tertiaryCTA?: {
    text: string
    description: string
    action: string
    icon: string
  }
  trustIndicators?: Array<{
    icon: string
    text: string
  }>
}

export interface ContextualCTAData {
  id: string
  title?: string
  description?: string
  text: string
  action: string
  section?: string
  variant?: 'primary' | 'secondary' | 'success' | 'warning'
  size?: 'small' | 'medium' | 'large'
  urgent?: boolean
  animated?: boolean
  icon?: string
  image?: string
  imageAlt?: string
  buttonIcon?: string
  buttonVariant?: 'primary' | 'secondary'
  showArrow?: boolean
  benefits?: string[]
  socialProof?: Array<{
    icon: string
    text: string
  }>
  secondaryAction?: {
    text: string
    action: string
  }
  urgency?: {
    text: string
    countdown?: number
  }
  trustBadges?: Array<{
    name: string
    image: string
    description: string
  }>
  backgroundPattern?: 'dots' | 'lines'
}

export interface SectionCTAData {
  id: string
  text: string
  action: string
  section?: string
  placement: 'inline' | 'floating' | 'banner' | 'sidebar'
  style?: 'default' | 'minimal' | 'prominent' | 'urgent'
  sticky?: boolean
  primaryIcon?: string
  showArrow?: boolean
  contextualMessage?: {
    title: string
    description: string
    progress?: {
      percentage: number
      text: string
    }
  }
  secondaryAction?: {
    text: string
    action: string
    icon?: string
  }
  tertiaryAction?: {
    text: string
    action?: string
    href: string
    preventDefault?: boolean
  }
  additionalContext?: {
    valueProps?: Array<{
      icon: string
      text: string
    }>
    socialProof?: {
      avatars: string[]
      count: string
      label: string
    }
    riskFree?: string
  }
  dismissible?: boolean
  backgroundElements?: Array<{
    id: string
    type: 'circle' | 'square' | 'triangle'
    style: Record<string, string>
  }>
}

export interface StickyHeaderCTAData {
  id: string
  text: string
  compactText?: string
  action: string
  primaryIcon?: string
  secondaryAction?: {
    text: string
    action: string
    icon?: string
  }
  trustIndicators?: Array<{
    icon: string
    text: string
  }>
  notification?: {
    text: string
  }
}

// Conversion Tracking and Analytics Interfaces
export interface ConversionFunnelStep {
  id: string
  name: string
  order: number
  required: boolean
}

export interface ConversionMetrics {
  totalConversions: number
  conversionRate: number
  averageTimeToConversion: number
  topConvertingCTAs: Array<{
    ctaId: string
    conversions: number
    rate: number
  }>
  funnelDropoffPoints: Array<{
    stepId: string
    dropoffRate: number
  }>
  audiencePerformance: {
    individual: { conversions: number; rate: number }
    institutional: { conversions: number; rate: number }
  }
}

export interface UserBehaviorEvent {
  elementId?: string
  elementText?: string
  section?: string
  x?: number
  y?: number
  relativeX?: number
  relativeY?: number
  timestamp?: number
  customData?: Record<string, any>
}

// Heat Map Interfaces
export interface HeatMapData {
  clicks: HeatMapClick[]
  scrollDepth: HeatMapScroll[]
  timeSpent: HeatMapTimeSpent[]
  ctaPerformance: CTAPerformanceData[]
}

export interface HeatMapClick {
  x: number
  y: number
  relativeX: number
  relativeY: number
  element: string
  elementId: string
  elementClass: string
  elementText: string
  section: string
  timestamp: number
  viewportWidth: number
  viewportHeight: number
  pageUrl: string
  audience: AudienceType
}

export interface HeatMapScroll {
  scrollY: number
  scrollPercentage: number
  timestamp: number
  timeSinceLastScroll: number
  viewportHeight: number
  documentHeight: number
  pageUrl: string
  audience: AudienceType
}

export interface HeatMapTimeSpent {
  section: string
  timeSpent: number
  timestamp: number
  pageUrl: string
  audience: AudienceType
}

export interface CTAPerformanceData {
  ctaId: string
  ctaText: string
  ctaType: string
  section: string
  position: {
    x: number
    y: number
    relativeX: number
    relativeY: number
  }
  timestamp: number
  pageUrl: string
  audience: AudienceType
  viewportWidth: number
  viewportHeight: number
}

// A/B Testing Result Interfaces
export interface ABTestResult {
  testId: string
  testName: string
  status: 'running' | 'completed' | 'paused'
  startDate: Date
  endDate?: Date
  variants: ABTestVariantResult[]
  winner?: string
  statisticalSignificance: boolean
  confidenceLevel: number
}

export interface ABTestVariantResult {
  variantId: string
  variantName: string
  participants: number
  conversions: number
  conversionRate: number
  improvement: number
  statisticalSignificance: boolean
}

export interface ABTestStatistics {
  testId: string
  totalParticipants: number
  totalConversions: number
  overallConversionRate: number
  variants: Array<{
    variantId: string
    participants: number
    conversions: number
    conversionRate: number
    confidenceInterval: {
      lower: number
      upper: number
    }
  }>
  significance: {
    pValue: number
    significant: boolean
    confidenceLevel: number
  }
}
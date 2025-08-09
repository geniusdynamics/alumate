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
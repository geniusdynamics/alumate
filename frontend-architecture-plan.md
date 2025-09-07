# Frontend Architecture Plan

## Overview
This document outlines the frontend architecture for the template creation system and brand management features using Vue 3 Composition API with TypeScript.

## Component Structure

### Root Directory: `resources/js/components/TemplateSystem`

```
TemplateSystem/
├── TemplateLibrary/           # Template browsing and selection
│   ├── TemplateLibrary.vue
│   ├── TemplateCard.vue
│   ├── TemplateGrid.vue
│   ├── TemplateList.vue
│   ├── TemplateFilters.vue
│   ├── TemplateSearch.vue
│   └── TemplateCategories.vue
├── TemplateEditor/            # Template creation and editing
│   ├── TemplateEditor.vue
│   ├── TemplateStructure.vue
│   ├── TemplateSections.vue
│   ├── TemplateSection.vue
│   ├── TemplateComponents.vue
│   ├── TemplateComponent.vue
│   └── TemplatePreview.vue
├── TemplateCustomizer/        # Brand and content customization
│   ├── TemplateCustomizer.vue
│   ├── BrandCustomizer.vue
│   ├── ColorPicker.vue
│   ├── FontSelector.vue
│   ├── LogoUploader.vue
│   ├── ContentEditor.vue
│   └── CustomizationPreview.vue
├── LandingPageBuilder/       # Landing page creation
│   ├── LandingPageBuilder.vue
│   ├── PageConfiguration.vue
│   ├── PageSections.vue
│   ├── PageSection.vue
│   ├── PageComponents.vue
│   └── PagePreview.vue
├── BrandManager/             # Brand asset management
│   ├── BrandManager.vue
│   ├── BrandAssets.vue
│   ├── BrandLogoManager.vue
│   ├── BrandColorManager.vue
│   ├── BrandFontManager.vue
│   ├── BrandTemplateManager.vue
│   └── BrandGuidelines.vue
├── AnalyticsDashboard/        # Performance tracking
│   ├── AnalyticsDashboard.vue
│   ├── TemplateAnalytics.vue
│   ├── LandingPageAnalytics.vue
│   ├── ConversionChart.vue
│   ├── PerformanceMetrics.vue
│   └── Recommendations.vue
├── ABTesting/                 # A/B testing interface
│   ├── ABTestManager.vue
│   ├── ABTestCreator.vue
│   ├── ABTestResults.vue
│   ├── VariantManager.vue
│   └── TrafficSplitter.vue
├── Shared/                    # Shared components
│   ├── TemplateHeader.vue
│   ├── TemplateFooter.vue
│   ├── TemplateSidebar.vue
│   ├── ResponsivePreview.vue
│   ├── MobilePreview.vue
│   ├── TabletPreview.vue
│   ├── DesktopPreview.vue
│   ├── TemplateActions.vue
│   └── TemplateStatus.vue
└── Modals/                    # Modal dialogs
    ├── TemplatePreviewModal.vue
    ├── TemplateDuplicateModal.vue
    ├── TemplateVersionModal.vue
    ├── BrandAssetModal.vue
    ├── ABTestModal.vue
    └── AnalyticsModal.vue
```

## State Management

### Pinia Stores

#### 1. TemplateStore (`stores/template.ts`)
```typescript
interface TemplateState {
  templates: Template[]
  currentTemplate: Template | null
  selectedTemplate: Template | null
  filters: TemplateFilters
  pagination: Pagination
  loading: boolean
  error: string | null
}

interface TemplateActions {
  fetchTemplates(filters?: TemplateFilters): Promise<void>
  createTemplate(data: TemplateCreateData): Promise<Template>
  updateTemplate(id: string, data: TemplateUpdateData): Promise<Template>
  deleteTemplate(id: string): Promise<boolean>
  duplicateTemplate(id: string, modifications: any): Promise<Template>
  selectTemplate(template: Template): void
  clearSelectedTemplate(): void
  setCurrentTemplate(template: Template): void
  clearCurrentTemplate(): void
  searchTemplates(query: string): Promise<Template[]>
}
```

#### 2. LandingPageStore (`stores/landingPage.ts`)
```typescript
interface LandingPageState {
  landingPages: LandingPage[]
  currentLandingPage: LandingPage | null
  selectedLandingPage: LandingPage | null
  filters: LandingPageFilters
  pagination: Pagination
  loading: boolean
  error: string | null
}

interface LandingPageActions {
  fetchLandingPages(filters?: LandingPageFilters): Promise<void>
  createLandingPage(data: LandingPageCreateData): Promise<LandingPage>
  updateLandingPage(id: string, data: LandingPageUpdateData): Promise<LandingPage>
  deleteLandingPage(id: string): Promise<boolean>
  publishLandingPage(id: string): Promise<LandingPage>
  unpublishLandingPage(id: string): Promise<LandingPage>
  archiveLandingPage(id: string): Promise<LandingPage>
  selectLandingPage(page: LandingPage): void
  clearSelectedLandingPage(): void
  setCurrentLandingPage(page: LandingPage): void
  clearCurrentLandingPage(): void
}
```

#### 3. BrandStore (`stores/brand.ts`)
```typescript
interface BrandState {
  logos: BrandLogo[]
  colors: BrandColor[]
  fonts: BrandFont[]
  templates: BrandTemplate[]
  guidelines: BrandGuidelines | null
  currentLogo: BrandLogo | null
  currentColor: BrandColor | null
  currentFont: BrandFont | null
  loading: boolean
  error: string | null
}

interface BrandActions {
  fetchBrandAssets(tenantId: string): Promise<void>
  createLogo(data: BrandLogoCreateData): Promise<BrandLogo>
  updateLogo(id: string, data: BrandLogoUpdateData): Promise<BrandLogo>
  deleteLogo(id: string): Promise<boolean>
  setPrimaryLogo(id: string): Promise<boolean>
  createColor(data: BrandColorCreateData): Promise<BrandColor>
  updateColor(id: string, data: BrandColorUpdateData): Promise<BrandColor>
  deleteColor(id: string): Promise<boolean>
  createFont(data: BrandFontCreateData): Promise<BrandFont>
  updateFont(id: string, data: BrandFontUpdateData): Promise<BrandFont>
  deleteFont(id: string): Promise<boolean>
  setPrimaryFont(id: string): Promise<boolean>
  createTemplate(data: BrandTemplateCreateData): Promise<BrandTemplate>
  updateTemplate(id: string, data: BrandTemplateUpdateData): Promise<BrandTemplate>
  deleteTemplate(id: string): Promise<boolean>
  updateGuidelines(data: BrandGuidelinesUpdateData): Promise<BrandGuidelines>
  runConsistencyCheck(): Promise<ConsistencyReport>
}
```

#### 4. AnalyticsStore (`stores/analytics.ts`)
```typescript
interface AnalyticsState {
  templateMetrics: TemplateMetrics[]
  landingPageMetrics: LandingPageMetrics[]
  brandMetrics: BrandMetrics
  abTestResults: ABTestResult[]
  loading: boolean
  error: string | null
}

interface AnalyticsActions {
  fetchTemplateAnalytics(templateId: string): Promise<TemplateMetrics>
  fetchLandingPageAnalytics(pageId: string): Promise<LandingPageMetrics>
  fetchBrandAnalytics(tenantId: string): Promise<BrandMetrics>
  fetchABTestResults(testId: string): Promise<ABTestResult>
  trackTemplateUsage(templateId: string, context: string): void
  trackConversion(pageId: string, type: string): void
}
```

## TypeScript Interfaces

### Core Interfaces (`types/template.ts`)

```typescript
// Template interfaces
export interface Template {
  id: string
  tenant_id: string
  name: string
  slug: string
  description: string | null
  category: string
  audience_type: string
  campaign_type: string
  structure: TemplateStructure
  default_config: Record<string, any>
  performance_metrics: PerformanceMetrics
  preview_image: string | null
  preview_url: string | null
  version: number
  is_active: boolean
  is_premium: boolean
  usage_count: number
  last_used_at: string | null
  tags: string[]
  created_at: string
  updated_at: string
}

export interface TemplateStructure {
  sections: TemplateSection[]
  components: TemplateComponent[]
  layout: TemplateLayout
}

export interface TemplateSection {
  id: string
  type: string
  config: Record<string, any>
  components: string[]
  order: number
}

export interface TemplateComponent {
  id: string
  type: string
  config: Record<string, any>
  props: Record<string, any>
}

export interface TemplateLayout {
  breakpoints: BreakpointConfig
  spacing: SpacingConfig
  container: ContainerConfig
}

export interface PerformanceMetrics {
  conversion_rate: number
  avg_load_time: number
  bounce_rate: number
  engagement_score: number
  last_updated: string
}

// Landing Page interfaces
export interface LandingPage {
  id: string
  template_id: string
  tenant_id: string
  name: string
  slug: string
  description: string | null
  config: Record<string, any>
  brand_config: Record<string, any>
  audience_type: string
  campaign_type: string
  category: string
  status: 'draft' | 'reviewing' | 'published' | 'archived' | 'suspended'
  published_at: string | null
  draft_hash: string
  version: number
  usage_count: number
  conversion_count: number
  preview_url: string | null
  public_url: string | null
  seo_title: string | null
  seo_description: string | null
  seo_keywords: string[]
  social_image: string | null
  tracking_id: string | null
  favicon_url: string | null
  custom_css: string | null
  custom_js: string | null
  created_at: string
  updated_at: string
}

// Brand interfaces
export interface BrandLogo {
  id: string
  tenant_id: string
  name: string
  type: 'primary' | 'secondary' | 'favicon' | 'social'
  url: string
  alt: string | null
  size: number | null
  mime_type: string | null
  is_primary: boolean
  optimized: boolean
  cdn_url: string | null
  variants: LogoVariant[]
  usage_guidelines: UsageGuidelines
  created_at: string
  updated_at: string
}

export interface BrandColor {
  id: string
  tenant_id: string
  name: string
  value: string
  type: 'primary' | 'secondary' | 'accent' | 'neutral' | 'warning' | 'error' | 'success' | 'info'
  usage_guidelines: string | null
  usage_count: number
  contrast_ratios: ContrastRatio[]
  accessibility: AccessibilityInfo
  created_at: string
  updated_at: string
}

export interface BrandFont {
  id: string
  tenant_id: string
  name: string
  family: string
  weights: string[]
  styles: string[]
  is_primary: boolean
  type: 'system' | 'google' | 'custom'
  source: string
  url: string | null
  fallbacks: string[]
  usage_count: number
  loading_strategy: 'swap' | 'block' | 'optional'
  created_at: string
  updated_at: string
}

export interface BrandTemplate {
  id: string
  tenant_id: string
  name: string
  description: string | null
  primary_font: string | null
  secondary_font: string | null
  logo_variant: string | null
  tags: string[]
  is_default: boolean
  usage_count: number
  colors: BrandColor[]
  created_at: string
  updated_at: string
}

export interface BrandGuidelines {
  id: string
  tenant_id: string
  enforce_color_palette: boolean
  require_contrast_check: boolean
  min_contrast_ratio: number
  enforce_font_families: boolean
  enforce_typography_scale: boolean
  max_heading_size: number
  max_body_size: number
  enforce_logo_placement: boolean
  min_logo_size: number
  logo_clear_space: number
  created_at: string
  updated_at: string
}

// Analytics interfaces
export interface TemplateMetrics {
  template_id: string
  usage_count: number
  conversion_rate: number
  avg_load_time: number
  bounce_rate: number
  engagement_score: number
  recommendations: Recommendation[]
  trends: TrendData[]
}

export interface LandingPageMetrics {
  page_id: string
  traffic: TrafficData
  conversions: ConversionData
  performance: PerformanceData
  seo_metrics: SEOMetrics
}

export interface BrandMetrics {
  asset_usage: AssetUsage[]
  color_usage: ColorUsage[]
  font_usage: FontUsage[]
  consistency_score: number
  issues: BrandIssue[]
}

// A/B Testing interfaces
export interface ABTest {
  id: string
  tenant_id: string
  name: string
  description: string | null
  template_id: string
  variants: ABTestVariant[]
  traffic_split: TrafficSplit
  status: 'draft' | 'running' | 'paused' | 'completed'
  start_date: string | null
  end_date: string | null
  conversion_goal: string
  created_at: string
  updated_at: string
}

export interface ABTestVariant {
  id: string
  test_id: string
  name: string
  config_modifications: Record<string, any>
  traffic_percentage: number
  conversion_count: number
  conversion_rate: number
}

export interface TrafficSplit {
  variant_a_percentage: number
  variant_b_percentage: number
  variant_c_percentage?: number
}

// Utility interfaces
export interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface TemplateFilters {
  search?: string
  category?: string
  audience_type?: string
  campaign_type?: string
  status?: string
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
  per_page?: number
}

export interface Recommendation {
  template_id: string
  reason: string
  confidence_score: number
  projected_improvement: number
}

export interface TrendData {
  date: string
  value: number
  metric: string
}

export interface TrafficData {
  total_visitors: number
  unique_visitors: number
  page_views: number
  avg_session_duration: number
  bounce_rate: number
}

export interface ConversionData {
  total_conversions: number
  conversion_rate: number
  revenue: number
  cost_per_conversion: number
}

export interface PerformanceData {
  dom_content_loaded: number
  page_load_time: number
  first_paint: number
  first_contentful_paint: number
  largest_contentful_paint: number
}

export interface SEOMetrics {
  keyword_rankings: KeywordRanking[]
  backlinks: number
  social_shares: number
  crawl_errors: number
}

export interface KeywordRanking {
  keyword: string
  position: number
  search_volume: number
  trend: 'up' | 'down' | 'stable'
}

export interface AssetUsage {
  asset_type: string
  asset_id: string
  usage_count: number
  last_used: string
}

export interface ColorUsage {
  color_id: string
  usage_count: number
  contexts: string[]
}

export interface FontUsage {
  font_id: string
  usage_count: number
  contexts: string[]
}

export interface BrandIssue {
  id: string
  title: string
  description: string
  severity: 'low' | 'medium' | 'high' | 'critical'
  affected_components: string[]
  auto_fix_available: boolean
  fix_action: string
  category: string
}

export interface ContrastRatio {
  background: string
  ratio: number
  level: string
}

export interface AccessibilityInfo {
  wcag_compliant: boolean
  contrast_issues: string[]
}

export interface LogoVariant {
  type: string
  url: string
  size: number
  format: string
}

export interface UsageGuidelines {
  min_size: number
  clear_space: number
  allowed_backgrounds: string[]
  prohibited_uses: string[]
}

export interface BreakpointConfig {
  xs: number
  sm: number
  md: number
  lg: number
  xl: number
}

export interface SpacingConfig {
  base_unit: number
  scale: number[]
}

export interface ContainerConfig {
  maxWidth: string
  padding: string
}

export interface ABTestResult {
  test_id: string
  winner_variant: string
  statistical_significance: boolean
  confidence_level: number
  improvement_percentage: number
  variant_results: VariantResult[]
}

export interface VariantResult {
  variant_id: string
  conversion_rate: number
  conversion_count: number
  sample_size: number
  p_value: number
}
```

## Composables

### Core Composables (`composables/template.ts`)

```typescript
// Template composables
export function useTemplate() {
  const template = ref<Template | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  const fetchTemplate = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/templates/${id}`)
      template.value = response.data
    } catch (err) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }
  
  const createTemplate = async (data: TemplateCreateData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/templates', data)
      template.value = response.data
      return template.value
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }
  
  const updateTemplate = async (id: string, data: TemplateUpdateData) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/templates/${id}`, data)
      template.value = response.data
      return template.value
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }
  
  return {
    template,
    loading,
    error,
    fetchTemplate,
    createTemplate,
    updateTemplate
  }
}

// Landing page composables
export function useLandingPage() {
  // Similar pattern for landing pages
}

// Brand composables
export function useBrand() {
  // Similar pattern for brand assets
}

// Analytics composables
export function useAnalytics() {
  // Similar pattern for analytics data
}

// A/B Testing composables
export function useABTest() {
  // Similar pattern for A/B testing
}
```

## API Integration

### API Service Layer (`services/template-api.ts`)

```typescript
class TemplateAPIService {
  private baseUrl: string
  
  constructor() {
    this.baseUrl = '/api/v1'
  }
  
  // Template endpoints
  async getTemplates(tenantId: string, params?: TemplateFilters) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/templates`, { params })
    return response.data
  }
  
  async getTemplate(tenantId: string, templateId: string) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/templates/${templateId}`)
    return response.data
  }
  
  async createTemplate(tenantId: string, data: TemplateCreateData) {
    const response = await axios.post(`${this.baseUrl}/tenants/${tenantId}/templates`, data)
    return response.data
  }
  
  async updateTemplate(tenantId: string, templateId: string, data: TemplateUpdateData) {
    const response = await axios.put(`${this.baseUrl}/tenants/${tenantId}/templates/${templateId}`, data)
    return response.data
  }
  
  async deleteTemplate(tenantId: string, templateId: string) {
    const response = await axios.delete(`${this.baseUrl}/tenants/${tenantId}/templates/${templateId}`)
    return response.data
  }
  
  async duplicateTemplate(tenantId: string, templateId: string, modifications: any) {
    const response = await axios.post(`${this.baseUrl}/tenants/${tenantId}/templates/${templateId}/duplicate`, { modifications })
    return response.data
  }
  
  // Landing page endpoints
  async getLandingPages(tenantId: string, params?: LandingPageFilters) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/landing-pages`, { params })
    return response.data
  }
  
  async getLandingPage(tenantId: string, pageId: string) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/landing-pages/${pageId}`)
    return response.data
  }
  
  async createLandingPage(tenantId: string, data: LandingPageCreateData) {
    const response = await axios.post(`${this.baseUrl}/tenants/${tenantId}/landing-pages`, data)
    return response.data
  }
  
  async updateLandingPage(tenantId: string, pageId: string, data: LandingPageUpdateData) {
    const response = await axios.put(`${this.baseUrl}/tenants/${tenantId}/landing-pages/${pageId}`, data)
    return response.data
  }
  
  async deleteLandingPage(tenantId: string, pageId: string) {
    const response = await axios.delete(`${this.baseUrl}/tenants/${tenantId}/landing-pages/${pageId}`)
    return response.data
  }
  
  async publishLandingPage(tenantId: string, pageId: string) {
    const response = await axios.post(`${this.baseUrl}/tenants/${tenantId}/landing-pages/${pageId}/publish`)
    return response.data
  }
  
  // Brand endpoints
  async getBrandAssets(tenantId: string) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/brand/assets`)
    return response.data
  }
  
  async createBrandLogo(tenantId: string, data: FormData) {
    const response = await axios.post(`${this.baseUrl}/tenants/${tenantId}/brand/logos`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }
  
  async updateBrandLogo(tenantId: string, logoId: string, data: BrandLogoUpdateData) {
    const response = await axios.put(`${this.baseUrl}/tenants/${tenantId}/brand/logos/${logoId}`, data)
    return response.data
  }
  
  async deleteBrandLogo(tenantId: string, logoId: string) {
    const response = await axios.delete(`${this.baseUrl}/tenants/${tenantId}/brand/logos/${logoId}`)
    return response.data
  }
  
  // Analytics endpoints
  async getTemplateAnalytics(tenantId: string, templateId: string) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/analytics/templates/${templateId}`)
    return response.data
  }
  
  async getLandingPageAnalytics(tenantId: string, pageId: string) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/analytics/landing-pages/${pageId}`)
    return response.data
  }
  
  // A/B Testing endpoints
  async getABTests(tenantId: string) {
    const response = await axios.get(`${this.baseUrl}/tenants/${tenantId}/ab-tests`)
    return response.data
  }
  
  async createABTest(tenantId: string, data: ABTestCreateData) {
    const response = await axios.post(`${this.baseUrl}/tenants/${tenantId}/ab-tests`, data)
    return response.data
  }
  
  async startABTest(tenantId: string, testId: string) {
    const response = await axios.post(`${this.baseUrl}/tenants/${tenantId}/ab-tests/${testId}/start`)
    return response.data
  }
}

export const templateApi = new TemplateAPIService()
```

## Routing Structure

### Vue Router Configuration (`router/template-routes.ts`)

```typescript
const templateRoutes = [
  {
    path: '/templates',
    name: 'TemplateLibrary',
    component: () => import('../components/TemplateSystem/TemplateLibrary/TemplateLibrary.vue'),
    meta: { requiresAuth: true, title: 'Template Library' }
  },
  {
    path: '/templates/create',
    name: 'TemplateCreate',
    component: () => import('../components/TemplateSystem/TemplateEditor/TemplateEditor.vue'),
    meta: { requiresAuth: true, title: 'Create Template' }
  },
  {
    path: '/templates/:id/edit',
    name: 'TemplateEdit',
    component: () => import('../components/TemplateSystem/TemplateEditor/TemplateEditor.vue'),
    meta: { requiresAuth: true, title: 'Edit Template' },
    props: true
  },
  {
    path: '/templates/:id/customize',
    name: 'TemplateCustomize',
    component: () => import('../components/TemplateSystem/TemplateCustomizer/TemplateCustomizer.vue'),
    meta: { requiresAuth: true, title: 'Customize Template' },
    props: true
  },
  {
    path: '/landing-pages',
    name: 'LandingPageList',
    component: () => import('../components/TemplateSystem/LandingPageBuilder.vue'),
    meta: { requiresAuth: true, title: 'Landing Pages' }
  },
  {
    path: '/landing-pages/create',
    name: 'LandingPageCreate',
    component: () => import('../components/TemplateSystem/LandingPageBuilder/LandingPageBuilder.vue'),
    meta: { requiresAuth: true, title: 'Create Landing Page' }
  },
  {
    path: '/landing-pages/:id/edit',
    name: 'LandingPageEdit',
    component: () => import('../components/TemplateSystem/LandingPageBuilder.vue'),
    meta: { requiresAuth: true, title: 'Edit Landing Page' },
    props: true
  },
  {
    path: '/brand',
    name: 'BrandManager',
    component: () => import('../components/TemplateSystem/BrandManager/BrandManager.vue'),
    meta: { requiresAuth: true, title: 'Brand Manager' }
  },
  {
    path: '/analytics',
    name: 'AnalyticsDashboard',
    component: () => import('../components/TemplateSystem/AnalyticsDashboard/AnalyticsDashboard.vue'),
    meta: { requiresAuth: true, title: 'Analytics Dashboard' }
  },
  {
    path: '/ab-tests',
    name: 'ABTestManager',
    component: () => import('../components/TemplateSystem/ABTesting/ABTestManager.vue'),
    meta: { requiresAuth: true, title: 'A/B Tests' }
  }
]

export default templateRoutes
```

## Styling Architecture

### CSS Architecture

#### Utility Classes
```scss
// Base utility classes
.template-container {
  @apply max-w-7xl mx-auto px-4 sm:px-6 lg:px-8;
}

.template-card {
  @apply bg-white rounded-lg shadow overflow-hidden transition-all duration-200 hover:shadow-lg;
}

.template-button {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.template-input {
  @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm;
}

.template-grid {
  @apply grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4;
}

.template-sidebar {
  @apply hidden lg:block lg:w-80 flex-shrink-0;
}

.template-main {
  @apply flex-1 overflow-y-auto;
}
```

#### Component-Specific Styles
Each component will have its own SCSS module for scoped styling:

```scss
// TemplateEditor.module.scss
.editorContainer {
  display: flex;
  height: calc(100vh - 64px);
  
  .sidebar {
    width: 280px;
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
  }
  
  .mainContent {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
  }
  
  .previewPanel {
    width: 320px;
    background: #f1f5f9;
    border-left: 1px solid #e2e8f0;
    overflow-y: auto;
  }
}
```

## Responsive Design

### Breakpoints
```scss
// Tailwind-like breakpoints
$breakpoints: (
  xs: 0,
  sm: 640px,
  md: 768px,
  lg: 1024px,
  xl: 1280px,
  xxl: 1536px
);

// Mixins for responsive design
@mixin respond-to($breakpoint) {
  @if map-has-key($breakpoints, $breakpoint) {
    @media (min-width: map-get($breakpoints, $breakpoint)) {
      @content;
    }
  }
}

// Usage
.component {
  padding: 1rem;
  
  @include respond-to(md) {
    padding: 2rem;
  }
  
  @include respond-to(lg) {
    padding: 3rem;
  }
}
```

## Performance Optimization

### Lazy Loading
```typescript
// Lazy load heavy components
const TemplateEditor = defineAsyncComponent({
  loader: () => import('../components/TemplateSystem/TemplateEditor/TemplateEditor.vue'),
  loadingComponent: LoadingSpinner,
  errorComponent: ErrorComponent,
  delay: 200,
  timeout: 3000
})
```

### Code Splitting
```typescript
// Split vendor and application code
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'pinia', 'axios'],
          ui: ['@headlessui/vue', '@heroicons/vue'],
          charts: ['chart.js', 'vue-chartjs']
        }
      }
    }
  }
})
```

### Caching Strategy
```typescript
// Service worker for asset caching
const CACHE_NAME = 'template-system-v1'

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll([
          '/templates/',
          '/brand/',
          '/analytics/',
          // Add critical assets
        ])
      })
  )
})

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        return response || fetch(event.request)
      })
  )
})
```

## Testing Strategy

### Unit Tests
```typescript
// Template component tests
describe('TemplateCard', () => {
  it('renders template name correctly', () => {
    const wrapper = mount(TemplateCard, {
      props: {
        template: mockTemplate
      }
    })
    
    expect(wrapper.text()).toContain(mockTemplate.name)
  })
  
  it('emits select event when clicked', async () => {
    const wrapper = mount(TemplateCard, {
      props: {
        template: mockTemplate
      }
    })
    
    await wrapper.find('.template-card').trigger('click')
    expect(wrapper.emitted('select')).toBeTruthy()
  })
})

// Composable tests
describe('useTemplate', () => {
  it('fetches template data correctly', async () => {
    const { template, fetchTemplate } = useTemplate()
    
    await fetchTemplate('template-1')
    
    expect(template.value).toEqual(mockTemplate)
  })
})
```

### Integration Tests
```typescript
// End-to-end workflow tests
describe('Template Creation Flow', () => {
  it('creates a new template successfully', () => {
    cy.visit('/templates/create')
    cy.get('[data-testid="template-name"]').type('My New Template')
    cy.get('[data-testid="template-category"]').select('landing')
    cy.get('[data-testid="save-template"]').click()
    cy.url().should('include', '/templates/')
    cy.contains('Template created successfully')
  })
})
```

## Accessibility

### ARIA Labels and Roles
```vue
<template>
  <div 
    role="button" 
    tabindex="0"
    :aria-label="`Select template ${template.name}`"
    :aria-pressed="isSelected"
    @keydown.enter.space="selectTemplate"
    class="template-card"
  >
    <!-- Template content -->
  </div>
</template>
```

### Keyboard Navigation
```typescript
// Ensure all interactive elements are keyboard accessible
const handleKeyDown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'Enter':
    case ' ':
      event.preventDefault()
      selectTemplate()
      break
    case 'ArrowRight':
      focusNext()
      break
    case 'ArrowLeft':
      focusPrevious()
      break
  }
}
```

## Internationalization

### i18n Support
```typescript
// Multi-language support
const messages = {
  en: {
    template: {
      library: 'Template Library',
      create: 'Create Template',
      edit: 'Edit Template',
      delete: 'Delete Template',
      duplicate: 'Duplicate Template'
    }
  },
  es: {
    template: {
      library: 'Biblioteca de Plantillas',
      create: 'Crear Plantilla',
      edit: 'Editar Plantilla',
      delete: 'Eliminar Plantilla',
      duplicate: 'Duplicar Plantilla'
    }
  }
}

const i18n = createI18n({
  locale: 'en',
  messages
})
```

## Deployment Considerations

### Build Optimization
```typescript
// Vite configuration for production
export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
    // Other plugins
  ],
  build: {
    rollupOptions: {
      output: {
        chunkFileNames: 'js/[name]-[hash].js',
        entryFileNames: 'js/[name]-[hash].js',
        assetFileNames: '[ext]/[name]-[hash].[ext]'
      }
    },
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    }
  }
})
```

### CDN Integration
```typescript
// Asset optimization for CDN
const cdnConfig = {
  publicPath: process.env.CDN_URL || '/static/',
  assetsDir: 'assets',
  filenameHashing: true
}
```

## Monitoring and Analytics

### Error Tracking
```typescript
// Sentry integration for error monitoring
import * as Sentry from '@sentry/vue'

Sentry.init({
  app,
  dsn: import.meta.env.VITE_SENTRY_DSN,
  integrations: [
    new Sentry.BrowserTracing({
      routingInstrumentation: Sentry.vueRouterInstrumentation(router),
    }),
  ],
  tracesSampleRate: 1.0,
})
```

### Performance Monitoring
```typescript
// Web Vitals tracking
import { getCLS, getFID, getFCP, getLCP, getTTFB } from 'web-vitals'

getCLS(console.log)
getFID(console.log)
getFCP(console.log)
getLCP(console.log)
getTTFB(console.log)
```

This frontend architecture provides a comprehensive foundation for the template creation system with proper component organization, state management, type safety, and performance optimization.
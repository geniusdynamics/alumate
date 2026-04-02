/**
 * Analytics Types
 *
 * TypeScript definitions for analytics data structures including heat maps,
 * tracking events, and API responses.
 */

/**
 * Simplified JSON Schema type for custom events
 */
export interface JSONSchema7 {
    $schema?: string;
    $id?: string;
    title?: string;
    description?: string;
    type?: string;
    properties?: Record<string, JSONSchema7>;
    required?: string[];
    additionalProperties?: boolean;
    items?: JSONSchema7;
    enum?: any[];
    minimum?: number;
    maximum?: number;
    minLength?: number;
    maxLength?: number;
    format?: string;
    pattern?: string;
    [key: string]: any;
}

export interface HeatMapPoint {
    x: number; // Normalized coordinate (0-100)
    y: number; // Normalized coordinate (0-100)
    intensity: number; // Click intensity/count at this point
}

export interface HeatMapData {
    heatMapData: HeatMapPoint[];
    pageUrl: string;
    dateRange: {
        start: string;
        end: string;
    };
    totalClicks: number;
}

export interface HeatMapApiResponse {
    success: boolean;
    data: HeatMapData;
    message?: string;
    error?: string;
}

export interface DateRange {
    from?: string;
    to?: string;
}

export interface HeatMapViewerProps {
    pageUrl: string;
    dateRange?: DateRange;
}

// Heat map rendering configuration
export interface HeatMapConfig {
    canvasWidth: number;
    canvasHeight: number;
    minIntensity: number;
    maxIntensity: number;
    colorGradient: {
        low: string; // Blue for cool areas
        high: string; // Red for hot areas
    };
    pointRadius: number;
    blurRadius: number;
}

// Interaction state for heat map
export interface HeatMapInteraction {
    isZoomed: boolean;
    zoomLevel: number;
    panOffset: { x: number; y: number };
    hoveredPoint: HeatMapPoint | null;
    tooltipPosition: { x: number; y: number } | null;
}

// Accessibility features
export interface HeatMapAccessibility {
    ariaLabel: string;
    keyboardNavigation: boolean;
    screenReaderDescription: string;
}

// A/B Testing Types
export interface ABTestVariant {
    id?: string;
    name: string;
    weight: number;
    description?: string;
}

export interface ABTestData {
    id?: string;
    name: string;
    description: string;
    status: 'draft' | 'active' | 'paused' | 'completed';
    variants: ABTestVariant[];
    audience_criteria: string[];
    goal_event: string;
    created_at?: string;
    updated_at?: string;
}

export interface ABTestResults {
    test_id: string;
    date_range: {
        start: string;
        end: string;
    };
    variants: {
        variant_id: string;
        variant_name: string;
        participants: number;
        conversions: number;
        conversion_rate: number;
        confidence_interval?: {
            lower: number;
            upper: number;
        };
    }[];
    significance: {
        p_value: number;
        is_significant: boolean;
        winner_variant?: string;
        effect_size?: number;
    };
    total_participants: number;
    total_conversions: number;
}

export interface ABTestApiResponse {
    success: boolean;
    data: ABTestData | ABTestData[] | ABTestResults;
    message?: string;
    error?: string;
    errors?: Record<string, string[]>;
}

export interface ABTestFormProps {
    test?: ABTestData;
    isEdit: boolean;
}

export interface ABTestResultsProps {
    testId: string;
    dateRange?: DateRange;
}

// Session Recording Types
export interface SessionEvent {
    type: 'click' | 'scroll' | 'form_submit' | 'page_view' | 'input' | 'focus' | 'blur';
    timestamp: number;
    x?: number;
    y?: number;
    element?: string;
    elementType?: string;
    data?: Record<string, any>;
    url?: string;
    userAgent?: string;
    viewport?: {
        width: number;
        height: number;
    };
}

export interface SessionData {
    events: SessionEvent[];
    metadata: {
        duration: number;
        pages: string[];
        insights: string[];
        userAgent?: string;
        viewport?: {
            width: number;
            height: number;
        };
        privacyMasked: boolean;
    };
}

export interface SessionAnnotation {
    id: string;
    timestamp: number;
    content: string;
    type: 'note' | 'highlight' | 'issue';
    createdAt: number;
}

export interface SessionPlaybackState {
    currentTime: number;
    duration: number;
    isPlaying: boolean;
    speed: number;
    isLoading: boolean;
    error: string | null;
    sessionData: SessionData | null;
    annotations: SessionAnnotation[];
    currentEventIndex: number;
}

export interface SessionPlayerProps {
    sessionId: string;
    autoPlay?: boolean;
    initialSpeed?: number;
    showAnnotations?: boolean;
}

export interface SessionApiResponse {
    success: boolean;
    data: SessionData;
    message?: string;
    error?: string;
}

// Cohort Analysis Types
export interface CohortData {
    id: string;
    name: string;
    criteria: Record<string, any>;
    created_at: string;
    members_count: number;
    metrics: {
        size: number;
        retention_30d: number;
        churn: number;
        retention?: {
            day7: number;
            day30: number;
            day90: number;
            day180: number;
            trend: number[];
        };
        engagement?: {
            score: number;
            sessionsPerWeek: number;
            pagesPerSession: number;
            activeDaysPerWeek: number;
        };
        conversion?: {
            rate: number;
            funnel: ConversionStep[];
        };
        churn_rate?: {
            day7: number;
            day30: number;
            day90: number;
            day180: number;
        };
    };
    insights?: CohortInsight[];
}

export interface CohortInsight {
    type: 'positive' | 'warning' | 'critical';
    message: string;
    recommendation: string;
    impact: 'low' | 'medium' | 'high';
    severity?: 'low' | 'medium' | 'high' | 'critical';
    metric?: string;
    value?: number;
    benchmark?: number;
}

export interface TrendAnalysisData {
    cohort_id: string;
    cohort_name: string;
    period: 'day' | 'week' | 'month';
    periods_analyzed: number;
    trends: TrendPoint[];
    summary: TrendSummary;
    analyzed_at: string;
}

export interface TrendPoint {
    period: string;
    active_users: number;
    event_count: number;
    avg_events_per_user: number;
    indicator?: 'up' | 'slight_up' | 'neutral' | 'slight_down' | 'down';
    change_percent?: number;
}

export interface TrendSummary {
    overall_trend: 'improving' | 'stable' | 'declining';
    avg_active_users: number;
    total_events: number;
    periods_with_growth: number;
    periods_with_decline: number;
}

export interface Insight {
    id: string;
    type: 'trend' | 'anomaly';
    metric: string;
    description: string;
    trend_score?: number;
    data_points?: { date: string; value: number }[];
    anomaly?: boolean;
    severity?: 'low' | 'medium' | 'high' | 'critical';
    value?: number;
    baseline?: number;
    z_score?: number;
    recommendation?: {
        type: string;
        target: string;
        description: string;
        expected_impact: string;
        priority: 'low' | 'medium' | 'high' | 'critical';
        action: string;
        attribution_insights?: any;
    };
    effectiveness?: number;
    timestamp: string;
}

export interface Trend {
    metric: string;
    description: string;
    trend_score: number;
    data_points: { date: string; value: number }[];
    moving_average?: { date: string; value: number }[];
    anomaly: boolean;
}

export interface Anomaly {
    metric: string;
    date: string;
    description: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    value: number;
    baseline: number;
    z_score: number;
}

export interface Recommendation {
    type: string;
    target: string;
    description: string;
    expected_impact: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    action: string;
    attribution_insights?: any;
}

export interface ConversionStep {
    step: string;
    count: number;
    rate: number;
}

export interface CohortComparisonData {
    cohorts: CohortData[];
    statisticalSignificance: StatisticalComparison[];
    bestPerforming: {
        retention7d: string;
        retention30d: string;
        engagement: string;
    };
}

export interface StatisticalComparison {
    cohort1: string;
    cohort2: string;
    metric: string;
    pValue: number;
    significant: boolean;
    effectSize: number;
}

export interface CohortAnalyzerProps {
    initialCohortIds?: string[];
    defaultMetrics?: string[];
    dateRange?: DateRange;
}

export interface CohortFilterOptions {
    dateRange: DateRange;
    metrics: string[];
    cohortIds: string[];
    comparisonMode: boolean;
}

export interface CohortApiResponse {
    success: boolean;
    data: CohortData | CohortComparisonData;
    message?: string;
    error?: string;
}

// Attribution Analysis Types
export interface AttributionData {
    user_id: string;
    touchpoints: Touchpoint[];
    models: {
        first_click: ModelResult;
        last_click: ModelResult;
        linear: ModelResult;
        time_decay: ModelResult;
    };
    channels: ChannelPerformance[];
    budget_recommendations: BudgetSuggestion[];
}

export interface Touchpoint {
    type: 'email' | 'ad' | 'social' | 'direct' | 'referral';
    channel: string;
    timestamp: number;
    value: number;
    conversion_value?: number;
}

export interface ModelResult {
    [channel: string]: number;
}

export interface ChannelPerformance {
    channel: string;
    contribution: number;
    roi: number;
    conversions: number;
    total_value: number;
}

export interface BudgetSuggestion {
    channel: string;
    current_allocation: number;
    recommended_change: number;
    rationale: string;
    expected_impact: string;
}

export interface AttributionVisualizerProps {
    userId?: string;
    defaultModel?: 'first_click' | 'last_click' | 'linear' | 'time_decay';
}

export interface AttributionApiResponse {
    success: boolean;
    data: AttributionData;
    message?: string;
    error?: string;
}

// New Attribution Touch Types
export interface AttributionTouch {
    id: string;
    tenant_id: string;
    user_id: string;
    session_id?: string;
    event_type: 'page_view' | 'click' | 'form_submit' | 'purchase' | 'signup' | 'login';
    source?: string;
    medium?: string;
    campaign?: string;
    value: number;
    timestamp: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: string;
        name: string;
        email: string;
    };
    touch_summary?: string;
}

export interface AttributionReport {
    user_id: string;
    period: {
        start: string;
        end: string;
    };
    model: 'last_touch' | 'first_touch' | 'linear' | 'time_decay';
    total_value: number;
    sources: AttributionSource[];
    touch_count: number;
}

export interface AttributionSource {
    name: string;
    percentage: number;
    value: number;
    touch_count: number;
    first_touch: boolean;
    last_touch: boolean;
}

export interface AttributionSummary {
    period: {
        start: string;
        end: string;
    };
    model: string;
    total_users: number;
    total_value: number;
    source_breakdown: AttributionSourceBreakdown[];
    user_attributions: AttributionReport[];
}

export interface AttributionSourceBreakdown {
    name: string;
    total_value: number;
    total_percentage: number;
    user_count: number;
    avg_percentage: number;
}

export interface TrackTouchData {
    user_id?: string;
    session_id?: string;
    event_type: 'page_view' | 'click' | 'form_submit' | 'purchase' | 'signup' | 'login';
    source?: string;
    medium?: string;
    campaign?: string;
    value: number;
    timestamp?: string;
}

export interface AttributionFilters {
    user_id?: string;
    source?: string;
    start_date?: string;
    end_date?: string;
    model?: 'last_touch' | 'first_touch' | 'linear' | 'time_decay';
}

export interface AttributionTouchListResponse {
    success: boolean;
    data: AttributionTouch[];
    attribution?: AttributionReport;
    pagination: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
    };
}

export interface AttributionReportResponse {
    success: boolean;
    data: {
        attribution: AttributionReport;
        touch_history: AttributionTouch[];
        period: {
            start: string;
            end: string;
        };
        model: string;
    };
}

export interface AttributionSummaryResponse {
    success: boolean;
    data: AttributionSummary;
}

// Custom Event Management Types

export interface SchemaField {
    name: string;
    type: 'string' | 'number' | 'integer' | 'boolean' | 'object' | 'array';
    required?: boolean;
    description?: string;
    default?: any;
    enum?: any[];
    minimum?: number;
    maximum?: number;
    minLength?: number;
    maxLength?: number;
    format?: string;
    pattern?: string;
}

export interface EventSchema {
    type: 'object';
    properties: Record<string, SchemaField>;
    required?: string[];
    additionalProperties?: boolean;
}

export interface CustomEventDefinition {
    id: number;
    tenant_id: number;
    name: string;
    description: string;
    category?: 'conversion' | 'engagement' | 'error' | 'custom';
    schema?: EventSchema;
    parameters_json: Array<{
        name: string;
        type: 'string' | 'number' | 'boolean';
    }>;
    created_by: number;
    status: 'active' | 'inactive';
    is_active?: boolean;
    created_at: string;
    updated_at: string;
    aggregates?: CustomEventAnalytics;
    analytics?: CustomEventAnalytics;
    last_tracked?: string;
}

export interface CustomEvent {
    id: number;
    tenant_id: number;
    definition_id: number;
    user_id: number;
    data_json: Record<string, any>;
    timestamp: string;
    created_at: string;
    updated_at: string;
}

export interface DefineEventData {
    name: string;
    description: string;
    parameters_json: Array<{
        name: string;
        type: 'string' | 'number' | 'boolean';
    }>;
}

export interface TrackEventData {
    definition_id: number;
    user_id: number;
    data_json: Record<string, any>;
    timestamp?: string;
}

export interface CustomEventAnalytics {
    total_events: number;
    unique_users: number;
    avg_events_per_user?: number;
    time_series: Array<{
        date: string;
        count: number;
    }>;
    aggregates: Record<string, any>;
    funnel_data?: Array<{
        event_name: string;
        count: number;
        conversion_rate: number;
    }>;
    time_distribution?: Array<{
        hour: number;
        count: number;
    }>;
    correlations?: Array<{
        event1: string;
        event2: string;
        correlation: number;
    }>;
    top_events?: Array<{
        name: string;
        count: number;
    }>;
}

export interface OptimizationSuggestion {
    type: 'info' | 'improvement' | 'insight' | 'recommendation';
    title: string;
    description: string;
    priority: 'low' | 'medium' | 'high';
    action?: string;
    metric?: string;
    value?: number;
    benchmark?: number;
}

export interface OptimizationResponse {
    success: boolean;
    data: {
        definition_id: number;
        suggestions: OptimizationSuggestion[];
        generated_at: string;
    };
}

export interface CustomEventManagerProps {
    initialTab?: 'list' | 'create' | 'analytics' | 'flow';
}

export interface CustomEventListResponse {
    success: boolean;
    data: CustomEventDefinition[];
    pagination: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
    };
}

export interface CustomEventAnalyticsResponse {
    success: boolean;
    data: CustomEventAnalytics;
}
// Behavior Flow Analysis Types
export interface BehaviorFlowData {
    definition_id: number;
    definition_name: string;
    period: {
        start: string;
        end: string;
    };
    flow_graph: {
        nodes: FlowNode[];
        edges: FlowEdge[];
    };
    metrics: {
        total_users: number;
        total_paths: number;
        avg_path_length: number;
        unique_events: number;
    };
    common_paths: CommonPath[];
    optimization_suggestions: BehaviorFlowOptimizationSuggestion[];
}

export interface FlowNode {
    id: string;
    name: string;
    type: 'start' | 'event' | 'end';
    count: number;
    percentage: number;
}

export interface FlowEdge {
    source: string;
    target: string;
    count: number;
    percentage: number;
}

export interface CommonPath {
    path: string[];
    count: number;
    percentage: number;
    avg_time: number;
}

export interface BehaviorFlowOptimizationSuggestion {
    type: 'drop_off' | 'path_length' | 'engagement';
    priority: 'low' | 'medium' | 'high';
    description: string;
    recommendation: string;
    expected_impact: string;
}

export interface FunnelAnalysisData {
    definition_id: number;
    definition_name: string;
    period: {
        start: string;
        end: string;
    };
    funnel_steps: FunnelStep[];
    metrics: {
        total_users: number;
        overall_conversion_rate: number;
        avg_drop_off_rate: number;
    };
    drop_off_points: DropOffPoint[];
    optimization_suggestions: BehaviorFlowOptimizationSuggestion[];
}

export interface FunnelStep {
    step: number;
    event_name: string;
    users: number;
    conversion_rate: number;
    drop_off_rate: number;
}

export interface DropOffPoint {
    step: number;
    event_name: string;
    drop_off_rate: number;
    users_lost: number;
    potential_impact: string;
}

export interface BehaviorFlowResponse {
    success: boolean;
    data: BehaviorFlowData;
}

export interface FunnelAnalysisResponse {
    success: boolean;
    data: FunnelAnalysisData;
}

// Insights API Response Types
export interface InsightApiResponse {
    success: boolean;
    data: Insight | Insight[];
    message?: string;
    error?: string;
    errors?: Record<string, string[]>;
}

// Attribution Visualization Types
export interface AttributionModelComparison {
    model: string;
    description: string;
    sources: ModelSource[];
    total_value: number;
}

export interface ModelSource {
    name: string;
    value: number;
    percentage: number;
    touch_count: number;
}

export interface ChannelContribution {
    channel: string;
    contribution: number;
    percentage: number;
    conversions: number;
    value: number;
    color: string;
}

export interface BudgetRecommendation {
    channel: string;
    current_percentage: number;
    recommended_percentage: number;
    change_amount: number;
    rationale: string;
    expected_impact: string;
    priority: 'low' | 'medium' | 'high';
    roi: number;
}

export interface ConversionPath {
    id: string;
    steps: ConversionStepData[];
    total_value: number;
    touch_count: number;
    start_date: string;
    end_date: string;
}

export interface ConversionStepData {
    order: number;
    timestamp: string;
    channel: string;
    event_type: string;
    value: number;
    is_conversion: boolean;
}

export interface AttributionTouchpoint {
    id: string;
    timestamp: string;
    channel: string;
    event_type: string;
    value: number;
    campaign?: string;
    medium?: string;
    source?: string;
}

export interface ROIMetrics {
    channel: string;
    spend: number;
    revenue: number;
    roi: number;
    roi_percentage: number;
    conversions: number;
    cost_per_conversion: number;
    customer_lifetime_value?: number;
}

export interface AttributionVisualizationData {
    model_comparisons: AttributionModelComparison[];
    channel_contributions: ChannelContribution[];
    budget_recommendations: BudgetRecommendation[];
    conversion_paths: ConversionPath[];
    roi_metrics: ROIMetrics[];
    period: {
        start: string;
        end: string;
    };
}

export interface AttributionVisualizationResponse {
    success: boolean;
    data: AttributionVisualizationData;
    insights?: string[];
    message?: string;
    error?: string;
}

export interface ChannelPerformanceResponse {
    success: boolean;
    data: Record<string, {
        total_touches: number;
        total_value: number;
        conversion_rate: number;
        roi: number;
        roi_category: string;
    }>;
    period: {
        start: string;
        end: string;
    };
    summary: {
        total_conversions: number;
        total_conversion_value: number;
        average_engagement: number;
        roi_distribution: Record<string, number>;
        best_performing_channel: string | null;
    };
}

export interface BudgetRecommendationsResponse {
    success: boolean;
    data: {
        recommendations: BudgetRecommendation[];
        total_budget: number;
        summary: {
            avg_roi: number;
            total_expected_revenue: number;
            optimization_score: number;
        };
    };
    insights: string[];
}

// Learning Analytics Types
export interface LearningProgress {
    id: string;
    tenant_id: string;
    user_id: string;
    course_id: string;
    module_id?: number;
    progress_percentage: number;
    engagement_duration: number;
    completion_timestamp?: string;
    certifications?: Certification[];
    interactions_count: number;
    modules_completed: number;
    total_score?: number;
    engagement_score?: number;
    certified: boolean;
    updated_at?: string;
}

export interface Certification {
    cert_id: string;
    issued_at: string;
    score?: number;
    impact_score?: number;
}

export interface LearningInsights {
    certifications: Certification[];
    total_certifications: number;
    career_impact_score: number;
    employability_boost: number;
    insights: string[];
}

export interface Course {
    id: string;
    tenant_id: string;
    name: string;
    modules_count: number;
    duration_estimate?: number;
}

export interface LearningApiResponse {
    success: boolean;
    data: LearningProgress | LearningProgress[] | Course[];
    message?: string;
    error?: string;
    errors?: Record<string, string[]>;
}

// Template and Landing Page Analytics Types

export interface TemplateMetrics {
    template_id: number;
    usage_count: number;
    conversion_rate: number;
    avg_load_time: number;
    bounce_rate: number;
    engagement_score: number;
    recommendations: TemplateRecommendation[];
    trends: MetricTrendData[];
}

export interface LandingPageMetrics {
    page_id: number;
    traffic: LandingPageTrafficData;
    conversions: LandingPageConversionData;
    performance: LandingPagePerformanceData;
    seo_metrics: LandingPageSEOMetrics;
}

export interface LandingPageTrafficData {
    total_visitors: number;
    unique_visitors: number;
    page_views: number;
    avg_session_duration: number;
    bounce_rate: number;
}

export interface LandingPageConversionData {
    total_conversions: number;
    conversion_rate: number;
    revenue: number;
    cost_per_conversion: number;
}

export interface LandingPagePerformanceData {
    dom_content_loaded: number;
    page_load_time: number;
    first_paint: number;
    first_contentful_paint: number;
    largest_contentful_paint: number;
}

export interface LandingPageSEOMetrics {
    keyword_rankings: KeywordRanking[];
    backlinks: number;
    social_shares: number;
    crawl_errors: number;
}

export interface KeywordRanking {
    keyword: string;
    position: number;
    search_volume: number;
    trend: 'up' | 'down' | 'stable';
}

export interface TemplateRecommendation {
    template_id: number;
    reason: string;
    confidence_score: number;
    projected_improvement: number;
}

export interface MetricTrendData {
    date: string;
    value: number;
    metric: string;
}

// A/B Testing Types for Template System
export interface TemplateABTest {
    id: number;
    tenant_id: string;
    name: string;
    description: string | null;
    template_id: number;
    variants: TemplateABTestVariant[];
    traffic_split: TemplateTrafficSplit;
    status: TemplateABTestStatus;
    start_date: string | null;
    end_date: string | null;
    conversion_goal: string;
    created_at: string;
    updated_at: string;
}

export type TemplateABTestStatus = 'draft' | 'running' | 'paused' | 'completed';

export interface TemplateABTestVariant {
    id: number;
    test_id: number;
    name: string;
    config_modifications: Record<string, unknown>;
    traffic_percentage: number;
    conversion_count: number;
    conversion_rate: number;
}

export interface TemplateTrafficSplit {
    variant_a_percentage: number;
    variant_b_percentage: number;
    variant_c_percentage?: number;
}

export interface TemplateABTestResult {
    test_id: number;
    winner_variant: string;
    statistical_significance: boolean;
    confidence_level: number;
    improvement_percentage: number;
    variant_results: TemplateVariantResult[];
}

export interface TemplateVariantResult {
    variant_id: number;
    conversion_rate: number;
    conversion_count: number;
    sample_size: number;
    p_value: number;
}

// Brand Metrics
export interface BrandMetrics {
    asset_usage: BrandAssetUsage[];
    color_usage: BrandColorUsage[];
    font_usage: BrandFontUsage[];
    consistency_score: number;
    issues: BrandIssue[];
}

export interface BrandAssetUsage {
    asset_type: string;
    asset_id: number;
    usage_count: number;
    last_used: string;
}

export interface BrandColorUsage {
    color_id: number;
    usage_count: number;
    contexts: string[];
}

export interface BrandFontUsage {
    font_id: number;
    usage_count: number;
    contexts: string[];
}

export interface BrandIssue {
    id: string;
    title: string;
    description: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    affected_components: string[];
    auto_fix_available: boolean;
    fix_action: string;
    category: string;
}

// Analytics Filters for Template/Landing Page System
export interface TemplateAnalyticsFilters {
    date_range?: {
        start: string;
        end: string;
    };
    template_id?: number;
    landing_page_id?: number;
    metric_type?: string;
    group_by?: 'day' | 'week' | 'month';
}

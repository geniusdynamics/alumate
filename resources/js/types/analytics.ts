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
export interface CustomEventDefinition {
    id: number;
    tenant_id: number;
    name: string;
    description: string;
    parameters_json: Array<{
        name: string;
        type: 'string' | 'number' | 'boolean';
    }>;
    created_by: number;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
    aggregates?: CustomEventAnalytics;
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
    time_series: Array<{
        date: string;
        count: number;
    }>;
    aggregates: Record<string, any>;
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

// Insights API Response Types
export interface InsightApiResponse {
    success: boolean;
    data: Insight | Insight[];
    message?: string;
    error?: string;
    errors?: Record<string, string[]>;
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

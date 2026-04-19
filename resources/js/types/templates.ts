// Template system type definitions for the Alumate platform

export interface Template {
    id: number;
    tenant_id: string;
    name: string;
    slug: string;
    description: string | null;
    category: string;
    audience_type: AudienceType;
    campaign_type: CampaignType;
    structure: TemplateStructure | null;
    default_config: Record<string, unknown>;
    performance_metrics: PerformanceMetrics;
    preview_image: string | null;
    preview_url: string | null;
    version: number;
    is_active: boolean;
    is_premium: boolean;
    usage_count: number;
    last_used_at: string | null;
    tags: string[];
    created_at: string;
    updated_at: string;
}

export interface TemplateStructure {
    sections: TemplateSection[];
    components: TemplateComponent[];
    layout: TemplateLayout;
}

export interface TemplateSection {
    id: string;
    type: string;
    config: Record<string, unknown>;
    components: string[];
    order: number;
}

export interface TemplateComponent {
    id: string;
    type: string;
    config: Record<string, unknown>;
    props: Record<string, unknown>;
}

export interface TemplateLayout {
    breakpoints: BreakpointConfig;
    spacing: SpacingConfig;
    container: ContainerConfig;
}

export interface BreakpointConfig {
    xs: number;
    sm: number;
    md: number;
    lg: number;
    xl: number;
}

export interface SpacingConfig {
    base_unit: number;
    scale: number[];
}

export interface ContainerConfig {
    maxWidth: string;
    padding: string;
}

export interface PerformanceMetrics {
    conversion_rate: number;
    avg_load_time: number;
    bounce_rate: number;
    engagement_score: number;
    last_updated: string;
}

export type AudienceType = 'individual' | 'institution' | 'employer';

export type CampaignType =
    | 'onboarding'
    | 'event_promotion'
    | 'networking'
    | 'career_services'
    | 'recruiting'
    | 'donation'
    | 'leadership'
    | 'marketing';

export type TemplateCategory = 'individual' | 'institution' | 'employer';

export type TemplateStatus = 'draft' | 'active' | 'inactive' | 'archived';

export interface TemplateFilters {
    search?: string;
    category?: TemplateCategory;
    audience_type?: AudienceType;
    campaign_type?: CampaignType;
    status?: TemplateStatus;
    is_active?: boolean;
    is_premium?: boolean;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    per_page?: number;
}

export interface Recommendation {
    template_id: number;
    reason: string;
    confidence_score: number;
    projected_improvement: number;
}

export interface TrendData {
    date: string;
    value: number;
    metric: string;
}

export interface TemplateCreateData {
    name: string;
    description?: string;
    category?: TemplateCategory;
    audience_type?: AudienceType;
    campaign_type?: CampaignType;
    structure?: Partial<TemplateStructure>;
    default_config?: Record<string, unknown>;
    tags?: string[];
    is_premium?: boolean;
}

export interface TemplateUpdateData extends Partial<TemplateCreateData> {
    id: number;
}

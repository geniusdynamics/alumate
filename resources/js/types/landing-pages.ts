// Landing page type definitions for the Alumate platform

import type { Template, TemplateStructure, PerformanceMetrics } from './templates';
import type { BrandConfig } from './brand';

export type LandingPageStatus = 'draft' | 'reviewing' | 'published' | 'archived' | 'suspended';

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

export type LandingPageCategory = 'individual' | 'institution' | 'employer';

export interface LandingPageConfig {
    sections?: LandingPageSection[];
    theme?: string;
    layout?: string;
    custom_settings?: Record<string, unknown>;
    brand?: Record<string, unknown>;
}

export interface LandingPageSection {
    id: string;
    type: string;
    config: Record<string, unknown>;
    order: number;
}

export interface LandingPage {
    id: number;
    template_id: number | null;
    name: string;
    slug: string;
    description: string | null;
    config: LandingPageConfig;
    brand_config: Record<string, unknown>;
    audience_type: AudienceType;
    campaign_type: CampaignType;
    category: LandingPageCategory;
    status: LandingPageStatus;
    published_at: string | null;
    draft_hash: string;
    version: number;
    usage_count: number;
    conversion_count: number;
    preview_url: string | null;
    public_url: string | null;
    seo_title: string | null;
    seo_description: string | null;
    seo_keywords: string[];
    social_image: string | null;
    tracking_id: string | null;
    favicon_url: string | null;
    custom_css: string | null;
    custom_js: string | null;
    created_at: string;
    updated_at: string;
    template?: Template;
}

export interface LandingPageSubmission {
    id: number;
    landing_page_id: number;
    lead_id: number | null;
    form_name: string;
    form_data: Record<string, unknown>;
    utm_data: Record<string, string | null> | null;
    session_data: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    referrer: string | null;
    status: 'pending' | 'processed' | 'failed';
    processed_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface LandingPageAnalytics {
    id: number;
    landing_page_id: number;
    template_id: number | null;
    event_type: string;
    event_name: string;
    event_data: Record<string, unknown> | null;
    session_id: string;
    visitor_id: string | null;
    ip_address: string | null;
    user_agent: string | null;
    referrer: string | null;
    utm_data: Record<string, string | null> | null;
    device_type: string | null;
    browser: string | null;
    os: string | null;
    country: string | null;
    city: string | null;
    event_time: string;
    is_compliant: boolean;
    consent_given: boolean;
    created_at: string;
}

export interface LandingPageFilters {
    search?: string;
    status?: LandingPageStatus;
    category?: LandingPageCategory;
    audience_type?: AudienceType;
    campaign_type?: CampaignType;
    template_id?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    per_page?: number;
}

export interface LandingPageCreateData {
    name: string;
    template_id?: number;
    description?: string;
    audience_type?: AudienceType;
    campaign_type?: CampaignType;
    category?: LandingPageCategory;
    config?: Partial<LandingPageConfig>;
    brand_config?: Record<string, unknown>;
    seo_title?: string;
    seo_description?: string;
    seo_keywords?: string[];
    custom_css?: string;
    custom_js?: string;
}

export interface LandingPageUpdateData extends Partial<LandingPageCreateData> {
    id: number;
}

export interface LandingPagePublishOptions {
    publish_at?: string;
    custom_url?: string;
}

export interface LandingPagePerformanceStats {
    usage_count: number;
    conversion_count: number;
    conversion_rate: number;
    is_performing: boolean;
}

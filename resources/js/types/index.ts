export interface BreadcrumbItemType {
    title: string;
    href?: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    roles?: Role[];
    permissions?: string[];
    created_at: string;
    updated_at: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions?: Permission[];
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
    links: {
        first: string;
        last: string;
        prev?: string;
        next?: string;
    };
}

export interface PageProps {
    auth: {
        user: User;
    };
    flash: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
    errors: Record<string, string>;
}

// Alumni and User Profile Interfaces
export interface AlumniProfile {
    id: number;
    name: string;
    email: string;
    avatar_url?: string;
    location?: string;
    current_position?: string;
    current_company?: string;
    bio?: string;
    skills?: string[];
    industries?: string[];
    graduation_year?: number;
    school?: string;
    degree?: string;
    work_experiences?: WorkExperience[];
    educations?: Education[];
    mutual_connections?: Connection[];
    shared_circles?: Circle[];
    shared_groups?: Group[];
    privacy_settings?: PrivacySettings;
    created_at: string;
    updated_at: string;
}

export interface WorkExperience {
    id: number;
    title: string;
    company: string;
    location?: string;
    start_date: string;
    end_date?: string;
    is_current: boolean;
    description?: string;
}

export interface Education {
    id: number;
    institution: Institution;
    degree: string;
    field_of_study?: string;
    graduation_year?: number;
    gpa?: number;
    honors?: string;
}

export interface Institution {
    id: number;
    name: string;
    type: string;
    location?: string;
    logo_url?: string;
}

export interface Connection {
    id: number;
    name: string;
    avatar_url?: string;
    current_position?: string;
    connected_at?: string;
    status: 'pending' | 'accepted' | 'declined' | 'blocked';
}

export interface Circle {
    id: number;
    name: string;
    description?: string;
    member_count?: number;
    privacy: 'public' | 'private' | 'invite_only';
}

export interface Group {
    id: number;
    name: string;
    description?: string;
    member_count?: number;
    type: 'professional' | 'social' | 'academic' | 'regional';
}

export interface PrivacySettings {
    searchable?: boolean;
    show_email?: boolean;
    show_phone?: boolean;
    show_employment?: boolean;
    show_education?: boolean;
    allow_messages?: boolean;
}

// Post and Engagement Interfaces
export interface Post {
    id: number;
    title?: string;
    content: string;
    post_type: 'text' | 'image' | 'video' | 'link' | 'poll' | 'event';
    user: User;
    user_id: number;
    engagement_counts?: EngagementCounts;
    tags?: string[];
    attachments?: Attachment[];
    privacy: 'public' | 'connections' | 'circles' | 'private';
    created_at: string;
    updated_at: string;
}

export interface EngagementCounts {
    like: number;
    comment: number;
    share: number;
    view: number;
}

export interface Attachment {
    id: number;
    type: 'image' | 'video' | 'document' | 'link';
    url: string;
    filename?: string;
    mime_type?: string;
    size?: number;
}

// Analytics Interfaces
export interface AnalyticsData {
    engagement_metrics?: EngagementMetrics;
    alumni_activity?: AlumniActivityData;
    community_health?: CommunityHealthData;
    platform_usage?: PlatformUsageData;
    overview?: AnalyticsOverview;
    program_effectiveness?: ProgramEffectiveness[];
    salary_analysis?: SalaryAnalysis;
    industry_placement?: IndustryPlacement[];
    demographic_outcomes?: DemographicOutcome[];
    career_paths?: CareerPaths;
    trends?: TrendData[];
}

export interface EngagementMetrics {
    total_users: number;
    active_users: number;
    new_users: number;
    posts_created: number;
    engagement_rate: number;
    connections_made: number;
    events_attended: number;
    user_retention: RetentionData;
}

export interface RetentionData {
    '7_day': number;
    '30_day': number;
}

export interface AlumniActivityData {
    daily_active_users: DailyMetric[];
    post_activity: DailyMetric[];
    engagement_trends: EngagementTrend[];
    feature_usage: FeatureUsage;
    geographic_distribution: GeographicData[];
    graduation_year_activity: YearlyActivity[];
}

export interface DailyMetric {
    date: string;
    count: number;
}

export interface EngagementTrend {
    date: string;
    type: string;
    count: number;
}

export interface FeatureUsage {
    timeline_views: number;
    directory_searches: number;
    job_views: number;
    event_views: number;
    profile_views: number;
}

export interface GeographicData {
    location: string;
    count: number;
}

export interface YearlyActivity {
    graduation_year: number;
    count: number;
}

export interface CommunityHealthData {
    network_density: number;
    group_participation: GroupParticipation[];
    circle_engagement: CircleEngagement[];
    content_quality_score: number;
    user_satisfaction: UserSatisfaction;
    platform_growth_rate: number;
}

export interface GroupParticipation {
    name: string;
    members_count: number;
    posts_count: number;
}

export interface CircleEngagement {
    name: string;
    members_count: number;
    posts_count: number;
}

export interface UserSatisfaction {
    average_rating: number;
    nps_score: number;
    satisfaction_trend: 'increasing' | 'decreasing' | 'stable';
}

export interface PlatformUsageData {
    page_views: PageViewData;
    session_duration: SessionData;
    bounce_rate: number;
    device_breakdown: DeviceBreakdown;
    browser_breakdown: BrowserBreakdown;
    peak_usage_times: PeakUsageData;
    feature_adoption: FeatureAdoption;
}

export interface PageViewData {
    total_views: number;
    unique_views: number;
    daily_breakdown: DailyMetric[];
}

export interface SessionData {
    average_duration: number;
    median_duration: number;
    bounce_sessions: number;
}

export interface DeviceBreakdown {
    desktop: number;
    mobile: number;
    tablet: number;
}

export interface BrowserBreakdown {
    chrome: number;
    firefox: number;
    safari: number;
    edge: number;
    other: number;
}

export interface PeakUsageData {
    hourly: HourlyUsage[];
    daily: DailyUsage;
}

export interface HourlyUsage {
    hour: number;
    usage: number;
}

export interface DailyUsage {
    monday: number;
    tuesday: number;
    wednesday: number;
    thursday: number;
    friday: number;
    saturday: number;
    sunday: number;
}

export interface FeatureAdoption {
    social_timeline: number;
    alumni_directory: number;
    job_matching: number;
    events: number;
    mentorship: number;
}

export interface AnalyticsOverview {
    total_graduates: number;
    employment_rate: number;
    average_salary: number;
    time_to_employment: number;
}

export interface ProgramEffectiveness {
    program_name: string;
    employment_rate: number;
    salary_average: number;
    trend: 'improving' | 'declining' | 'stable';
}

export interface SalaryAnalysis {
    median_salary: number;
    salary_range: SalaryRange;
    by_industry: IndustrySalary[];
    by_experience: ExperienceSalary[];
}

export interface SalaryRange {
    min: number;
    max: number;
    percentile_25: number;
    percentile_75: number;
}

export interface IndustrySalary {
    industry: string;
    median_salary: number;
    count: number;
}

export interface ExperienceSalary {
    years_experience: string;
    median_salary: number;
    count: number;
}

export interface IndustryPlacement {
    industry: string;
    count: number;
    percentage: number;
}

export interface DemographicOutcome {
    demographic: string;
    category: string;
    employment_rate: number;
    average_salary: number;
}

export interface CareerPaths {
    common_progressions: CareerProgression[];
    industry_transitions: IndustryTransition[];
}

export interface CareerProgression {
    from_role: string;
    to_role: string;
    frequency: number;
    average_time: number;
}

export interface IndustryTransition {
    from_industry: string;
    to_industry: string;
    frequency: number;
}

export interface TrendData {
    metric: string;
    data_points: DataPoint[];
    trend_direction: 'up' | 'down' | 'stable';
}

export interface DataPoint {
    period: string;
    value: number;
}

// Directory and Search Interfaces
export interface DirectoryFilters {
    search?: string;
    location?: string;
    graduation_year?: number | YearRange;
    industry?: string | string[];
    skills?: string | string[];
    current_company?: string;
    course?: string;
    institution?: string;
}

export interface YearRange {
    min?: number;
    max?: number;
}

export interface DirectoryResponse {
    data: AlumniProfile[];
    meta: PaginationMeta;
    filters: {
        courses: CourseOption[];
        institutions: InstitutionOption[];
        locations: string[];
        industries: string[];
        skills: string[];
    };
}

export interface CourseOption {
    id: number;
    name: string;
    institution_id: number;
}

export interface InstitutionOption {
    id: number;
    name: string;
    type: string;
}

// Event and Component Props Interfaces
export interface ComponentProps {
    [key: string]: unknown;
}

export interface EngagementEvent {
    type: 'like' | 'comment' | 'share' | 'view';
    post_id?: number;
    user_id?: number;
    metadata?: Record<string, unknown>;
}

export interface ExportConfig {
    format: 'csv' | 'xlsx' | 'json' | 'pdf';
    data_type: 'engagement' | 'users' | 'posts' | 'analytics';
    filters?: Record<string, unknown>;
    date_range?: {
        start: string;
        end: string;
    };
}

export interface ReportConfig {
    name: string;
    metrics: string[];
    filters?: Record<string, unknown>;
    schedule?: {
        frequency: 'daily' | 'weekly' | 'monthly';
        recipients: string[];
    };
}

declare global {
    interface Window {
        Laravel: {
            csrfToken: string;
        };
    }
}

// Component Library Types
export type {
  ComponentCategory,
  AudienceType,
  BackgroundMediaType,
  MediaAsset,
  GradientConfig,
  BackgroundMedia,
  CTAButton,
  StatisticCounter,
  HeroComponentConfig,
  ComponentInstance,
  Component,
  HeroSampleData
} from './components'
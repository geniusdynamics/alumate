// Brand management type definitions for the Alumate platform

export type BrandLogoType = 'primary' | 'secondary' | 'favicon' | 'social';

export interface LogoVariant {
    type: string;
    url: string;
    size: number;
    format: string;
}

export interface UsageGuidelines {
    min_size: number;
    clear_space: number;
    allowed_backgrounds: string[];
    prohibited_uses: string[];
}

export interface BrandLogo {
    id: number;
    tenant_id: string;
    name: string;
    type: BrandLogoType;
    url: string;
    alt: string | null;
    size: number | null;
    mime_type: string | null;
    is_primary: boolean;
    optimized: boolean;
    cdn_url: string | null;
    variants: LogoVariant[];
    usage_guidelines: UsageGuidelines | null;
    created_at: string;
    updated_at: string;
}

export type BrandColorType =
    | 'primary'
    | 'secondary'
    | 'accent'
    | 'neutral'
    | 'warning'
    | 'error'
    | 'success'
    | 'info';

export interface ContrastRatio {
    background: string;
    ratio: number;
    level: 'AA' | 'AAA' | 'fail';
}

export interface AccessibilityInfo {
    wcag_compliant: boolean;
    contrast_issues: string[];
}

export interface BrandColor {
    id: number;
    tenant_id: string;
    name: string;
    value: string;
    type: BrandColorType;
    usage_guidelines: string | null;
    usage_count: number;
    contrast_ratios: ContrastRatio[];
    accessibility: AccessibilityInfo;
    created_at: string;
    updated_at: string;
}

export type BrandFontType = 'system' | 'google' | 'custom';

export type FontLoadingStrategy = 'swap' | 'block' | 'optional';

export interface BrandFont {
    id: number;
    tenant_id: string;
    name: string;
    family: string;
    weights: string[];
    styles: string[];
    is_primary: boolean;
    type: BrandFontType;
    source: string;
    url: string | null;
    fallbacks: string[];
    usage_count: number;
    loading_strategy: FontLoadingStrategy;
    created_at: string;
    updated_at: string;
}

export interface BrandTemplate {
    id: number;
    tenant_id: string;
    name: string;
    description: string | null;
    primary_font: string | null;
    secondary_font: string | null;
    logo_variant: string | null;
    tags: string[];
    is_default: boolean;
    usage_count: number;
    colors: BrandColor[];
    created_at: string;
    updated_at: string;
}

export interface BrandGuidelines {
    id: number;
    tenant_id: string;
    enforce_color_palette: boolean;
    require_contrast_check: boolean;
    min_contrast_ratio: number;
    enforce_font_families: boolean;
    enforce_typography_scale: boolean;
    max_heading_size: number;
    max_body_size: number;
    enforce_logo_placement: boolean;
    min_logo_size: number;
    logo_clear_space: number;
    created_at: string;
    updated_at: string;
}

export interface BrandConfig {
    id: number;
    tenant_id: string;
    name: string;
    description: string | null;
    primary_color: string | null;
    secondary_color: string | null;
    accent_color: string | null;
    primary_font: string | null;
    secondary_font: string | null;
    logo_url: string | null;
    favicon_url: string | null;
    social_image_url: string | null;
    custom_css: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

// Create/Update data interfaces
export interface BrandLogoCreateData {
    name: string;
    type: BrandLogoType;
    url: string;
    alt?: string;
    is_primary?: boolean;
}

export interface BrandColorCreateData {
    name: string;
    value: string;
    type: BrandColorType;
    usage_guidelines?: string;
}

export interface BrandFontCreateData {
    name: string;
    family: string;
    weights: string[];
    styles?: string[];
    type: BrandFontType;
    source: string;
    fallbacks?: string[];
    loading_strategy?: FontLoadingStrategy;
}

export interface BrandTemplateCreateData {
    name: string;
    description?: string;
    primary_font?: string;
    secondary_font?: string;
    logo_variant?: string;
    tags?: string[];
    is_default?: boolean;
    color_ids?: number[];
}

export interface BrandGuidelinesUpdateData {
    enforce_color_palette?: boolean;
    require_contrast_check?: boolean;
    min_contrast_ratio?: number;
    enforce_font_families?: boolean;
    enforce_typography_scale?: boolean;
    max_heading_size?: number;
    max_body_size?: number;
    enforce_logo_placement?: boolean;
    min_logo_size?: number;
    logo_clear_space?: number;
}

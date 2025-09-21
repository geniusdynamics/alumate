/**
 * Analytics Types
 *
 * TypeScript definitions for analytics data structures including heat maps,
 * tracking events, and API responses.
 */

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

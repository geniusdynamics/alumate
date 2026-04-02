import type {
    TemplateMetrics,
    LandingPageMetrics,
    BrandMetrics,
    TemplateABTest,
    TemplateABTestResult,
    TemplateAnalyticsFilters,
} from '@/Types';

const CSRF_TOKEN = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

function headers(extra: Record<string, string> = {}): Record<string, string> {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN(),
        'X-Requested-With': 'XMLHttpRequest',
        ...extra,
    };
}

async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await fetch(url, { headers: headers(), ...options });

    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: response.statusText }));
        throw new Error(error.message ?? `HTTP ${response.status}`);
    }

    return response.json();
}

export class AnalyticsAPIService {
    private baseUrl = '/api/analytics';

    // Template Analytics
    async getTemplateAnalytics(templateId: number, filters: TemplateAnalyticsFilters = {}): Promise<TemplateMetrics> {
        const params = this.buildParams(filters);
        return request<TemplateMetrics>(`${this.baseUrl}/templates/${templateId}?${params.toString()}`);
    }

    async trackTemplateUsage(templateId: number, context: string = 'view'): Promise<void> {
        return request<void>(`${this.baseUrl}/templates/${templateId}/track`, {
            method: 'POST',
            body: JSON.stringify({ context }),
        });
    }

    // Landing Page Analytics
    async getLandingPageAnalytics(pageId: number, filters: TemplateAnalyticsFilters = {}): Promise<LandingPageMetrics> {
        const params = this.buildParams(filters);
        return request<LandingPageMetrics>(`${this.baseUrl}/landing-pages/${pageId}?${params.toString()}`);
    }

    async trackConversion(pageId: number, type: string = 'form_submit'): Promise<void> {
        return request<void>(`${this.baseUrl}/landing-pages/${pageId}/convert`, {
            method: 'POST',
            body: JSON.stringify({ type }),
        });
    }

    // Brand Analytics
    async getBrandAnalytics(): Promise<BrandMetrics> {
        return request<BrandMetrics>(`${this.baseUrl}/brand`);
    }

    // A/B Testing
    async getABTests(): Promise<TemplateABTest[]> {
        return request<TemplateABTest[]>(`${this.baseUrl}/ab-tests`);
    }

    async createABTest(data: {
        name: string;
        template_id: number;
        variants: { name: string; config_modifications: Record<string, unknown> }[];
        conversion_goal: string;
    }): Promise<TemplateABTest> {
        return request<TemplateABTest>(`${this.baseUrl}/ab-tests`, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async startABTest(testId: number): Promise<TemplateABTest> {
        return request<TemplateABTest>(`${this.baseUrl}/ab-tests/${testId}/start`, { method: 'POST' });
    }

    async pauseABTest(testId: number): Promise<TemplateABTest> {
        return request<TemplateABTest>(`${this.baseUrl}/ab-tests/${testId}/pause`, { method: 'POST' });
    }

    async getABTestResults(testId: number): Promise<TemplateABTestResult> {
        return request<TemplateABTestResult>(`${this.baseUrl}/ab-tests/${testId}/results`);
    }

    async declareWinner(testId: number, variantId: number): Promise<void> {
        return request<void>(`${this.baseUrl}/ab-tests/${testId}/winner`, {
            method: 'POST',
            body: JSON.stringify({ variant_id: variantId }),
        });
    }

    private buildParams(filters: TemplateAnalyticsFilters): URLSearchParams {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                if (typeof value === 'object') {
                    params.append(key, JSON.stringify(value));
                } else {
                    params.append(key, String(value));
                }
            }
        });
        return params;
    }
}

export const analyticsApi = new AnalyticsAPIService();

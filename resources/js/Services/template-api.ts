import type {
    Template,
    TemplateFilters,
    TemplateCreateData,
    PaginatedResponse,
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
    const response = await fetch(url, {
        headers: headers(),
        ...options,
    });

    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: response.statusText }));
        throw new Error(error.message ?? `HTTP ${response.status}`);
    }

    return response.json();
}

export class TemplateAPIService {
    private baseUrl = '/api/templates';

    async getTemplates(filters: TemplateFilters = {}): Promise<PaginatedResponse<Template>> {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                params.append(key, String(value));
            }
        });
        return request<PaginatedResponse<Template>>(`${this.baseUrl}?${params.toString()}`);
    }

    async getTemplate(id: number): Promise<Template> {
        return request<Template>(`${this.baseUrl}/${id}`);
    }

    async createTemplate(data: TemplateCreateData): Promise<Template> {
        return request<Template>(this.baseUrl, {
            method: 'POST',
            body: JSON.stringify(data),
        });
    }

    async updateTemplate(id: number, data: Partial<TemplateCreateData>): Promise<Template> {
        return request<Template>(`${this.baseUrl}/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
        });
    }

    async deleteTemplate(id: number): Promise<void> {
        return request<void>(`${this.baseUrl}/${id}`, { method: 'DELETE' });
    }

    async duplicateTemplate(id: number, modifications: Record<string, unknown> = {}): Promise<Template> {
        return request<Template>(`${this.baseUrl}/${id}/duplicate`, {
            method: 'POST',
            body: JSON.stringify(modifications),
        });
    }

    async searchTemplates(query: string, filters: TemplateFilters = {}): Promise<Template[]> {
        const params = new URLSearchParams({ search: query });
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                params.append(key, String(value));
            }
        });
        return request<Template[]>(`${this.baseUrl}/search?${params.toString()}`);
    }

    async getTemplateCategories(): Promise<string[]> {
        return request<string[]>(`${this.baseUrl}/categories`);
    }

    async getTemplateRecommendations(campaignType: string): Promise<Template[]> {
        return request<Template[]>(`${this.baseUrl}/recommendations?campaign_type=${campaignType}`);
    }
}

export const templateApi = new TemplateAPIService();

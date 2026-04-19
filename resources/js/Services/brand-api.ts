import type {
    BrandLogo,
    BrandColor,
    BrandFont,
    BrandTemplate,
    BrandGuidelines,
    BrandLogoCreateData,
    BrandColorCreateData,
    BrandFontCreateData,
    BrandGuidelinesUpdateData,
} from '@/Types';

const CSRF_TOKEN = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

function headers(extra: Record<string, string> = {}): Record<string, string> {
    return {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF_TOKEN(),
        'X-Requested-With': 'XMLHttpRequest',
        ...extra,
    };
}

function jsonHeaders(extra: Record<string, string> = {}): Record<string, string> {
    return {
        ...headers(extra),
        'Content-Type': 'application/json',
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

export class BrandAPIService {
    private baseUrl = '/api/brand';

    // Logos
    async getLogos(): Promise<BrandLogo[]> {
        return request<BrandLogo[]>(`${this.baseUrl}/logos`);
    }

    async uploadLogo(formData: FormData): Promise<BrandLogo> {
        const response = await fetch(`${this.baseUrl}/logos`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({ message: response.statusText }));
            throw new Error(error.message ?? 'Upload failed');
        }

        return response.json();
    }

    async updateLogo(id: number, data: Partial<BrandLogoCreateData>): Promise<BrandLogo> {
        return request<BrandLogo>(`${this.baseUrl}/logos/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    async deleteLogo(id: number): Promise<void> {
        return request<void>(`${this.baseUrl}/logos/${id}`, { method: 'DELETE' });
    }

    async setPrimaryLogo(id: number): Promise<BrandLogo> {
        return request<BrandLogo>(`${this.baseUrl}/logos/${id}/primary`, { method: 'POST' });
    }

    // Colors
    async getColors(): Promise<BrandColor[]> {
        return request<BrandColor[]>(`${this.baseUrl}/colors`);
    }

    async createColor(data: BrandColorCreateData): Promise<BrandColor> {
        return request<BrandColor>(`${this.baseUrl}/colors`, {
            method: 'POST',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    async updateColor(id: number, data: Partial<BrandColorCreateData>): Promise<BrandColor> {
        return request<BrandColor>(`${this.baseUrl}/colors/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    async deleteColor(id: number): Promise<void> {
        return request<void>(`${this.baseUrl}/colors/${id}`, { method: 'DELETE' });
    }

    // Fonts
    async getFonts(): Promise<BrandFont[]> {
        return request<BrandFont[]>(`${this.baseUrl}/fonts`);
    }

    async createFont(data: BrandFontCreateData): Promise<BrandFont> {
        return request<BrandFont>(`${this.baseUrl}/fonts`, {
            method: 'POST',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    async updateFont(id: number, data: Partial<BrandFontCreateData>): Promise<BrandFont> {
        return request<BrandFont>(`${this.baseUrl}/fonts/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    async deleteFont(id: number): Promise<void> {
        return request<void>(`${this.baseUrl}/fonts/${id}`, { method: 'DELETE' });
    }

    async setPrimaryFont(id: number): Promise<BrandFont> {
        return request<BrandFont>(`${this.baseUrl}/fonts/${id}/primary`, { method: 'POST' });
    }

    // Brand Templates
    async getBrandTemplates(): Promise<BrandTemplate[]> {
        return request<BrandTemplate[]>(`${this.baseUrl}/templates`);
    }

    async createBrandTemplate(data: { name: string; description?: string }): Promise<BrandTemplate> {
        return request<BrandTemplate>(`${this.baseUrl}/templates`, {
            method: 'POST',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    async updateBrandTemplate(id: number, data: Record<string, unknown>): Promise<BrandTemplate> {
        return request<BrandTemplate>(`${this.baseUrl}/templates/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    async deleteBrandTemplate(id: number): Promise<void> {
        return request<void>(`${this.baseUrl}/templates/${id}`, { method: 'DELETE' });
    }

    // Guidelines
    async getGuidelines(): Promise<BrandGuidelines> {
        return request<BrandGuidelines>(`${this.baseUrl}/guidelines`);
    }

    async updateGuidelines(data: Partial<BrandGuidelinesUpdateData>): Promise<BrandGuidelines> {
        return request<BrandGuidelines>(`${this.baseUrl}/guidelines`, {
            method: 'PUT',
            body: JSON.stringify(data),
            headers: jsonHeaders(),
        });
    }

    // Consistency Check
    async runConsistencyCheck(): Promise<{ issues: unknown[]; score: number }> {
        return request(`${this.baseUrl}/consistency-check`, { method: 'POST' });
    }
}

export const brandApi = new BrandAPIService();

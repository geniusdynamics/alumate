import { httpService } from './httpService';
import type {
  Template,
  TemplateCollection,
  TemplateSearchParams,
  TemplateFilterOptions,
  TemplateUsageStats,
  TemplatePreviewConfig
} from '@/types/components';

class TemplateService {
  private readonly baseUrl = '/api/templates';

  /**
   * Fetch templates with optional filtering and pagination
   */
  async fetchTemplates(params: TemplateSearchParams): Promise<TemplateCollection> {
    const queryParams = new URLSearchParams();

    // Add filters
    if (params.filters.category?.length) {
      queryParams.append('category', params.filters.category.join(','));
    }
    if (params.filters.audienceType?.length) {
      queryParams.append('audience_type', params.filters.audienceType.join(','));
    }
    if (params.filters.campaignType?.length) {
      queryParams.append('campaign_type', params.filters.campaignType.join(','));
    }
    if (params.filters.tags?.length) {
      queryParams.append('tags', params.filters.tags.join(','));
    }
    if (params.filters.isPremium !== undefined) {
      queryParams.append('is_premium', params.filters.isPremium.toString());
    }
    if (params.filters.searchQuery) {
      queryParams.append('search', params.filters.searchQuery);
    }

    // Add pagination
    queryParams.append('page', params.page.toString());
    queryParams.append('per_page', params.perPage.toString());
    queryParams.append('sort_by', params.sortBy);
    queryParams.append('sort_order', params.sortOrder);

    const response = await httpService.get<TemplateCollection>(
      `${this.baseUrl}?${queryParams.toString()}`
    );

    return response.data;
  }

  /**
   * Fetch a single template by ID
   */
  async fetchTemplate(id: number): Promise<Template> {
    const response = await httpService.get<Template>(`${this.baseUrl}/${id}`);
    return response.data;
  }

  /**
   * Fetch template usage statistics
   */
  async fetchTemplateStats(id: number): Promise<TemplateUsageStats> {
    const response = await httpService.get<TemplateUsageStats>(`${this.baseUrl}/${id}/stats`);
    return response.data;
  }

  /**
   * Update template usage (increment usage count)
   */
  async updateTemplateUsage(id: number): Promise<void> {
    await httpService.post(`${this.baseUrl}/${id}/usage`);
  }

  /**
   * Search templates with debounced query
   */
  async searchTemplates(query: string, filters?: Partial<TemplateFilterOptions>): Promise<Template[]> {
    const queryParams = new URLSearchParams();
    queryParams.append('search', query);
    queryParams.append('search_only', 'true');

    if (filters?.category?.length) {
      queryParams.append('category', filters.category.join(','));
    }

    const response = await httpService.get<{ data: Template[] }>(
      `${this.baseUrl}/search?${queryParams.toString()}`
    );

    return response.data.data;
  }

  /**
   * Get template categories for filtering
   */
  async fetchCategories(): Promise<{ value: string; label: string; count: number }[]> {
    const response = await httpService.get<{ data: Array<{ value: string; label: string; count: number }> }>(
      `${this.baseUrl}/categories`
    );
    return response.data.data;
  }

  /**
   * Get template tags for filtering
   */
  async fetchTags(): Promise<{ value: string; label: string; count: number }[]> {
    const response = await httpService.get<{ data: Array<{ value: string; label: string; count: number }> }>(
      `${this.baseUrl}/tags`
    );
    return response.data.data;
  }

  /**
   * Generate template preview data with brand customization
   */
  async generatePreview(templateId: number | string, config: TemplatePreviewConfig): Promise<{
    html: string;
    css: string;
    data: any;
    responsive_previews?: {
      desktop: { html: string; width: number };
      tablet: { html: string; width: number };
      mobile: { html: string; width: number };
    };
  }> {
    const response = await httpService.post<{
      html: string;
      css: string;
      data: any;
      responsive_previews?: {
        desktop: { html: string; width: number };
        tablet: { html: string; width: number };
        mobile: { html: string; width: number };
      };
    }>(`${this.baseUrl}/${templateId}/preview`, config);

    return response.data;
  }

  /**
   * Get responsive preview data for multiple viewports
   */
  async generateResponsivePreview(templateId: number | string, customConfig: any = {}): Promise<{
    responsivePreviews: {
      [viewport: string]: {
        html: string;
        width: number;
        height: number;
      };
    };
    assets: {
      styles: string[];
      scripts: string[];
      css: string;
    };
  }> {
    const response = await httpService.post<{
      responsivePreviews: {
        [viewport: string]: {
          html: string;
          width: number;
          height: number;
        };
      };
      assets: {
        styles: string[];
        scripts: string[];
        css: string;
      };
    }>(`${this.baseUrl}/${templateId}/responsive-preview`, { customConfig });

    return response.data;
  }

  /**
   * Check if template is accessible (for tenant isolation)
   */
  async checkTemplateAccess(templateId: number): Promise<boolean> {
    try {
      await httpService.get(`${this.baseUrl}/${templateId}/access`);
      return true;
    } catch {
      return false;
    }
  }

  /**
   * Favorite/unfavorite a template
   */
  async toggleFavorite(templateId: number): Promise<{ is_favorited: boolean }> {
    const response = await httpService.post<{ is_favorited: boolean }>(
      `${this.baseUrl}/${templateId}/favorite`
    );
    return response.data;
  }

  /**
   * Get favorited templates
   */
  async fetchFavoritedTemplates(): Promise<Template[]> {
    const response = await httpService.get<{ data: Template[] }>(`${this.baseUrl}/favorites`);
    return response.data.data;
  }

  /**
   * Get recently used templates
   */
  async fetchRecentlyUsed(limit: number = 10): Promise<Template[]> {
    const response = await httpService.get<{ data: Template[] }>(
      `${this.baseUrl}/recent?limit=${limit}`
    );
    return response.data.data;
  }

  /**
   * Validate template configuration
   */
  async validateTemplate(templateId: number, config: any): Promise<{
    valid: boolean;
    errors?: string[];
    warnings?: string[];
  }> {
    const response = await httpService.post<{
      valid: boolean;
      errors?: string[];
      warnings?: string[];
    }>(`${this.baseUrl}/${templateId}/validate`, { config });

    return response.data;
  }
}

export const templateService = new TemplateService();
export default templateService;
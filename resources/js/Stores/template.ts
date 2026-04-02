import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type {
    Template,
    TemplateFilters,
    TemplateCreateData,
    TemplateUpdateData,
    PaginatedResponse,
} from '@/Types';

export const useTemplateStore = defineStore('template', () => {
    const templates = ref<Template[]>([]);
    const currentTemplate = ref<Template | null>(null);
    const selectedTemplate = ref<Template | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    const hasTemplates = computed(() => templates.value.length > 0);
    const hasCurrentTemplate = computed(() => currentTemplate.value !== null);

    async function fetchTemplates(filters: TemplateFilters = {}) {
        loading.value = true;
        error.value = null;
        try {
            const params = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    params.append(key, String(value));
                }
            });

            const response = await fetch(`/api/templates?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch templates');

            const data = await response.json();
            templates.value = data.data ?? data;
            if (data.meta) {
                pagination.value = data.meta;
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function fetchTemplate(id: number) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/templates/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch template');

            currentTemplate.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function createTemplate(data: TemplateCreateData): Promise<Template | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const errData = await response.json();
                throw new Error(errData.message ?? 'Failed to create template');
            }

            const template = await response.json();
            templates.value.unshift(template);
            return template;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function updateTemplate(id: number, data: Partial<TemplateUpdateData>): Promise<Template | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/templates/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) throw new Error('Failed to update template');

            const template = await response.json();
            const index = templates.value.findIndex(t => t.id === id);
            if (index !== -1) {
                templates.value[index] = template;
            }
            if (currentTemplate.value?.id === id) {
                currentTemplate.value = template;
            }
            return template;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function deleteTemplate(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/templates/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to delete template');

            templates.value = templates.value.filter(t => t.id !== id);
            if (currentTemplate.value?.id === id) {
                currentTemplate.value = null;
            }
            if (selectedTemplate.value?.id === id) {
                selectedTemplate.value = null;
            }
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function duplicateTemplate(id: number, modifications: Record<string, unknown> = {}): Promise<Template | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/templates/${id}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(modifications),
            });

            if (!response.ok) throw new Error('Failed to duplicate template');

            const template = await response.json();
            templates.value.unshift(template);
            return template;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    function selectTemplate(template: Template) {
        selectedTemplate.value = template;
    }

    function clearSelectedTemplate() {
        selectedTemplate.value = null;
    }

    function setCurrentTemplate(template: Template) {
        currentTemplate.value = template;
    }

    function clearCurrentTemplate() {
        currentTemplate.value = null;
    }

    function clearError() {
        error.value = null;
    }

    return {
        templates,
        currentTemplate,
        selectedTemplate,
        loading,
        error,
        pagination,
        hasTemplates,
        hasCurrentTemplate,
        fetchTemplates,
        fetchTemplate,
        createTemplate,
        updateTemplate,
        deleteTemplate,
        duplicateTemplate,
        selectTemplate,
        clearSelectedTemplate,
        setCurrentTemplate,
        clearCurrentTemplate,
        clearError,
    };
});

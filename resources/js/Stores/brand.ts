import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type {
    BrandLogo,
    BrandColor,
    BrandFont,
    BrandTemplate,
    BrandGuidelines,
    BrandGuidelinesUpdateData,
} from '@/Types';

export const useBrandStore = defineStore('brand', () => {
    const logos = ref<BrandLogo[]>([]);
    const colors = ref<BrandColor[]>([]);
    const fonts = ref<BrandFont[]>([]);
    const templates = ref<BrandTemplate[]>([]);
    const guidelines = ref<BrandGuidelines | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const primaryLogo = computed(() => logos.value.find(l => l.is_primary) ?? null);
    const primaryFont = computed(() => fonts.value.find(f => f.is_primary) ?? null);
    const hasBrandAssets = computed(() =>
        logos.value.length > 0 || colors.value.length > 0 || fonts.value.length > 0
    );

    async function fetchBrandAssets() {
        loading.value = true;
        error.value = null;
        try {
            const [logosRes, colorsRes, fontsRes, templatesRes, guidelinesRes] = await Promise.all([
                fetch('/api/brand/logos', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }),
                fetch('/api/brand/colors', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }),
                fetch('/api/brand/fonts', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }),
                fetch('/api/brand/templates', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }),
                fetch('/api/brand/guidelines', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }),
            ]);

            if (logosRes.ok) logos.value = await logosRes.json();
            if (colorsRes.ok) colors.value = await colorsRes.json();
            if (fontsRes.ok) fonts.value = await fontsRes.json();
            if (templatesRes.ok) templates.value = await templatesRes.json();
            if (guidelinesRes.ok) guidelines.value = await guidelinesRes.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch brand assets';
        } finally {
            loading.value = false;
        }
    }

    async function uploadLogo(formData: FormData): Promise<BrandLogo | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/brand/logos', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            if (!response.ok) throw new Error('Failed to upload logo');

            const logo = await response.json();
            logos.value.unshift(logo);
            return logo;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function deleteLogo(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/brand/logos/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to delete logo');

            logos.value = logos.value.filter(l => l.id !== id);
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function setPrimaryLogo(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/brand/logos/${id}/primary`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to set primary logo');

            logos.value = logos.value.map(l => ({
                ...l,
                is_primary: l.id === id,
            }));
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function createColor(data: { name: string; value: string; type: string }): Promise<BrandColor | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/brand/colors', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) throw new Error('Failed to create color');

            const color = await response.json();
            colors.value.unshift(color);
            return color;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function deleteColor(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/brand/colors/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to delete color');

            colors.value = colors.value.filter(c => c.id !== id);
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function createFont(data: { name: string; family: string; type: string }): Promise<BrandFont | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/brand/fonts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) throw new Error('Failed to create font');

            const font = await response.json();
            fonts.value.unshift(font);
            return font;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function deleteFont(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/brand/fonts/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to delete font');

            fonts.value = fonts.value.filter(f => f.id !== id);
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function updateGuidelines(data: Partial<BrandGuidelinesUpdateData>): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/brand/guidelines', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) throw new Error('Failed to update guidelines');

            guidelines.value = await response.json();
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    function clearError() {
        error.value = null;
    }

    return {
        logos,
        colors,
        fonts,
        templates,
        guidelines,
        loading,
        error,
        primaryLogo,
        primaryFont,
        hasBrandAssets,
        fetchBrandAssets,
        uploadLogo,
        deleteLogo,
        setPrimaryLogo,
        createColor,
        deleteColor,
        createFont,
        deleteFont,
        updateGuidelines,
        clearError,
    };
});

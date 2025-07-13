import { router } from '@inertiajs/vue3';

interface FilterParams {
    search?: string;
    sort_column?: string;
    sort_direction?: string;
    per_page?: number;
    page?: number;
    [key: string]: any;
}

export const useDataTable = () => {
    const loading = ref(false);

    const getRoute = (routeName?: string): string => {
        return routeName || window.location.pathname;
    };

    const handleSearch = (query: string, filters: FilterParams, routeName?: string) => {
        loading.value = true;
        router.get(
            getRoute(routeName),
            { ...filters, search: query },
            {
                preserveState: true,
                replace: true,
                onFinish: () => (loading.value = false),
            },
        );
    };

    const handleSort = (column: string, direction: 'asc' | 'desc', filters: FilterParams, routeName?: string) => {
        loading.value = true;
        router.get(
            getRoute(routeName),
            { ...filters, sort_column: column, sort_direction: direction },
            {
                preserveState: true,
                replace: true,
                onFinish: () => (loading.value = false),
            },
        );
    };

    const handlePaginate = (page: number, filters: FilterParams, routeName?: string) => {
        loading.value = true;
        router.get(
            getRoute(routeName),
            { ...filters, page },
            {
                preserveState: true,
                replace: true,
                onFinish: () => (loading.value = false),
            },
        );
    };

    const handlePerPageChange = (perPage: number, filters: FilterParams, routeName?: string) => {
        loading.value = true;
        router.get(
            getRoute(routeName),
            { ...filters, per_page: perPage },
            {
                preserveState: true,
                replace: true,
                onFinish: () => (loading.value = false),
            },
        );
    };

    return {
        loading,
        handleSearch,
        handleSort,
        handlePaginate,
        handlePerPageChange,
    };
};

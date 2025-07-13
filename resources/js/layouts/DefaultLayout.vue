<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Sidebar } from '@/components/ui/sidebar';
import AppHeader from '@/components/layout/AppHeader.vue';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    title?: string;
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    title: 'Dashboard',
    breadcrumbs: () => [],
});
</script>

<template>
    <div>
        <Head :title="title" />
        
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <AppHeader :breadcrumbs="breadcrumbs" />
                <div class="flex flex-1 flex-col gap-4 p-4">
                    <slot />
                </div>
            </SidebarInset>
        </SidebarProvider>
    </div>
</template>


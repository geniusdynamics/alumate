<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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

const page = usePage();
const flash = computed(() => page.props.flash);
</script>

<template>
    <div>
        <Head :title="title" />
        
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <AppHeader :breadcrumbs="breadcrumbs" />
                <div class="flex flex-1 flex-col gap-4 p-4">
                    <div v-if="flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Success!</strong>
                        <span class="block sm:inline">{{ flash.success }}</span>
                    </div>
                    <slot />
                </div>
            </SidebarInset>
        </SidebarProvider>
    </div>
</template>


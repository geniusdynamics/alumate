<script setup lang="ts">
import AppContent from '@/Components/AppContent.vue';
import AppShell from '@/Components/AppShell.vue';
import AppSidebarHeader from '@/Components/AppSidebarHeader.vue';
import MainSidebar from '@/Components/MainSidebar.vue';
<<<<<<< HEAD:resources/js/layouts/app/AppSidebarLayout.vue
import type { BreadcrumbItemType } from '@/Types';
=======
import type { BreadcrumbItemType } from '@/types';
>>>>>>> origin/db1:resources/js/Layouts/app/AppSidebarLayout.vue

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <MainSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
















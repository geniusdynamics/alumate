<script setup lang="ts">
import AppContent from '@/Components/AppContent.vue';
import AppHeader from '@/Components/AppHeader.vue';
import AppShell from '@/Components/AppShell.vue';
<<<<<<< HEAD:resources/js/layouts/app/AppHeaderLayout.vue
import type { BreadcrumbItemType } from '@/Types';
=======
import type { BreadcrumbItemType } from '@/types';
>>>>>>> origin/db1:resources/js/Layouts/app/AppHeaderLayout.vue

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
















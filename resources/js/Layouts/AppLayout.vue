<script setup lang="ts">
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
<<<<<<< HEAD:resources/js/layouts/AppLayout.vue
import type { BreadcrumbItemType } from '@/Types';
=======
import type { BreadcrumbItemType } from '@/types';
>>>>>>> origin/db1:resources/js/Layouts/AppLayout.vue

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
    <DefaultLayout :title="title" :breadcrumbs="breadcrumbs">
        <slot />
    </DefaultLayout>
</template>
















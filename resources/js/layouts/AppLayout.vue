<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue';
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
    <DefaultLayout :title="title" :breadcrumbs="breadcrumbs">
        <slot />
    </DefaultLayout>
</template>

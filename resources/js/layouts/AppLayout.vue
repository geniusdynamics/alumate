<script setup lang="ts">
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import type { BreadcrumbItemType } from '@/Types';

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




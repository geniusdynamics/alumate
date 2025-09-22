<template>
    <div :class="cn('animate-pulse rounded-md bg-muted', className)" v-bind="$attrs">
        <slot />
    </div>
</template>

<script setup lang="ts">
import { cn } from '@/utils/cn';

interface Props {
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    className: '',
});
</script>

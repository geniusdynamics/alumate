<template>
    <div
        :class="cn('relative h-4 w-full overflow-hidden rounded-full bg-secondary', className)"
        v-bind="$attrs"
    >
        <div
            class="h-full w-full flex-1 bg-primary transition-all duration-300 ease-in-out"
            :style="{ transform: `translateX(-${100 - (value || 0)}%)` }"
        />
    </div>
</template>

<script setup lang="ts">
import { cn } from '@/utils/cn';

interface Props {
    value?: number;
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    value: 0,
    className: '',
});
</script>

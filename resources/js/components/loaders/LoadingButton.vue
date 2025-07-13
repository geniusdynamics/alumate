<template>
    <Button
        :disabled="loading || disabled"
        :class="cn(buttonVariants({ variant, size }), className)"
        v-bind="$attrs"
        @click="handleClick"
    >
        <Spinner v-if="loading" size="sm" class="mr-2" />
        <slot v-if="!loading || showTextWhenLoading" />
        <span v-else-if="loadingText">{{ loadingText }}</span>
    </Button>
</template>

<script setup lang="ts">
import { Button, buttonVariants } from '@/components/ui/button';
import Spinner from './Spinner.vue';
import { cn } from '@/utils/cn';
import type { VariantProps } from 'class-variance-authority';

interface Props extends VariantProps<typeof buttonVariants> {
    loading?: boolean;
    disabled?: boolean;
    loadingText?: string;
    showTextWhenLoading?: boolean;
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    disabled: false,
    loadingText: '',
    showTextWhenLoading: false,
    className: '',
    variant: 'default',
    size: 'default',
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const handleClick = (event: MouseEvent) => {
    if (!props.loading && !props.disabled) {
        emit('click', event);
    }
};
</script>

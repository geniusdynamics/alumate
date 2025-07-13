<script setup lang="ts">
interface Props {
    as?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl' | 'full';
    padding?: 'none' | 'sm' | 'md' | 'lg' | 'xl';
    center?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'div',
    maxWidth: '7xl',
    padding: 'md',
    center: true,
});

const maxWidthClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    '6xl': 'max-w-6xl',
    '7xl': 'max-w-7xl',
    full: 'max-w-full',
};

const paddingClasses = {
    none: '',
    sm: 'px-4 py-2',
    md: 'px-6 py-4',
    lg: 'px-8 py-6',
    xl: 'px-12 py-8',
};

const containerClasses = [
    'w-full',
    maxWidthClasses[props.maxWidth],
    paddingClasses[props.padding],
    props.center ? 'mx-auto' : '',
].filter(Boolean).join(' ');
</script>

<template>
    <component :is="as" :class="containerClasses">
        <slot />
    </component>
</template>

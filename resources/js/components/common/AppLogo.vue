<script setup lang="ts">
import AppLogoIcon from './AppLogoIcon.vue';

interface Props {
    showText?: boolean;
    class?: string;
}

withDefaults(defineProps<Props>(), {
    showText: true,
    class: '',
});
</script>

<template>
    <div :class="['flex items-center', $props.class]">
        <AppLogoIcon class="h-8 w-8" />
        <span v-if="showText" class="ml-2 text-xl font-bold text-gray-900">
            Starter Kit
        </span>
    </div>
</template>


<template>
    <label :for="for" :class="classes">
        <span v-if="value">{{ value }}</span>
        <slot v-else />
    </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    for: String,
    value: String,
});

const classes = computed(() => [
    'block font-medium text-sm text-gray-700 dark:text-gray-300',
]);
</script>
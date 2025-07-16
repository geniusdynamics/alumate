<template>
    <input
        :id="id"
        ref="input"
        :value="modelValue"
        :type="type"
        :class="classes"
        :required="required"
        :autocomplete="autocomplete"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    id: String,
    modelValue: String,
    type: {
        type: String,
        default: 'text',
    },
    required: Boolean,
    autocomplete: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

const classes = computed(() => [
    'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full',
]);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>
<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from './InputError.vue';

interface Props {
    label: string;
    id: string;
    type?: string;
    required?: boolean;
    autofocus?: boolean;
    tabindex?: number;
    autocomplete?: string;
    placeholder?: string;
    error?: string;
    modelValue?: string;
}

withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    autofocus: false,
    tabindex: undefined,
    autocomplete: undefined,
    placeholder: undefined,
    error: undefined,
    modelValue: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const handleInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <div class="space-y-2">
        <Label :for="id" class="text-sm font-medium">
            {{ label }}
        </Label>
        <Input
            :id="id"
            :type="type"
            :required="required"
            :autofocus="autofocus"
            :tabindex="tabindex"
            :autocomplete="autocomplete"
            :placeholder="placeholder"
            :value="modelValue"
            @input="handleInput"
            class="w-full"
        />
        <InputError :message="error" />
    </div>
</template>

<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    modelValue?: string;
    placeholder?: string;
    debounceMs?: number;
    showClearButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Search...',
    debounceMs: 300,
    showClearButton: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'search': [value: string];
    'clear': [];
}>();

const inputValue = ref(props.modelValue);
let debounceTimer: NodeJS.Timeout | null = null;

const handleInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const value = target.value;
    
    inputValue.value = value;
    emit('update:modelValue', value);
    
    // Clear existing timer
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    
    // Set new timer
    debounceTimer = setTimeout(() => {
        emit('search', value);
    }, props.debounceMs);
};

const clearSearch = () => {
    inputValue.value = '';
    emit('update:modelValue', '');
    emit('clear');
    
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
};

watch(() => props.modelValue, (newValue) => {
    inputValue.value = newValue;
});
</script>

<template>
    <div class="relative">
        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
            :value="inputValue"
            @input="handleInput"
            :placeholder="placeholder"
            class="pl-10"
            :class="{ 'pr-10': showClearButton && inputValue }"
        />
        <Button
            v-if="showClearButton && inputValue"
            variant="ghost"
            size="sm"
            @click="clearSearch"
            class="absolute right-1 top-1/2 transform -translate-y-1/2 h-8 w-8 p-0 hover:bg-transparent"
        >
            <X class="h-4 w-4" />
        </Button>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
    isLoading?: boolean;
    submitText: string;
    disabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    isLoading: false,
    disabled: false,
});

const emit = defineEmits<{
    submit: [];
}>();

const handleSubmit = (e: Event) => {
    e.preventDefault();
    emit('submit');
};
</script>

<template>
    <form @submit="handleSubmit" class="space-y-6">
        <div class="space-y-4">
            <slot name="fields" />
        </div>
        
        <div class="space-y-4">
            <Button 
                type="submit" 
                class="w-full" 
                :disabled="disabled || isLoading"
            >
                <LoaderCircle v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                {{ submitText }}
            </Button>
            
            <slot name="footer" />
        </div>
    </form>
</template>

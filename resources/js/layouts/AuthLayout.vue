<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLogo from '@/components/common/AppLogo.vue';

interface Props {
    title?: string;
    description?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Authentication',
    description: '',
});
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8 bg-background">
        <Head :title="title" />
        
        <div class="w-full max-w-md mx-auto">
            <div class="flex flex-col items-center space-y-6">
                <div class="flex justify-center">
                    <AppLogo class="h-10 w-auto" />
                </div>
                
                <div class="text-center space-y-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                
                <div class="w-full bg-card border rounded-lg p-6 shadow-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

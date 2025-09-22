<script setup lang="ts">
import AppLogo from '@/Components/common/AppLogo.vue';
import { Head } from '@inertiajs/vue3';

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
    <div class="flex min-h-screen flex-col justify-center bg-background px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <Head :title="title" />

        <div class="mx-auto w-full max-w-md">
            <div class="flex flex-col items-center space-y-6">
                <div class="flex justify-center">
                    <AppLogo class="h-10 w-auto" />
                </div>

                <div class="space-y-2 text-center">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <div class="w-full rounded-lg border bg-card p-6 shadow-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

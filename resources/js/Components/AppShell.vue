<script setup lang="ts">
import { SidebarProvider } from '@/Components/ui/sidebar';
<<<<<<< HEAD:resources/js/components/AppShell.vue
import { SharedData } from '@/Types';
=======
import { SharedData } from '@/types';
>>>>>>> origin/db1:resources/js/Components/AppShell.vue
import { usePage } from '@inertiajs/vue3';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const isOpen = usePage<SharedData>().props.sidebarOpen;
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
    </SidebarProvider>
</template>
















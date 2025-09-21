<template>
    <section class="success-stories-section">
        <!-- Component placeholder - will be implemented in task 6 -->
        <div class="container mx-auto px-4">
            <h2 class="mb-8 text-center text-3xl font-bold">Alumni Success Stories</h2>
            <!-- Success stories content will be implemented in later tasks -->
        </div>
    </section>
</template>

<script setup lang="ts">
import { AudienceType } from '@/types/homepage';

interface Props {
    audience: AudienceType;
    stories: any[];
}

defineProps<Props>();
</script>

<style scoped>
.success-stories-section {
    @apply bg-gray-50 py-16;
}
</style>

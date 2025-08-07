<template>
  <section class="success-stories-section">
    <!-- Component placeholder - will be implemented in task 6 -->
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-8">Alumni Success Stories</h2>
      <!-- Success stories content will be implemented in later tasks -->
    </div>
  </section>
</template>

<script setup lang="ts">
import { AudienceType } from '@/types/homepage'

interface Props {
  audience: AudienceType
  stories: any[]
}

defineProps<Props>()
</script>

<style scoped>
.success-stories-section {
  @apply py-16 bg-gray-50;
}
</style>
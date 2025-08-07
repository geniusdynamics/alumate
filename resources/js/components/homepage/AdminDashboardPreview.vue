<template>
  <section class="admin-dashboard-preview">
    <!-- Component placeholder - will be implemented in task 8.1 -->
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-8">Admin Dashboard Preview</h2>
      <!-- Admin dashboard preview will be implemented in later tasks -->
    </div>
  </section>
</template>

<script setup lang="ts">
const emit = defineEmits<{
  'demo-request': [data?: any]
}>()
</script>

<style scoped>
.admin-dashboard-preview {
  @apply py-16 bg-white;
}
</style>
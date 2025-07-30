<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 animate-pulse">
    <!-- Post header -->
    <div class="flex items-center space-x-3 mb-4">
      <!-- Avatar skeleton -->
      <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
      
      <!-- User info skeleton -->
      <div class="flex-1">
        <div class="h-4 bg-gray-300 rounded w-32 mb-2"></div>
        <div class="h-3 bg-gray-200 rounded w-24"></div>
      </div>
      
      <!-- Menu button skeleton -->
      <div class="w-6 h-6 bg-gray-300 rounded"></div>
    </div>

    <!-- Post content skeleton -->
    <div class="mb-4">
      <div class="h-4 bg-gray-300 rounded w-full mb-2"></div>
      <div class="h-4 bg-gray-300 rounded w-4/5 mb-2"></div>
      <div class="h-4 bg-gray-300 rounded w-3/5"></div>
    </div>

    <!-- Media skeleton (sometimes) -->
    <div v-if="Math.random() > 0.5" class="mb-4">
      <div class="h-48 bg-gray-300 rounded-lg"></div>
    </div>

    <!-- Engagement bar skeleton -->
    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
      <!-- Reaction buttons -->
      <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-1">
          <div class="w-5 h-5 bg-gray-300 rounded"></div>
          <div class="h-3 bg-gray-300 rounded w-6"></div>
        </div>
        <div class="flex items-center space-x-1">
          <div class="w-5 h-5 bg-gray-300 rounded"></div>
          <div class="h-3 bg-gray-300 rounded w-6"></div>
        </div>
        <div class="flex items-center space-x-1">
          <div class="w-5 h-5 bg-gray-300 rounded"></div>
          <div class="h-3 bg-gray-300 rounded w-6"></div>
        </div>
      </div>
      
      <!-- Share/bookmark buttons -->
      <div class="flex items-center space-x-2">
        <div class="w-5 h-5 bg-gray-300 rounded"></div>
        <div class="w-5 h-5 bg-gray-300 rounded"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
// No props or logic needed for skeleton
</script>

<style scoped>
/* Animation for skeleton loading */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
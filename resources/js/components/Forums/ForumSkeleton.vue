<template>
  <div class="bg-white rounded-lg shadow p-6 animate-pulse">
    <div class="flex items-start space-x-4">
      <!-- Forum Icon Skeleton -->
      <div class="flex-shrink-0 w-12 h-12 bg-gray-200 rounded-lg"></div>

      <!-- Forum Info Skeleton -->
      <div class="flex-1 space-y-3">
        <div class="h-6 bg-gray-200 rounded w-3/4"></div>
        <div class="space-y-2">
          <div class="h-4 bg-gray-200 rounded w-full"></div>
          <div class="h-4 bg-gray-200 rounded w-2/3"></div>
        </div>
        <div class="h-5 bg-gray-200 rounded w-1/4"></div>
      </div>

      <!-- Stats Skeleton -->
      <div class="flex-shrink-0 space-y-2">
        <div class="text-center">
          <div class="h-6 w-8 bg-gray-200 rounded mx-auto"></div>
          <div class="h-3 w-12 bg-gray-200 rounded mt-1"></div>
        </div>
      </div>
    </div>

    <!-- Latest Topics Skeleton -->
    <div class="mt-4 pt-4 border-t border-gray-200">
      <div class="h-4 bg-gray-200 rounded w-1/4 mb-2"></div>
      <div class="space-y-2">
        <div v-for="i in 3" :key="i" class="flex justify-between">
          <div class="h-3 bg-gray-200 rounded w-2/3"></div>
          <div class="h-3 bg-gray-200 rounded w-1/4"></div>
        </div>
      </div>
    </div>

    <!-- Last Activity Skeleton -->
    <div class="mt-4 pt-4 border-t border-gray-200">
      <div class="flex justify-between">
        <div class="h-3 bg-gray-200 rounded w-1/4"></div>
        <div class="h-3 bg-gray-200 rounded w-1/6"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
// This is a skeleton loading component for forums
</script>
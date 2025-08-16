<template>
  <div class="user-list-skeleton">
    <div 
      v-for="i in count" 
      :key="i"
      class="user-item-skeleton"
    >
      <div class="avatar-skeleton skeleton-item"></div>
      <div class="content-skeleton">
        <div class="name-skeleton skeleton-item"></div>
        <div class="details-skeleton skeleton-item"></div>
        <div class="meta-skeleton skeleton-item"></div>
      </div>
      <div class="actions-skeleton">
        <div class="button-skeleton skeleton-item"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  count?: number
}

withDefaults(defineProps<Props>(), {
  count: 5
})
</script>

<style scoped>
.user-list-skeleton {
  @apply space-y-4;
}

.user-item-skeleton {
  @apply flex items-center space-x-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow;
}

.avatar-skeleton {
  @apply w-12 h-12 rounded-full flex-shrink-0;
}

.content-skeleton {
  @apply flex-1 space-y-2;
}

.name-skeleton {
  @apply h-4 w-32 rounded;
}

.details-skeleton {
  @apply h-3 w-48 rounded;
}

.meta-skeleton {
  @apply h-3 w-24 rounded;
}

.actions-skeleton {
  @apply flex-shrink-0;
}

.button-skeleton {
  @apply h-8 w-20 rounded;
}
</style>
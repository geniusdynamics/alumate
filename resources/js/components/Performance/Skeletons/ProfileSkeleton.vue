<template>
  <div class="profile-skeleton">
    <!-- Header Section -->
    <div class="profile-header-skeleton">
      <div class="cover-skeleton skeleton-item"></div>
      <div class="profile-info-skeleton">
        <div class="avatar-large-skeleton skeleton-item"></div>
        <div class="name-section-skeleton">
          <div class="name-skeleton skeleton-item"></div>
          <div class="title-skeleton skeleton-item"></div>
          <div class="location-skeleton skeleton-item"></div>
        </div>
        <div class="actions-skeleton">
          <div class="button-skeleton skeleton-item"></div>
          <div class="button-skeleton skeleton-item"></div>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-skeleton">
      <div 
        v-for="i in 4" 
        :key="i"
        class="stat-item-skeleton"
      >
        <div class="stat-number-skeleton skeleton-item"></div>
        <div class="stat-label-skeleton skeleton-item"></div>
      </div>
    </div>

    <!-- Content Sections -->
    <div class="content-sections-skeleton">
      <div class="section-skeleton">
        <div class="section-title-skeleton skeleton-item"></div>
        <div class="section-content-skeleton">
          <div class="content-line-skeleton skeleton-item"></div>
          <div class="content-line-skeleton skeleton-item"></div>
          <div class="content-line-short-skeleton skeleton-item"></div>
        </div>
      </div>

      <div class="section-skeleton">
        <div class="section-title-skeleton skeleton-item"></div>
        <div class="section-content-skeleton">
          <div 
            v-for="i in 3" 
            :key="i"
            class="list-item-skeleton"
          >
            <div class="list-icon-skeleton skeleton-item"></div>
            <div class="list-text-skeleton skeleton-item"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// No props needed for this skeleton
</script>

<style scoped>
.profile-skeleton {
  @apply space-y-6;
}

.profile-header-skeleton {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden;
}

.cover-skeleton {
  @apply h-32 w-full;
}

.profile-info-skeleton {
  @apply relative px-6 pb-6;
}

.avatar-large-skeleton {
  @apply w-24 h-24 rounded-full border-4 border-white dark:border-gray-800 -mt-12 mb-4;
}

.name-section-skeleton {
  @apply space-y-2 mb-4;
}

.name-skeleton {
  @apply h-6 w-48 rounded;
}

.title-skeleton {
  @apply h-4 w-32 rounded;
}

.location-skeleton {
  @apply h-3 w-24 rounded;
}

.actions-skeleton {
  @apply flex space-x-3;
}

.button-skeleton {
  @apply h-9 w-24 rounded;
}

.stats-skeleton {
  @apply grid grid-cols-2 md:grid-cols-4 gap-4 bg-white dark:bg-gray-800 rounded-lg shadow p-6;
}

.stat-item-skeleton {
  @apply text-center space-y-1;
}

.stat-number-skeleton {
  @apply h-6 w-12 mx-auto rounded;
}

.stat-label-skeleton {
  @apply h-3 w-16 mx-auto rounded;
}

.content-sections-skeleton {
  @apply space-y-6;
}

.section-skeleton {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4;
}

.section-title-skeleton {
  @apply h-5 w-32 rounded;
}

.section-content-skeleton {
  @apply space-y-3;
}

.content-line-skeleton {
  @apply h-3 w-full rounded;
}

.content-line-short-skeleton {
  @apply h-3 w-3/4 rounded;
}

.list-item-skeleton {
  @apply flex items-center space-x-3;
}

.list-icon-skeleton {
  @apply w-4 h-4 rounded;
}

.list-text-skeleton {
  @apply h-3 w-32 rounded;
}
</style>
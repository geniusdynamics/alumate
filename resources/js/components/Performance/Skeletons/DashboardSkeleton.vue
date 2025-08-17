<template>
  <div class="dashboard-skeleton">
    <!-- Header -->
    <div class="dashboard-header-skeleton">
      <div class="header-title-skeleton skeleton-item"></div>
      <div class="header-actions-skeleton">
        <div class="action-button-skeleton skeleton-item"></div>
        <div class="action-button-skeleton skeleton-item"></div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid-skeleton">
      <div 
        v-for="i in 4" 
        :key="i"
        class="stat-card-skeleton"
      >
        <div class="stat-icon-skeleton skeleton-item"></div>
        <div class="stat-content-skeleton">
          <div class="stat-value-skeleton skeleton-item"></div>
          <div class="stat-label-skeleton skeleton-item"></div>
          <div class="stat-change-skeleton skeleton-item"></div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid-skeleton">
      <!-- Chart Section -->
      <div class="chart-section-skeleton">
        <div class="chart-header-skeleton">
          <div class="chart-title-skeleton skeleton-item"></div>
          <div class="chart-controls-skeleton">
            <div class="control-skeleton skeleton-item"></div>
            <div class="control-skeleton skeleton-item"></div>
          </div>
        </div>
        <div class="chart-skeleton skeleton-item"></div>
      </div>

      <!-- Activity Feed -->
      <div class="activity-section-skeleton">
        <div class="activity-header-skeleton skeleton-item"></div>
        <div class="activity-list-skeleton">
          <div 
            v-for="i in 5" 
            :key="i"
            class="activity-item-skeleton"
          >
            <div class="activity-icon-skeleton skeleton-item"></div>
            <div class="activity-content-skeleton">
              <div class="activity-text-skeleton skeleton-item"></div>
              <div class="activity-time-skeleton skeleton-item"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section -->
    <div class="bottom-section-skeleton">
      <!-- Table -->
      <div class="table-section-skeleton">
        <div class="table-header-skeleton">
          <div class="table-title-skeleton skeleton-item"></div>
          <div class="table-search-skeleton skeleton-item"></div>
        </div>
        <div class="table-skeleton">
          <!-- Table Headers -->
          <div class="table-row-skeleton">
            <div 
              v-for="i in 4" 
              :key="i"
              class="table-header-cell-skeleton skeleton-item"
            ></div>
          </div>
          <!-- Table Rows -->
          <div 
            v-for="i in 5" 
            :key="i"
            class="table-row-skeleton"
          >
            <div 
              v-for="j in 4" 
              :key="j"
              class="table-cell-skeleton skeleton-item"
            ></div>
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
.dashboard-skeleton {
  @apply space-y-6;
}

.dashboard-header-skeleton {
  @apply flex justify-between items-center;
}

.header-title-skeleton {
  @apply h-8 w-48 rounded;
}

.header-actions-skeleton {
  @apply flex space-x-3;
}

.action-button-skeleton {
  @apply h-9 w-24 rounded;
}

.stats-grid-skeleton {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6;
}

.stat-card-skeleton {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex items-center space-x-4;
}

.stat-icon-skeleton {
  @apply w-12 h-12 rounded-lg flex-shrink-0;
}

.stat-content-skeleton {
  @apply flex-1 space-y-1;
}

.stat-value-skeleton {
  @apply h-6 w-16 rounded;
}

.stat-label-skeleton {
  @apply h-3 w-20 rounded;
}

.stat-change-skeleton {
  @apply h-3 w-12 rounded;
}

.content-grid-skeleton {
  @apply grid grid-cols-1 lg:grid-cols-3 gap-6;
}

.chart-section-skeleton {
  @apply lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4;
}

.chart-header-skeleton {
  @apply flex justify-between items-center;
}

.chart-title-skeleton {
  @apply h-5 w-32 rounded;
}

.chart-controls-skeleton {
  @apply flex space-x-2;
}

.control-skeleton {
  @apply h-8 w-20 rounded;
}

.chart-skeleton {
  @apply h-64 w-full rounded;
}

.activity-section-skeleton {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4;
}

.activity-header-skeleton {
  @apply h-5 w-24 rounded;
}

.activity-list-skeleton {
  @apply space-y-4;
}

.activity-item-skeleton {
  @apply flex items-start space-x-3;
}

.activity-icon-skeleton {
  @apply w-8 h-8 rounded-full flex-shrink-0;
}

.activity-content-skeleton {
  @apply flex-1 space-y-1;
}

.activity-text-skeleton {
  @apply h-3 w-full rounded;
}

.activity-time-skeleton {
  @apply h-3 w-16 rounded;
}

.bottom-section-skeleton {
  @apply space-y-6;
}

.table-section-skeleton {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4;
}

.table-header-skeleton {
  @apply flex justify-between items-center;
}

.table-title-skeleton {
  @apply h-5 w-32 rounded;
}

.table-search-skeleton {
  @apply h-9 w-64 rounded;
}

.table-skeleton {
  @apply space-y-2;
}

.table-row-skeleton {
  @apply grid grid-cols-4 gap-4 py-3;
}

.table-header-cell-skeleton {
  @apply h-4 rounded;
}

.table-cell-skeleton {
  @apply h-3 rounded;
}
</style>
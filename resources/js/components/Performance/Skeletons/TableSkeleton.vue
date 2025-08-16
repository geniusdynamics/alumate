<template>
  <div class="table-skeleton">
    <!-- Table Header -->
    <div class="table-header-skeleton">
      <div class="table-title-skeleton skeleton-item"></div>
      <div class="table-controls-skeleton">
        <div class="search-skeleton skeleton-item"></div>
        <div class="filter-skeleton skeleton-item"></div>
      </div>
    </div>

    <!-- Table Content -->
    <div class="table-content-skeleton">
      <!-- Column Headers -->
      <div class="table-headers-skeleton">
        <div 
          v-for="i in columns" 
          :key="i"
          class="table-header-cell-skeleton skeleton-item"
        ></div>
      </div>

      <!-- Table Rows -->
      <div class="table-rows-skeleton">
        <div 
          v-for="i in rows" 
          :key="i"
          class="table-row-skeleton"
          :class="{ 'table-row-alternate': i % 2 === 0 }"
        >
          <div 
            v-for="j in columns" 
            :key="j"
            class="table-cell-skeleton skeleton-item"
            :class="getCellClass(j)"
          ></div>
        </div>
      </div>
    </div>

    <!-- Table Footer -->
    <div class="table-footer-skeleton">
      <div class="pagination-info-skeleton skeleton-item"></div>
      <div class="pagination-controls-skeleton">
        <div class="pagination-button-skeleton skeleton-item"></div>
        <div class="pagination-numbers-skeleton">
          <div 
            v-for="i in 3" 
            :key="i"
            class="pagination-number-skeleton skeleton-item"
          ></div>
        </div>
        <div class="pagination-button-skeleton skeleton-item"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  rows?: number
  columns?: number
  showHeader?: boolean
  showFooter?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  rows: 8,
  columns: 5,
  showHeader: true,
  showFooter: true
})

const getCellClass = (columnIndex: number): string => {
  // Vary cell widths for more realistic appearance
  const widthClasses = [
    'w-1/4',    // First column (usually ID or name)
    'w-1/3',    // Second column
    'w-1/5',    // Third column
    'w-1/6',    // Fourth column
    'w-1/8'     // Last column (usually actions)
  ]
  
  return widthClasses[columnIndex - 1] || 'w-1/5'
}
</script>

<style scoped>
.table-skeleton {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden;
}

.table-header-skeleton {
  @apply flex justify-between items-center p-6 border-b border-gray-200 dark:border-gray-700;
}

.table-title-skeleton {
  @apply h-6 w-32 rounded;
}

.table-controls-skeleton {
  @apply flex items-center space-x-4;
}

.search-skeleton {
  @apply h-9 w-64 rounded;
}

.filter-skeleton {
  @apply h-9 w-24 rounded;
}

.table-content-skeleton {
  @apply overflow-hidden;
}

.table-headers-skeleton {
  @apply grid gap-4 px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600;
  grid-template-columns: repeat(v-bind(columns), minmax(0, 1fr));
}

.table-header-cell-skeleton {
  @apply h-4 rounded;
}

.table-rows-skeleton {
  @apply divide-y divide-gray-200 dark:divide-gray-700;
}

.table-row-skeleton {
  @apply grid gap-4 px-6 py-4;
  grid-template-columns: repeat(v-bind(columns), minmax(0, 1fr));
}

.table-row-alternate {
  @apply bg-gray-50 dark:bg-gray-700/50;
}

.table-cell-skeleton {
  @apply h-3 rounded;
}

.table-footer-skeleton {
  @apply flex justify-between items-center px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700;
}

.pagination-info-skeleton {
  @apply h-4 w-32 rounded;
}

.pagination-controls-skeleton {
  @apply flex items-center space-x-2;
}

.pagination-button-skeleton {
  @apply h-8 w-8 rounded;
}

.pagination-numbers-skeleton {
  @apply flex items-center space-x-1;
}

.pagination-number-skeleton {
  @apply h-8 w-8 rounded;
}
</style>
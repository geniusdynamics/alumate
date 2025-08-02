<template>
  <div v-if="links.length > 3" class="pagination">
    <div class="pagination-info">
      <p class="text-sm text-gray-700">
        Showing
        <span class="font-medium">{{ from }}</span>
        to
        <span class="font-medium">{{ to }}</span>
        of
        <span class="font-medium">{{ total }}</span>
        results
      </p>
    </div>

    <div class="pagination-links">
      <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
        <template v-for="(link, index) in links" :key="index">
          <!-- Previous Button -->
          <button
            v-if="link.label.includes('Previous')"
            @click="changePage(link.url)"
            :disabled="!link.url"
            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="sr-only">Previous</span>
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </button>

          <!-- Next Button -->
          <button
            v-else-if="link.label.includes('Next')"
            @click="changePage(link.url)"
            :disabled="!link.url"
            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="sr-only">Next</span>
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
          </button>

          <!-- Page Numbers -->
          <button
            v-else
            @click="changePage(link.url)"
            :disabled="!link.url"
            :class="[
              'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
              link.active
                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
            ]"
          >
            {{ link.label }}
          </button>
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'

interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

interface Props {
  links: PaginationLink[]
  from: number
  to: number
  total: number
}

defineProps<Props>()

const changePage = (url: string | null) => {
  if (url) {
    router.get(url, {}, {
      preserveState: true,
      preserveScroll: true,
    })
  }
}
</script>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
}

.pagination-info {
  flex: 1 1 0%;
  display: flex;
  justify-content: space-between;
}

.pagination-links {
  display: none;
}

@media (min-width: 640px) {
  .pagination {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
  
  .pagination-info {
    display: none;
  }
  
  .pagination-links {
    display: flex;
    flex: 1 1 0%;
    align-items: center;
    justify-content: space-between;
  }
}

@media (max-width: 640px) {
  .pagination-links {
    display: flex;
    justify-content: center;
    margin-top: 0.75rem;
  }
  
  .pagination-info {
    text-align: center;
  }
}
</style>
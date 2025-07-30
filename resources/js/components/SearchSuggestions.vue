<template>
  <div class="search-suggestions">
    <div class="suggestions-list">
      <div
        v-for="(suggestion, index) in suggestions"
        :key="index"
        @click="selectSuggestion(suggestion)"
        class="suggestion-item"
      >
        <div class="suggestion-icon">
          <svg v-if="suggestion.type === 'name'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <svg v-else-if="suggestion.type === 'company'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
        <div class="suggestion-content">
          <div class="suggestion-text">{{ suggestion.text }}</div>
          <div class="suggestion-type">{{ formatSuggestionType(suggestion.type) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  suggestions: {
    type: Array,
    required: true
  }
})

// Emits
const emit = defineEmits(['select', 'close'])

// Methods
const selectSuggestion = (suggestion) => {
  emit('select', suggestion)
}

const formatSuggestionType = (type) => {
  const typeMap = {
    name: 'Person',
    company: 'Company',
    skill: 'Skill',
    location: 'Location'
  }
  return typeMap[type] || 'Search'
}

// Close suggestions when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.search-suggestions')) {
    emit('close')
  }
}

// Add event listener for clicking outside
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.search-suggestions {
  @apply absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50;
}

.suggestions-list {
  @apply max-h-64 overflow-y-auto;
}

.suggestion-item {
  @apply flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0;
}

.suggestion-icon {
  @apply text-gray-400;
}

.suggestion-content {
  @apply flex-1;
}

.suggestion-text {
  @apply font-medium text-gray-900;
}

.suggestion-type {
  @apply text-sm text-gray-500;
}
</style>
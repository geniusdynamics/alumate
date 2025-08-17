<script setup lang="ts">
import { inject, computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  value: string
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  class: undefined,
})

const tabsContext = inject('tabsContext') as {
  activeTab: { value: string }
  setActiveTab: (value: string) => void
}

const isActive = computed(() => tabsContext.activeTab.value === props.value)
</script>

<template>
  <div
    v-if="isActive"
    :class="cn(
      'mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
      props.class
    )"
  >
    <slot />
  </div>
</template>
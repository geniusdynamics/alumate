<script setup lang="ts">
import { inject, computed } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  value: string
  class?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  class: undefined,
  disabled: false,
})

const tabsContext = inject('tabsContext') as {
  activeTab: { value: string }
  setActiveTab: (value: string) => void
}

const isActive = computed(() => tabsContext.activeTab.value === props.value)

function handleClick() {
  if (!props.disabled) {
    tabsContext.setActiveTab(props.value)
  }
}
</script>

<template>
  <button
    type="button"
    :class="cn(
      'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
      isActive ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
      props.class
    )"
    :disabled="disabled"
    @click="handleClick"
  >
    <slot />
  </button>
</template>
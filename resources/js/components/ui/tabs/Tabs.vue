<script setup lang="ts">
import { provide, ref, computed } from 'vue'

interface Props {
  defaultValue?: string
  modelValue?: string
}

const props = withDefaults(defineProps<Props>(), {
  defaultValue: undefined,
  modelValue: undefined,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const activeTab = ref(props.modelValue || props.defaultValue || '')

const currentValue = computed({
  get: () => props.modelValue !== undefined ? props.modelValue : activeTab.value,
  set: (value: string) => {
    if (props.modelValue !== undefined) {
      emit('update:modelValue', value)
    } else {
      activeTab.value = value
    }
  }
})

function setActiveTab(value: string) {
  currentValue.value = value
}

// Provide the tab context to child components
provide('tabsContext', {
  activeTab: currentValue,
  setActiveTab,
})
</script>

<template>
  <div class="tabs">
    <slot />
  </div>
</template>
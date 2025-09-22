<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
<<<<<<< HEAD:resources/js/components/ui/sidebar/SidebarInput.vue
import { cn } from '@/Lib/utils'
=======
import { cn } from '@/lib/utils'
>>>>>>> origin/db1:resources/js/Components/ui/sidebar/SidebarInput.vue
import { Input } from '@/Components/ui/input'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <Input
    data-slot="sidebar-input"
    data-sidebar="input"
    :class="cn(
      'bg-background h-8 w-full shadow-none',
      props.class,
    )"
  >
    <slot />
  </Input>
</template>












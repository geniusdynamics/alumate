<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
<<<<<<< HEAD:resources/js/components/ui/sidebar/SidebarSeparator.vue
import { cn } from '@/Lib/utils'
=======
import { cn } from '@/lib/utils'
>>>>>>> origin/db1:resources/js/Components/ui/sidebar/SidebarSeparator.vue
import { Separator } from '@/Components/ui/separator'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <Separator
    data-slot="sidebar-separator"
    data-sidebar="separator"
    :class="cn('bg-sidebar-border mx-2 w-auto', props.class)"
  >
    <slot />
  </Separator>
</template>












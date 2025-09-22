<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
<<<<<<< HEAD:resources/js/components/ui/sidebar/SidebarTrigger.vue
import { cn } from '@/Lib/utils'
=======
import { cn } from '@/lib/utils'
>>>>>>> origin/db1:resources/js/Components/ui/sidebar/SidebarTrigger.vue
import { Button } from '@/Components/ui/button'
import { PanelLeft } from 'lucide-vue-next'
import { useSidebar } from './utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { toggleSidebar } = useSidebar()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="ghost"
    size="icon"
    :class="cn('h-7 w-7', props.class)"
    @click="toggleSidebar"
  >
    <PanelLeft />
    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>












<template>
  <svg
    :class="classes"
    :width="size"
    :height="size"
    :viewBox="viewBox"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path
      v-if="iconPath"
      :d="iconPath"
      :stroke="stroke"
      :fill="fill"
      :stroke-width="strokeWidth"
      :stroke-linecap="strokeLinecap"
      :stroke-linejoin="strokeLinejoin"
    />
    <g v-else-if="iconPaths">
      <path
        v-for="(path, index) in iconPaths"
        :key="index"
        :d="path.d"
        :stroke="path.stroke || stroke"
        :fill="path.fill || fill"
        :stroke-width="path.strokeWidth || strokeWidth"
        :stroke-linecap="path.strokeLinecap || strokeLinecap"
        :stroke-linejoin="path.strokeLinejoin || strokeLinejoin"
      />
    </g>
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  name: string
  size?: number | string
  classes?: string
  stroke?: string
  fill?: string
  strokeWidth?: number | string
  strokeLinecap?: string
  strokeLinejoin?: string
}

const props = withDefaults(defineProps<Props>(), {
  size: 24,
  classes: '',
  stroke: 'currentColor',
  fill: 'none',
  strokeWidth: 2,
  strokeLinecap: 'round',
  strokeLinejoin: 'round'
})

const viewBox = computed(() => `0 0 ${props.size} ${props.size}`)

// Icon definitions - using Heroicons/Lucide style paths
const icons = {
  // Basic icons
  'x': 'M18 6L6 18M6 6l12 12',
  'check': 'M20 6L9 17l-5-5',
  'plus': 'M12 5v14m-7-7h14',
  'minus': 'M5 12h14',
  'edit': 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7',
  'save': 'M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z',
  'upload': 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12',
  'download': 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3',
  'refresh': 'M1 4v6h6M23 20v-6h-6',
  'settings': 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
  'copy': 'M20 9H11a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2z',
  'trash': 'M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z',
  
  // Device icons
  'monitor': 'M20 3H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zM8 21h8',
  'tablet': 'M18 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z',
  'smartphone': 'M17 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z',
  
  // Navigation icons
  'eye': 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z',
  'external-link': 'M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3',
  'arrow-right': 'M5 12h14M12 5l7 7-7 7',
  'arrow-left': 'M19 12H5M12 19l-7-7 7-7',
  
  // Action icons
  'undo': 'M3 7v6h6M21 17a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 13',
  'redo': 'M21 7v6h-6M3 17a9 9 0 0 1 9-9c2.52 0 4.93 1 6.74 2.74L21 13',
  'history': 'M3 3v5h5M3.05 13A9 9 0 1 0 6 5.3l-3 3.2',
  'clock': 'M12 2v10l4 4',
  'spinner': 'M21 12a9 9 0 1 1-6.219-8.56',
  
  // UI icons
  'layers': 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
  'grid': 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
  'cursor-click': 'M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z',
  'code': 'M16 18l6-6-6-6M8 6l-6 6 6 6',
  'palette': 'M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10c1.38 0 2.5-.56 2.5-1.25 0-.34-.13-.65-.35-.88-.22-.23-.35-.54-.35-.87 0-.69.56-1.25 1.25-1.25H16c3.31 0 6-2.69 6-6 0-4.97-4.03-9-10-9z',
  
  // Status icons
  'check-circle': 'M22 11.08V12a10 10 0 1 1-5.93-9.14',
  'alert-circle': 'M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zM12 7v5M12 16h.01',
  'move': 'M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M2 12h20M12 2v20'
}

const iconPath = computed(() => {
  return icons[props.name] || null
})

const iconPaths = computed(() => {
  // For complex icons that need multiple paths
  if (props.name === 'spinner') {
    return [
      {
        d: 'M21 12a9 9 0 1 1-6.219-8.56',
        stroke: props.stroke,
        fill: 'none',
        strokeWidth: props.strokeWidth
      }
    ]
  }
  return null
})
</script>

<style scoped>
svg {
  display: inline-block;
  vertical-align: middle;
}
</style>
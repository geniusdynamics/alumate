<template>
  <svg
    :class="iconClasses"
    :style="iconStyles"
    :aria-label="ariaLabel"
    :aria-hidden="ariaHidden"
    fill="currentColor"
    viewBox="0 0 24 24"
  >
    <path :d="iconPath" />
  </svg>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  name: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  color?: string
  ariaLabel?: string
  ariaHidden?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  ariaHidden: false
})

const iconClasses = computed(() => [
  'icon',
  `icon--${props.size}`,
  {
    'icon--colored': !!props.color
  }
])

const iconStyles = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.color) {
    styles.color = props.color
  }
  
  return styles
})

// Icon paths - in a real implementation, you might use a more comprehensive icon library
const iconPaths: Record<string, string> = {
  'arrow-right': 'M8.25 4.5l7.5 7.5-7.5 7.5',
  'external-link': 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',
  'chevron-right': 'M8.25 4.5l7.5 7.5-7.5 7.5',
  'chevron-left': 'M15.75 19.5L8.25 12l7.5-7.5',
  'chevron-down': 'M19.5 8.25l-7.5 7.5-7.5-7.5',
  'chevron-up': 'M4.5 15.75l7.5-7.5 7.5 7.5',
  'play': 'M8 5v14l11-7z',
  'pause': 'M6 19h4V5H6v14zm8-14v14h4V5h-4z',
  'download': 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M12 3v13.5m0 0l-3.75-3.75M12 16.5l3.75-3.75',
  'link': 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
  'star': 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
  'heart': 'M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z',
  'check': 'M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z',
  'x': 'M18 6L6 18M6 6l12 12',
  'plus': 'M12 5v14m-7-7h14',
  'minus': 'M5 12h14',
  'search': 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
  'menu': 'M4 6h16M4 12h16M4 18h16',
  'close': 'M6 18L18 6M6 6l12 12',
  'info': 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  'warning': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
  'error': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
  'success': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
}

const iconPath = computed(() => {
  return iconPaths[props.name] || iconPaths['info'] // Fallback to info icon
})
</script>

<style scoped>
.icon {
  @apply inline-block flex-shrink-0;
}

.icon--xs {
  @apply w-3 h-3;
}

.icon--sm {
  @apply w-4 h-4;
}

.icon--md {
  @apply w-5 h-5;
}

.icon--lg {
  @apply w-6 h-6;
}

.icon--xl {
  @apply w-8 h-8;
}

.icon--colored {
  @apply text-current;
}
</style>
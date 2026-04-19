<template>
  <span 
    class="animated-counter"
    :aria-label="ariaLabel || `${displayValue}`"
  >
    {{ displayValue }}
  </span>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface Props {
  targetValue: number
  format?: 'number' | 'percentage' | 'currency'
  suffix?: string
  animate?: boolean
  duration?: number
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  format: 'number',
  animate: true,
  duration: 2000
})

const currentValue = ref(0)
const animationFrame = ref<number>()

const displayValue = computed(() => {
  let value = Math.round(currentValue.value).toString()
  
  if (props.format === 'percentage') {
    value = `${Math.round(currentValue.value)}%`
  } else if (props.format === 'currency') {
    value = `$${Math.round(currentValue.value).toLocaleString()}`
  } else if (props.format === 'number') {
    value = Math.round(currentValue.value).toLocaleString()
  }
  
  return props.suffix ? `${value}${props.suffix}` : value
})

const animateToTarget = () => {
  if (!props.animate) {
    currentValue.value = props.targetValue
    return
  }

  const startValue = currentValue.value
  const endValue = props.targetValue
  const startTime = performance.now()
  
  const animate = (currentTime: number) => {
    const elapsed = currentTime - startTime
    const progress = Math.min(elapsed / props.duration, 1)
    
    // Easing function (ease-out)
    const easeOut = 1 - Math.pow(1 - progress, 3)
    
    currentValue.value = startValue + (endValue - startValue) * easeOut
    
    if (progress < 1) {
      animationFrame.value = requestAnimationFrame(animate)
    }
  }
  
  animationFrame.value = requestAnimationFrame(animate)
}

watch(() => props.targetValue, () => {
  if (animationFrame.value) {
    cancelAnimationFrame(animationFrame.value)
  }
  animateToTarget()
}, { immediate: true })

watch(() => props.animate, (newAnimate) => {
  if (newAnimate) {
    animateToTarget()
  } else {
    currentValue.value = props.targetValue
  }
})

onMounted(() => {
  if (props.animate) {
    // Small delay to ensure smooth animation start
    setTimeout(animateToTarget, 100)
  } else {
    currentValue.value = props.targetValue
  }
})
</script>

<style scoped>
.animated-counter {
  @apply font-bold tabular-nums;
}
</style>
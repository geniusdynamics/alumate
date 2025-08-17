import { ref, onMounted, onUnmounted } from 'vue'

export function useExitIntent() {
  const showExitIntent = ref(false)
  const exitIntentTriggered = ref(false)

  const handleMouseLeave = (event: MouseEvent) => {
    // Only trigger on top of viewport
    if (event.clientY <= 0 && !exitIntentTriggered.value) {
      showExitIntent.value = true
      exitIntentTriggered.value = true
    }
  }

  const resetExitIntent = () => {
    showExitIntent.value = false
  }

  onMounted(() => {
    document.addEventListener('mouseleave', handleMouseLeave)
  })

  onUnmounted(() => {
    document.removeEventListener('mouseleave', handleMouseLeave)
  })

  return {
    showExitIntent,
    resetExitIntent
  }
}

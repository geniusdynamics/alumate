import { ref, onMounted, onUnmounted } from 'vue'

export function useExitIntent() {
  const showExitIntent = ref(false)
  const hasTriggered = ref(false)
  const isEnabled = ref(true)

  let mouseLeaveTimer: NodeJS.Timeout | null = null

  const handleMouseLeave = (e: MouseEvent) => {
    // Only trigger if mouse is leaving from the top of the page
    if (e.clientY <= 0 && !hasTriggered.value && isEnabled.value) {
      // Add a small delay to avoid false positives
      mouseLeaveTimer = setTimeout(() => {
        showExitIntent.value = true
        hasTriggered.value = true
      }, 100)
    }
  }

  const handleMouseEnter = () => {
    // Cancel the timer if mouse re-enters quickly
    if (mouseLeaveTimer) {
      clearTimeout(mouseLeaveTimer)
      mouseLeaveTimer = null
    }
  }

  const resetExitIntent = () => {
    showExitIntent.value = false
  }

  const disableExitIntent = () => {
    isEnabled.value = false
  }

  const enableExitIntent = () => {
    isEnabled.value = true
    hasTriggered.value = false
  }

  onMounted(() => {
    // Only enable on desktop devices
    if (window.innerWidth >= 768) {
      document.addEventListener('mouseleave', handleMouseLeave)
      document.addEventListener('mouseenter', handleMouseEnter)
    }
  })

  onUnmounted(() => {
    document.removeEventListener('mouseleave', handleMouseLeave)
    document.removeEventListener('mouseenter', handleMouseEnter)
    
    if (mouseLeaveTimer) {
      clearTimeout(mouseLeaveTimer)
    }
  })

  return {
    showExitIntent,
    hasTriggered,
    isEnabled,
    resetExitIntent,
    disableExitIntent,
    enableExitIntent
  }
}
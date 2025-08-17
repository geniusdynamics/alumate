import { ref, computed } from 'vue'

export function useAudienceDetection() {
  const isMobile = ref(false)
  const isTablet = ref(false)
  const isDesktop = ref(true)

  // Simple device detection
  if (typeof window !== 'undefined') {
    const checkDevice = () => {
      const width = window.innerWidth
      isMobile.value = width < 768
      isTablet.value = width >= 768 && width < 1024
      isDesktop.value = width >= 1024
    }

    checkDevice()
    window.addEventListener('resize', checkDevice)
  }

  return {
    isMobile,
    isTablet,
    isDesktop
  }
}

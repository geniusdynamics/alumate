import { ref, onMounted, onUnmounted } from 'vue'

export function useScrollTracking() {
  const scrollDepth = ref(0)
  const isScrolling = ref(false)

  let scrollTimeout: number | undefined

  const handleScroll = () => {
    if (typeof window === 'undefined') return

    const scrollTop = window.pageYOffset || document.documentElement.scrollTop
    const scrollHeight = document.documentElement.scrollHeight - window.innerHeight
    const currentScrollDepth = Math.round((scrollTop / scrollHeight) * 100)
    
    scrollDepth.value = Math.max(scrollDepth.value, currentScrollDepth)
    isScrolling.value = true

    clearTimeout(scrollTimeout)
    scrollTimeout = window.setTimeout(() => {
      isScrolling.value = false
    }, 150)
  }

  onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true })
  })

  onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
    if (scrollTimeout) {
      clearTimeout(scrollTimeout)
    }
  })

  return {
    scrollDepth,
    isScrolling
  }
}

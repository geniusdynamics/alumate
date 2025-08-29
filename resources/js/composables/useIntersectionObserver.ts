import { ref, onMounted, onUnmounted, type Ref } from 'vue'

interface UseIntersectionObserverOptions {
  threshold?: number | number[]
  root?: Element | null
  rootMargin?: string
}

export function useIntersectionObserver(
  target: Ref<Element | undefined>,
  options: UseIntersectionObserverOptions = {}
) {
  const isIntersecting = ref(false)
  const isSupported = ref(false)
  
  let observer: IntersectionObserver | null = null

  const cleanup = () => {
    if (observer) {
      observer.disconnect()
      observer = null
    }
  }

  const observe = () => {
    if (!target.value || !isSupported.value) return

    cleanup()

    observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0]
        if (entry) {
          isIntersecting.value = entry.isIntersecting
        }
      },
      {
        threshold: options.threshold ?? 0.1,
        root: options.root ?? null,
        rootMargin: options.rootMargin ?? '0px'
      }
    )

    observer.observe(target.value)
  }

  onMounted(() => {
    isSupported.value = typeof window !== 'undefined' && 'IntersectionObserver' in window
    
    if (isSupported.value && target.value) {
      observe()
    }
  })

  onUnmounted(() => {
    cleanup()
  })

  return {
    isIntersecting,
    isSupported,
    observe,
    cleanup
  }
}
import { ref, onMounted, onUnmounted, type Ref } from 'vue'

export interface LazyLoadOptions {
  rootMargin?: string
  threshold?: number | number[]
  once?: boolean
}

export function useLazyLoading(
  target: Ref<HTMLElement | null>,
  callback: () => void,
  options: LazyLoadOptions = {}
) {
  const isIntersecting = ref(false)
  const hasLoaded = ref(false)
  
  const {
    rootMargin = '50px',
    threshold = 0.1,
    once = true
  } = options

  let observer: IntersectionObserver | null = null

  const startObserving = () => {
    if (!target.value || !('IntersectionObserver' in window)) {
      // Fallback for browsers without IntersectionObserver
      callback()
      hasLoaded.value = true
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          isIntersecting.value = entry.isIntersecting
          
          if (entry.isIntersecting && (!once || !hasLoaded.value)) {
            callback()
            hasLoaded.value = true
            
            if (once) {
              observer?.unobserve(entry.target)
            }
          }
        })
      },
      {
        rootMargin,
        threshold
      }
    )

    observer.observe(target.value)
  }

  const stopObserving = () => {
    if (observer && target.value) {
      observer.unobserve(target.value)
      observer.disconnect()
      observer = null
    }
  }

  onMounted(() => {
    startObserving()
  })

  onUnmounted(() => {
    stopObserving()
  })

  return {
    isIntersecting,
    hasLoaded,
    startObserving,
    stopObserving
  }
}

// Composable for lazy loading images
export function useLazyImage(src: string, options: LazyLoadOptions = {}) {
  const imageRef = ref<HTMLImageElement | null>(null)
  const isLoaded = ref(false)
  const isError = ref(false)
  const currentSrc = ref('')

  const loadImage = () => {
    if (isLoaded.value || !src) return

    const img = new Image()
    
    img.onload = () => {
      currentSrc.value = src
      isLoaded.value = true
      isError.value = false
    }
    
    img.onerror = () => {
      isError.value = true
      isLoaded.value = false
    }
    
    img.src = src
  }

  useLazyLoading(imageRef, loadImage, options)

  return {
    imageRef,
    currentSrc,
    isLoaded,
    isError
  }
}

// Composable for lazy loading components
export function useLazyComponent(
  loadComponent: () => Promise<any>,
  options: LazyLoadOptions = {}
) {
  const containerRef = ref<HTMLElement | null>(null)
  const component = ref(null)
  const isLoading = ref(false)
  const isError = ref(false)

  const loadComponentAsync = async () => {
    if (component.value || isLoading.value) return

    isLoading.value = true
    isError.value = false

    try {
      const loadedComponent = await loadComponent()
      component.value = loadedComponent.default || loadedComponent
    } catch (error) {
      console.error('Error loading component:', error)
      isError.value = true
    } finally {
      isLoading.value = false
    }
  }

  useLazyLoading(containerRef, loadComponentAsync, options)

  return {
    containerRef,
    component,
    isLoading,
    isError
  }
}
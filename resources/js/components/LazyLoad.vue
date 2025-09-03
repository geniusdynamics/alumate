<template>
  <div
    ref="container"
    class="lazy-load-container"
    :class="[isVisible ? 'visible' : 'hidden']"
  >
    <!-- Loading skeleton -->
    <slot v-if="!isVisible" name="skeleton">
      <div class="lazy-load-skeleton">
        <slot name="skeleton-content">
          <div class="skeleton-pulse"></div>
        </slot>
      </div>
    </slot>

    <!-- Content -->
    <div v-show="isVisible" class="lazy-load-content">
      <slot :isVisible="isVisible" :hasBeenVisible="hasBeenVisible"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'

interface Props {
  rootMargin?: string
  threshold?: number | number[]
  triggerOnce?: boolean
  disabled?: boolean
  rootElement?: HTMLElement | null
}

const props = withDefaults(defineProps<Props>(), {
  rootMargin: '50px',
  threshold: 0.1,
  triggerOnce: true,
  disabled: false,
  rootElement: null,
})

const emit = defineEmits<{
  visible: [element: Element]
  hidden: [element: Element]
}>()

const container = ref<Element>()
const isVisible = ref(false)
const hasBeenVisible = ref(false)
let observer: IntersectionObserver | null = null

const createObserver = () => {
  if (typeof window === 'undefined' || !window.IntersectionObserver) {
    console.warn('IntersectionObserver not supported')
    isVisible.value = true
    hasBeenVisible.value = true
    return
  }

  const options = {
    root: props.rootElement || null,
    rootMargin: props.rootMargin,
    threshold: props.threshold,
  }

  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        isVisible.value = true
        hasBeenVisible.value = true
        emit('visible', entry.target)

        // Disconnect if only triggered once
        if (props.triggerOnce && observer) {
          observer.disconnect()
        }
      } else if (!props.triggerOnce) {
        isVisible.value = false
        emit('hidden', entry.target)
      }
    })
  }, options)
}

const observe = () => {
  if (!container.value || !observer) return

  observer.observe(container.value)
}

const unobserve = () => {
  if (!container.value || !observer) return

  observer.unobserve(container.value)
}

const destroyObserver = () => {
  if (observer) {
    observer.disconnect()
    observer = null
  }
}

// Watch for disabled prop changes
watch(() => props.disabled, (newDisabled) => {
  if (newDisabled) {
    isVisible.value = true
    hasBeenVisible.value = true
    destroyObserver()
  } else if (!hasBeenVisible.value) {
    createObserver()
    observe()
  }
})

onMounted(() => {
  if (props.disabled) {
    isVisible.value = true
    hasBeenVisible.value = true
    return
  }

  createObserver()
  observe()
})

onBeforeUnmount(() => {
  destroyObserver()
})

// Expose methods for parent component control
defineExpose({
  trigger: () => {
    isVisible.value = true
    hasBeenVisible.value = true
  },
  reset: () => {
    if (!props.triggerOnce) {
      isVisible.value = false
      hasBeenVisible.value = false
      observe()
    }
  },
  destroy: destroyObserver,
})
</script>

<style scoped>
.lazy-load-container {
  position: relative;
  width: 100%;
}

.lazy-load-container.hidden {
  opacity: 0;
}

.lazy-load-container.visible {
  opacity: 1;
  transition: opacity 0.3s ease-in-out;
}

.lazy-load-skeleton {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
  border-radius: 8px;
}

.lazy-load-content {
  width: 100%;
  animation: fadeInUp 0.5s ease-out;
}

.skeleton-pulse {
  width: 100%;
  height: 200px;
  background: linear-gradient(
    90deg,
    #f5f5f5 25%,
    #e6e6e6 50%,
    #f5f5f5 75%
  );
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* RTL Support */
[dir="rtl"] .lazy-load-content {
  animation: fadeInLeft 0.5s ease-out;
}

@keyframes fadeInLeft {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .lazy-load-container.visible {
    transition: opacity 0.2s ease-in-out;
  }

  .lazy-load-skeleton {
    border-radius: 4px;
  }

  .lazy-load-content {
    animation: fadeInUp 0.3s ease-out;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .lazy-load-skeleton {
    background-color: #ffffff;
    border: 1px solid #000000;
  }

  .skeleton-pulse {
    background: linear-gradient(
      90deg,
      #ffffff 25%,
      #cccccc 50%,
      #ffffff 75%
    );
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .lazy-load-container.visible,
  .lazy-load-content {
    transition: none;
    animation: none;
  }

  .skeleton-pulse {
    animation: none;
  }
}
</style>
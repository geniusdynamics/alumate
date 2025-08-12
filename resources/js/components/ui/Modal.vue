<!-- ABOUTME: Enhanced modal component with accessibility features and standardized z-index -->
<!-- ABOUTME: Uses useModal composable for focus trapping, escape handling, and scroll management -->

<template>
  <teleport to="body">
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        ref="modalRef"
        class="fixed inset-0 z-[50] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="title ? 'modal-title' : undefined"
        :aria-describedby="description ? 'modal-description' : undefined"
      >
        <!-- Background overlay -->
        <div
          class="fixed inset-0 bg-black/50 transition-opacity backdrop-blur-sm"
          @click="closeOnClickOutside && close()"
          aria-hidden="true"
        ></div>

        <!-- Modal container -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
          <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="isOpen"
              class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 w-full"
              :class="sizeClasses"
            >
              <!-- Close button -->
              <button
                v-if="showCloseButton"
                @click="close()"
                class="absolute right-4 top-4 z-[10] rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none"
                aria-label="Close modal"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              <!-- Header -->
              <div v-if="title || $slots.header" class="px-6 pt-6 pb-4">
                <slot name="header">
                  <h3
                    v-if="title"
                    id="modal-title"
                    class="text-lg font-semibold leading-6 text-gray-900 dark:text-white"
                  >
                    {{ title }}
                  </h3>
                  <p
                    v-if="description"
                    id="modal-description"
                    class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                  >
                    {{ description }}
                  </p>
                </slot>
              </div>

              <!-- Content -->
              <div class="px-6 py-4">
                <slot />
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer" class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:flex sm:flex-row-reverse">
                <slot name="footer" />
              </div>
            </div>
          </transition>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { computed, watchEffect } from 'vue'
import { useModal } from '@/composables/useModal'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(value)
  },
  closeOnClickOutside: {
    type: Boolean,
    default: true
  },
  closeOnEscape: {
    type: Boolean,
    default: true
  },
  showCloseButton: {
    type: Boolean,
    default: true
  },
  trapFocus: {
    type: Boolean,
    default: true
  },
  lockBodyScroll: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'close', 'open'])

// Size classes mapping
const sizeClasses = computed(() => {
  const sizes = {
    xs: 'sm:max-w-xs',
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    full: 'sm:max-w-full sm:m-4'
  }
  return sizes[props.size] || sizes.md
})

// Use modal composable
const {
  isOpen,
  modalRef,
  open: openModal,
  close: closeModal
} = useModal({
  onClose: () => {
    emit('update:modelValue', false)
    emit('close')
  },
  closeOnEscape: props.closeOnEscape,
  trapFocus: props.trapFocus,
  lockBodyScroll: props.lockBodyScroll
})

// Sync with v-model
const open = () => {
  openModal()
  emit('update:modelValue', true)
  emit('open')
}

const close = () => {
  closeModal()
}

// Watch for prop changes
watchEffect(() => {
  if (props.modelValue && !isOpen.value) {
    openModal()
  } else if (!props.modelValue && isOpen.value) {
    closeModal()
  }
})

// Expose methods for template refs
defineExpose({
  open,
  close,
  isOpen
})
</script>
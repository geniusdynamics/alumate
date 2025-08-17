// ABOUTME: Modal management composable for handling accessibility and user interactions
// ABOUTME: Provides focus trapping, escape key handling, and body scroll management

import { ref, onMounted, onUnmounted, nextTick } from 'vue'

/**
 * Composable for managing modal behavior and accessibility
 * @param {Object} options - Configuration options
 * @param {Function} options.onClose - Callback when modal should close
 * @param {boolean} options.closeOnEscape - Whether to close on escape key (default: true)
 * @param {boolean} options.trapFocus - Whether to trap focus within modal (default: true)
 * @param {boolean} options.lockBodyScroll - Whether to lock body scroll (default: true)
 */
export function useModal(options = {}) {
  const {
    onClose,
    closeOnEscape = true,
    trapFocus = true,
    lockBodyScroll = true
  } = options

  const isOpen = ref(false)
  const modalRef = ref(null)
  const previousActiveElement = ref(null)
  const focusableElements = ref([])
  const firstFocusableElement = ref(null)
  const lastFocusableElement = ref(null)

  // Store original body overflow style
  let originalBodyOverflow = ''

  /**
   * Get all focusable elements within the modal
   */
  const getFocusableElements = () => {
    if (!modalRef.value) return []
    
    const focusableSelectors = [
      'button:not([disabled])',
      'input:not([disabled])',
      'textarea:not([disabled])',
      'select:not([disabled])',
      'a[href]',
      '[tabindex]:not([tabindex="-1"])',
      '[contenteditable="true"]'
    ].join(', ')
    
    return Array.from(modalRef.value.querySelectorAll(focusableSelectors))
      .filter(el => {
        const style = window.getComputedStyle(el)
        return style.display !== 'none' && style.visibility !== 'hidden'
      })
  }

  /**
   * Handle focus trapping within modal
   */
  const handleFocusTrap = (event) => {
    if (!trapFocus || !isOpen.value) return
    
    const focusables = getFocusableElements()
    if (focusables.length === 0) return
    
    const firstElement = focusables[0]
    const lastElement = focusables[focusables.length - 1]
    
    if (event.key === 'Tab') {
      if (event.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstElement) {
          event.preventDefault()
          lastElement.focus()
        }
      } else {
        // Tab
        if (document.activeElement === lastElement) {
          event.preventDefault()
          firstElement.focus()
        }
      }
    }
  }

  /**
   * Handle escape key press
   */
  const handleEscapeKey = (event) => {
    if (event.key === 'Escape' && closeOnEscape && isOpen.value) {
      close()
    }
  }

  /**
   * Lock body scroll
   */
  const lockScroll = () => {
    if (!lockBodyScroll) return
    
    originalBodyOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
  }

  /**
   * Unlock body scroll
   */
  const unlockScroll = () => {
    if (!lockBodyScroll) return
    
    document.body.style.overflow = originalBodyOverflow
  }

  /**
   * Open the modal
   */
  const open = async () => {
    isOpen.value = true
    previousActiveElement.value = document.activeElement
    
    lockScroll()
    
    await nextTick()
    
    if (trapFocus && modalRef.value) {
      const focusables = getFocusableElements()
      if (focusables.length > 0) {
        focusables[0].focus()
      }
    }
  }

  /**
   * Close the modal
   */
  const close = () => {
    isOpen.value = false
    unlockScroll()
    
    // Restore focus to previously active element
    if (previousActiveElement.value) {
      previousActiveElement.value.focus()
      previousActiveElement.value = null
    }
    
    if (onClose) {
      onClose()
    }
  }

  /**
   * Toggle modal state
   */
  const toggle = () => {
    if (isOpen.value) {
      close()
    } else {
      open()
    }
  }

  // Set up event listeners
  onMounted(() => {
    document.addEventListener('keydown', handleFocusTrap)
    document.addEventListener('keydown', handleEscapeKey)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleFocusTrap)
    document.removeEventListener('keydown', handleEscapeKey)
    unlockScroll()
  })

  return {
    isOpen,
    modalRef,
    open,
    close,
    toggle,
    handleFocusTrap,
    handleEscapeKey
  }
}

export default useModal
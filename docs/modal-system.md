# Modal and Z-Index Management System

## Overview

This document outlines the standardized modal and z-index management system implemented to resolve popup layering conflicts and improve accessibility.

## Z-Index System

### Standardized Values

The z-index system is centralized in `/resources/js/utils/zIndex.js` with the following hierarchy:

```javascript
export const Z_INDEX = {
  // Base layers
  BASE: 0,
  DROPDOWN: 10,
  STICKY: 20,
  FIXED: 30,
  
  // Overlay layers
  OVERLAY_BACKDROP: 40,
  MODAL_BACKDROP: 50,
  MODAL_CONTENT: 60,
  
  // High priority overlays
  TOOLTIP: 70,
  POPOVER: 80,
  NOTIFICATION: 90,
  
  // Critical overlays (should be used sparingly)
  GUIDED_TOUR: 100,
  MEDIA_VIEWER: 110,
  CRITICAL_ALERT: 120
}
```

### Usage Guidelines

1. **Always use standardized z-index values** from the centralized system
2. **Use Tailwind arbitrary values** like `z-[50]` instead of `z-50` for custom values
3. **Layer modals properly**: backdrop at z-[50], content at z-[60]
4. **Reserve high values** (100+) for critical overlays only

## Modal Components

### Enhanced Modal Component

Use the new `/resources/js/components/ui/Modal.vue` component for new modals:

```vue
<template>
  <Modal
    v-model="isOpen"
    title="Modal Title"
    description="Optional description"
    size="md"
    :close-on-click-outside="true"
    :close-on-escape="true"
    @close="handleClose"
  >
    <!-- Modal content -->
    <p>Your modal content here</p>
    
    <template #footer>
      <button @click="isOpen = false">Cancel</button>
      <button @click="handleSave">Save</button>
    </template>
  </Modal>
</template>
```

### Modal Composable

For custom modal implementations, use the `useModal` composable:

```javascript
import { useModal } from '@/composables/useModal'

const {
  isOpen,
  modalRef,
  open,
  close
} = useModal({
  onClose: () => {
    // Handle close logic
  },
  closeOnEscape: true,
  trapFocus: true,
  lockBodyScroll: true
})
```

## Accessibility Features

### Focus Management
- **Focus trapping**: Focus stays within modal when open
- **Focus restoration**: Returns focus to trigger element when closed
- **Keyboard navigation**: Tab and Shift+Tab cycle through focusable elements

### ARIA Attributes
- `role="dialog"` and `aria-modal="true"` on modal containers
- `aria-labelledby` for modal titles
- `aria-describedby` for modal descriptions
- `aria-hidden="true"` on background overlays

### Keyboard Support
- **Escape key**: Closes modal (configurable)
- **Tab navigation**: Proper focus trapping
- **Enter/Space**: Activates buttons and interactive elements

## Best Practices

### Modal Structure
```vue
<template>
  <teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-[50] overflow-y-auto"
      role="dialog"
      aria-modal="true"
    >
      <!-- Background overlay -->
      <div
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        @click="closeOnClickOutside && close()"
        aria-hidden="true"
      ></div>
      
      <!-- Modal content -->
      <div class="relative z-[60] ...">
        <!-- Modal content here -->
      </div>
    </div>
  </teleport>
</template>
```

### Styling Guidelines

1. **Use backdrop-blur-sm** for modern glass effect
2. **Use bg-black/50** instead of bg-opacity classes
3. **Add transitions** for smooth open/close animations
4. **Support dark mode** with dark: variants
5. **Make responsive** with proper mobile considerations

### Performance Considerations

1. **Use teleport to body** to avoid z-index conflicts
2. **Lazy load modal content** when possible
3. **Clean up event listeners** in onUnmounted
4. **Lock body scroll** to prevent background scrolling

## Migration Guide

### Updating Existing Modals

1. **Replace z-index values**:
   ```diff
   - class="fixed inset-0 z-50"
   + class="fixed inset-0 z-[50]"
   ```

2. **Update background styling**:
   ```diff
   - class="bg-gray-600 bg-opacity-50"
   + class="bg-black/50 backdrop-blur-sm"
   ```

3. **Add accessibility attributes**:
   ```diff
   - <div class="fixed inset-0 ...">
   + <div class="fixed inset-0 ..." role="dialog" aria-modal="true">
   ```

4. **Consider using the new Modal component** for consistency

### Common Issues and Solutions

#### Z-Index Conflicts
- **Problem**: Modals appearing behind other elements
- **Solution**: Use standardized z-index values from the centralized system

#### Focus Management
- **Problem**: Focus escapes modal or doesn't return properly
- **Solution**: Use the `useModal` composable for automatic focus management

#### Mobile Issues
- **Problem**: Modals not working well on mobile devices
- **Solution**: Use proper viewport units and touch-friendly interactions

## Testing

### Accessibility Testing
- Test with screen readers
- Verify keyboard navigation
- Check focus management
- Validate ARIA attributes

### Cross-browser Testing
- Test backdrop-blur support
- Verify z-index behavior
- Check mobile responsiveness
- Test with different viewport sizes

## Future Improvements

1. **Modal stacking**: Support for multiple modals
2. **Animation library integration**: More sophisticated transitions
3. **Portal management**: Better teleport destination management
4. **Performance monitoring**: Track modal performance metrics
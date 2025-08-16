/**
 * Accessibility Helpers Utility
 * 
 * Provides utility functions and composables for implementing
 * WCAG 2.1 AA compliant accessibility features throughout the
 * Modern Alumni Platform.
 */

/**
 * Focus Management Utilities
 */
export class FocusManager {
    constructor() {
        this.focusableSelectors = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])',
            '[contenteditable="true"]'
        ].join(', ');
        
        this.previousFocus = null;
    }

    /**
     * Get all focusable elements within a container
     */
    getFocusableElements(container = document) {
        return Array.from(container.querySelectorAll(this.focusableSelectors))
            .filter(element => this.isVisible(element) && !this.isDisabled(element));
    }

    /**
     * Check if element is visible
     */
    isVisible(element) {
        const style = window.getComputedStyle(element);
        return style.display !== 'none' && 
               style.visibility !== 'hidden' && 
               style.opacity !== '0' &&
               element.offsetWidth > 0 && 
               element.offsetHeight > 0;
    }

    /**
     * Check if element is disabled
     */
    isDisabled(element) {
        return element.disabled || 
               element.getAttribute('aria-disabled') === 'true' ||
               element.getAttribute('tabindex') === '-1';
    }

    /**
     * Trap focus within a container (for modals, dropdowns)
     */
    trapFocus(container, options = {}) {
        const focusableElements = this.getFocusableElements(container);
        
        if (focusableElements.length === 0) {
            console.warn('No focusable elements found in container');
            return () => {};
        }

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        // Store previous focus
        this.previousFocus = document.activeElement;

        // Focus first element if specified
        if (options.focusFirst !== false) {
            firstElement.focus();
        }

        const handleKeyDown = (e) => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        };

        container.addEventListener('keydown', handleKeyDown);

        // Return cleanup function
        return () => {
            container.removeEventListener('keydown', handleKeyDown);
            if (this.previousFocus && options.restoreFocus !== false) {
                this.previousFocus.focus();
            }
        };
    }

    /**
     * Move focus to element with announcement
     */
    moveFocusTo(element, announcement = null) {
        if (!element) return;

        element.focus();

        if (announcement) {
            this.announce(announcement);
        }
    }

    /**
     * Create skip link
     */
    createSkipLink(targetId, text = 'Skip to main content') {
        const skipLink = document.createElement('a');
        skipLink.href = `#${targetId}`;
        skipLink.textContent = text;
        skipLink.className = 'skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-lg focus:shadow-lg';
        
        skipLink.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById(targetId);
            if (target) {
                target.focus();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        return skipLink;
    }
}

/**
 * ARIA Utilities
 */
export class AriaManager {
    /**
     * Set ARIA attributes on element
     */
    static setAttributes(element, attributes) {
        Object.entries(attributes).forEach(([key, value]) => {
            if (value === null || value === undefined) {
                element.removeAttribute(key);
            } else {
                element.setAttribute(key, value);
            }
        });
    }

    /**
     * Toggle ARIA expanded state
     */
    static toggleExpanded(trigger, target = null) {
        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
        const newState = !isExpanded;
        
        trigger.setAttribute('aria-expanded', newState);
        
        if (target) {
            target.setAttribute('aria-hidden', !newState);
        }

        return newState;
    }

    /**
     * Set ARIA pressed state for toggle buttons
     */
    static setPressed(button, pressed) {
        button.setAttribute('aria-pressed', pressed);
        
        // Update visual state if needed
        if (pressed) {
            button.classList.add('pressed', 'active');
        } else {
            button.classList.remove('pressed', 'active');
        }
    }

    /**
     * Create ARIA live region
     */
    static createLiveRegion(politeness = 'polite', atomic = false) {
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('aria-live', politeness);
        liveRegion.setAttribute('aria-atomic', atomic);
        liveRegion.className = 'sr-only';
        liveRegion.id = `live-region-${Date.now()}`;
        
        document.body.appendChild(liveRegion);
        
        return liveRegion;
    }

    /**
     * Announce message to screen readers
     */
    static announce(message, politeness = 'polite') {
        const liveRegion = this.createLiveRegion(politeness, true);
        
        // Small delay to ensure screen readers pick up the change
        setTimeout(() => {
            liveRegion.textContent = message;
            
            // Clean up after announcement
            setTimeout(() => {
                if (liveRegion.parentNode) {
                    liveRegion.parentNode.removeChild(liveRegion);
                }
            }, 1000);
        }, 100);
    }

    /**
     * Create accessible description
     */
    static createDescription(text, id = null) {
        const description = document.createElement('div');
        description.id = id || `description-${Date.now()}`;
        description.textContent = text;
        description.className = 'sr-only';
        
        return description;
    }

    /**
     * Associate label with form control
     */
    static associateLabel(control, labelText, required = false) {
        let label = control.previousElementSibling;
        
        if (!label || label.tagName !== 'LABEL') {
            label = document.createElement('label');
            control.parentNode.insertBefore(label, control);
        }

        const labelId = `label-${control.id || Date.now()}`;
        label.id = labelId;
        label.htmlFor = control.id;
        label.textContent = labelText;

        if (required) {
            const requiredIndicator = document.createElement('span');
            requiredIndicator.textContent = ' *';
            requiredIndicator.className = 'text-red-500';
            requiredIndicator.setAttribute('aria-label', 'required');
            label.appendChild(requiredIndicator);
            
            control.setAttribute('aria-required', 'true');
        }

        return label;
    }
}

/**
 * Color Contrast Utilities
 */
export class ColorContrastManager {
    /**
     * Calculate relative luminance
     */
    static getRelativeLuminance(r, g, b) {
        const [rs, gs, bs] = [r, g, b].map(c => {
            c = c / 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });
        
        return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
    }

    /**
     * Calculate contrast ratio between two colors
     */
    static getContrastRatio(color1, color2) {
        const l1 = this.getRelativeLuminance(...color1);
        const l2 = this.getRelativeLuminance(...color2);
        
        const lighter = Math.max(l1, l2);
        const darker = Math.min(l1, l2);
        
        return (lighter + 0.05) / (darker + 0.05);
    }

    /**
     * Check if color combination meets WCAG standards
     */
    static meetsWCAG(foreground, background, level = 'AA', size = 'normal') {
        const ratio = this.getContrastRatio(foreground, background);
        
        const requirements = {
            'AA': { normal: 4.5, large: 3.0 },
            'AAA': { normal: 7.0, large: 4.5 }
        };
        
        const required = requirements[level][size];
        return ratio >= required;
    }

    /**
     * Convert hex color to RGB
     */
    static hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? [
            parseInt(result[1], 16),
            parseInt(result[2], 16),
            parseInt(result[3], 16)
        ] : null;
    }

    /**
     * Validate color contrast for element
     */
    static validateElementContrast(element) {
        const styles = window.getComputedStyle(element);
        const color = styles.color;
        const backgroundColor = styles.backgroundColor;
        
        // This is a simplified version - in practice, you'd need more robust color parsing
        console.log(`Element contrast check:`, { color, backgroundColor });
        
        return true; // Placeholder - implement full color parsing as needed
    }
}

/**
 * Keyboard Navigation Utilities
 */
export class KeyboardNavigationManager {
    constructor() {
        this.keyHandlers = new Map();
    }

    /**
     * Add keyboard shortcut
     */
    addShortcut(key, handler, options = {}) {
        const shortcutKey = this.normalizeKey(key, options);
        
        if (!this.keyHandlers.has(shortcutKey)) {
            this.keyHandlers.set(shortcutKey, []);
        }
        
        this.keyHandlers.get(shortcutKey).push({
            handler,
            options,
            element: options.element || document
        });

        const listener = (e) => this.handleKeyPress(e, shortcutKey);
        (options.element || document).addEventListener('keydown', listener);
        
        return () => {
            (options.element || document).removeEventListener('keydown', listener);
        };
    }

    /**
     * Normalize key combination
     */
    normalizeKey(key, options) {
        const parts = [];
        
        if (options.ctrl) parts.push('ctrl');
        if (options.alt) parts.push('alt');
        if (options.shift) parts.push('shift');
        if (options.meta) parts.push('meta');
        
        parts.push(key.toLowerCase());
        
        return parts.join('+');
    }

    /**
     * Handle key press
     */
    handleKeyPress(e, shortcutKey) {
        const pressedKey = this.normalizeKey(e.key, {
            ctrl: e.ctrlKey,
            alt: e.altKey,
            shift: e.shiftKey,
            meta: e.metaKey
        });

        if (pressedKey === shortcutKey) {
            const handlers = this.keyHandlers.get(shortcutKey) || [];
            
            handlers.forEach(({ handler, options }) => {
                if (options.preventDefault !== false) {
                    e.preventDefault();
                }
                
                if (options.stopPropagation !== false) {
                    e.stopPropagation();
                }
                
                handler(e);
            });
        }
    }

    /**
     * Create roving tabindex for lists
     */
    createRovingTabindex(container, itemSelector) {
        const items = Array.from(container.querySelectorAll(itemSelector));
        let currentIndex = 0;

        // Set initial tabindex
        items.forEach((item, index) => {
            item.setAttribute('tabindex', index === 0 ? '0' : '-1');
        });

        const handleKeyDown = (e) => {
            let newIndex = currentIndex;

            switch (e.key) {
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault();
                    newIndex = (currentIndex + 1) % items.length;
                    break;
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault();
                    newIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                    break;
                case 'Home':
                    e.preventDefault();
                    newIndex = 0;
                    break;
                case 'End':
                    e.preventDefault();
                    newIndex = items.length - 1;
                    break;
                default:
                    return;
            }

            // Update tabindex
            items[currentIndex].setAttribute('tabindex', '-1');
            items[newIndex].setAttribute('tabindex', '0');
            items[newIndex].focus();
            
            currentIndex = newIndex;
        };

        container.addEventListener('keydown', handleKeyDown);
        
        return () => {
            container.removeEventListener('keydown', handleKeyDown);
        };
    }
}

/**
 * Screen Reader Utilities
 */
export class ScreenReaderManager {
    /**
     * Hide element from screen readers
     */
    static hide(element) {
        element.setAttribute('aria-hidden', 'true');
    }

    /**
     * Show element to screen readers
     */
    static show(element) {
        element.removeAttribute('aria-hidden');
    }

    /**
     * Create screen reader only text
     */
    static createSROnlyText(text) {
        const span = document.createElement('span');
        span.className = 'sr-only';
        span.textContent = text;
        return span;
    }

    /**
     * Add screen reader description
     */
    static addDescription(element, description) {
        const descId = `desc-${Date.now()}`;
        const descElement = document.createElement('div');
        descElement.id = descId;
        descElement.className = 'sr-only';
        descElement.textContent = description;
        
        element.parentNode.insertBefore(descElement, element.nextSibling);
        element.setAttribute('aria-describedby', descId);
        
        return descElement;
    }

    /**
     * Announce loading state
     */
    static announceLoading(element, message = 'Loading...') {
        element.setAttribute('aria-busy', 'true');
        element.setAttribute('aria-live', 'polite');
        
        const loadingText = this.createSROnlyText(message);
        element.appendChild(loadingText);
        
        return () => {
            element.removeAttribute('aria-busy');
            element.removeAttribute('aria-live');
            if (loadingText.parentNode) {
                loadingText.parentNode.removeChild(loadingText);
            }
        };
    }

    /**
     * Announce error state
     */
    static announceError(element, errorMessage) {
        element.setAttribute('aria-invalid', 'true');
        
        const errorId = `error-${Date.now()}`;
        const errorElement = document.createElement('div');
        errorElement.id = errorId;
        errorElement.className = 'text-red-600 text-sm mt-1';
        errorElement.textContent = errorMessage;
        errorElement.setAttribute('role', 'alert');
        
        element.parentNode.insertBefore(errorElement, element.nextSibling);
        element.setAttribute('aria-describedby', errorId);
        
        return () => {
            element.removeAttribute('aria-invalid');
            element.removeAttribute('aria-describedby');
            if (errorElement.parentNode) {
                errorElement.parentNode.removeChild(errorElement);
            }
        };
    }
}

/**
 * Mobile Accessibility Utilities
 */
export class MobileAccessibilityManager {
    /**
     * Ensure minimum touch target size
     */
    static ensureTouchTargetSize(element, minSize = 44) {
        const rect = element.getBoundingClientRect();
        
        if (rect.width < minSize || rect.height < minSize) {
            element.style.minWidth = `${minSize}px`;
            element.style.minHeight = `${minSize}px`;
            element.style.display = element.style.display || 'inline-flex';
            element.style.alignItems = 'center';
            element.style.justifyContent = 'center';
        }
    }

    /**
     * Add swipe gesture alternative
     */
    static addSwipeAlternative(element, onSwipe, direction = 'horizontal') {
        // Add keyboard alternative for swipe gestures
        const handleKeyDown = (e) => {
            const keys = direction === 'horizontal' 
                ? ['ArrowLeft', 'ArrowRight']
                : ['ArrowUp', 'ArrowDown'];
            
            if (keys.includes(e.key)) {
                e.preventDefault();
                const swipeDirection = e.key.includes('Left') || e.key.includes('Up') ? 'prev' : 'next';
                onSwipe(swipeDirection);
            }
        };

        element.addEventListener('keydown', handleKeyDown);
        element.setAttribute('tabindex', '0');
        
        // Add instructions for screen readers
        const instructions = ScreenReaderManager.createSROnlyText(
            `Use arrow keys to navigate ${direction === 'horizontal' ? 'left and right' : 'up and down'}`
        );
        element.appendChild(instructions);
        
        return () => {
            element.removeEventListener('keydown', handleKeyDown);
        };
    }

    /**
     * Handle reduced motion preference
     */
    static respectReducedMotion(element, animationClass) {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (prefersReducedMotion) {
            element.classList.add('motion-reduce');
        } else {
            element.classList.add(animationClass);
        }
    }
}

/**
 * Form Accessibility Utilities
 */
export class FormAccessibilityManager {
    /**
     * Enhance form with accessibility features
     */
    static enhanceForm(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            this.enhanceInput(input);
        });

        // Add form validation
        form.addEventListener('submit', (e) => {
            this.validateForm(form, e);
        });
    }

    /**
     * Enhance individual input
     */
    static enhanceInput(input) {
        // Ensure label association
        if (!input.labels || input.labels.length === 0) {
            console.warn('Input missing label:', input);
        }

        // Add required indicator
        if (input.required) {
            input.setAttribute('aria-required', 'true');
        }

        // Add input validation
        input.addEventListener('blur', () => {
            this.validateInput(input);
        });

        input.addEventListener('input', () => {
            // Clear error state on input
            if (input.getAttribute('aria-invalid') === 'true') {
                this.clearInputError(input);
            }
        });
    }

    /**
     * Validate individual input
     */
    static validateInput(input) {
        const isValid = input.checkValidity();
        
        if (!isValid) {
            this.showInputError(input, input.validationMessage);
        } else {
            this.clearInputError(input);
        }
        
        return isValid;
    }

    /**
     * Show input error
     */
    static showInputError(input, message) {
        input.setAttribute('aria-invalid', 'true');
        
        let errorElement = document.getElementById(`${input.id}-error`);
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.id = `${input.id}-error`;
            errorElement.className = 'text-red-600 text-sm mt-1';
            errorElement.setAttribute('role', 'alert');
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }
        
        errorElement.textContent = message;
        input.setAttribute('aria-describedby', errorElement.id);
    }

    /**
     * Clear input error
     */
    static clearInputError(input) {
        input.removeAttribute('aria-invalid');
        
        const errorElement = document.getElementById(`${input.id}-error`);
        if (errorElement) {
            errorElement.remove();
        }
        
        input.removeAttribute('aria-describedby');
    }

    /**
     * Validate entire form
     */
    static validateForm(form, event) {
        const inputs = form.querySelectorAll('input, select, textarea');
        let isValid = true;
        let firstInvalidInput = null;

        inputs.forEach(input => {
            if (!this.validateInput(input)) {
                isValid = false;
                if (!firstInvalidInput) {
                    firstInvalidInput = input;
                }
            }
        });

        if (!isValid) {
            event.preventDefault();
            
            // Focus first invalid input
            if (firstInvalidInput) {
                firstInvalidInput.focus();
                AriaManager.announce('Please correct the errors in the form');
            }
        }

        return isValid;
    }
}

/**
 * Main Accessibility Manager
 * Coordinates all accessibility utilities
 */
export class AccessibilityManager {
    constructor() {
        this.focusManager = new FocusManager();
        this.keyboardManager = new KeyboardNavigationManager();
        this.initialized = false;
    }

    /**
     * Initialize accessibility features
     */
    init() {
        if (this.initialized) return;

        this.setupGlobalKeyboardShortcuts();
        this.setupSkipLinks();
        this.enhanceExistingElements();
        this.setupReducedMotionSupport();
        
        this.initialized = true;
    }

    /**
     * Setup global keyboard shortcuts
     */
    setupGlobalKeyboardShortcuts() {
        // Skip to main content
        this.keyboardManager.addShortcut('1', () => {
            const main = document.querySelector('main, [role="main"]');
            if (main) {
                this.focusManager.moveFocusTo(main, 'Skipped to main content');
            }
        }, { alt: true });

        // Skip to navigation
        this.keyboardManager.addShortcut('2', () => {
            const nav = document.querySelector('nav, [role="navigation"]');
            if (nav) {
                this.focusManager.moveFocusTo(nav, 'Skipped to navigation');
            }
        }, { alt: true });

        // Open accessibility help
        this.keyboardManager.addShortcut('h', () => {
            this.showAccessibilityHelp();
        }, { alt: true, shift: true });
    }

    /**
     * Setup skip links
     */
    setupSkipLinks() {
        const main = document.querySelector('main, [role="main"]');
        if (main && !main.id) {
            main.id = 'main-content';
        }

        const skipLink = this.focusManager.createSkipLink('main-content');
        document.body.insertBefore(skipLink, document.body.firstChild);
    }

    /**
     * Enhance existing elements
     */
    enhanceExistingElements() {
        // Enhance forms
        document.querySelectorAll('form').forEach(form => {
            FormAccessibilityManager.enhanceForm(form);
        });

        // Ensure touch target sizes on mobile
        if (window.innerWidth <= 768) {
            document.querySelectorAll('button, a, input, select').forEach(element => {
                MobileAccessibilityManager.ensureTouchTargetSize(element);
            });
        }

        // Add ARIA labels to unlabeled buttons
        document.querySelectorAll('button:not([aria-label]):not([aria-labelledby])').forEach(button => {
            if (!button.textContent.trim()) {
                console.warn('Button missing accessible name:', button);
            }
        });
    }

    /**
     * Setup reduced motion support
     */
    setupReducedMotionSupport() {
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        
        const handleReducedMotion = (e) => {
            document.body.classList.toggle('reduce-motion', e.matches);
        };

        handleReducedMotion(mediaQuery);
        mediaQuery.addEventListener('change', handleReducedMotion);
    }

    /**
     * Show accessibility help modal
     */
    showAccessibilityHelp() {
        AriaManager.announce('Opening accessibility help');
        
        // This would open a help modal with keyboard shortcuts and accessibility features
        console.log('Accessibility Help - Keyboard Shortcuts:');
        console.log('Alt + 1: Skip to main content');
        console.log('Alt + 2: Skip to navigation');
        console.log('Alt + Shift + H: Show this help');
        console.log('Tab: Navigate forward');
        console.log('Shift + Tab: Navigate backward');
        console.log('Enter/Space: Activate buttons and links');
        console.log('Escape: Close modals and dropdowns');
    }

    /**
     * Announce message to screen readers
     */
    announce(message, politeness = 'polite') {
        AriaManager.announce(message, politeness);
    }

    /**
     * Create accessible modal
     */
    createAccessibleModal(content, options = {}) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', options.titleId || 'modal-title');
        
        if (options.describedBy) {
            modal.setAttribute('aria-describedby', options.describedBy);
        }

        // Backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'absolute inset-0 bg-black bg-opacity-50';
        backdrop.addEventListener('click', () => {
            if (options.closeOnBackdrop !== false) {
                this.closeModal(modal);
            }
        });

        // Content
        const modalContent = document.createElement('div');
        modalContent.className = 'relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto';
        modalContent.appendChild(content);

        modal.appendChild(backdrop);
        modal.appendChild(modalContent);

        // Setup focus trap
        const cleanup = this.focusManager.trapFocus(modalContent, {
            focusFirst: true,
            restoreFocus: true
        });

        // Handle escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                this.closeModal(modal);
            }
        };

        document.addEventListener('keydown', handleEscape);

        // Cleanup function
        modal._cleanup = () => {
            cleanup();
            document.removeEventListener('keydown', handleEscape);
        };

        document.body.appendChild(modal);
        this.announce('Modal opened');

        return modal;
    }

    /**
     * Close modal
     */
    closeModal(modal) {
        if (modal._cleanup) {
            modal._cleanup();
        }
        
        modal.remove();
        this.announce('Modal closed');
    }
}

// Create global instance
export const accessibilityManager = new AccessibilityManager();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        accessibilityManager.init();
    });
} else {
    accessibilityManager.init();
}

// Export individual managers for specific use cases
export {
    FocusManager,
    AriaManager,
    ColorContrastManager,
    KeyboardNavigationManager,
    ScreenReaderManager,
    MobileAccessibilityManager,
    FormAccessibilityManager
};

// Vue 3 Composables for accessibility
export function useFocusManagement() {
    const focusManager = new FocusManager();
    
    return {
        trapFocus: focusManager.trapFocus.bind(focusManager),
        moveFocusTo: focusManager.moveFocusTo.bind(focusManager),
        getFocusableElements: focusManager.getFocusableElements.bind(focusManager)
    };
}

export function useAria() {
    return {
        setAttributes: AriaManager.setAttributes,
        toggleExpanded: AriaManager.toggleExpanded,
        setPressed: AriaManager.setPressed,
        announce: AriaManager.announce,
        createDescription: AriaManager.createDescription
    };
}

export function useKeyboardNavigation() {
    const keyboardManager = new KeyboardNavigationManager();
    
    return {
        addShortcut: keyboardManager.addShortcut.bind(keyboardManager),
        createRovingTabindex: keyboardManager.createRovingTabindex.bind(keyboardManager)
    };
}

export function useScreenReader() {
    return {
        hide: ScreenReaderManager.hide,
        show: ScreenReaderManager.show,
        createSROnlyText: ScreenReaderManager.createSROnlyText,
        addDescription: ScreenReaderManager.addDescription,
        announceLoading: ScreenReaderManager.announceLoading,
        announceError: ScreenReaderManager.announceError
    };
}
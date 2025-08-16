<template>
    <div class="touch-optimized-controls">
        <!-- Touch-optimized Button -->
        <component
            v-if="type === 'button'"
            :is="href ? 'a' : 'button'"
            :href="href"
            :type="href ? undefined : buttonType"
            :disabled="disabled"
            @click="handleClick"
            @touchstart="handleTouchStart"
            @touchend="handleTouchEnd"
            class="touch-button"
            :class="[
                sizeClasses,
                variantClasses,
                {
                    'touch-button-disabled': disabled,
                    'touch-button-loading': loading,
                    'touch-button-pressed': isPressed
                }
            ]"
            :aria-label="ariaLabel || label"
            :aria-pressed="pressed"
            :aria-disabled="disabled"
            :aria-busy="loading"
        >
            <div class="touch-button-content">
                <component 
                    v-if="loading" 
                    :is="LoadingIcon" 
                    class="touch-button-icon animate-spin" 
                    aria-hidden="true"
                />
                <component 
                    v-else-if="icon" 
                    :is="icon" 
                    class="touch-button-icon" 
                    aria-hidden="true"
                />
                <span v-if="$slots.default || label" class="touch-button-text">
                    <slot>{{ label }}</slot>
                </span>
                <span v-if="badge" class="touch-button-badge" :aria-label="`${badge} notifications`">{{ badge }}</span>
                <span v-if="loading" class="sr-only">Loading...</span>
            </div>
            <div v-if="ripple" class="touch-ripple" ref="rippleRef"></div>
        </component>

        <!-- Touch-optimized Input -->
        <div
            v-else-if="type === 'input'"
            class="touch-input-container"
            :class="{ 'touch-input-focused': isFocused, 'touch-input-error': error }"
        >
            <label v-if="label" :for="inputId" class="touch-input-label">
                {{ label }}
                <span v-if="required" class="text-red-500" aria-label="required">*</span>
            </label>
            
            <div class="touch-input-wrapper">
                <component
                    v-if="inputIcon"
                    :is="inputIcon"
                    class="touch-input-icon"
                    aria-hidden="true"
                />
                
                <input
                    :id="inputId"
                    ref="inputRef"
                    :type="inputType"
                    :value="modelValue"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    :required="required"
                    :autocomplete="autocomplete"
                    :inputmode="inputMode"
                    @input="handleInput"
                    @focus="handleFocus"
                    @blur="handleBlur"
                    @touchstart="handleInputTouchStart"
                    class="touch-input"
                    :class="{ 'touch-input-with-icon': inputIcon }"
                    :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
                    :aria-invalid="error ? 'true' : undefined"
                    :aria-required="required"
                />
                
                <button
                    v-if="clearable && modelValue"
                    @click="clearInput"
                    class="touch-input-clear"
                    type="button"
                    :aria-label="`Clear ${label || 'input'}`"
                >
                    <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
            
            <div v-if="error" :id="`${inputId}-error`" class="touch-input-error-text" role="alert">
                {{ error }}
            </div>
            
            <div v-else-if="hint" :id="`${inputId}-hint`" class="touch-input-hint">
                {{ hint }}
            </div>
        </div>

        <!-- Touch-optimized Select -->
        <div
            v-else-if="type === 'select'"
            class="touch-select-container"
            :class="{ 'touch-select-error': error }"
        >
            <label v-if="label" :for="selectId" class="touch-select-label">
                {{ label }}
                <span v-if="required" class="text-red-500">*</span>
            </label>
            
            <div class="touch-select-wrapper" @click="toggleSelect">
                <div
                    :id="selectId"
                    class="touch-select"
                    :class="{ 
                        'touch-select-open': isSelectOpen,
                        'touch-select-placeholder': !selectedOption
                    }"
                    role="combobox"
                    :aria-expanded="isSelectOpen"
                    :aria-haspopup="true"
                    tabindex="0"
                    @keydown="handleSelectKeydown"
                >
                    <span class="touch-select-text">
                        {{ selectedOption?.label || placeholder || 'Select an option' }}
                    </span>
                    <ChevronDownIcon 
                        class="touch-select-icon" 
                        :class="{ 'rotate-180': isSelectOpen }"
                    />
                </div>
                
                <div
                    v-if="isSelectOpen"
                    class="touch-select-dropdown"
                    role="listbox"
                >
                    <div
                        v-for="(option, index) in options"
                        :key="option.value"
                        @click="selectOption(option)"
                        class="touch-select-option"
                        :class="{ 
                            'touch-select-option-selected': option.value === modelValue,
                            'touch-select-option-highlighted': highlightedIndex === index
                        }"
                        role="option"
                        :aria-selected="option.value === modelValue"
                    >
                        <span>{{ option.label }}</span>
                        <CheckIcon 
                            v-if="option.value === modelValue" 
                            class="h-5 w-5 text-blue-600 dark:text-blue-400" 
                        />
                    </div>
                </div>
            </div>
            
            <div v-if="error" class="touch-select-error-text">
                {{ error }}
            </div>
        </div>

        <!-- Touch-optimized Toggle/Switch -->
        <div
            v-else-if="type === 'toggle'"
            class="touch-toggle-container"
        >
            <label v-if="label" class="touch-toggle-label">
                {{ label }}
            </label>
            
            <button
                @click="toggleSwitch"
                @touchstart="handleToggleTouchStart"
                @touchend="handleToggleTouchEnd"
                class="touch-toggle"
                :class="{ 
                    'touch-toggle-on': modelValue,
                    'touch-toggle-pressed': isTogglePressed
                }"
                role="switch"
                :aria-checked="modelValue"
                :aria-label="ariaLabel || `Toggle ${label}`"
                :disabled="disabled"
            >
                <span class="touch-toggle-thumb"></span>
            </button>
            
            <span v-if="description" class="touch-toggle-description">
                {{ description }}
            </span>
        </div>

        <!-- Touch-optimized Slider -->
        <div
            v-else-if="type === 'slider'"
            class="touch-slider-container"
        >
            <label v-if="label" class="touch-slider-label">
                {{ label }}
                <span class="touch-slider-value">{{ modelValue }}</span>
            </label>
            
            <div
                ref="sliderRef"
                class="touch-slider-track"
                @touchstart="handleSliderTouchStart"
                @touchmove="handleSliderTouchMove"
                @touchend="handleSliderTouchEnd"
                @click="handleSliderClick"
            >
                <div 
                    class="touch-slider-fill" 
                    :style="{ width: `${sliderPercentage}%` }"
                ></div>
                <div 
                    class="touch-slider-thumb" 
                    :style="{ left: `${sliderPercentage}%` }"
                    :class="{ 'touch-slider-thumb-dragging': isSliderDragging }"
                ></div>
            </div>
            
            <div v-if="showMinMax" class="touch-slider-labels">
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ min }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ max }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { 
    XMarkIcon, 
    ChevronDownIcon, 
    CheckIcon,
    ArrowPathIcon as LoadingIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ['button', 'input', 'select', 'toggle', 'slider'].includes(value)
    },
    // Button props
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'outline', 'ghost', 'danger'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    icon: Object,
    label: String,
    badge: [String, Number],
    loading: Boolean,
    disabled: Boolean,
    href: String,
    buttonType: {
        type: String,
        default: 'button'
    },
    pressed: Boolean,
    ripple: {
        type: Boolean,
        default: true
    },
    
    // Input props
    modelValue: [String, Number, Boolean],
    inputType: {
        type: String,
        default: 'text'
    },
    placeholder: String,
    required: Boolean,
    autocomplete: String,
    inputMode: String,
    inputIcon: Object,
    clearable: Boolean,
    error: String,
    hint: String,
    
    // Select props
    options: {
        type: Array,
        default: () => []
    },
    
    // Toggle props
    description: String,
    
    // Slider props
    min: {
        type: Number,
        default: 0
    },
    max: {
        type: Number,
        default: 100
    },
    step: {
        type: Number,
        default: 1
    },
    showMinMax: Boolean,
    
    // Common props
    ariaLabel: String
})

const emit = defineEmits(['update:modelValue', 'click', 'focus', 'blur', 'change'])

// Refs
const inputRef = ref(null)
const rippleRef = ref(null)
const sliderRef = ref(null)

// State
const isPressed = ref(false)
const isFocused = ref(false)
const isSelectOpen = ref(false)
const highlightedIndex = ref(-1)
const isTogglePressed = ref(false)
const isSliderDragging = ref(false)

// Computed
const inputId = computed(() => `touch-input-${Math.random().toString(36).substr(2, 9)}`)
const selectId = computed(() => `touch-select-${Math.random().toString(36).substr(2, 9)}`)

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'touch-button-sm',
        md: 'touch-button-md',
        lg: 'touch-button-lg',
        xl: 'touch-button-xl'
    }
    return sizes[props.size]
})

const variantClasses = computed(() => {
    const variants = {
        primary: 'touch-button-primary',
        secondary: 'touch-button-secondary',
        outline: 'touch-button-outline',
        ghost: 'touch-button-ghost',
        danger: 'touch-button-danger'
    }
    return variants[props.variant]
})

const selectedOption = computed(() => {
    return props.options.find(option => option.value === props.modelValue)
})

const sliderPercentage = computed(() => {
    return ((props.modelValue - props.min) / (props.max - props.min)) * 100
})

// Methods
const handleClick = (e) => {
    if (props.disabled || props.loading) return
    
    if (props.ripple) {
        createRipple(e)
    }
    
    emit('click', e)
}

const handleTouchStart = () => {
    if (props.disabled || props.loading) return
    isPressed.value = true
}

const handleTouchEnd = () => {
    isPressed.value = false
}

const createRipple = (e) => {
    if (!rippleRef.value) return
    
    const ripple = rippleRef.value
    const rect = e.currentTarget.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height)
    const x = e.clientX - rect.left - size / 2
    const y = e.clientY - rect.top - size / 2
    
    ripple.style.width = ripple.style.height = size + 'px'
    ripple.style.left = x + 'px'
    ripple.style.top = y + 'px'
    ripple.classList.add('touch-ripple-active')
    
    setTimeout(() => {
        ripple.classList.remove('touch-ripple-active')
    }, 600)
}

// Input methods
const handleInput = (e) => {
    emit('update:modelValue', e.target.value)
}

const handleFocus = (e) => {
    isFocused.value = true
    emit('focus', e)
}

const handleBlur = (e) => {
    isFocused.value = false
    emit('blur', e)
}

const handleInputTouchStart = () => {
    // Prevent zoom on iOS
    if (inputRef.value) {
        inputRef.value.style.fontSize = '16px'
    }
}

const clearInput = () => {
    emit('update:modelValue', '')
    if (inputRef.value) {
        inputRef.value.focus()
    }
}

// Select methods
const toggleSelect = () => {
    isSelectOpen.value = !isSelectOpen.value
    if (isSelectOpen.value) {
        highlightedIndex.value = props.options.findIndex(option => option.value === props.modelValue)
    }
}

const selectOption = (option) => {
    emit('update:modelValue', option.value)
    emit('change', option)
    isSelectOpen.value = false
    highlightedIndex.value = -1
}

const handleSelectKeydown = (e) => {
    switch (e.key) {
        case 'Enter':
        case ' ':
            e.preventDefault()
            if (isSelectOpen.value && highlightedIndex.value >= 0) {
                selectOption(props.options[highlightedIndex.value])
            } else {
                toggleSelect()
            }
            break
        case 'Escape':
            isSelectOpen.value = false
            highlightedIndex.value = -1
            break
        case 'ArrowDown':
            e.preventDefault()
            if (!isSelectOpen.value) {
                toggleSelect()
            } else {
                highlightedIndex.value = Math.min(highlightedIndex.value + 1, props.options.length - 1)
            }
            break
        case 'ArrowUp':
            e.preventDefault()
            if (isSelectOpen.value) {
                highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0)
            }
            break
    }
}

// Toggle methods
const toggleSwitch = () => {
    if (props.disabled) return
    emit('update:modelValue', !props.modelValue)
    emit('change', !props.modelValue)
}

const handleToggleTouchStart = () => {
    if (props.disabled) return
    isTogglePressed.value = true
}

const handleToggleTouchEnd = () => {
    isTogglePressed.value = false
}

// Slider methods
const handleSliderTouchStart = (e) => {
    isSliderDragging.value = true
    updateSliderValue(e.touches[0])
}

const handleSliderTouchMove = (e) => {
    if (!isSliderDragging.value) return
    e.preventDefault()
    updateSliderValue(e.touches[0])
}

const handleSliderTouchEnd = () => {
    isSliderDragging.value = false
}

const handleSliderClick = (e) => {
    updateSliderValue(e)
}

const updateSliderValue = (touch) => {
    if (!sliderRef.value) return
    
    const rect = sliderRef.value.getBoundingClientRect()
    const percentage = Math.max(0, Math.min(1, (touch.clientX - rect.left) / rect.width))
    const value = Math.round((percentage * (props.max - props.min) + props.min) / props.step) * props.step
    
    emit('update:modelValue', Math.max(props.min, Math.min(props.max, value)))
}

// Close select dropdown when clicking outside
const handleClickOutside = (e) => {
    if (isSelectOpen.value && !e.target.closest('.touch-select-container')) {
        isSelectOpen.value = false
        highlightedIndex.value = -1
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Touch Button Styles */
.touch-button {
    @apply relative inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 overflow-hidden;
    @apply focus:outline-none focus:ring-2 focus:ring-offset-2;
    min-height: 44px;
    min-width: 44px;
}

.touch-button-sm {
    @apply px-3 py-2 text-sm;
    min-height: 40px;
}

.touch-button-md {
    @apply px-4 py-3 text-base;
    min-height: 44px;
}

.touch-button-lg {
    @apply px-6 py-4 text-lg;
    min-height: 48px;
}

.touch-button-xl {
    @apply px-8 py-5 text-xl;
    min-height: 52px;
}

.touch-button-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800;
    @apply focus:ring-blue-500;
}

.touch-button-secondary {
    @apply bg-gray-200 text-gray-900 hover:bg-gray-300 active:bg-gray-400;
    @apply dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:active:bg-gray-500;
    @apply focus:ring-gray-500;
}

.touch-button-outline {
    @apply border-2 border-blue-600 text-blue-600 hover:bg-blue-50 active:bg-blue-100;
    @apply dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-900/20 dark:active:bg-blue-900/40;
    @apply focus:ring-blue-500;
}

.touch-button-ghost {
    @apply text-gray-700 hover:bg-gray-100 active:bg-gray-200;
    @apply dark:text-gray-300 dark:hover:bg-gray-800 dark:active:bg-gray-700;
    @apply focus:ring-gray-500;
}

.touch-button-danger {
    @apply bg-red-600 text-white hover:bg-red-700 active:bg-red-800;
    @apply focus:ring-red-500;
}

.touch-button-disabled {
    @apply opacity-50 cursor-not-allowed;
}

.touch-button-pressed {
    @apply scale-95;
}

.touch-button-content {
    @apply flex items-center space-x-2;
}

.touch-button-icon {
    @apply h-5 w-5 flex-shrink-0;
}

.touch-button-badge {
    @apply ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-0.5;
}

.touch-ripple {
    @apply absolute rounded-full bg-white bg-opacity-30 pointer-events-none;
    transform: scale(0);
    transition: transform 0.6s ease-out;
}

.touch-ripple-active {
    transform: scale(1);
}

/* Touch Input Styles */
.touch-input-container {
    @apply w-full;
}

.touch-input-label {
    @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
}

.touch-input-wrapper {
    @apply relative;
}

.touch-input {
    @apply w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg;
    @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-white;
    @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
    @apply transition-colors duration-200;
    min-height: 44px;
    font-size: 16px; /* Prevents zoom on iOS */
}

.touch-input-with-icon {
    @apply pl-12;
}

.touch-input-icon {
    @apply absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400;
}

.touch-input-clear {
    @apply absolute right-3 top-1/2 transform -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600;
    @apply dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700;
}

.touch-input-focused {
    @apply ring-2 ring-blue-500;
}

.touch-input-error .touch-input {
    @apply border-red-500 focus:ring-red-500;
}

.touch-input-error-text {
    @apply mt-1 text-sm text-red-600 dark:text-red-400;
}

.touch-input-hint {
    @apply mt-1 text-sm text-gray-500 dark:text-gray-400;
}

/* Touch Select Styles */
.touch-select-container {
    @apply relative w-full;
}

.touch-select-label {
    @apply block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
}

.touch-select-wrapper {
    @apply relative;
}

.touch-select {
    @apply w-full px-4 py-3 text-base border border-gray-300 dark:border-gray-600 rounded-lg;
    @apply bg-white dark:bg-gray-800 text-gray-900 dark:text-white cursor-pointer;
    @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
    @apply flex items-center justify-between;
    min-height: 44px;
}

.touch-select-placeholder {
    @apply text-gray-500 dark:text-gray-400;
}

.touch-select-icon {
    @apply h-5 w-5 text-gray-400 transition-transform duration-200;
}

.touch-select-dropdown {
    @apply absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600;
    @apply rounded-lg shadow-lg z-50 max-h-60 overflow-y-auto;
}

.touch-select-option {
    @apply px-4 py-3 text-base cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700;
    @apply flex items-center justify-between;
    min-height: 44px;
}

.touch-select-option-selected {
    @apply bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400;
}

.touch-select-option-highlighted {
    @apply bg-gray-100 dark:bg-gray-700;
}

.touch-select-error .touch-select {
    @apply border-red-500 focus:ring-red-500;
}

.touch-select-error-text {
    @apply mt-1 text-sm text-red-600 dark:text-red-400;
}

/* Touch Toggle Styles */
.touch-toggle-container {
    @apply flex items-center space-x-3;
}

.touch-toggle-label {
    @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.touch-toggle {
    @apply relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200;
    @apply bg-gray-200 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
    min-height: 44px;
    min-width: 44px;
    padding: 9px;
}

.touch-toggle-on {
    @apply bg-blue-600 dark:bg-blue-500;
}

.touch-toggle-pressed {
    @apply scale-95;
}

.touch-toggle-thumb {
    @apply inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200;
    @apply translate-x-0;
}

.touch-toggle-on .touch-toggle-thumb {
    @apply translate-x-5;
}

.touch-toggle-description {
    @apply text-sm text-gray-500 dark:text-gray-400;
}

/* Touch Slider Styles */
.touch-slider-container {
    @apply w-full;
}

.touch-slider-label {
    @apply flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
}

.touch-slider-value {
    @apply text-blue-600 dark:text-blue-400;
}

.touch-slider-track {
    @apply relative h-2 bg-gray-200 dark:bg-gray-700 rounded-full cursor-pointer;
    min-height: 44px;
    padding: 21px 0;
}

.touch-slider-fill {
    @apply absolute top-1/2 left-0 h-2 bg-blue-600 dark:bg-blue-500 rounded-full transform -translate-y-1/2;
}

.touch-slider-thumb {
    @apply absolute top-1/2 w-6 h-6 bg-white border-2 border-blue-600 dark:border-blue-500 rounded-full;
    @apply transform -translate-x-1/2 -translate-y-1/2 cursor-grab shadow-md;
    @apply transition-transform duration-150;
}

.touch-slider-thumb-dragging {
    @apply scale-110 cursor-grabbing;
}

.touch-slider-labels {
    @apply flex justify-between mt-2;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .touch-button,
    .touch-input,
    .touch-select,
    .touch-toggle,
    .touch-slider-thumb {
        transition: none;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .touch-button-outline {
        @apply border-4;
    }
    
    .touch-input,
    .touch-select {
        @apply border-2;
    }
}
</style>
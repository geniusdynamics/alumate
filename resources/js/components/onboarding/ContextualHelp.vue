<template>
    <div
        class="contextual-help-tooltip fixed z-50 bg-gray-900 text-white rounded-lg shadow-xl max-w-xs"
        :style="tooltipStyle"
    >
        <!-- Arrow -->
        <div
            class="absolute w-3 h-3 bg-gray-900 transform rotate-45"
            :style="arrowStyle"
        ></div>

        <!-- Content -->
        <div class="relative p-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center">
                        <QuestionMarkCircleIcon class="w-4 h-4 text-white" />
                    </div>
                    <h3 class="text-sm font-semibold">{{ helpContent.title }}</h3>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-white"
                >
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>

            <!-- Description -->
            <p class="text-sm text-gray-300 mb-3">
                {{ helpContent.description }}
            </p>

            <!-- Steps (if provided) -->
            <div v-if="helpContent.steps && helpContent.steps.length > 0" class="mb-3">
                <h4 class="text-xs font-semibold text-gray-200 mb-2">How to:</h4>
                <ol class="text-xs text-gray-300 space-y-1">
                    <li
                        v-for="(step, index) in helpContent.steps"
                        :key="step"
                        class="flex items-start space-x-2"
                    >
                        <span class="flex items-center justify-center w-4 h-4 bg-blue-500 text-white rounded-full text-xs font-medium flex-shrink-0 mt-0.5">
                            {{ index + 1 }}
                        </span>
                        <span>{{ step }}</span>
                    </li>
                </ol>
            </div>

            <!-- Tips (if provided) -->
            <div v-if="helpContent.tips && helpContent.tips.length > 0" class="mb-3">
                <h4 class="text-xs font-semibold text-gray-200 mb-2">💡 Tips:</h4>
                <ul class="text-xs text-gray-300 space-y-1">
                    <li
                        v-for="tip in helpContent.tips"
                        :key="tip"
                        class="flex items-start space-x-2"
                    >
                        <span class="text-yellow-400 mt-0.5">•</span>
                        <span>{{ tip }}</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div v-if="helpContent.actions && helpContent.actions.length > 0" class="flex space-x-2">
                <button
                    v-for="action in helpContent.actions"
                    :key="action.label"
                    @click="performAction(action)"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-medium"
                >
                    {{ action.label }}
                </button>
            </div>

            <!-- Learn More Link -->
            <div v-if="helpContent.learnMoreUrl" class="mt-3 pt-3 border-t border-gray-700">
                <a
                    :href="helpContent.learnMoreUrl"
                    target="_blank"
                    class="text-xs text-blue-400 hover:text-blue-300 flex items-center space-x-1"
                >
                    <span>Learn more</span>
                    <ArrowTopRightOnSquareIcon class="w-3 h-3" />
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {
    XMarkIcon,
    QuestionMarkCircleIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    helpContent: {
        type: Object,
        required: true
    },
    position: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close'])

const tooltipStyle = computed(() => {
    const { x, y, placement = 'bottom' } = props.position
    
    let top, left
    const tooltipWidth = 320 // max-w-xs = 20rem = 320px
    const tooltipHeight = 200 // estimated height
    
    switch (placement) {
        case 'top':
            top = y - tooltipHeight - 10
            left = x - (tooltipWidth / 2)
            break
        case 'bottom':
            top = y + 10
            left = x - (tooltipWidth / 2)
            break
        case 'left':
            top = y - (tooltipHeight / 2)
            left = x - tooltipWidth - 10
            break
        case 'right':
            top = y - (tooltipHeight / 2)
            left = x + 10
            break
        default:
            top = y + 10
            left = x - (tooltipWidth / 2)
    }
    
    // Adjust if tooltip goes off screen
    if (left < 10) left = 10
    if (left + tooltipWidth > window.innerWidth - 10) {
        left = window.innerWidth - tooltipWidth - 10
    }
    
    if (top < 10) top = 10
    if (top + tooltipHeight > window.innerHeight - 10) {
        top = window.innerHeight - tooltipHeight - 10
    }
    
    return {
        top: `${top}px`,
        left: `${left}px`
    }
})

const arrowStyle = computed(() => {
    const { placement = 'bottom' } = props.position
    
    switch (placement) {
        case 'top':
            return {
                bottom: '-6px',
                left: '50%',
                transform: 'translateX(-50%) rotate(45deg)'
            }
        case 'bottom':
            return {
                top: '-6px',
                left: '50%',
                transform: 'translateX(-50%) rotate(45deg)'
            }
        case 'left':
            return {
                right: '-6px',
                top: '50%',
                transform: 'translateY(-50%) rotate(45deg)'
            }
        case 'right':
            return {
                left: '-6px',
                top: '50%',
                transform: 'translateY(-50%) rotate(45deg)'
            }
        default:
            return {
                top: '-6px',
                left: '50%',
                transform: 'translateX(-50%) rotate(45deg)'
            }
    }
})

const performAction = (action) => {
    if (action.type === 'navigate') {
        window.location.href = action.url
    } else if (action.type === 'event') {
        window.dispatchEvent(new CustomEvent(action.event, { detail: action.data }))
    } else if (action.type === 'function') {
        if (window[action.function]) {
            window[action.function](action.data)
        }
    }
    
    emit('close')
}
</script>

<style scoped>
.contextual-help-tooltip {
    animation: fadeInScale 0.2s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
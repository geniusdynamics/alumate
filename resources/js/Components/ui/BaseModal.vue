<template>
    <Teleport to="body">
        <Transition
            name="modal"
            @enter="onEnter"
            @after-enter="onAfterEnter"
            @leave="onLeave"
            @after-leave="onAfterLeave"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click="handleBackdropClick"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
                
                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        ref="modalRef"
                        :class="[
                            'relative w-full transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all',
                            maxWidthClass
                        ]"
                        @click.stop
                    >
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    maxWidth: {
        type: String,
        default: 'md'
    },
    closeable: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['close'])

const modalRef = ref(null)

const maxWidthClass = computed(() => {
    const sizes = {
        'sm': 'max-w-sm',
        'md': 'max-w-md',
        'lg': 'max-w-lg',
        'xl': 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
        '4xl': 'max-w-4xl',
        '5xl': 'max-w-5xl',
        '6xl': 'max-w-6xl',
        '7xl': 'max-w-7xl'
    }
    return sizes[props.maxWidth] || sizes.md
})

const handleBackdropClick = () => {
    if (props.closeable) {
        emit('close')
    }
}

const onEnter = (el) => {
    el.classList.add('opacity-0', 'scale-95')
}

const onAfterEnter = (el) => {
    el.classList.remove('opacity-0', 'scale-95')
    el.classList.add('opacity-100', 'scale-100')
}

const onLeave = (el) => {
    el.classList.remove('opacity-100', 'scale-100')
    el.classList.add('opacity-0', 'scale-95')
}

const onAfterLeave = (el) => {
    el.classList.remove('opacity-0', 'scale-95')
}

// Handle escape key
watch(() => props.show, (newValue) => {
    if (newValue) {
        nextTick(() => {
            document.addEventListener('keydown', handleEscape)
            document.body.style.overflow = 'hidden'
        })
    } else {
        document.removeEventListener('keydown', handleEscape)
        document.body.style.overflow = ''
    }
})

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.closeable) {
        emit('close')
    }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
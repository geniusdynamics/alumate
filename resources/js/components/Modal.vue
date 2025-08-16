<template>
    <teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[50] overflow-y-auto" role="dialog" aria-modal="true" :aria-labelledby="title ? 'modal-title' : undefined">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div 
                    class="fixed inset-0 bg-black/50 transition-opacity backdrop-blur-sm" 
                    @click="closeOnClickOutside && $emit('close')"
                    aria-hidden="true"
                ></div>

                <!-- Modal panel -->
                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-[60]">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 v-if="title" id="modal-title" class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                    {{ title }}
                </h3>
                                <div class="mt-2">
                                    <slot />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="$slots.footer" class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: ''
    },
    closeOnClickOutside: {
        type: Boolean,
        default: true
    }
});

defineEmits(['close']);
</script>
<template>
    <teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[50] overflow-y-auto"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="title ? 'modal-title' : undefined"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                    @click="closeOnClickOutside && $emit('close')"
                    aria-hidden="true"
                ></div>

                <!-- Modal panel -->
                <div
                    class="relative z-[60] inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-gray-800"
                >
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-gray-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 v-if="title" id="modal-title" class="mb-4 text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                    {{ title }}
                                </h3>
                                <div class="mt-2">
                                    <slot />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="$slots.footer" class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-700">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup lang="ts">
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    closeOnClickOutside: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['close']);
</script>


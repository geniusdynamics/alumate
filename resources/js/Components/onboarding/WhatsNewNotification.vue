<template>
    <div class="fixed right-4 top-4 z-50 max-w-sm">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-500 to-blue-600 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white bg-opacity-20">
                            <SparklesIcon class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-white">What's New</h3>
                            <p class="text-sm text-green-100">{{ updates.length }} new update{{ updates.length !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="text-white hover:text-green-100">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Updates List -->
            <div class="max-h-96 overflow-y-auto">
                <div
                    v-for="update in updates"
                    :key="update.id"
                    class="border-b border-gray-200 p-4 last:border-b-0 dark:border-gray-700"
                    :class="{ 'bg-blue-50 bg-opacity-30 dark:bg-blue-900': !update.read }"
                >
                    <!-- Update Header -->
                    <div class="mb-2 flex items-start justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full" :class="getUpdateTypeStyle(update.type)">
                                <component :is="getUpdateIcon(update.type)" class="h-3 w-3" />
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ update.title }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(update.created_at) }}
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="!update.read"
                            class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                        >
                            New
                        </span>
                    </div>

                    <!-- Update Content -->
                    <p class="mb-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ update.description }}
                    </p>

                    <!-- Update Features -->
                    <div v-if="update.features && update.features.length > 0" class="mb-3">
                        <ul class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                            <li v-for="feature in update.features" :key="feature" class="flex items-center space-x-2">
                                <CheckCircleIcon class="h-3 w-3 flex-shrink-0 text-green-500" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Update Actions -->
                    <div v-if="update.actions && update.actions.length > 0" class="flex space-x-2">
                        <button
                            v-for="action in update.actions"
                            :key="action.label"
                            @click="performUpdateAction(update, action)"
                            class="rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 p-4 dark:bg-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <input
                            id="auto-show-updates"
                            v-model="autoShowUpdates"
                            type="checkbox"
                            class="h-3 w-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="auto-show-updates" class="text-xs text-gray-600 dark:text-gray-400"> Show new updates automatically </label>
                    </div>

                    <button @click="viewAllUpdates" class="text-xs font-medium text-blue-600 hover:text-blue-500">View All</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    ArrowPathIcon,
    BugAntIcon,
    CheckCircleIcon,
    PlusIcon,
    RocketLaunchIcon,
    ShieldCheckIcon,
    SparklesIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';
import { ref } from 'vue';

const props = defineProps({
    updates: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['close', 'view-details']);

const autoShowUpdates = ref(true);

const getUpdateTypeStyle = (type) => {
    const styles = {
        feature: 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
        improvement: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
        bugfix: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
        security: 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
        announcement: 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400',
    };
    return styles[type] || styles.feature;
};

const getUpdateIcon = (type) => {
    const icons = {
        feature: PlusIcon,
        improvement: ArrowPathIcon,
        bugfix: BugAntIcon,
        security: ShieldCheckIcon,
        announcement: RocketLaunchIcon,
    };
    return icons[type] || PlusIcon;
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM d, yyyy');
};

const performUpdateAction = (update, action) => {
    if (action.type === 'navigate') {
        emit('view-details', { ...update, route: action.url });
    } else if (action.type === 'modal') {
        window.dispatchEvent(
            new CustomEvent('show-update-modal', {
                detail: { update, action },
            }),
        );
    } else if (action.type === 'feature-spotlight') {
        window.dispatchEvent(
            new CustomEvent('show-feature-spotlight', {
                detail: { feature: action.feature },
            }),
        );
    }
};

const viewAllUpdates = () => {
    // Save auto-show preference
    localStorage.setItem('autoShowUpdates', autoShowUpdates.value.toString());

    // Navigate to updates page
    window.location.href = '/updates';
};
</script>

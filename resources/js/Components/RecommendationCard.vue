<template>
    <div class="recommendation-card rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
        <!-- User Info -->
        <div class="mb-4 flex items-start space-x-4">
            <div class="flex-shrink-0">
                <img
                    :src="recommendation.user.avatar_url || '/images/default-avatar.png'"
                    :alt="recommendation.user.name"
                    class="h-16 w-16 rounded-full object-cover"
                />
            </div>

            <div class="min-w-0 flex-1">
                <h3 class="truncate text-lg font-semibold text-gray-900">
                    {{ recommendation.user.name }}
                </h3>

                <p v-if="recommendation.user.current_title" class="truncate text-sm text-gray-600">
                    {{ recommendation.user.current_title }}
                    <span v-if="recommendation.user.current_company"> at {{ recommendation.user.current_company }} </span>
                </p>

                <p v-if="recommendation.user.location" class="mt-1 text-sm text-gray-500">
                    <Icon name="map-pin" class="mr-1 inline h-4 w-4" />
                    {{ recommendation.user.location }}
                </p>

                <div class="mt-2 flex items-center">
                    <div class="flex items-center text-sm text-blue-600">
                        <Icon name="star" class="mr-1 h-4 w-4" />
                        <span>{{ Math.round(recommendation.score * 100) }}% match</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Reasons -->
        <ConnectionReasons
            :reasons="recommendation.reasons"
            :mutual-connections="recommendation.mutual_connections"
            :shared-circles="recommendation.shared_circles"
            class="mb-4"
        />

        <!-- Mutual Connections Preview -->
        <div v-if="recommendation.mutual_connections && recommendation.mutual_connections.length > 0" class="mb-4">
            <div class="flex items-center space-x-2">
                <div class="flex -space-x-2">
                    <img
                        v-for="connection in recommendation.mutual_connections.slice(0, 3)"
                        :key="connection.id"
                        :src="connection.avatar_url || '/images/default-avatar.png'"
                        :alt="connection.name"
                        :title="connection.name"
                        class="h-6 w-6 rounded-full border-2 border-white object-cover"
                    />
                </div>
                <span class="text-sm text-gray-600">
                    {{ recommendation.mutual_connections.length }} mutual connection{{ recommendation.mutual_connections.length !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between border-t border-gray-100 pt-4">
            <div class="flex space-x-2">
                <button
                    @click="$emit('connect', recommendation)"
                    :disabled="connecting"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                >
                    <Icon v-if="connecting" name="spinner" class="mr-2 h-4 w-4 animate-spin" />
                    <Icon v-else name="user-plus" class="mr-2 h-4 w-4" />
                    Connect
                </button>

                <button
                    @click="viewProfile"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                >
                    <Icon name="eye" class="mr-2 h-4 w-4" />
                    View
                </button>
            </div>

            <div class="ml-4 flex items-center space-x-1">
                <button
                    @click="$emit('dismiss', recommendation)"
                    :title="'Dismiss recommendation'"
                    class="rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                >
                    <Icon name="x" class="h-4 w-4" />
                </button>

                <button
                    @click="$emit('feedback', recommendation)"
                    :title="'Provide feedback'"
                    class="rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                >
                    <Icon name="flag" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Selection checkbox for bulk actions -->
        <div v-if="showBulkSelect" class="absolute right-4 top-4">
            <input
                :id="`select-${recommendation.user.id}`"
                v-model="isSelected"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConnectionReasons from './ConnectionReasons.vue';
import Icon from './Icon.vue';

const props = defineProps({
    recommendation: {
        type: Object,
        required: true,
    },
    showBulkSelect: {
        type: Boolean,
        default: false,
    },
    selected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['connect', 'dismiss', 'feedback', 'select']);

const connecting = ref(false);

const isSelected = computed({
    get: () => props.selected,
    set: (value) => emit('select', { recommendation: props.recommendation, selected: value }),
});

const viewProfile = () => {
    router.visit(`/alumni/${props.recommendation.user.id}`);
};
</script>

<style scoped>
.recommendation-card {
    position: relative;
}
</style>


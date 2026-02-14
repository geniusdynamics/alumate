<template>
    <div class="card-mobile border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="card-mobile-header">
            <h3 class="card-mobile-title">People You May Know</h3>
            <UsersIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
        </div>

        <div class="space-y-4">
            <!-- Loading State -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="animate-pulse">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-3/4 rounded bg-gray-200 dark:bg-gray-700"></div>
                            <div class="h-3 w-1/2 rounded bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="h-8 w-16 rounded bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                </div>
            </div>

            <!-- Suggestions -->
            <div v-else-if="suggestions.length > 0" class="space-y-3">
                <div
                    v-for="suggestion in suggestions"
                    :key="suggestion.id"
                    class="flex items-center space-x-3 rounded-lg p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                    <div class="flex-shrink-0">
                        <img
                            v-if="suggestion.avatar_url"
                            :src="suggestion.avatar_url"
                            :alt="suggestion.name"
                            class="h-10 w-10 rounded-full object-cover"
                        />
                        <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-600">
                            <span class="text-sm font-medium text-white">
                                {{ getInitials(suggestion.name) }}
                            </span>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                            {{ suggestion.name }}
                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ suggestion.title }} at {{ suggestion.company }}</p>
                        <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                            {{ suggestion.connection_reason }}
                        </p>
                    </div>

                    <div class="flex-shrink-0">
                        <button
                            @click="sendConnectionRequest(suggestion)"
                            :disabled="suggestion.connecting"
                            class="btn-mobile-sm bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <UserPlusIcon v-if="!suggestion.connecting" class="h-4 w-4" />
                            <div v-else class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-6 text-center">
                <UsersIcon class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No suggestions available</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Complete your profile to get better suggestions</p>
            </div>
        </div>

        <!-- View All Link -->
        <div v-if="suggestions.length > 0" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <Link
                :href="route('alumni.directory')"
                class="flex items-center justify-center space-x-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
                <span>Browse Alumni Directory</span>
                <ArrowRightIcon class="h-4 w-4" />
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ArrowRightIcon, UserPlusIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const loading = ref(true);
const suggestions = ref([]);

const props = defineProps({
    limit: {
        type: Number,
        default: 3,
    },
});

onMounted(async () => {
    await fetchSuggestions();
});

const fetchSuggestions = async () => {
    try {
        loading.value = true;
        const response = await fetch(`/api/dashboard/alumni-suggestions?limit=${props.limit}`);
        const data = await response.json();
        suggestions.value = data.suggestions || [];
    } catch (error) {
        console.error('Failed to fetch alumni suggestions:', error);
        suggestions.value = [];
    } finally {
        loading.value = false;
    }
};

const getInitials = (name) => {
    if (!name) return 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const sendConnectionRequest = async (suggestion) => {
    try {
        suggestion.connecting = true;

        const response = await fetch('/api/connections/request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                user_id: suggestion.id,
                message: `Hi ${suggestion.name.split(' ')[0]}, I'd like to connect with you on our alumni platform!`,
            }),
        });

        if (response.ok) {
            // Remove from suggestions after successful request
            suggestions.value = suggestions.value.filter((s) => s.id !== suggestion.id);
        } else {
            throw new Error('Failed to send connection request');
        }
    } catch (error) {
        console.error('Failed to send connection request:', error);
        alert('Failed to send connection request. Please try again.');
    } finally {
        suggestion.connecting = false;
    }
};
</script>


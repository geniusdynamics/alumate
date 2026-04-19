<template>
    <div class="coffee-chat-suggestions">
        <div class="mb-6">
            <h2 class="mb-2 text-2xl font-bold text-gray-900">Coffee Chat Suggestions</h2>
            <p class="text-gray-600">Connect with fellow alumni for meaningful conversations</p>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Industry</label>
                    <select
                        v-model="filters.industry"
                        @change="loadSuggestions"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">All Industries</option>
                        <option value="technology">Technology</option>
                        <option value="finance">Finance</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="education">Education</option>
                        <option value="consulting">Consulting</option>
                        <option value="marketing">Marketing</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Location</label>
                    <input
                        v-model="filters.location"
                        @input="debounceLoadSuggestions"
                        type="text"
                        placeholder="City, State or Country"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Interests</label>
                    <input
                        v-model="interestsInput"
                        @input="handleInterestsInput"
                        type="text"
                        placeholder="Separate with commas"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
        </div>

        <!-- Suggestions Grid -->
        <div v-else-if="suggestions.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="suggestion in suggestions"
                :key="suggestion.id"
                class="rounded-lg border bg-white shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="p-6">
                    <!-- User Info -->
                    <div class="mb-4 flex items-center space-x-4">
                        <img
                            :src="suggestion.avatar_url || '/default-avatar.png'"
                            :alt="suggestion.name"
                            class="h-12 w-12 rounded-full object-cover"
                        />
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ suggestion.name }}</h3>
                            <p class="text-sm text-gray-600">{{ suggestion.title }}</p>
                            <p class="text-sm text-gray-500">{{ suggestion.company }}</p>
                        </div>
                    </div>

                    <!-- Match Score -->
                    <div class="mb-4">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Match Score</span>
                            <span class="text-sm font-semibold text-blue-600"> {{ Math.round(suggestion.matching_score * 100) }}% </span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-200">
                            <div
                                class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                                :style="{ width: `${suggestion.matching_score * 100}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="mb-4 space-y-2">
                        <div v-if="suggestion.industry" class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-briefcase mr-2 w-4"></i>
                            {{ suggestion.industry }}
                        </div>
                        <div v-if="suggestion.location" class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-map-marker-alt mr-2 w-4"></i>
                            {{ suggestion.location }}
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-coffee mr-2 w-4"></i>
                            {{ suggestion.coffee_chats_completed }} coffee chats completed
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button
                        @click="requestCoffeeChat(suggestion)"
                        :disabled="requestingUsers.has(suggestion.id)"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700 disabled:bg-gray-400"
                    >
                        <span v-if="requestingUsers.has(suggestion.id)">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Sending Request...
                        </span>
                        <span v-else>
                            <i class="fas fa-coffee mr-2"></i>
                            Request Coffee Chat
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="py-12 text-center">
            <i class="fas fa-coffee mb-4 text-6xl text-gray-300"></i>
            <h3 class="mb-2 text-lg font-medium text-gray-900">No suggestions found</h3>
            <p class="mb-4 text-gray-600">Try adjusting your filters to find more alumni to connect with</p>
            <button @click="clearFilters" class="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">Clear Filters</button>
        </div>

        <!-- Coffee Chat Request Modal -->
        <CoffeeChatRequestModal v-if="showRequestModal" :recipient="selectedRecipient" @close="closeRequestModal" @request-sent="handleRequestSent" />
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { debounce } from 'lodash';
import { onMounted, ref } from 'vue';
import CoffeeChatRequestModal from './CoffeeChatRequestModal.vue';

// State
const suggestions = ref([]);
const loading = ref(false);
const requestingUsers = ref(new Set());
const showRequestModal = ref(false);
const selectedRecipient = ref(null);

// Filters
const filters = ref({
    industry: '',
    location: '',
    interests: [],
});

const interestsInput = ref('');

// Methods
const loadSuggestions = async () => {
    loading.value = true;

    try {
        const response = await axios.get('/api/coffee-chat/suggestions', {
            params: {
                industry: filters.value.industry || undefined,
                location: filters.value.location || undefined,
                interests: filters.value.interests.length > 0 ? filters.value.interests : undefined,
            },
        });

        suggestions.value = response.data.data;
    } catch (error) {
        console.error('Error loading suggestions:', error);
    } finally {
        loading.value = false;
    }
};

const debounceLoadSuggestions = debounce(loadSuggestions, 500);

const handleInterestsInput = () => {
    filters.value.interests = interestsInput.value
        .split(',')
        .map((interest) => interest.trim())
        .filter((interest) => interest.length > 0);

    debounceLoadSuggestions();
};

const requestCoffeeChat = (recipient) => {
    selectedRecipient.value = recipient;
    showRequestModal.value = true;
};

const closeRequestModal = () => {
    showRequestModal.value = false;
    selectedRecipient.value = null;
};

const handleRequestSent = (recipient) => {
    // Remove the recipient from suggestions since request was sent
    suggestions.value = suggestions.value.filter((s) => s.id !== recipient.id);
    closeRequestModal();
};

const clearFilters = () => {
    filters.value = {
        industry: '',
        location: '',
        interests: [],
    };
    interestsInput.value = '';
    loadSuggestions();
};

// Lifecycle
onMounted(() => {
    loadSuggestions();
});
</script>

<style scoped>
.coffee-chat-suggestions {
    @apply mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8;
}
</style>


<template>
    <DefaultLayout title="Frequently Asked Questions">
        <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Frequently Asked Questions</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Find quick answers to common questions about using the platform</p>
                    </div>

                    <!-- Search FAQs -->
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="filterFAQs"
                            type="text"
                            placeholder="Search FAQs..."
                            class="w-80 rounded-lg border border-gray-300 py-2 pl-10 pr-4 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    @click="filterByCategory(category.id)"
                    class="rounded-lg bg-white p-4 text-left shadow transition-shadow hover:shadow-md dark:bg-gray-800"
                    :class="selectedCategory === category.id ? 'ring-2 ring-blue-500' : ''"
                >
                    <component :is="category.icon" class="mb-2 h-6 w-6 text-blue-600 dark:text-blue-400" />
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ category.name }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ category.count }} questions</p>
                </button>
            </div>

            <!-- FAQ List -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ selectedCategory ? getCategoryName(selectedCategory) : 'All Questions' }}
                        </h2>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400"> {{ filteredFAQs.length }} questions </span>
                            <button v-if="selectedCategory" @click="clearFilter" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                Clear Filter
                            </button>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="faq in filteredFAQs" :key="faq.id" class="p-6">
                        <button @click="toggleFAQ(faq.id)" class="flex w-full items-center justify-between text-left">
                            <h3 class="pr-4 text-lg font-medium text-gray-900 dark:text-white">
                                {{ faq.question }}
                            </h3>
                            <div class="flex items-center space-x-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium capitalize text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                >
                                    {{ faq.category }}
                                </span>
                                <ChevronDownIcon
                                    class="h-5 w-5 flex-shrink-0 text-gray-500 transition-transform"
                                    :class="{ 'rotate-180': openFAQs.includes(faq.id) }"
                                />
                            </div>
                        </button>

                        <div v-if="openFAQs.includes(faq.id)" class="mt-4">
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300" v-html="faq.answer"></div>

                            <!-- FAQ Actions -->
                            <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Was this helpful?</span>
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="markHelpful(faq.id, true)"
                                            class="flex items-center space-x-1 text-sm text-green-600 hover:text-green-500 dark:text-green-400"
                                        >
                                            <HandThumbUpIcon class="h-4 w-4" />
                                            <span>Yes</span>
                                        </button>
                                        <button
                                            @click="markHelpful(faq.id, false)"
                                            class="flex items-center space-x-1 text-sm text-red-600 hover:text-red-500 dark:text-red-400"
                                        >
                                            <HandThumbDownIcon class="h-4 w-4" />
                                            <span>No</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <HandThumbUpIcon class="h-4 w-4" />
                                    <span>{{ faq.helpful_count || 0 }} found this helpful</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Results -->
                <div v-if="filteredFAQs.length === 0" class="p-12 text-center">
                    <QuestionMarkCircleIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No FAQs Found</h3>
                    <p class="mb-4 text-gray-600 dark:text-gray-400">
                        {{ searchQuery ? 'Try adjusting your search terms' : 'No questions match the selected category' }}
                    </p>
                    <button @click="clearFilter" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">View All FAQs</button>
                </div>
            </div>

            <!-- Still Need Help -->
            <div class="mt-8 rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="mb-2 text-lg font-semibold">Still Need Help?</h3>
                        <p class="text-blue-100">Can't find what you're looking for? Our support team is here to help.</p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('training.index')"
                            class="rounded-md bg-white bg-opacity-20 px-4 py-2 font-medium text-white transition-colors hover:bg-opacity-30"
                        >
                            Browse Guides
                        </Link>
                        <a
                            href="mailto:support@alumni.com"
                            class="rounded-md bg-white bg-opacity-20 px-4 py-2 font-medium text-white transition-colors hover:bg-opacity-30"
                        >
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>

            <!-- Suggest FAQ -->
            <div class="mt-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Suggest a Question</h3>
                <p class="mb-4 text-gray-600 dark:text-gray-400">Have a question that's not covered here? Let us know and we'll add it to our FAQ.</p>

                <form @submit.prevent="suggestFAQ" class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Your Question </label>
                        <input
                            v-model="suggestionForm.question"
                            type="text"
                            required
                            placeholder="What would you like to know?"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Category </label>
                        <select
                            v-model="suggestionForm.category"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Select a category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Additional Context (Optional) </label>
                        <textarea
                            v-model="suggestionForm.context"
                            rows="3"
                            placeholder="Provide any additional context that might help us answer your question..."
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="!suggestionForm.question || submittingSuggestion"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:bg-gray-400"
                        >
                            {{ submittingSuggestion ? 'Submitting...' : 'Suggest Question' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DefaultLayout>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {
    AcademicCapIcon,
    BriefcaseIcon,
    ChatBubbleLeftRightIcon,
    ChevronDownIcon,
    Cog6ToothIcon,
    CurrencyDollarIcon,
    HandThumbDownIcon,
    HandThumbUpIcon,
    HeartIcon,
    MagnifyingGlassIcon,
    QuestionMarkCircleIcon,
    ShieldCheckIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    faqs: Array,
    role: String,
});

const searchQuery = ref('');
const selectedCategory = ref('');
const openFAQs = ref([]);
const submittingSuggestion = ref(false);

const suggestionForm = ref({
    question: '',
    category: '',
    context: '',
});

const categories = ref([
    { id: 'networking', name: 'Networking', icon: UserIcon, count: 0 },
    { id: 'career', name: 'Career', icon: BriefcaseIcon, count: 0 },
    { id: 'privacy', name: 'Privacy', icon: ShieldCheckIcon, count: 0 },
    { id: 'social', name: 'Social Features', icon: ChatBubbleLeftRightIcon, count: 0 },
    { id: 'support', name: 'Support', icon: Cog6ToothIcon, count: 0 },
    { id: 'mentorship', name: 'Mentorship', icon: AcademicCapIcon, count: 0 },
    { id: 'fundraising', name: 'Fundraising', icon: CurrencyDollarIcon, count: 0 },
    { id: 'general', name: 'General', icon: HeartIcon, count: 0 },
]);

const filteredFAQs = computed(() => {
    let filtered = props.faqs;

    // Filter by category
    if (selectedCategory.value) {
        filtered = filtered.filter((faq) => faq.category === selectedCategory.value);
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter((faq) => faq.question.toLowerCase().includes(query) || faq.answer.toLowerCase().includes(query));
    }

    return filtered;
});

onMounted(() => {
    // Count FAQs by category
    categories.value.forEach((category) => {
        category.count = props.faqs.filter((faq) => faq.category === category.id).length;
    });
});

const filterFAQs = () => {
    // Search is reactive through computed property
};

const filterByCategory = (categoryId) => {
    if (selectedCategory.value === categoryId) {
        selectedCategory.value = '';
    } else {
        selectedCategory.value = categoryId;
    }
    searchQuery.value = '';
};

const clearFilter = () => {
    selectedCategory.value = '';
    searchQuery.value = '';
};

const getCategoryName = (categoryId) => {
    const category = categories.value.find((cat) => cat.id === categoryId);
    return category ? category.name : 'Unknown Category';
};

const toggleFAQ = (faqId) => {
    const index = openFAQs.value.indexOf(faqId);
    if (index > -1) {
        openFAQs.value.splice(index, 1);
    } else {
        openFAQs.value.push(faqId);
    }
};

const markHelpful = async (faqId, helpful) => {
    try {
        const response = await fetch('/api/training/faq-helpful', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                faq_id: faqId,
                helpful: helpful,
            }),
        });

        const data = await response.json();
        if (data.success) {
            // Show success message
            logger.log('Feedback recorded');
        }
    } catch (error) {
        console.error('Failed to record feedback:', error);
    }
};

const suggestFAQ = async () => {
    if (!suggestionForm.value.question) return;

    submittingSuggestion.value = true;

    try {
        const response = await fetch('/api/training/suggest-faq', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(suggestionForm.value),
        });

        const data = await response.json();
        if (data.success) {
            suggestionForm.value = { question: '', category: '', context: '' };
            // Show success message
        }
    } catch (error) {
        console.error('Failed to submit suggestion:', error);
    } finally {
        submittingSuggestion.value = false;
    }
};
</script>















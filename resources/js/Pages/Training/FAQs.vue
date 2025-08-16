<template>
    <DefaultLayout title="Frequently Asked Questions">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Frequently Asked Questions</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Find quick answers to common questions about using the platform
                        </p>
                    </div>
                    
                    <!-- Search FAQs -->
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="filterFAQs"
                            type="text"
                            placeholder="Search FAQs..."
                            class="w-80 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                        <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    @click="filterByCategory(category.id)"
                    class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow text-left"
                    :class="selectedCategory === category.id ? 'ring-2 ring-blue-500' : ''"
                >
                    <component :is="category.icon" class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-2" />
                    <h3 class="font-medium text-gray-900 dark:text-white text-sm">{{ category.name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ category.count }} questions</p>
                </button>
            </div>

            <!-- FAQ List -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ selectedCategory ? getCategoryName(selectedCategory) : 'All Questions' }}
                        </h2>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ filteredFAQs.length }} questions
                            </span>
                            <button
                                v-if="selectedCategory"
                                @click="clearFilter"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500"
                            >
                                Clear Filter
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="faq in filteredFAQs"
                        :key="faq.id"
                        class="p-6"
                    >
                        <button
                            @click="toggleFAQ(faq.id)"
                            class="flex items-center justify-between w-full text-left"
                        >
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white pr-4">
                                {{ faq.question }}
                            </h3>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 capitalize">
                                    {{ faq.category }}
                                </span>
                                <ChevronDownIcon 
                                    class="w-5 h-5 text-gray-500 transition-transform flex-shrink-0"
                                    :class="{ 'rotate-180': openFAQs.includes(faq.id) }"
                                />
                            </div>
                        </button>
                        
                        <div
                            v-if="openFAQs.includes(faq.id)"
                            class="mt-4"
                        >
                            <div 
                                class="text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none"
                                v-html="faq.answer"
                            ></div>
                            
                            <!-- FAQ Actions -->
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Was this helpful?</span>
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="markHelpful(faq.id, true)"
                                            class="flex items-center space-x-1 text-sm text-green-600 dark:text-green-400 hover:text-green-500"
                                        >
                                            <HandThumbUpIcon class="w-4 h-4" />
                                            <span>Yes</span>
                                        </button>
                                        <button
                                            @click="markHelpful(faq.id, false)"
                                            class="flex items-center space-x-1 text-sm text-red-600 dark:text-red-400 hover:text-red-500"
                                        >
                                            <HandThumbDownIcon class="w-4 h-4" />
                                            <span>No</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                    <HandThumbUpIcon class="w-4 h-4" />
                                    <span>{{ faq.helpful_count || 0 }} found this helpful</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- No Results -->
                <div v-if="filteredFAQs.length === 0" class="p-12 text-center">
                    <QuestionMarkCircleIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No FAQs Found</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        {{ searchQuery ? 'Try adjusting your search terms' : 'No questions match the selected category' }}
                    </p>
                    <button
                        @click="clearFilter"
                        class="text-blue-600 dark:text-blue-400 hover:text-blue-500 font-medium"
                    >
                        View All FAQs
                    </button>
                </div>
            </div>

            <!-- Still Need Help -->
            <div class="mt-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Still Need Help?</h3>
                        <p class="text-blue-100">
                            Can't find what you're looking for? Our support team is here to help.
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('training.index')"
                            class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            Browse Guides
                        </Link>
                        <a
                            href="mailto:support@alumni.com"
                            class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>

            <!-- Suggest FAQ -->
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Suggest a Question
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Have a question that's not covered here? Let us know and we'll add it to our FAQ.
                </p>
                
                <form @submit.prevent="suggestFAQ" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Your Question
                        </label>
                        <input
                            v-model="suggestionForm.question"
                            type="text"
                            required
                            placeholder="What would you like to know?"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category
                        </label>
                        <select
                            v-model="suggestionForm.category"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Select a category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Additional Context (Optional)
                        </label>
                        <textarea
                            v-model="suggestionForm.context"
                            rows="3"
                            placeholder="Provide any additional context that might help us answer your question..."
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        ></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="!suggestionForm.question || submittingSuggestion"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            {{ submittingSuggestion ? 'Submitting...' : 'Suggest Question' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DefaultLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import {
    MagnifyingGlassIcon,
    ChevronDownIcon,
    QuestionMarkCircleIcon,
    HandThumbUpIcon,
    HandThumbDownIcon,
    UserIcon,
    BriefcaseIcon,
    ShieldCheckIcon,
    ChatBubbleLeftRightIcon,
    Cog6ToothIcon,
    HeartIcon,
    CurrencyDollarIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    faqs: Array,
    role: String
})

const searchQuery = ref('')
const selectedCategory = ref('')
const openFAQs = ref([])
const submittingSuggestion = ref(false)

const suggestionForm = ref({
    question: '',
    category: '',
    context: ''
})

const categories = ref([
    { id: 'networking', name: 'Networking', icon: UserIcon, count: 0 },
    { id: 'career', name: 'Career', icon: BriefcaseIcon, count: 0 },
    { id: 'privacy', name: 'Privacy', icon: ShieldCheckIcon, count: 0 },
    { id: 'social', name: 'Social Features', icon: ChatBubbleLeftRightIcon, count: 0 },
    { id: 'support', name: 'Support', icon: Cog6ToothIcon, count: 0 },
    { id: 'mentorship', name: 'Mentorship', icon: AcademicCapIcon, count: 0 },
    { id: 'fundraising', name: 'Fundraising', icon: CurrencyDollarIcon, count: 0 },
    { id: 'general', name: 'General', icon: HeartIcon, count: 0 }
])

const filteredFAQs = computed(() => {
    let filtered = props.faqs

    // Filter by category
    if (selectedCategory.value) {
        filtered = filtered.filter(faq => faq.category === selectedCategory.value)
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(faq => 
            faq.question.toLowerCase().includes(query) ||
            faq.answer.toLowerCase().includes(query)
        )
    }

    return filtered
})

onMounted(() => {
    // Count FAQs by category
    categories.value.forEach(category => {
        category.count = props.faqs.filter(faq => faq.category === category.id).length
    })
})

const filterFAQs = () => {
    // Search is reactive through computed property
}

const filterByCategory = (categoryId) => {
    if (selectedCategory.value === categoryId) {
        selectedCategory.value = ''
    } else {
        selectedCategory.value = categoryId
    }
    searchQuery.value = ''
}

const clearFilter = () => {
    selectedCategory.value = ''
    searchQuery.value = ''
}

const getCategoryName = (categoryId) => {
    const category = categories.value.find(cat => cat.id === categoryId)
    return category ? category.name : 'Unknown Category'
}

const toggleFAQ = (faqId) => {
    const index = openFAQs.value.indexOf(faqId)
    if (index > -1) {
        openFAQs.value.splice(index, 1)
    } else {
        openFAQs.value.push(faqId)
    }
}

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
                helpful: helpful
            })
        })
        
        const data = await response.json()
        if (data.success) {
            // Show success message
            console.log('Feedback recorded')
        }
    } catch (error) {
        console.error('Failed to record feedback:', error)
    }
}

const suggestFAQ = async () => {
    if (!suggestionForm.value.question) return
    
    submittingSuggestion.value = true
    
    try {
        const response = await fetch('/api/training/suggest-faq', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(suggestionForm.value)
        })
        
        const data = await response.json()
        if (data.success) {
            suggestionForm.value = { question: '', category: '', context: '' }
            // Show success message
        }
    } catch (error) {
        console.error('Failed to submit suggestion:', error)
    } finally {
        submittingSuggestion.value = false
    }
}
</script>
<template>
    <DefaultLayout title="Training & Documentation">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Training & Documentation</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-2">
                            Master the platform with our comprehensive guides and tutorials
                        </p>
                    </div>
                    
                    <!-- Search -->
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="performSearch"
                            type="text"
                            placeholder="Search training content..."
                            class="w-80 pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        >
                        <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Training Progress -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Your Training Progress</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ trainingProgress.completed_steps }} of {{ trainingProgress.total_steps }} completed
                    </span>
                </div>
                
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>Progress</span>
                        <span>{{ trainingProgress.completion_percentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: trainingProgress.completion_percentage + '%' }"
                        ></div>
                    </div>
                </div>
                
                <div v-if="trainingProgress.next_recommended_action" class="flex items-center space-x-2">
                    <LightBulbIcon class="w-5 h-5 text-yellow-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        Next: {{ trainingProgress.next_recommended_action }}
                    </span>
                </div>
            </div>

            <!-- Search Results -->
            <div v-if="searchResults && searchQuery" class="mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                        Search Results ({{ searchResults.total_results }})
                    </h2>
                    
                    <!-- Search Results Content -->
                    <div class="space-y-6">
                        <!-- Guides Results -->
                        <div v-if="searchResults.guides.length > 0">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">User Guides</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div
                                    v-for="guide in searchResults.guides"
                                    :key="guide.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                                    @click="viewGuide(guide.id)"
                                >
                                    <div class="flex items-start space-x-3">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                            <component :is="getIcon(guide.icon)" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ guide.title }}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ guide.description }}</p>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ guide.estimated_time }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tutorials Results -->
                        <div v-if="searchResults.tutorials.length > 0">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Video Tutorials</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="tutorial in searchResults.tutorials"
                                    :key="tutorial.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                                    @click="viewTutorial(tutorial.id)"
                                >
                                    <div class="aspect-video bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <PlayIcon class="w-12 h-12 text-gray-400" />
                                    </div>
                                    <div class="p-4">
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ tutorial.title }}</h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ tutorial.description }}</p>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ tutorial.duration }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FAQs Results -->
                        <div v-if="searchResults.faqs.length > 0">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">FAQs</h3>
                            <div class="space-y-3">
                                <div
                                    v-for="faq in searchResults.faqs"
                                    :key="faq.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                                >
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ faq.question }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400" v-html="faq.answer"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content (when not searching) -->
            <div v-else class="space-y-8">
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <button
                        @click="startOnboarding"
                        class="p-6 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg shadow hover:shadow-lg transition-shadow text-left"
                    >
                        <RocketLaunchIcon class="w-8 h-8 mb-3" />
                        <h3 class="text-lg font-semibold mb-2">Take a Tour</h3>
                        <p class="text-blue-100 text-sm">Get a guided walkthrough</p>
                    </button>

                    <Link
                        :href="route('training.faqs')"
                        class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow text-left block"
                    >
                        <QuestionMarkCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400 mb-3" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">FAQs</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Find quick answers</p>
                    </Link>

                    <button
                        @click="showKeyboardShortcuts"
                        class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow text-left"
                    >
                        <CommandLineIcon class="w-8 h-8 text-purple-600 dark:text-purple-400 mb-3" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Shortcuts</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Work more efficiently</p>
                    </button>

                    <a
                        href="mailto:support@alumni.com"
                        class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow text-left block"
                    >
                        <EnvelopeIcon class="w-8 h-8 text-red-600 dark:text-red-400 mb-3" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Get Help</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">Contact support</p>
                    </a>
                </div>

                <!-- User Guides -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">User Guides</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Step-by-step guides tailored for your role</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                v-for="guide in userGuides"
                                :key="guide.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
                                @click="viewGuide(guide.id)"
                            >
                                <div class="flex items-start space-x-4">
                                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                        <component :is="getIcon(guide.icon)" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                            {{ guide.title }}
                                        </h3>
                                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                                            {{ guide.description }}
                                        </p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ guide.estimated_time }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ guide.sections.length }} sections
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video Tutorials -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Video Tutorials</h2>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Learn through interactive video content</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="tutorial in videoTutorials"
                                :key="tutorial.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                                @click="viewTutorial(tutorial.id)"
                            >
                                <!-- Video Thumbnail -->
                                <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center relative">
                                    <PlayIcon class="w-16 h-16 text-white bg-black bg-opacity-50 rounded-full p-4" />
                                    <div class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                                        {{ tutorial.duration }}
                                    </div>
                                </div>
                                
                                <!-- Tutorial Info -->
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                                        {{ tutorial.title }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ tutorial.description }}
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="topic in tutorial.topics.slice(0, 3)"
                                            :key="topic"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                        >
                                            {{ topic }}
                                        </span>
                                        <span
                                            v-if="tutorial.topics.length > 3"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                        >
                                            +{{ tutorial.topics.length - 3 }} more
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular FAQs -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Popular FAQs</h2>
                                <p class="text-gray-600 dark:text-gray-400 mt-1">Quick answers to common questions</p>
                            </div>
                            <Link
                                :href="route('training.faqs')"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-500 text-sm font-medium"
                            >
                                View All FAQs →
                            </Link>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="faq in faqs.slice(0, 5)"
                                :key="faq.id"
                                class="border-b border-gray-200 dark:border-gray-700 last:border-b-0 pb-4 last:pb-0"
                            >
                                <button
                                    @click="toggleFAQ(faq.id)"
                                    class="flex items-center justify-between w-full text-left"
                                >
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ faq.question }}</h3>
                                    <ChevronDownIcon 
                                        class="w-5 h-5 text-gray-500 transition-transform"
                                        :class="{ 'rotate-180': openFAQs.includes(faq.id) }"
                                    />
                                </button>
                                
                                <div
                                    v-if="openFAQs.includes(faq.id)"
                                    class="mt-3 text-gray-700 dark:text-gray-300 text-sm"
                                    v-html="faq.answer"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keyboard Shortcuts Modal -->
            <div
                v-if="showShortcuts"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click="closeShortcuts"
            >
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                            <button
                                @click="closeShortcuts"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div
                                v-for="shortcut in keyboardShortcuts"
                                :key="shortcut.key"
                                class="flex items-center justify-between"
                            >
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ shortcut.description }}</span>
                                <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                    {{ shortcut.key }}
                                </kbd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import {
    MagnifyingGlassIcon,
    LightBulbIcon,
    RocketLaunchIcon,
    QuestionMarkCircleIcon,
    CommandLineIcon,
    EnvelopeIcon,
    PlayIcon,
    ChevronDownIcon,
    XMarkIcon,
    ChatBubbleLeftRightIcon,
    UsersIcon,
    BriefcaseIcon,
    CalendarIcon,
    ChartBarIcon,
    MapIcon,
    AcademicCapIcon,
    HeartIcon,
    CurrencyDollarIcon,
    TrophyIcon,
    SparklesIcon,
    ShieldCheckIcon,
    BuildingOfficeIcon,
    InformationCircleIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    userGuides: Array,
    videoTutorials: Array,
    trainingProgress: Object,
    faqs: Array,
    role: String
})

const searchQuery = ref('')
const searchResults = ref(null)
const openFAQs = ref([])
const showShortcuts = ref(false)
const searchTimeout = ref(null)

const keyboardShortcuts = [
    { key: '?', description: 'Show keyboard shortcuts' },
    { key: 'Ctrl + K', description: 'Quick search' },
    { key: 'Ctrl + /', description: 'Toggle help menu' },
    { key: 'Ctrl + N', description: 'Create new post' },
    { key: 'Ctrl + Shift + T', description: 'Start tour' },
    { key: 'Esc', description: 'Close modals/menus' }
]

onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

const handleKeydown = (event) => {
    if (event.key === '?' && !event.ctrlKey && !event.metaKey) {
        event.preventDefault()
        showShortcuts.value = true
    }
    
    if (event.key === 'Escape') {
        showShortcuts.value = false
    }
}

const getIcon = (iconName) => {
    const icons = {
        'chat': ChatBubbleLeftRightIcon,
        'users': UsersIcon,
        'briefcase': BriefcaseIcon,
        'calendar': CalendarIcon,
        'chart': ChartBarIcon,
        'map': MapIcon,
        'academic': AcademicCapIcon,
        'heart': HeartIcon,
        'currency': CurrencyDollarIcon,
        'trophy': TrophyIcon,
        'sparkles': SparklesIcon,
        'rocket': RocketLaunchIcon,
        'shield': ShieldCheckIcon,
        'building': BuildingOfficeIcon,
        'info': InformationCircleIcon,
        'monitor': ComputerDesktopIcon
    }
    return icons[iconName] || SparklesIcon
}

const performSearch = () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }
    
    if (!searchQuery.value.trim()) {
        searchResults.value = null
        return
    }
    
    searchTimeout.value = setTimeout(async () => {
        try {
            const response = await fetch(`/api/training/search?query=${encodeURIComponent(searchQuery.value)}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            
            const data = await response.json()
            if (data.success) {
                searchResults.value = data.data
            }
        } catch (error) {
            console.error('Search failed:', error)
        }
    }, 300)
}

const startOnboarding = () => {
    window.dispatchEvent(new CustomEvent('restart-onboarding-tour'))
}

const showKeyboardShortcuts = () => {
    showShortcuts.value = true
}

const closeShortcuts = () => {
    showShortcuts.value = false
}

const viewGuide = (guideId) => {
    router.visit(route('training.guide', guideId))
}

const viewTutorial = (tutorialId) => {
    router.visit(route('training.tutorial', tutorialId))
}

const toggleFAQ = (faqId) => {
    const index = openFAQs.value.indexOf(faqId)
    if (index > -1) {
        openFAQs.value.splice(index, 1)
    } else {
        openFAQs.value.push(faqId)
    }
}
</script>
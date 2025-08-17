<template>
    <DefaultLayout :title="guide.title">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <Link
                            :href="route('training.index')"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                        >
                            <HomeIcon class="w-4 h-4 mr-2" />
                            Training
                        </Link>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <ChevronRightIcon class="w-4 h-4 text-gray-400" />
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                                {{ guide.title }}
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Guide Header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <component :is="getIcon(guide.icon)" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                {{ guide.title }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                {{ guide.description }}
                            </p>
                            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-1">
                                    <ClockIcon class="w-4 h-4" />
                                    <span>{{ guide.estimated_time }}</span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <DocumentTextIcon class="w-4 h-4" />
                                    <span>{{ guide.sections.length }} sections</span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <TagIcon class="w-4 h-4" />
                                    <span class="capitalize">{{ guide.category }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="markAsCompleted"
                                :disabled="isCompleted"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-md text-sm font-medium transition-colors"
                            >
                                <CheckIcon v-if="isCompleted" class="w-4 h-4 mr-1 inline" />
                                {{ isCompleted ? 'Completed' : 'Mark Complete' }}
                            </button>
                            <button
                                @click="showFeedbackModal = true"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md text-sm font-medium transition-colors"
                            >
                                Give Feedback
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Indicator -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Guide Progress</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ completedSections.length }} of {{ guide.sections.length }} sections
                    </span>
                </div>
                
                <div class="mb-4">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: (completedSections.length / guide.sections.length) * 100 + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Section Checklist -->
                <div class="space-y-2">
                    <div
                        v-for="(section, index) in guide.sections"
                        :key="index"
                        class="flex items-center space-x-3"
                    >
                        <button
                            @click="toggleSection(index)"
                            class="flex-shrink-0"
                        >
                            <div
                                class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                :class="completedSections.includes(index) 
                                    ? 'bg-blue-600 border-blue-600' 
                                    : 'border-gray-300 dark:border-gray-600 hover:border-blue-500'"
                            >
                                <CheckIcon
                                    v-if="completedSections.includes(index)"
                                    class="w-3 h-3 text-white"
                                />
                            </div>
                        </button>
                        <span
                            class="text-sm transition-colors"
                            :class="completedSections.includes(index) 
                                ? 'text-gray-500 dark:text-gray-400 line-through' 
                                : 'text-gray-700 dark:text-gray-300'"
                        >
                            {{ section }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Guide Content -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6">
                    <div class="prose dark:prose-invert max-w-none">
                        <!-- Dynamic content based on guide type -->
                        <div v-if="guide.id === 'getting-started'">
                            <h2>Getting Started with Your Alumni Network</h2>
                            
                            <h3>1. Complete Your Profile</h3>
                            <p>Your profile is your digital business card. A complete profile helps you:</p>
                            <ul>
                                <li>Get better job recommendations</li>
                                <li>Receive more connection requests</li>
                                <li>Appear in relevant searches</li>
                            </ul>
                            
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 my-6">
                                <div class="flex items-start space-x-3">
                                    <LightBulbIcon class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                                    <div>
                                        <h4 class="font-semibold text-blue-900 dark:text-blue-100">Pro Tip</h4>
                                        <p class="text-blue-800 dark:text-blue-200 text-sm">
                                            Profiles with professional photos receive 40% more connection requests.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h3>2. Upload Professional Photo</h3>
                            <p>Choose a clear, professional headshot that represents you well. Avoid:</p>
                            <ul>
                                <li>Group photos or photos with other people</li>
                                <li>Casual or party photos</li>
                                <li>Low-resolution or blurry images</li>
                            </ul>

                            <h3>3. Add Career Timeline</h3>
                            <p>Your career timeline helps others understand your professional journey:</p>
                            <ul>
                                <li>Include all relevant positions</li>
                                <li>Add brief descriptions of your achievements</li>
                                <li>Keep information current and accurate</li>
                            </ul>

                            <h3>4. Connect with Classmates</h3>
                            <p>Start building your network by connecting with people you know:</p>
                            <ul>
                                <li>Search for classmates from your graduation year</li>
                                <li>Look for colleagues from previous jobs</li>
                                <li>Send personalized connection requests</li>
                            </ul>

                            <h3>5. Join Relevant Groups</h3>
                            <p>Groups help you connect with alumni who share your interests:</p>
                            <ul>
                                <li>Join your school's official groups</li>
                                <li>Look for industry-specific groups</li>
                                <li>Participate in group discussions</li>
                            </ul>
                        </div>

                        <div v-else-if="guide.id === 'networking-guide'">
                            <h2>Networking & Building Connections</h2>
                            
                            <h3>Finding Alumni in Your Industry</h3>
                            <p>Use the advanced search filters to find alumni who work in your field:</p>
                            <ul>
                                <li>Filter by industry and job function</li>
                                <li>Look for alumni at target companies</li>
                                <li>Search by location if you're interested in specific markets</li>
                            </ul>

                            <h3>Sending Connection Requests</h3>
                            <p>Personalized connection requests have much higher acceptance rates:</p>
                            <ul>
                                <li>Mention shared experiences (same school, mutual connections)</li>
                                <li>Explain why you want to connect</li>
                                <li>Keep it brief but personal</li>
                            </ul>

                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 my-6">
                                <h4 class="font-semibold text-green-900 dark:text-green-100 mb-2">Example Connection Request</h4>
                                <p class="text-green-800 dark:text-green-200 text-sm italic">
                                    "Hi Sarah, I noticed we both graduated from State University and work in marketing. 
                                    I'd love to connect and learn about your experience at TechCorp. Looking forward to connecting!"
                                </p>
                            </div>

                            <h3>Engaging with Posts</h3>
                            <p>Engagement helps build relationships:</p>
                            <ul>
                                <li>Like and comment on posts from your network</li>
                                <li>Share relevant content with thoughtful commentary</li>
                                <li>Congratulate connections on their achievements</li>
                            </ul>
                        </div>

                        <!-- Add more guide content as needed -->
                        <div v-else>
                            <h2>{{ guide.title }}</h2>
                            <p>{{ guide.description }}</p>
                            
                            <h3>What You'll Learn</h3>
                            <ul>
                                <li v-for="section in guide.sections" :key="section">{{ section }}</li>
                            </ul>
                            
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 my-6">
                                <div class="flex items-start space-x-3">
                                    <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" />
                                    <div>
                                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-100">Note</h4>
                                        <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                                            This guide is being developed. Check back soon for detailed content.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Guides -->
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Related Guides</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- This would be populated with related guides -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">Career Development Tools</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Learn to leverage the platform for career growth</p>
                    </div>
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">Social Timeline & Sharing</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Master social features and engagement</p>
                    </div>
                </div>
            </div>

            <!-- Feedback Modal -->
            <div
                v-if="showFeedbackModal"
                class="fixed inset-0 z-50 overflow-y-auto"
                @click="showFeedbackModal = false"
            >
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    
                    <div
                        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
                        @click.stop
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Guide Feedback</h3>
                            <button
                                @click="showFeedbackModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <form @submit.prevent="submitFeedback">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    How helpful was this guide?
                                </label>
                                <div class="flex space-x-2">
                                    <button
                                        v-for="rating in 5"
                                        :key="rating"
                                        type="button"
                                        @click="feedbackForm.rating = rating"
                                        class="p-1"
                                    >
                                        <StarIcon
                                            class="w-6 h-6 transition-colors"
                                            :class="rating <= feedbackForm.rating 
                                                ? 'text-yellow-400 fill-current' 
                                                : 'text-gray-300 dark:text-gray-600'"
                                        />
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Additional Comments
                                </label>
                                <textarea
                                    v-model="feedbackForm.feedback"
                                    rows="4"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="What could we improve about this guide?"
                                ></textarea>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="showFeedbackModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-500 dark:hover:text-gray-400"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="!feedbackForm.rating || submittingFeedback"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                >
                                    {{ submittingFeedback ? 'Submitting...' : 'Submit Feedback' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import {
    HomeIcon,
    ChevronRightIcon,
    ClockIcon,
    DocumentTextIcon,
    TagIcon,
    CheckIcon,
    LightBulbIcon,
    ExclamationTriangleIcon,
    XMarkIcon,
    StarIcon,
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
    RocketLaunchIcon,
    ShieldCheckIcon,
    BuildingOfficeIcon,
    InformationCircleIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    guide: Object,
    role: String,
    trainingProgress: Object
})

const completedSections = ref([])
const showFeedbackModal = ref(false)
const submittingFeedback = ref(false)
const feedbackForm = ref({
    rating: 0,
    feedback: ''
})

const isCompleted = computed(() => {
    return completedSections.value.length === props.guide.sections.length
})

onMounted(() => {
    // Load completed sections from local storage or user progress
    const saved = localStorage.getItem(`guide_progress_${props.guide.id}`)
    if (saved) {
        completedSections.value = JSON.parse(saved)
    }
})

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

const toggleSection = (index) => {
    const sectionIndex = completedSections.value.indexOf(index)
    if (sectionIndex > -1) {
        completedSections.value.splice(sectionIndex, 1)
    } else {
        completedSections.value.push(index)
    }
    
    // Save progress
    localStorage.setItem(`guide_progress_${props.guide.id}`, JSON.stringify(completedSections.value))
}

const markAsCompleted = async () => {
    if (isCompleted.value) return
    
    // Mark all sections as completed
    completedSections.value = props.guide.sections.map((_, index) => index)
    localStorage.setItem(`guide_progress_${props.guide.id}`, JSON.stringify(completedSections.value))
    
    // Mark in backend
    try {
        await fetch('/api/training/mark-step-completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                step_id: props.guide.id
            })
        })
    } catch (error) {
        console.error('Failed to mark guide as completed:', error)
    }
}

const submitFeedback = async () => {
    if (!feedbackForm.value.rating) return
    
    submittingFeedback.value = true
    
    try {
        const response = await fetch('/api/training/feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                type: 'guide',
                content_id: props.guide.id,
                rating: feedbackForm.value.rating,
                feedback: feedbackForm.value.feedback
            })
        })
        
        const data = await response.json()
        if (data.success) {
            showFeedbackModal.value = false
            feedbackForm.value = { rating: 0, feedback: '' }
            // Show success message
        }
    } catch (error) {
        console.error('Failed to submit feedback:', error)
    } finally {
        submittingFeedback.value = false
    }
}
</script>
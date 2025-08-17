<template>
    <DefaultLayout :title="tutorial.title">
        <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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
                                {{ tutorial.title }}
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Video Content -->
                <div class="lg:col-span-2">
                    <!-- Video Player -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                        <div class="aspect-video bg-gray-900 rounded-t-lg relative">
                            <!-- Video Player Placeholder -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <PlayIcon class="w-20 h-20 text-white mb-4 mx-auto" />
                                    <p class="text-white text-lg">Video Player</p>
                                    <p class="text-gray-300 text-sm">{{ tutorial.duration }}</p>
                                </div>
                            </div>
                            
                            <!-- Video Controls Overlay -->
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                                <div class="flex items-center justify-between text-white">
                                    <div class="flex items-center space-x-4">
                                        <button class="hover:text-blue-400 transition-colors">
                                            <PlayIcon class="w-6 h-6" />
                                        </button>
                                        <button class="hover:text-blue-400 transition-colors">
                                            <SpeakerWaveIcon class="w-6 h-6" />
                                        </button>
                                        <span class="text-sm">0:00 / {{ tutorial.duration }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button class="hover:text-blue-400 transition-colors">
                                            <Cog6ToothIcon class="w-6 h-6" />
                                        </button>
                                        <button class="hover:text-blue-400 transition-colors">
                                            <ArrowsPointingOutIcon class="w-6 h-6" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Video Info -->
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        {{ tutorial.title }}
                                    </h1>
                                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                                        {{ tutorial.description }}
                                    </p>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center space-x-1">
                                            <ClockIcon class="w-4 h-4" />
                                            <span>{{ tutorial.duration }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <TagIcon class="w-4 h-4" />
                                            <span class="capitalize">{{ tutorial.category }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        @click="markAsWatched"
                                        :disabled="isWatched"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-md text-sm font-medium transition-colors"
                                    >
                                        <CheckIcon v-if="isWatched" class="w-4 h-4 mr-1 inline" />
                                        {{ isWatched ? 'Watched' : 'Mark as Watched' }}
                                    </button>
                                    <button
                                        @click="showFeedbackModal = true"
                                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md text-sm font-medium transition-colors"
                                    >
                                        Rate Tutorial
                                    </button>
                                </div>
                            </div>

                            <!-- Topics Covered -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Topics Covered</h3>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="topic in tutorial.topics"
                                        :key="topic"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                    >
                                        {{ topic }}
                                    </span>
                                </div>
                            </div>

                            <!-- Video Chapters/Timestamps -->
                            <div class="mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Video Chapters</h3>
                                <div class="space-y-2">
                                    <div
                                        v-for="(chapter, index) in videoChapters"
                                        :key="index"
                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-colors"
                                        @click="seekToChapter(chapter.timestamp)"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <span class="text-sm font-mono text-gray-500 dark:text-gray-400">
                                                {{ chapter.timestamp }}
                                            </span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ chapter.title }}
                                            </span>
                                        </div>
                                        <PlayIcon class="w-4 h-4 text-gray-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Transcript -->
                            <div v-if="showTranscript">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transcript</h3>
                                    <button
                                        @click="showTranscript = false"
                                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                    >
                                        Hide Transcript
                                    </button>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-64 overflow-y-auto">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                        Welcome to this tutorial on setting up your alumni profile. In this video, we'll walk through 
                                        each step of creating a compelling profile that will help you connect with other alumni and 
                                        discover new opportunities. Let's start by navigating to your profile settings...
                                    </p>
                                </div>
                            </div>
                            <div v-else>
                                <button
                                    @click="showTranscript = true"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500 font-medium"
                                >
                                    Show Transcript
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Comments & Questions
                        </h3>
                        
                        <!-- Add Comment -->
                        <div class="mb-6">
                            <textarea
                                v-model="newComment"
                                rows="3"
                                placeholder="Ask a question or share your thoughts about this tutorial..."
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                            <div class="flex justify-end mt-2">
                                <button
                                    @click="addComment"
                                    :disabled="!newComment.trim()"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-sm font-medium rounded-md transition-colors"
                                >
                                    Post Comment
                                </button>
                            </div>
                        </div>

                        <!-- Comments List -->
                        <div class="space-y-4">
                            <div
                                v-for="comment in comments"
                                :key="comment.id"
                                class="flex space-x-3"
                            >
                                <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <UserIcon class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ comment.author }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ comment.timestamp }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ comment.content }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Related Tutorials -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Related Tutorials</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="relatedTutorial in relatedTutorials"
                                    :key="relatedTutorial.id"
                                    class="flex space-x-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded-lg transition-colors"
                                    @click="viewTutorial(relatedTutorial.id)"
                                >
                                    <div class="w-16 h-12 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center flex-shrink-0">
                                        <PlayIcon class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ relatedTutorial.title }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ relatedTutorial.duration }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tutorial Notes -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Notes</h3>
                        </div>
                        <div class="p-6">
                            <textarea
                                v-model="userNotes"
                                rows="6"
                                placeholder="Take notes while watching the tutorial..."
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                            <div class="flex justify-end mt-2">
                                <button
                                    @click="saveNotes"
                                    class="px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs font-medium rounded transition-colors"
                                >
                                    Save Notes
                                </button>
                            </div>
                        </div>
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
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rate Tutorial</h3>
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
                                    How helpful was this tutorial?
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
                                    placeholder="What did you like? What could be improved?"
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
                                    {{ submittingFeedback ? 'Submitting...' : 'Submit Rating' }}
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
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import {
    HomeIcon,
    ChevronRightIcon,
    PlayIcon,
    ClockIcon,
    TagIcon,
    CheckIcon,
    SpeakerWaveIcon,
    Cog6ToothIcon,
    ArrowsPointingOutIcon,
    UserIcon,
    XMarkIcon,
    StarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tutorial: Object,
    role: String,
    relatedTutorials: Array,
    trainingProgress: Object
})

const isWatched = ref(false)
const showTranscript = ref(false)
const showFeedbackModal = ref(false)
const submittingFeedback = ref(false)
const newComment = ref('')
const userNotes = ref('')

const feedbackForm = ref({
    rating: 0,
    feedback: ''
})

const videoChapters = ref([
    { timestamp: '0:00', title: 'Introduction' },
    { timestamp: '1:30', title: 'Profile Setup Basics' },
    { timestamp: '3:45', title: 'Adding Professional Photo' },
    { timestamp: '5:20', title: 'Career Timeline' },
    { timestamp: '7:10', title: 'Skills and Interests' },
    { timestamp: '8:30', title: 'Privacy Settings' }
])

const comments = ref([
    {
        id: 1,
        author: 'Sarah Johnson',
        timestamp: '2 days ago',
        content: 'Great tutorial! The step-by-step approach made it really easy to follow along.'
    },
    {
        id: 2,
        author: 'Mike Chen',
        timestamp: '1 week ago',
        content: 'Very helpful. I wish I had watched this when I first joined the platform.'
    }
])

onMounted(() => {
    // Load watched status and notes from local storage
    const watchedStatus = localStorage.getItem(`tutorial_watched_${props.tutorial.id}`)
    if (watchedStatus) {
        isWatched.value = JSON.parse(watchedStatus)
    }
    
    const savedNotes = localStorage.getItem(`tutorial_notes_${props.tutorial.id}`)
    if (savedNotes) {
        userNotes.value = savedNotes
    }
})

const markAsWatched = async () => {
    if (isWatched.value) return
    
    isWatched.value = true
    localStorage.setItem(`tutorial_watched_${props.tutorial.id}`, JSON.stringify(true))
    
    // Mark in backend
    try {
        await fetch('/api/training/mark-step-completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                step_id: props.tutorial.id
            })
        })
    } catch (error) {
        console.error('Failed to mark tutorial as watched:', error)
    }
}

const seekToChapter = (timestamp) => {
    // In a real implementation, this would seek the video player to the timestamp
    console.log('Seeking to:', timestamp)
}

const addComment = () => {
    if (!newComment.value.trim()) return
    
    const comment = {
        id: Date.now(),
        author: 'You',
        timestamp: 'Just now',
        content: newComment.value.trim()
    }
    
    comments.value.unshift(comment)
    newComment.value = ''
}

const saveNotes = () => {
    localStorage.setItem(`tutorial_notes_${props.tutorial.id}`, userNotes.value)
    // Show success message
}

const viewTutorial = (tutorialId) => {
    router.visit(route('training.tutorial', tutorialId))
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
                type: 'tutorial',
                content_id: props.tutorial.id,
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
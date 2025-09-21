<template>
    <DefaultLayout :title="tutorial.title">
        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-6 flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <Link
                            :href="route('training.index')"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                        >
                            <HomeIcon class="mr-2 h-4 w-4" />
                            Training
                        </Link>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <ChevronRightIcon class="h-4 w-4 text-gray-400" />
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                                {{ tutorial.title }}
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Main Video Content -->
                <div class="lg:col-span-2">
                    <!-- Video Player -->
                    <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="relative aspect-video rounded-t-lg bg-gray-900">
                            <!-- Video Player Placeholder -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <PlayIcon class="mx-auto mb-4 h-20 w-20 text-white" />
                                    <p class="text-lg text-white">Video Player</p>
                                    <p class="text-sm text-gray-300">{{ tutorial.duration }}</p>
                                </div>
                            </div>

                            <!-- Video Controls Overlay -->
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                                <div class="flex items-center justify-between text-white">
                                    <div class="flex items-center space-x-4">
                                        <button class="transition-colors hover:text-blue-400">
                                            <PlayIcon class="h-6 w-6" />
                                        </button>
                                        <button class="transition-colors hover:text-blue-400">
                                            <SpeakerWaveIcon class="h-6 w-6" />
                                        </button>
                                        <span class="text-sm">0:00 / {{ tutorial.duration }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button class="transition-colors hover:text-blue-400">
                                            <Cog6ToothIcon class="h-6 w-6" />
                                        </button>
                                        <button class="transition-colors hover:text-blue-400">
                                            <ArrowsPointingOutIcon class="h-6 w-6" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Video Info -->
                        <div class="p-6">
                            <div class="mb-4 flex items-start justify-between">
                                <div class="flex-1">
                                    <h1 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ tutorial.title }}
                                    </h1>
                                    <p class="mb-4 text-gray-600 dark:text-gray-400">
                                        {{ tutorial.description }}
                                    </p>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center space-x-1">
                                            <ClockIcon class="h-4 w-4" />
                                            <span>{{ tutorial.duration }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <TagIcon class="h-4 w-4" />
                                            <span class="capitalize">{{ tutorial.category }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        @click="markAsWatched"
                                        :disabled="isWatched"
                                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:bg-gray-400"
                                    >
                                        <CheckIcon v-if="isWatched" class="mr-1 inline h-4 w-4" />
                                        {{ isWatched ? 'Watched' : 'Mark as Watched' }}
                                    </button>
                                    <button
                                        @click="showFeedbackModal = true"
                                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        Rate Tutorial
                                    </button>
                                </div>
                            </div>

                            <!-- Topics Covered -->
                            <div class="mb-6">
                                <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Topics Covered</h3>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="topic in tutorial.topics"
                                        :key="topic"
                                        class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                    >
                                        {{ topic }}
                                    </span>
                                </div>
                            </div>

                            <!-- Video Chapters/Timestamps -->
                            <div class="mb-6">
                                <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Video Chapters</h3>
                                <div class="space-y-2">
                                    <div
                                        v-for="(chapter, index) in videoChapters"
                                        :key="index"
                                        class="flex cursor-pointer items-center justify-between rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600"
                                        @click="seekToChapter(chapter.timestamp)"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <span class="font-mono text-sm text-gray-500 dark:text-gray-400">
                                                {{ chapter.timestamp }}
                                            </span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ chapter.title }}
                                            </span>
                                        </div>
                                        <PlayIcon class="h-4 w-4 text-gray-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Transcript -->
                            <div v-if="showTranscript">
                                <div class="mb-3 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transcript</h3>
                                    <button
                                        @click="showTranscript = false"
                                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                    >
                                        Hide Transcript
                                    </button>
                                </div>
                                <div class="max-h-64 overflow-y-auto rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                                    <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                        Welcome to this tutorial on setting up your alumni profile. In this video, we'll walk through each step of
                                        creating a compelling profile that will help you connect with other alumni and discover new opportunities.
                                        Let's start by navigating to your profile settings...
                                    </p>
                                </div>
                            </div>
                            <div v-else>
                                <button
                                    @click="showTranscript = true"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400"
                                >
                                    Show Transcript
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Comments & Questions</h3>

                        <!-- Add Comment -->
                        <div class="mb-6">
                            <textarea
                                v-model="newComment"
                                rows="3"
                                placeholder="Ask a question or share your thoughts about this tutorial..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                            <div class="mt-2 flex justify-end">
                                <button
                                    @click="addComment"
                                    :disabled="!newComment.trim()"
                                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:bg-gray-400"
                                >
                                    Post Comment
                                </button>
                            </div>
                        </div>

                        <!-- Comments List -->
                        <div class="space-y-4">
                            <div v-for="comment in comments" :key="comment.id" class="flex space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                                    <UserIcon class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                </div>
                                <div class="flex-1">
                                    <div class="mb-1 flex items-center space-x-2">
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
                    <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Related Tutorials</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="relatedTutorial in relatedTutorials"
                                    :key="relatedTutorial.id"
                                    class="flex cursor-pointer space-x-3 rounded-lg p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="viewTutorial(relatedTutorial.id)"
                                >
                                    <div class="flex h-12 w-16 flex-shrink-0 items-center justify-center rounded bg-gray-200 dark:bg-gray-600">
                                        <PlayIcon class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {{ relatedTutorial.title }}
                                        </h4>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ relatedTutorial.duration }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tutorial Notes -->
                    <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Your Notes</h3>
                        </div>
                        <div class="p-6">
                            <textarea
                                v-model="userNotes"
                                rows="6"
                                placeholder="Take notes while watching the tutorial..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                            <div class="mt-2 flex justify-end">
                                <button
                                    @click="saveNotes"
                                    class="rounded bg-gray-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-700"
                                >
                                    Save Notes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Modal -->
            <div v-if="showFeedbackModal" class="fixed inset-0 z-50 overflow-y-auto" @click="showFeedbackModal = false">
                <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    <div
                        class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle dark:bg-gray-800"
                        @click.stop
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rate Tutorial</h3>
                            <button @click="showFeedbackModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="submitFeedback">
                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    How helpful was this tutorial?
                                </label>
                                <div class="flex space-x-2">
                                    <button v-for="rating in 5" :key="rating" type="button" @click="feedbackForm.rating = rating" class="p-1">
                                        <StarIcon
                                            class="h-6 w-6 transition-colors"
                                            :class="
                                                rating <= feedbackForm.rating ? 'fill-current text-yellow-400' : 'text-gray-300 dark:text-gray-600'
                                            "
                                        />
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Additional Comments </label>
                                <textarea
                                    v-model="feedbackForm.feedback"
                                    rows="4"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="What did you like? What could be improved?"
                                ></textarea>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="showFeedbackModal = false"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-400"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="!feedbackForm.rating || submittingFeedback"
                                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:bg-gray-400"
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
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {
    ArrowsPointingOutIcon,
    CheckIcon,
    ChevronRightIcon,
    ClockIcon,
    Cog6ToothIcon,
    HomeIcon,
    PlayIcon,
    SpeakerWaveIcon,
    StarIcon,
    TagIcon,
    UserIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    tutorial: Object,
    role: String,
    relatedTutorials: Array,
    trainingProgress: Object,
});

const isWatched = ref(false);
const showTranscript = ref(false);
const showFeedbackModal = ref(false);
const submittingFeedback = ref(false);
const newComment = ref('');
const userNotes = ref('');

const feedbackForm = ref({
    rating: 0,
    feedback: '',
});

const videoChapters = ref([
    { timestamp: '0:00', title: 'Introduction' },
    { timestamp: '1:30', title: 'Profile Setup Basics' },
    { timestamp: '3:45', title: 'Adding Professional Photo' },
    { timestamp: '5:20', title: 'Career Timeline' },
    { timestamp: '7:10', title: 'Skills and Interests' },
    { timestamp: '8:30', title: 'Privacy Settings' },
]);

const comments = ref([
    {
        id: 1,
        author: 'Sarah Johnson',
        timestamp: '2 days ago',
        content: 'Great tutorial! The step-by-step approach made it really easy to follow along.',
    },
    {
        id: 2,
        author: 'Mike Chen',
        timestamp: '1 week ago',
        content: 'Very helpful. I wish I had watched this when I first joined the platform.',
    },
]);

onMounted(() => {
    // Load watched status and notes from local storage
    const watchedStatus = localStorage.getItem(`tutorial_watched_${props.tutorial.id}`);
    if (watchedStatus) {
        isWatched.value = JSON.parse(watchedStatus);
    }

    const savedNotes = localStorage.getItem(`tutorial_notes_${props.tutorial.id}`);
    if (savedNotes) {
        userNotes.value = savedNotes;
    }
});

const markAsWatched = async () => {
    if (isWatched.value) return;

    isWatched.value = true;
    localStorage.setItem(`tutorial_watched_${props.tutorial.id}`, JSON.stringify(true));

    // Mark in backend
    try {
        await fetch('/api/training/mark-step-completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                step_id: props.tutorial.id,
            }),
        });
    } catch (error) {
        console.error('Failed to mark tutorial as watched:', error);
    }
};

const seekToChapter = (timestamp) => {
    // In a real implementation, this would seek the video player to the timestamp
    console.log('Seeking to:', timestamp);
};

const addComment = () => {
    if (!newComment.value.trim()) return;

    const comment = {
        id: Date.now(),
        author: 'You',
        timestamp: 'Just now',
        content: newComment.value.trim(),
    };

    comments.value.unshift(comment);
    newComment.value = '';
};

const saveNotes = () => {
    localStorage.setItem(`tutorial_notes_${props.tutorial.id}`, userNotes.value);
    // Show success message
};

const viewTutorial = (tutorialId) => {
    router.visit(route('training.tutorial', tutorialId));
};

const submitFeedback = async () => {
    if (!feedbackForm.value.rating) return;

    submittingFeedback.value = true;

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
                feedback: feedbackForm.value.feedback,
            }),
        });

        const data = await response.json();
        if (data.success) {
            showFeedbackModal.value = false;
            feedbackForm.value = { rating: 0, feedback: '' };
            // Show success message
        }
    } catch (error) {
        console.error('Failed to submit feedback:', error);
    } finally {
        submittingFeedback.value = false;
    }
};
</script>

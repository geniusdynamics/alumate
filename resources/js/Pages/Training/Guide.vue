<template>
    <DefaultLayout :title="guide.title">
        <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
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
                                {{ guide.title }}
                            </span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Guide Header -->
            <div class="mb-8 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                            <component :is="getIcon(guide.icon)" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="flex-1">
                            <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                                {{ guide.title }}
                            </h1>
                            <p class="mb-4 text-gray-600 dark:text-gray-400">
                                {{ guide.description }}
                            </p>
                            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center space-x-1">
                                    <ClockIcon class="h-4 w-4" />
                                    <span>{{ guide.estimated_time }}</span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <DocumentTextIcon class="h-4 w-4" />
                                    <span>{{ guide.sections.length }} sections</span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <TagIcon class="h-4 w-4" />
                                    <span class="capitalize">{{ guide.category }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                @click="markAsCompleted"
                                :disabled="isCompleted"
                                class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:bg-gray-400"
                            >
                                <CheckIcon v-if="isCompleted" class="mr-1 inline h-4 w-4" />
                                {{ isCompleted ? 'Completed' : 'Mark Complete' }}
                            </button>
                            <button
                                @click="showFeedbackModal = true"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Give Feedback
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Indicator -->
            <div class="mb-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Guide Progress</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ completedSections.length }} of {{ guide.sections.length }} sections
                    </span>
                </div>

                <div class="mb-4">
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                            :style="{ width: (completedSections.length / guide.sections.length) * 100 + '%' }"
                        ></div>
                    </div>
                </div>

                <!-- Section Checklist -->
                <div class="space-y-2">
                    <div v-for="(section, index) in guide.sections" :key="index" class="flex items-center space-x-3">
                        <button @click="toggleSection(index)" class="flex-shrink-0">
                            <div
                                class="flex h-5 w-5 items-center justify-center rounded border-2 transition-colors"
                                :class="
                                    completedSections.includes(index)
                                        ? 'border-blue-600 bg-blue-600'
                                        : 'border-gray-300 hover:border-blue-500 dark:border-gray-600'
                                "
                            >
                                <CheckIcon v-if="completedSections.includes(index)" class="h-3 w-3 text-white" />
                            </div>
                        </button>
                        <span
                            class="text-sm transition-colors"
                            :class="
                                completedSections.includes(index)
                                    ? 'text-gray-500 line-through dark:text-gray-400'
                                    : 'text-gray-700 dark:text-gray-300'
                            "
                        >
                            {{ section }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Guide Content -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
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

                            <div class="my-6 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                <div class="flex items-start space-x-3">
                                    <LightBulbIcon class="mt-0.5 h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    <div>
                                        <h4 class="font-semibold text-blue-900 dark:text-blue-100">Pro Tip</h4>
                                        <p class="text-sm text-blue-800 dark:text-blue-200">
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

                            <div class="my-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                                <h4 class="mb-2 font-semibold text-green-900 dark:text-green-100">Example Connection Request</h4>
                                <p class="text-sm italic text-green-800 dark:text-green-200">
                                    "Hi Sarah, I noticed we both graduated from State University and work in marketing. I'd love to connect and learn
                                    about your experience at TechCorp. Looking forward to connecting!"
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

                            <div class="my-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                                <div class="flex items-start space-x-3">
                                    <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                                    <div>
                                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-100">Note</h4>
                                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
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
            <div class="mt-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Related Guides</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- This would be populated with related guides -->
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h3 class="mb-2 font-medium text-gray-900 dark:text-white">Career Development Tools</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Learn to leverage the platform for career growth</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <h3 class="mb-2 font-medium text-gray-900 dark:text-white">Social Timeline & Sharing</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Master social features and engagement</p>
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
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Guide Feedback</h3>
                            <button @click="showFeedbackModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="submitFeedback">
                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> How helpful was this guide? </label>
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
                                    placeholder="What could we improve about this guide?"
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
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {
    AcademicCapIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    CheckIcon,
    ChevronRightIcon,
    ClockIcon,
    ComputerDesktopIcon,
    CurrencyDollarIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    HeartIcon,
    HomeIcon,
    InformationCircleIcon,
    LightBulbIcon,
    MapIcon,
    RocketLaunchIcon,
    ShieldCheckIcon,
    SparklesIcon,
    StarIcon,
    TagIcon,
    TrophyIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    guide: Object,
    role: String,
    trainingProgress: Object,
});

const completedSections = ref([]);
const showFeedbackModal = ref(false);
const submittingFeedback = ref(false);
const feedbackForm = ref({
    rating: 0,
    feedback: '',
});

const isCompleted = computed(() => {
    return completedSections.value.length === props.guide.sections.length;
});

onMounted(() => {
    // Load completed sections from local storage or user progress
    const saved = localStorage.getItem(`guide_progress_${props.guide.id}`);
    if (saved) {
        completedSections.value = JSON.parse(saved);
    }
});

const getIcon = (iconName) => {
    const icons = {
        chat: ChatBubbleLeftRightIcon,
        users: UsersIcon,
        briefcase: BriefcaseIcon,
        calendar: CalendarIcon,
        chart: ChartBarIcon,
        map: MapIcon,
        academic: AcademicCapIcon,
        heart: HeartIcon,
        currency: CurrencyDollarIcon,
        trophy: TrophyIcon,
        sparkles: SparklesIcon,
        rocket: RocketLaunchIcon,
        shield: ShieldCheckIcon,
        building: BuildingOfficeIcon,
        info: InformationCircleIcon,
        monitor: ComputerDesktopIcon,
    };
    return icons[iconName] || SparklesIcon;
};

const toggleSection = (index) => {
    const sectionIndex = completedSections.value.indexOf(index);
    if (sectionIndex > -1) {
        completedSections.value.splice(sectionIndex, 1);
    } else {
        completedSections.value.push(index);
    }

    // Save progress
    localStorage.setItem(`guide_progress_${props.guide.id}`, JSON.stringify(completedSections.value));
};

const markAsCompleted = async () => {
    if (isCompleted.value) return;

    // Mark all sections as completed
    completedSections.value = props.guide.sections.map((_, index) => index);
    localStorage.setItem(`guide_progress_${props.guide.id}`, JSON.stringify(completedSections.value));

    // Mark in backend
    try {
        await fetch('/api/training/mark-step-completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                step_id: props.guide.id,
            }),
        });
    } catch (error) {
        console.error('Failed to mark guide as completed:', error);
    }
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
                type: 'guide',
                content_id: props.guide.id,
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














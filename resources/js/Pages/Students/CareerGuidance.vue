<template>
    <AppLayout title="Career Guidance">
        <Head title="Student Career Guidance" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Career Guidance Center</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Get personalized career advice, explore opportunities, and plan your professional journey
                </p>
            </div>

            <!-- Career Assessment Banner -->
            <div v-if="!careerAssessment" class="mb-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold mb-2">Take Your Career Assessment</h2>
                        <p class="text-blue-100">Discover your strengths, interests, and ideal career paths with our comprehensive assessment</p>
                    </div>
                    <Link
                        :href="route('students.career-assessment')"
                        class="bg-white text-blue-600 px-6 py-3 rounded-md font-medium hover:bg-gray-100 transition-colors"
                    >
                        Start Assessment
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Personalized Recommendations -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Personalized for You</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Career Paths -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <BriefcaseIcon class="w-6 h-6 text-blue-600" />
                                        <h3 class="font-medium text-gray-900 dark:text-white">Recommended Careers</h3>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="career in recommendedCareers.slice(0, 3)"
                                            :key="career.title"
                                            class="flex items-center justify-between"
                                        >
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ career.title }}</span>
                                            <span class="text-xs text-blue-600 dark:text-blue-400">{{ career.match }}% match</span>
                                        </div>
                                    </div>
                                    <Link
                                        :href="route('students.career-explorer')"
                                        class="text-sm text-blue-600 hover:text-blue-500 mt-3 inline-block"
                                    >
                                        Explore All Careers →
                                    </Link>
                                </div>

                                <!-- Skills to Develop -->
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <AcademicCapIcon class="w-6 h-6 text-green-600" />
                                        <h3 class="font-medium text-gray-900 dark:text-white">Skills to Develop</h3>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="skill in skillsToImprove.slice(0, 3)"
                                            :key="skill.name"
                                            class="flex items-center justify-between"
                                        >
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ skill.name }}</span>
                                            <span class="text-xs text-green-600 dark:text-green-400">{{ skill.priority }}</span>
                                        </div>
                                    </div>
                                    <Link
                                        :href="route('students.skill-development')"
                                        class="text-sm text-green-600 hover:text-green-500 mt-3 inline-block"
                                    >
                                        View Learning Path →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Career Planning Tools -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Career Planning Tools</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <CareerToolCard
                                    v-for="tool in careerTools"
                                    :key="tool.id"
                                    :tool="tool"
                                    @use-tool="handleUseTool"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Industry Insights -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Industry Insights</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <IndustryInsightCard
                                    v-for="insight in industryInsights"
                                    :key="insight.id"
                                    :insight="insight"
                                    @view-details="handleViewInsight"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Alumni Career Stories -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">Alumni Career Stories</h2>
                                <Link
                                    :href="route('students.stories.discovery')"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    View All Stories
                                </Link>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <CareerStoryCard
                                    v-for="story in careerStories.slice(0, 3)"
                                    :key="story.id"
                                    :story="story"
                                    @read-story="handleReadStory"
                                    @connect-alumni="handleConnectAlumni"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Career Progress -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Progress</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Career Readiness</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ careerReadiness }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div 
                                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: careerReadiness + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Skills Development</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ skillsProgress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div 
                                            class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: skillsProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Network Building</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ networkProgress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div 
                                            class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: networkProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <Link 
                                :href="route('students.resume-builder')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <DocumentTextIcon class="w-5 h-5" />
                                <span>Resume Builder</span>
                            </Link>
                            <Link 
                                :href="route('students.interview-prep')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400"
                            >
                                <ChatBubbleLeftRightIcon class="w-5 h-5" />
                                <span>Interview Prep</span>
                            </Link>
                            <Link 
                                :href="route('students.networking-guide')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400"
                            >
                                <UsersIcon class="w-5 h-5" />
                                <span>Networking Guide</span>
                            </Link>
                            <Link 
                                :href="route('students.salary-insights')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-yellow-600 dark:hover:text-yellow-400"
                            >
                                <CurrencyDollarIcon class="w-5 h-5" />
                                <span>Salary Insights</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Career Events</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="upcomingEvents.length === 0" class="text-center py-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No upcoming events</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="event in upcomingEvents.slice(0, 3)"
                                    :key="event.id"
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-3"
                                >
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ event.title }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatEventDate(event.date) }}</p>
                                    <Link
                                        :href="route('events.show', event.id)"
                                        class="text-xs text-blue-600 hover:text-blue-500"
                                    >
                                        Learn More →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Career Resources -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Resources</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <a 
                                href="#"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <BookOpenIcon class="w-5 h-5" />
                                <span>Career Guide Library</span>
                            </a>
                            <a 
                                href="#"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-green-600 dark:hover:text-green-400"
                            >
                                <PlayIcon class="w-5 h-5" />
                                <span>Video Tutorials</span>
                            </a>
                            <a 
                                href="#"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-400"
                            >
                                <QuestionMarkCircleIcon class="w-5 h-5" />
                                <span>FAQ & Help</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { format } from 'date-fns'
import AppLayout from '@/layouts/AppLayout.vue'
import CareerToolCard from '@/Components/CareerToolCard.vue'
import IndustryInsightCard from '@/Components/IndustryInsightCard.vue'
import CareerStoryCard from '@/Components/CareerStoryCard.vue'
import {
    BriefcaseIcon,
    AcademicCapIcon,
    DocumentTextIcon,
    ChatBubbleLeftRightIcon,
    UsersIcon,
    CurrencyDollarIcon,
    BookOpenIcon,
    PlayIcon,
    QuestionMarkCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    careerAssessment: Object,
    recommendedCareers: Array,
    skillsToImprove: Array,
    careerTools: Array,
    industryInsights: Array,
    careerStories: Array,
    upcomingEvents: Array,
    careerReadiness: Number,
    skillsProgress: Number,
    networkProgress: Number,
})

const formatEventDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const handleUseTool = (toolId) => {
    router.visit(route('students.career-tools.use', toolId))
}

const handleViewInsight = (insightId) => {
    router.visit(route('students.industry-insights.show', insightId))
}

const handleReadStory = (storyId) => {
    router.visit(route('stories.show', storyId))
}

const handleConnectAlumni = (alumniId) => {
    router.post(route('api.connections.request'), {
        user_id: alumniId
    }, {
        preserveState: true
    })
}
</script>

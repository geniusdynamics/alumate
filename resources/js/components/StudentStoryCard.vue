<template>
    <div class="student-story-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow duration-200">
        <!-- Story Header -->
        <div class="relative">
            <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600">
                <img
                    v-if="story.featured_image"
                    :src="story.featured_image"
                    :alt="story.title"
                    class="w-full h-full object-cover"
                />
            </div>
            
            <!-- Story Type Badge -->
            <div class="absolute top-3 left-3">
                <span 
                    :class="getStoryTypeBadgeClass(story.category)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                    {{ formatStoryType(story.category) }}
                </span>
            </div>

            <!-- Save Button -->
            <div class="absolute top-3 right-3">
                <button
                    @click="toggleSave"
                    :class="story.is_saved ? 'text-yellow-400' : 'text-white hover:text-yellow-400'"
                    class="p-1 rounded-full bg-black bg-opacity-30 transition-colors"
                >
                    <BookmarkIcon :class="story.is_saved ? 'fill-current' : ''" class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Alumni Info -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <UserIcon class="w-6 h-6 text-gray-600 dark:text-gray-300" />
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ story.author.name }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ getAlumniDescription(story.author) }}
                    </p>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="text-xs text-blue-600 dark:text-blue-400">
                            Class of {{ story.author.graduation_year }}
                        </span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ story.author.current_position }}
                        </span>
                    </div>
                </div>
                <button
                    @click="connectWithAlumni"
                    class="text-blue-600 hover:text-blue-500 text-sm font-medium"
                >
                    Connect
                </button>
            </div>
        </div>

        <!-- Story Content -->
        <div class="p-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                {{ story.title }}
            </h2>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-3">
                {{ story.excerpt || story.content }}
            </p>

            <!-- Key Insights -->
            <div v-if="story.key_insights && story.key_insights.length > 0" class="mb-3">
                <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Key Insights:</h4>
                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="insight in story.key_insights.slice(0, 3)"
                        :key="insight"
                        class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                    >
                        {{ insight }}
                    </span>
                    <span
                        v-if="story.key_insights.length > 3"
                        class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                    >
                        +{{ story.key_insights.length - 3 }} more
                    </span>
                </div>
            </div>

            <!-- Career Path -->
            <div v-if="story.career_path" class="mb-3">
                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <BriefcaseIcon class="w-4 h-4" />
                    <span>{{ story.career_path }}</span>
                </div>
            </div>

            <!-- Skills & Technologies -->
            <div v-if="story.skills && story.skills.length > 0" class="mb-3">
                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="skill in story.skills.slice(0, 4)"
                        :key="skill"
                        class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                    >
                        {{ skill }}
                    </span>
                    <span
                        v-if="story.skills.length > 4"
                        class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-50 text-gray-500 dark:bg-gray-600 dark:text-gray-400"
                    >
                        +{{ story.skills.length - 4 }}
                    </span>
                </div>
            </div>

            <!-- Story Stats -->
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-1">
                        <EyeIcon class="w-4 h-4" />
                        <span>{{ formatNumber(story.views_count) }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <HeartIcon class="w-4 h-4" />
                        <span>{{ formatNumber(story.likes_count) }}</span>
                    </div>
                    <div class="flex items-center space-x-1">
                        <ChatBubbleLeftIcon class="w-4 h-4" />
                        <span>{{ formatNumber(story.comments_count) }}</span>
                    </div>
                </div>
                <span class="text-xs">{{ formatTimeAgo(story.published_at) }}</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
                <Link
                    :href="route('stories.show', story.id)"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                    Read Full Story
                </Link>
                <button
                    @click="shareStory"
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    title="Share story"
                >
                    <ShareIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Relevance Score (for students) -->
            <div v-if="story.relevance_score" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600 dark:text-gray-400">Relevance to your profile:</span>
                    <div class="flex items-center space-x-1">
                        <div class="flex space-x-1">
                            <div
                                v-for="i in 5"
                                :key="i"
                                :class="i <= story.relevance_score ? 'text-yellow-400' : 'text-gray-300'"
                                class="w-3 h-3"
                            >
                                <StarIcon class="w-full h-full fill-current" />
                            </div>
                        </div>
                        <span class="text-xs text-gray-600 dark:text-gray-400 ml-1">
                            {{ story.relevance_score }}/5
                        </span>
                    </div>
                </div>
                <p v-if="story.relevance_reason" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ story.relevance_reason }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { formatDistanceToNow } from 'date-fns'
import {
    UserIcon,
    BookmarkIcon,
    BriefcaseIcon,
    EyeIcon,
    HeartIcon,
    ChatBubbleLeftIcon,
    ShareIcon,
    StarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    story: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['connect-alumni', 'save-story'])

const getStoryTypeBadgeClass = (category) => {
    const classes = {
        career_change: 'bg-green-100 text-green-800',
        entrepreneurship: 'bg-purple-100 text-purple-800',
        leadership: 'bg-blue-100 text-blue-800',
        innovation: 'bg-yellow-100 text-yellow-800',
        social_impact: 'bg-red-100 text-red-800',
        default: 'bg-gray-100 text-gray-800'
    }
    return classes[category] || classes.default
}

const formatStoryType = (category) => {
    return category.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getAlumniDescription = (author) => {
    const parts = []
    if (author.degree) parts.push(author.degree)
    if (author.major) parts.push(author.major)
    return parts.join(' • ')
}

const formatNumber = (num) => {
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k'
    }
    return num.toString()
}

const formatTimeAgo = (timestamp) => {
    return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
}

const connectWithAlumni = () => {
    emit('connect-alumni', props.story.author.id)
}

const toggleSave = () => {
    emit('save-story', props.story.id)
}

const shareStory = () => {
    if (navigator.share) {
        navigator.share({
            title: props.story.title,
            text: props.story.excerpt,
            url: route('stories.show', props.story.id)
        })
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.origin + route('stories.show', props.story.id))
    }
}
</script>

<style scoped>
.student-story-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.student-story-card:hover {
    transform: translateY(-2px);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

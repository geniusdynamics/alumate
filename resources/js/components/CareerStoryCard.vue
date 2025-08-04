<template>
    <div class="career-story-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Story Header -->
        <div class="flex items-start space-x-4 mb-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                    <UserIcon class="w-6 h-6 text-white" />
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ story.title }}</h3>
                <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ story.author_name }}</span>
                    <span>•</span>
                    <span>{{ story.current_position }}</span>
                    <span v-if="story.company">@ {{ story.company }}</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <CalendarIcon class="w-4 h-4" />
                    <span>{{ formatDate(story.published_at || story.created_at) }}</span>
                    <span>•</span>
                    <span>{{ story.read_time || '5' }} min read</span>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span 
                    :class="getStoryTypeClass(story.story_type)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                    {{ formatStoryType(story.story_type) }}
                </span>
            </div>
        </div>

        <!-- Story Summary -->
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3">{{ story.summary || story.excerpt }}</p>
        </div>

        <!-- Career Journey Highlights -->
        <div v-if="story.career_highlights && story.career_highlights.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Career Journey</h4>
            <div class="space-y-2">
                <div 
                    v-for="highlight in story.career_highlights.slice(0, 3)"
                    :key="highlight.year"
                    class="flex items-center space-x-3 text-sm"
                >
                    <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>
                    <div class="flex-1">
                        <span class="font-medium text-gray-900 dark:text-white">{{ highlight.year }}</span>
                        <span class="text-gray-600 dark:text-gray-400 ml-2">{{ highlight.achievement }}</span>
                    </div>
                </div>
                <div v-if="story.career_highlights.length > 3" class="text-xs text-gray-500 dark:text-gray-400 ml-5">
                    +{{ story.career_highlights.length - 3 }} more milestones
                </div>
            </div>
        </div>

        <!-- Key Lessons -->
        <div v-if="story.key_lessons && story.key_lessons.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Key Lessons</h4>
            <ul class="space-y-1">
                <li 
                    v-for="lesson in story.key_lessons.slice(0, 2)"
                    :key="lesson"
                    class="flex items-start text-sm text-gray-600 dark:text-gray-400"
                >
                    <LightBulbIcon class="w-4 h-4 text-yellow-500 mr-2 flex-shrink-0 mt-0.5" />
                    {{ lesson }}
                </li>
                <li v-if="story.key_lessons.length > 2" class="text-sm text-gray-500 dark:text-gray-400">
                    +{{ story.key_lessons.length - 2 }} more lessons
                </li>
            </ul>
        </div>

        <!-- Skills & Technologies -->
        <div v-if="story.skills_mentioned && story.skills_mentioned.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Skills & Technologies</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="skill in story.skills_mentioned.slice(0, 6)"
                    :key="skill"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="story.skills_mentioned.length > 6"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ story.skills_mentioned.length - 6 }}
                </span>
            </div>
        </div>

        <!-- Industry & Role Info -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ story.industry || 'Technology' }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Industry</div>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ story.experience_level || 'Mid-Level' }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Experience</div>
            </div>
        </div>

        <!-- Story Stats -->
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-4">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-1">
                    <EyeIcon class="w-4 h-4" />
                    <span>{{ story.views_count || 0 }}</span>
                </div>
                <div class="flex items-center space-x-1">
                    <HeartIcon class="w-4 h-4" />
                    <span>{{ story.likes_count || 0 }}</span>
                </div>
                <div class="flex items-center space-x-1">
                    <ChatBubbleLeftIcon class="w-4 h-4" />
                    <span>{{ story.comments_count || 0 }}</span>
                </div>
            </div>
            <div class="flex items-center space-x-1">
                <StarIcon 
                    v-for="star in 5"
                    :key="star"
                    :class="star <= (story.rating || 4) ? 'text-yellow-400' : 'text-gray-300'"
                    class="w-4 h-4"
                />
                <span class="ml-1">{{ (story.rating || 4).toFixed(1) }}</span>
            </div>
        </div>

        <!-- Tags -->
        <div v-if="story.tags && story.tags.length > 0" class="mb-4">
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="tag in story.tags.slice(0, 4)"
                    :key="tag"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    #{{ tag }}
                </span>
                <span
                    v-if="story.tags.length > 4"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ story.tags.length - 4 }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                @click="readStory"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                Read Full Story
            </button>
            
            <button
                @click="likeStory"
                :class="story.is_liked ? 'text-red-600 border-red-300' : 'text-gray-600 border-gray-300'"
                class="px-4 py-2 hover:text-red-800 border hover:border-red-400 rounded-md text-sm font-medium transition-colors dark:hover:text-red-200"
            >
                <HeartIcon class="w-4 h-4" />
            </button>
            
            <button
                @click="saveStory"
                :class="story.is_saved ? 'text-yellow-600 border-yellow-300' : 'text-gray-600 border-gray-300'"
                class="px-4 py-2 hover:text-yellow-800 border hover:border-yellow-400 rounded-md text-sm font-medium transition-colors dark:hover:text-yellow-200"
            >
                <BookmarkIcon class="w-4 h-4" />
            </button>
            
            <button
                @click="shareStory"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 hover:border-gray-400 rounded-md text-sm font-medium transition-colors dark:text-gray-400 dark:hover:text-gray-200 dark:border-gray-600"
            >
                <ShareIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Author Connect -->
        <div v-if="story.author_available_for_mentoring" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium">{{ story.author_name }}</span> is available for mentoring
                </div>
                <button
                    @click="connectWithAuthor"
                    class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md font-medium transition-colors"
                >
                    Connect
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { format } from 'date-fns'
import {
    UserIcon,
    CalendarIcon,
    LightBulbIcon,
    EyeIcon,
    HeartIcon,
    ChatBubbleLeftIcon,
    StarIcon,
    BookmarkIcon,
    ShareIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    story: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['read-story', 'like-story', 'save-story', 'share-story', 'connect-author'])

const getStoryTypeClass = (type) => {
    const classes = {
        'career_change': 'bg-purple-100 text-purple-800',
        'success_story': 'bg-green-100 text-green-800',
        'learning_journey': 'bg-blue-100 text-blue-800',
        'entrepreneurship': 'bg-orange-100 text-orange-800',
        'mentorship': 'bg-indigo-100 text-indigo-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const formatStoryType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const readStory = () => {
    emit('read-story', props.story.id)
}

const likeStory = () => {
    emit('like-story', props.story.id)
}

const saveStory = () => {
    emit('save-story', props.story.id)
}

const shareStory = () => {
    emit('share-story', props.story.id)
}

const connectWithAuthor = () => {
    emit('connect-author', props.story.author_id)
}
</script>

<style scoped>
.career-story-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.career-story-card:hover {
    transform: translateY(-2px);
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

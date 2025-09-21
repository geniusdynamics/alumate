<template>
    <div class="overflow-hidden rounded-lg bg-white shadow-md transition-shadow duration-300 hover:shadow-lg">
        <!-- Featured Badge -->
        <div v-if="featured" class="absolute left-4 top-4 z-10">
            <span class="rounded-full bg-yellow-500 px-2 py-1 text-xs font-semibold text-white"> Featured </span>
        </div>

        <!-- Featured Image -->
        <div class="relative h-48 bg-gray-200">
            <img v-if="story.featured_image" :src="getImageUrl(story.featured_image)" :alt="story.title" class="h-full w-full object-cover" />
            <div v-else class="flex h-full w-full items-center justify-center">
                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
            </div>

            <!-- Overlay with view button -->
            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 transition-all duration-300 hover:bg-opacity-30">
                <button
                    @click="$emit('view', story)"
                    class="rounded-md bg-white px-4 py-2 font-medium text-gray-900 opacity-0 transition-opacity duration-300 hover:opacity-100"
                >
                    Read Story
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <!-- User Info -->
            <div class="mb-4 flex items-center">
                <img v-if="story.user.avatar_url" :src="story.user.avatar_url" :alt="story.user.name" class="mr-3 h-10 w-10 rounded-full" />
                <div v-else class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-300">
                    <span class="text-sm font-medium text-gray-600">
                        {{ story.user.name.charAt(0).toUpperCase() }}
                    </span>
                </div>
                <div>
                    <p class="font-medium text-gray-900">{{ story.user.name }}</p>
                    <p class="text-sm text-gray-500">
                        {{ story.current_role }}
                        <span v-if="story.current_company"> at {{ story.current_company }}</span>
                    </p>
                </div>
            </div>

            <!-- Title and Summary -->
            <h3 class="mb-2 line-clamp-2 text-lg font-semibold text-gray-900">
                {{ story.title }}
            </h3>
            <p class="mb-4 line-clamp-3 text-sm text-gray-600">
                {{ story.summary }}
            </p>

            <!-- Tags and Achievement Type -->
            <div class="mb-4">
                <div class="mb-2 flex items-center">
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                        {{ formatAchievementType(story.achievement_type) }}
                    </span>
                    <span
                        v-if="story.industry"
                        class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                    >
                        {{ story.industry }}
                    </span>
                </div>
                <div v-if="story.tags && story.tags.length > 0" class="flex flex-wrap gap-1">
                    <span
                        v-for="tag in story.tags.slice(0, 3)"
                        :key="tag"
                        class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800"
                    >
                        #{{ tag }}
                    </span>
                    <span v-if="story.tags.length > 3" class="text-xs text-gray-500"> +{{ story.tags.length - 3 }} more </span>
                </div>
            </div>

            <!-- Graduation Info -->
            <div v-if="story.graduation_year || story.degree_program" class="mb-4 text-sm text-gray-500">
                <span v-if="story.degree_program">{{ story.degree_program }}</span>
                <span v-if="story.graduation_year">
                    <span v-if="story.degree_program"> • </span>
                    Class of {{ story.graduation_year }}
                </span>
            </div>

            <!-- Engagement Stats -->
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                    <span class="flex items-center">
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        {{ story.view_count }}
                    </span>
                    <span class="flex items-center">
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                            />
                        </svg>
                        {{ story.like_count }}
                    </span>
                    <span class="flex items-center">
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"
                            />
                        </svg>
                        {{ story.share_count }}
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-2">
                    <button @click="$emit('like', story)" class="p-2 text-gray-400 transition-colors hover:text-red-500" title="Like this story">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                            />
                        </svg>
                    </button>
                    <button
                        v-if="story.allow_social_sharing"
                        @click="$emit('share', story)"
                        class="p-2 text-gray-400 transition-colors hover:text-blue-500"
                        title="Share this story"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface SuccessStory {
    id: number;
    title: string;
    summary: string;
    content: string;
    featured_image: string | null;
    media_urls: string[];
    industry: string | null;
    achievement_type: string;
    current_role: string | null;
    current_company: string | null;
    graduation_year: string | null;
    degree_program: string | null;
    tags: string[];
    demographics: Record<string, any>;
    status: string;
    is_featured: boolean;
    allow_social_sharing: boolean;
    view_count: number;
    share_count: number;
    like_count: number;
    published_at: string;
    user: {
        id: number;
        name: string;
        avatar_url: string | null;
    };
}

interface Props {
    story: SuccessStory;
    featured?: boolean;
}

defineProps<Props>();

defineEmits<{
    view: [story: SuccessStory];
    share: [story: SuccessStory];
    like: [story: SuccessStory];
}>();

const getImageUrl = (path: string) => {
    return `/storage/${path}`;
};

const formatAchievementType = (type: string) => {
    return type
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
</script>

<style scoped>
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

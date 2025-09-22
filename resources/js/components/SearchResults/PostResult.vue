<template>
    <div class="post-result">
        <div class="post-header">
            <div class="author-info">
                <img v-if="post.user_avatar" :src="post.user_avatar" :alt="`${post.user_name}'s avatar`" class="author-avatar" />
                <div v-else class="author-avatar-placeholder">
                    {{ getInitials(post.user_name) }}
                </div>

                <div class="author-details">
                    <h3 class="author-name">{{ post.user_name }}</h3>
                    <div class="post-meta">
                        <span class="post-date">{{ formatDate(post.created_at) }}</span>
                        <span v-if="post.post_type" class="post-type">
                            {{ formatPostType(post.post_type) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="relevance-score" :title="`Relevance score: ${score.toFixed(2)}`">
                <div class="score-bar">
                    <div class="score-fill" :style="{ width: `${Math.min(score * 10, 100)}%` }"></div>
                </div>
                <span class="score-text">{{ (score * 10).toFixed(0) }}%</span>
            </div>
        </div>

        <div class="post-content">
            <div class="post-text">
                <span v-html="highlightText(post.content, highlight.content)"></span>
            </div>

            <div v-if="post.tags && post.tags.length > 0" class="post-tags">
                <TagIcon class="h-4 w-4" />
                <div class="tags-list">
                    <span v-for="tag in post.tags" :key="tag" class="tag"> #{{ tag }} </span>
                </div>
            </div>
        </div>

        <div class="post-engagement">
            <div class="engagement-stats">
                <div v-if="post.likes_count" class="stat-item">
                    <HeartIcon class="h-4 w-4" />
                    <span>{{ post.likes_count }}</span>
                </div>

                <div v-if="post.comments_count" class="stat-item">
                    <ChatBubbleLeftIcon class="h-4 w-4" />
                    <span>{{ post.comments_count }}</span>
                </div>

                <div v-if="post.shares_count" class="stat-item">
                    <ShareIcon class="h-4 w-4" />
                    <span>{{ post.shares_count }}</span>
                </div>
            </div>

            <div class="post-actions">
                <button @click="viewPost" class="action-btn primary">
                    <EyeIcon class="h-4 w-4" />
                    View Post
                </button>

                <button @click="likePost" class="action-btn secondary" :class="{ liked: isLiked }" :disabled="isLiking">
                    <HeartIcon v-if="!isLiking" class="h-4 w-4" />
                    <LoadingSpinner v-else class="h-4 w-4" />
                    Like
                </button>

                <button @click="sharePost" class="action-btn secondary">
                    <ShareIcon class="h-4 w-4" />
                    Share
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { ChatBubbleLeftIcon, EyeIcon, HeartIcon, ShareIcon, TagIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoadingSpinner from '../LoadingSpinner.vue';

interface Post {
    id: number;
    user_id: number;
    user_name: string;
    user_avatar?: string;
    content: string;
    post_type?: string;
    tags?: string[];
    likes_count?: number;
    comments_count?: number;
    shares_count?: number;
    created_at: string;
    updated_at: string;
}

interface Highlight {
    content?: string[];
    [key: string]: string[] | undefined;
}

const props = defineProps<{
    post: Post;
    highlight: Highlight;
    score: number;
}>();

const emit = defineEmits<{
    'post-viewed': [postId: number];
    'post-liked': [postId: number];
    'post-shared': [postId: number];
}>();

// Reactive state
const isLiking = ref(false);
const isLiked = ref(false); // This would be determined from user's like status

// Toast composable
const { showToast } = useToast();

// Methods
const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const highlightText = (text: string, highlights?: string[]): string => {
    if (!highlights || highlights.length === 0) {
        return text;
    }

    // Join all highlight fragments and return as HTML
    return highlights.join('...');
};

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60);

    if (diffInHours < 1) {
        return 'Just now';
    } else if (diffInHours < 24) {
        return `${Math.floor(diffInHours)}h ago`;
    } else if (diffInHours < 24 * 7) {
        return `${Math.floor(diffInHours / 24)}d ago`;
    } else {
        return date.toLocaleDateString();
    }
};

const formatPostType = (type: string): string => {
    const typeMap: Record<string, string> = {
        career_update: 'Career Update',
        achievement: 'Achievement',
        general: 'Post',
        event: 'Event',
        job_posting: 'Job Posting',
    };

    return typeMap[type] || type;
};

const viewPost = () => {
    router.visit(`/posts/${props.post.id}`);
    emit('post-viewed', props.post.id);
};

const likePost = async () => {
    isLiking.value = true;

    try {
        const response = await fetch(`/api/posts/${props.post.id}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            const data = await response.json();
            isLiked.value = data.liked;

            // Update likes count (this would typically come from the response)
            if (data.liked) {
                props.post.likes_count = (props.post.likes_count || 0) + 1;
            } else {
                props.post.likes_count = Math.max((props.post.likes_count || 0) - 1, 0);
            }

            emit('post-liked', props.post.id);
        } else {
            throw new Error('Failed to like post');
        }
    } catch (error) {
        console.error('Failed to like post:', error);
        showToast('Failed to like post. Please try again.', 'error');
    } finally {
        isLiking.value = false;
    }
};

const sharePost = () => {
    // This would open a share modal or copy link to clipboard
    navigator.clipboard.writeText(`${window.location.origin}/posts/${props.post.id}`);
    showToast('Post link copied to clipboard!', 'success');
    emit('post-shared', props.post.id);
};
</script>

<style scoped>
.post-result {
    @apply space-y-4;
}

.post-header {
    @apply flex items-start justify-between;
}

.author-info {
    @apply flex items-center space-x-3;
}

.author-avatar {
    @apply h-10 w-10 rounded-full object-cover;
}

.author-avatar-placeholder {
    @apply flex h-10 w-10 items-center justify-center rounded-full bg-gray-300;
    @apply text-sm font-medium text-gray-600;
}

.author-details {
    @apply space-y-1;
}

.author-name {
    @apply font-medium text-gray-900;
}

.post-meta {
    @apply flex items-center space-x-2 text-sm text-gray-500;
}

.post-type {
    @apply rounded bg-blue-100 px-2 py-1 text-xs text-blue-800;
}

.relevance-score {
    @apply space-y-1 text-right;
}

.score-bar {
    @apply h-2 w-16 overflow-hidden rounded-full bg-gray-200;
}

.score-fill {
    @apply h-full bg-green-500 transition-all duration-300;
}

.score-text {
    @apply text-xs text-gray-500;
}

.post-content {
    @apply space-y-3;
}

.post-text {
    @apply leading-relaxed text-gray-700;
}

.post-tags {
    @apply flex items-center space-x-2;
}

.tags-list {
    @apply flex flex-wrap gap-1;
}

.tag {
    @apply cursor-pointer text-sm text-blue-600 hover:text-blue-800;
}

.post-engagement {
    @apply flex items-center justify-between border-t border-gray-200 pt-3;
}

.engagement-stats {
    @apply flex items-center space-x-4;
}

.stat-item {
    @apply flex items-center space-x-1 text-sm text-gray-500;
}

.post-actions {
    @apply flex items-center space-x-2;
}

.action-btn {
    @apply flex items-center space-x-1 rounded-md px-3 py-1 text-sm;
    @apply transition-colors;
}

.action-btn.primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-btn.secondary {
    @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
    @apply disabled:cursor-not-allowed disabled:opacity-50;
}

.action-btn.secondary.liked {
    @apply border-red-300 bg-red-50 text-red-600;
}

/* Highlight styles */
:deep(mark) {
    @apply rounded bg-yellow-200 px-1 text-yellow-900;
}
</style>















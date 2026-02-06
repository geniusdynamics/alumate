<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[50] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click="closeModal"
    >
        <div class="mx-4 max-h-96 w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6" @click.stop>
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Share Post</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Original Post Preview -->
            <div class="mb-4 rounded-lg bg-gray-50 p-4">
                <div class="mb-2 flex items-center space-x-3">
                    <img :src="post.user.avatar_url || '/default-avatar.png'" :alt="post.user.name" class="h-8 w-8 rounded-full" />
                    <div>
                        <div class="text-sm font-medium">{{ post.user.name }}</div>
                        <div class="text-xs text-gray-500">@{{ post.user.username }}</div>
                    </div>
                </div>
                <div class="line-clamp-3 text-sm text-gray-700">
                    {{ post.content }}
                </div>
                <div v-if="post.media_urls && post.media_urls.length > 0" class="mt-2">
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-image mr-1"></i>
                        {{ post.media_urls.length }} {{ post.media_urls.length === 1 ? 'image' : 'images' }}
                    </div>
                </div>
            </div>

            <!-- Commentary Input -->
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700"> Add your thoughts (optional) </label>
                <textarea
                    v-model="commentary"
                    placeholder="What do you think about this post?"
                    class="w-full resize-none rounded-lg border border-gray-300 p-3 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    rows="3"
                    :maxlength="maxCommentaryLength"
                    :disabled="loading"
                ></textarea>
                <div class="mt-1 text-right text-xs text-gray-500">{{ commentary.length }}/{{ maxCommentaryLength }}</div>
            </div>

            <!-- Share Options -->
            <div class="mb-4">
                <label class="mb-2 block text-sm font-medium text-gray-700"> Share to </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input v-model="shareToTimeline" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="ml-2 text-sm">Your timeline</span>
                    </label>
                    <label class="flex items-center">
                        <input v-model="shareToCircles" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                        <span class="ml-2 text-sm">Your circles</span>
                    </label>
                </div>
            </div>

            <!-- External Share Options -->
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium text-gray-700"> Share externally </label>
                <div class="flex space-x-2">
                    <button
                        @click="shareToSocial('twitter')"
                        class="flex items-center space-x-2 rounded-lg bg-blue-400 px-3 py-2 text-sm text-white hover:bg-blue-500"
                    >
                        <i class="fab fa-twitter"></i>
                        <span>Twitter</span>
                    </button>
                    <button
                        @click="shareToSocial('linkedin')"
                        class="flex items-center space-x-2 rounded-lg bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700"
                    >
                        <i class="fab fa-linkedin"></i>
                        <span>LinkedIn</span>
                    </button>
                    <button
                        @click="copyLink"
                        class="flex items-center space-x-2 rounded-lg bg-gray-600 px-3 py-2 text-sm text-white hover:bg-gray-700"
                    >
                        <i class="fas fa-link"></i>
                        <span>{{ linkCopied ? 'Copied!' : 'Copy Link' }}</span>
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3">
                <button @click="closeModal" class="px-4 py-2 text-gray-600 hover:text-gray-800" :disabled="loading">Cancel</button>
                <button
                    @click="sharePost"
                    :disabled="!canShare || loading"
                    class="rounded-lg bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <i v-if="loading" class="fas fa-spinner fa-spin mr-1"></i>
                    Share
                </button>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mt-3 text-sm text-red-500">
                {{ error }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    post: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'shared']);

const commentary = ref('');
const shareToTimeline = ref(true);
const shareToCircles = ref(true);
const loading = ref(false);
const error = ref('');
const linkCopied = ref(false);
const maxCommentaryLength = 1000;

const canShare = computed(() => {
    return shareToTimeline.value || shareToCircles.value;
});

const closeModal = () => {
    if (!loading.value) {
        commentary.value = '';
        shareToTimeline.value = true;
        shareToCircles.value = true;
        error.value = '';
        linkCopied.value = false;
        emit('close');
    }
};

const sharePost = async () => {
    if (!canShare.value || loading.value) return;

    loading.value = true;
    error.value = '';

    try {
        const response = await fetch(`/api/posts/${props.post.id}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                commentary: commentary.value.trim() || null,
                share_to_timeline: shareToTimeline.value,
                share_to_circles: shareToCircles.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            emit('shared', {
                sharedPost: data.shared_post,
                stats: data.stats,
                userEngagement: data.user_engagement,
            });
            closeModal();
        } else {
            error.value = data.message || 'Failed to share post';
        }
    } catch (err) {
        error.value = 'Network error. Please try again.';
        console.error('Error sharing post:', err);
    } finally {
        loading.value = false;
    }
};

const shareToSocial = (platform) => {
    const postUrl = `${window.location.origin}/posts/${props.post.id}`;
    const text = commentary.value.trim() || props.post.content.substring(0, 100) + '...';

    let shareUrl = '';

    switch (platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(postUrl)}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(postUrl)}`;
            break;
    }

    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
};

const copyLink = async () => {
    try {
        const postUrl = `${window.location.origin}/posts/${props.post.id}`;
        await navigator.clipboard.writeText(postUrl);
        linkCopied.value = true;

        setTimeout(() => {
            linkCopied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy link:', err);
        error.value = 'Failed to copy link';
    }
};
</script>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

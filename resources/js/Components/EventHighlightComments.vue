<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="flex max-h-[80vh] w-full max-w-2xl flex-col rounded-lg bg-white">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900">Comments</h2>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Comments List -->
            <div class="flex-1 overflow-y-auto p-6">
                <div v-if="comments.length > 0" class="space-y-4">
                    <div v-for="comment in comments" :key="comment.id" class="flex space-x-3">
                        <!-- Avatar -->
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-300">
                            <span class="text-sm font-semibold text-gray-600">
                                {{ getInitials(comment.user.name) }}
                            </span>
                        </div>

                        <!-- Comment Content -->
                        <div class="flex-1">
                            <div class="mb-1 flex items-center space-x-2">
                                <span class="font-medium text-gray-900">{{ comment.user.name }}</span>
                                <span class="text-sm text-gray-500">{{ formatDate(comment.created_at) }}</span>
                            </div>
                            <p class="text-gray-700">{{ comment.content }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        ></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No comments yet. Be the first to comment!</p>
                </div>
            </div>

            <!-- Comment Form -->
            <div class="border-t border-gray-200 p-6">
                <form @submit.prevent="addComment" class="flex space-x-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-300">
                        <span class="text-sm font-semibold text-gray-600">You</span>
                    </div>
                    <div class="flex-1">
                        <textarea
                            v-model="newComment"
                            rows="2"
                            class="w-full resize-none rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Add a comment..."
                            @keydown.enter.prevent="addComment"
                        ></textarea>
                        <div class="mt-2 flex justify-end">
                            <button
                                type="submit"
                                :disabled="!newComment.trim() || loading"
                                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{ loading ? 'Posting...' : 'Comment' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';

interface Props {
    show: boolean;
    highlight: any;
}

interface Emits {
    (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const loading = ref(false);
const newComment = ref('');

const comments = computed(() => {
    if (!props.highlight?.interactions) return [];
    return props.highlight.interactions
        .filter((interaction) => interaction.type === 'comment')
        .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());
});

const addComment = async () => {
    if (!newComment.value.trim() || !props.highlight) return;

    loading.value = true;

    try {
        await axios.post(`/api/highlights/${props.highlight.id}/interact`, {
            type: 'comment',
            content: newComment.value.trim(),
        });

        // Add the comment to the local state (simplified)
        const newCommentObj = {
            id: Date.now(),
            type: 'comment',
            content: newComment.value.trim(),
            user: { name: 'You' }, // This would come from the current user
            created_at: new Date().toISOString(),
        };

        if (!props.highlight.interactions) {
            props.highlight.interactions = [];
        }
        props.highlight.interactions.push(newCommentObj);

        newComment.value = '';
    } catch (error) {
        console.error('Failed to add comment:', error);
        // TODO-toast: alert('Failed to add comment. Please try again.');
    } finally {
        loading.value = false;
    }
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase();
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Reset comment form when modal is closed
watch(
    () => props.show,
    (newShow) => {
        if (!newShow) {
            newComment.value = '';
        }
    },
);
</script>

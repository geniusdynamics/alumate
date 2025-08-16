<template>
  <div class="live-engagement-counters">
    <!-- Main Engagement Buttons -->
    <div class="flex items-center space-x-4">
      <!-- Like Button -->
      <button
        @click="handleLike"
        class="flex items-center space-x-2 px-3 py-1 rounded-full transition-all duration-200 hover:bg-gray-100"
        :class="{ 'text-red-500 bg-red-50': userHasLiked }"
        :disabled="isLoading"
      >
        <HeartIcon 
          :class="[
            'w-5 h-5 transition-all duration-200',
            userHasLiked ? 'fill-current text-red-500' : 'text-gray-500'
          ]"
        />
        <span class="text-sm font-medium">
          {{ formatCount(engagementCounts.likes) }}
        </span>
        
        <!-- Live update indicator -->
        <div
          v-if="recentLikes > 0"
          class="animate-bounce text-xs text-red-500 font-bold"
        >
          +{{ recentLikes }}
        </div>
      </button>

      <!-- Comment Button -->
      <button
        @click="handleComment"
        class="flex items-center space-x-2 px-3 py-1 rounded-full transition-all duration-200 hover:bg-gray-100"
        :disabled="isLoading"
      >
        <ChatBubbleLeftIcon class="w-5 h-5 text-gray-500" />
        <span class="text-sm font-medium">
          {{ formatCount(engagementCounts.comments) }}
        </span>
        
        <!-- Live update indicator -->
        <div
          v-if="recentComments > 0"
          class="animate-bounce text-xs text-blue-500 font-bold"
        >
          +{{ recentComments }}
        </div>
      </button>

      <!-- Share Button -->
      <button
        @click="handleShare"
        class="flex items-center space-x-2 px-3 py-1 rounded-full transition-all duration-200 hover:bg-gray-100"
        :disabled="isLoading"
      >
        <ShareIcon class="w-5 h-5 text-gray-500" />
        <span class="text-sm font-medium">
          {{ formatCount(engagementCounts.shares) }}
        </span>
        
        <!-- Live update indicator -->
        <div
          v-if="recentShares > 0"
          class="animate-bounce text-xs text-green-500 font-bold"
        >
          +{{ recentShares }}
        </div>
      </button>

      <!-- Reactions Button (if enabled) -->
      <div v-if="showReactions" class="relative">
        <button
          @click="toggleReactions"
          class="flex items-center space-x-2 px-3 py-1 rounded-full transition-all duration-200 hover:bg-gray-100"
          :disabled="isLoading"
        >
          <FaceSmileIcon class="w-5 h-5 text-gray-500" />
          <span class="text-sm font-medium">
            {{ formatCount(engagementCounts.reactions) }}
          </span>
        </button>

        <!-- Reactions Popup -->
        <div
          v-if="showReactionsPicker"
          class="absolute bottom-full left-0 mb-2 p-2 bg-white rounded-lg shadow-lg border z-10"
        >
          <div class="flex space-x-2">
            <button
              v-for="reaction in availableReactions"
              :key="reaction.type"
              @click="handleReaction(reaction.type)"
              class="p-2 rounded-full hover:bg-gray-100 transition-colors"
              :title="reaction.label"
            >
              <span class="text-lg">{{ reaction.emoji }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity Feed (if enabled) -->
    <div v-if="showRecentActivity && recentEngagements.length > 0" class="mt-3">
      <div class="text-xs text-gray-500 mb-2">Recent Activity:</div>
      <div class="space-y-1">
        <div
          v-for="engagement in recentEngagements.slice(0, 3)"
          :key="`${engagement.user.id}-${engagement.timestamp}`"
          class="flex items-center space-x-2 text-xs text-gray-600 animate-fade-in"
        >
          <img
            :src="engagement.user.avatar_url"
            :alt="engagement.user.name"
            class="w-4 h-4 rounded-full"
          />
          <span class="font-medium">{{ engagement.user.name }}</span>
          <span>{{ getEngagementText(engagement) }}</span>
          <span class="text-gray-400">
            {{ formatDistanceToNow(new Date(engagement.timestamp), { addSuffix: true }) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Live Users Indicator -->
    <div v-if="showLiveUsers && liveUsers.length > 0" class="mt-2">
      <div class="flex items-center space-x-2">
        <div class="flex -space-x-1">
          <img
            v-for="user in liveUsers.slice(0, 3)"
            :key="user.id"
            :src="user.avatar_url"
            :alt="user.name"
            class="w-6 h-6 rounded-full border-2 border-white"
            :title="`${user.name} is viewing this post`"
          />
        </div>
        <span class="text-xs text-gray-500">
          {{ liveUsers.length }} {{ liveUsers.length === 1 ? 'person' : 'people' }} viewing
        </span>
        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { 
  HeartIcon, 
  ChatBubbleLeftIcon, 
  ShareIcon, 
  FaceSmileIcon 
} from '@heroicons/vue/24/outline';
import { usePostRealTime } from '@/Composables/useRealTimeUpdates';
import { formatDistanceToNow } from 'date-fns';
import { router } from '@inertiajs/vue3';

// Props
interface Props {
  postId: number;
  initialCounts?: {
    likes?: number;
    comments?: number;
    shares?: number;
    reactions?: number;
  };
  userHasLiked?: boolean;
  showReactions?: boolean;
  showRecentActivity?: boolean;
  showLiveUsers?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  initialCounts: () => ({ likes: 0, comments: 0, shares: 0, reactions: 0 }),
  userHasLiked: false,
  showReactions: true,
  showRecentActivity: true,
  showLiveUsers: false,
});

// Emits
const emit = defineEmits<{
  like: [postId: number];
  comment: [postId: number];
  share: [postId: number];
  reaction: [postId: number, reactionType: string];
}>();

// Composables
const { engagementCounts, recentEngagements, startListening } = usePostRealTime(props.postId);

// State
const isLoading = ref(false);
const showReactionsPicker = ref(false);
const recentLikes = ref(0);
const recentComments = ref(0);
const recentShares = ref(0);
const liveUsers = ref([]);

// Available reactions
const availableReactions = [
  { type: 'like', emoji: '👍', label: 'Like' },
  { type: 'love', emoji: '❤️', label: 'Love' },
  { type: 'celebrate', emoji: '🎉', label: 'Celebrate' },
  { type: 'support', emoji: '💪', label: 'Support' },
  { type: 'insightful', emoji: '💡', label: 'Insightful' },
  { type: 'funny', emoji: '😄', label: 'Funny' },
];

// Computed
const userHasLiked = ref(props.userHasLiked);

// Methods
const formatCount = (count: number): string => {
  if (count === 0) return '0';
  if (count < 1000) return count.toString();
  if (count < 1000000) return `${(count / 1000).toFixed(1)}K`;
  return `${(count / 1000000).toFixed(1)}M`;
};

const handleLike = async () => {
  if (isLoading.value) return;
  
  isLoading.value = true;
  try {
    await router.post(`/api/posts/${props.postId}/like`, {}, {
      preserveState: true,
      preserveScroll: true,
    });
    
    userHasLiked.value = !userHasLiked.value;
    emit('like', props.postId);
  } catch (error) {
    console.error('Failed to like post:', error);
  } finally {
    isLoading.value = false;
  }
};

const handleComment = () => {
  emit('comment', props.postId);
};

const handleShare = async () => {
  if (isLoading.value) return;
  
  isLoading.value = true;
  try {
    await router.post(`/api/posts/${props.postId}/share`, {}, {
      preserveState: true,
      preserveScroll: true,
    });
    
    emit('share', props.postId);
  } catch (error) {
    console.error('Failed to share post:', error);
  } finally {
    isLoading.value = false;
  }
};

const handleReaction = async (reactionType: string) => {
  if (isLoading.value) return;
  
  isLoading.value = true;
  showReactionsPicker.value = false;
  
  try {
    await router.post(`/api/posts/${props.postId}/reaction`, {
      type: reactionType,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
    
    emit('reaction', props.postId, reactionType);
  } catch (error) {
    console.error('Failed to add reaction:', error);
  } finally {
    isLoading.value = false;
  }
};

const toggleReactions = () => {
  showReactionsPicker.value = !showReactionsPicker.value;
};

const getEngagementText = (engagement: any): string => {
  switch (engagement.type) {
    case 'like':
      return 'liked this';
    case 'comment':
      return 'commented';
    case 'share':
      return 'shared this';
    case 'reaction':
      const reaction = availableReactions.find(r => r.type === engagement.data);
      return `reacted with ${reaction?.emoji || '👍'}`;
    default:
      return 'engaged with this';
  }
};

// Track recent engagement increases for animations
let previousCounts = { ...props.initialCounts };

watch(engagementCounts, (newCounts) => {
  // Calculate increases
  const likesIncrease = newCounts.likes - previousCounts.likes;
  const commentsIncrease = newCounts.comments - previousCounts.comments;
  const sharesIncrease = newCounts.shares - previousCounts.shares;

  if (likesIncrease > 0) {
    recentLikes.value = likesIncrease;
    setTimeout(() => { recentLikes.value = 0; }, 3000);
  }

  if (commentsIncrease > 0) {
    recentComments.value = commentsIncrease;
    setTimeout(() => { recentComments.value = 0; }, 3000);
  }

  if (sharesIncrease > 0) {
    recentShares.value = sharesIncrease;
    setTimeout(() => { recentShares.value = 0; }, 3000);
  }

  previousCounts = { ...newCounts };
}, { deep: true });

// Close reactions picker when clicking outside
const handleClickOutside = (event: Event) => {
  if (showReactionsPicker.value && !(event.target as Element).closest('.relative')) {
    showReactionsPicker.value = false;
  }
};

// Lifecycle
onMounted(() => {
  // Initialize engagement counts
  engagementCounts.value = { ...props.initialCounts };
  previousCounts = { ...props.initialCounts };
  
  // Start listening for real-time updates
  const unsubscribe = startListening(props.initialCounts);
  
  // Add click outside listener
  document.addEventListener('click', handleClickOutside);
  
  // Cleanup function
  onUnmounted(() => {
    if (unsubscribe) unsubscribe();
    document.removeEventListener('click', handleClickOutside);
  });
});
</script>

<style scoped>
@keyframes fade-in {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

.live-engagement-counters {
  @apply select-none;
}

/* Hover effects for engagement buttons */
.live-engagement-counters button:hover {
  transform: translateY(-1px);
}

.live-engagement-counters button:active {
  transform: translateY(0);
}

/* Pulse animation for live indicators */
@keyframes pulse-soft {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.animate-pulse-soft {
  animation: pulse-soft 2s infinite;
}
</style>
<template>
    <div class="achievement-celebration">
        <div class="celebration-header">
            <div class="user-info">
                <img
                    :src="celebration.user_achievement.user.avatar_url || '/default-avatar.png'"
                    :alt="celebration.user_achievement.user.name"
                    class="user-avatar"
                />
                <div>
                    <h3 class="user-name">{{ celebration.user_achievement.user.name }}</h3>
                    <p class="celebration-time">{{ formatTime(celebration.created_at) }}</p>
                </div>
            </div>

            <div class="achievement-info">
                <AchievementBadge
                    :achievement="celebration.user_achievement.achievement"
                    :is-earned="true"
                    :earned-at="celebration.user_achievement.earned_at"
                    :show-actions="false"
                    @click="showAchievementDetails"
                />
            </div>
        </div>

        <div class="celebration-content">
            <div class="celebration-message">
                {{ celebration.message }}
            </div>

            <div class="celebration-actions">
                <button @click="toggleCongratulation" :class="['congratulate-btn', { congratulated: hasCongratulated }]" :disabled="loading">
                    <Icon name="heart" />
                    <span>{{ hasCongratulated ? 'Congratulated' : 'Congratulate' }}</span>
                    <span class="count">{{ celebration.congratulations_count }}</span>
                </button>

                <button @click="showCongratulations = !showCongratulations" class="view-congratulations-btn">
                    <Icon name="message-circle" />
                    <span>View Congratulations</span>
                </button>

                <button v-if="celebration.post_id" @click="viewPost" class="view-post-btn">
                    <Icon name="external-link" />
                    <span>View Post</span>
                </button>
            </div>
        </div>

        <!-- Congratulations Section -->
        <div v-if="showCongratulations" class="congratulations-section">
            <div class="congratulations-header">
                <h4>Congratulations ({{ celebration.congratulations_count }})</h4>

                <div v-if="!hasCongratulated" class="add-congratulation">
                    <textarea
                        v-model="congratulationMessage"
                        placeholder="Add a congratulatory message..."
                        class="congratulation-input"
                        rows="2"
                    ></textarea>
                    <button @click="addCongratulation" :disabled="loading" class="send-congratulation-btn">
                        <Icon name="send" />
                    </button>
                </div>
            </div>

            <div class="congratulations-list">
                <div v-for="congratulation in congratulations" :key="congratulation.id" class="congratulation-item">
                    <img
                        :src="congratulation.user.avatar_url || '/default-avatar.png'"
                        :alt="congratulation.user.name"
                        class="congratulation-avatar"
                    />
                    <div class="congratulation-content">
                        <div class="congratulation-header">
                            <span class="congratulation-user">{{ congratulation.user.name }}</span>
                            <span class="congratulation-time">{{ formatTime(congratulation.created_at) }}</span>
                        </div>
                        <p v-if="congratulation.message" class="congratulation-message">
                            {{ congratulation.message }}
                        </p>
                    </div>
                </div>

                <div v-if="congratulations.length === 0" class="no-congratulations">No congratulations yet. Be the first to congratulate!</div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AchievementBadge from './AchievementBadge.vue';
import Icon from './Icon.vue';

interface User {
    id: number;
    name: string;
    avatar_url?: string;
}

interface Achievement {
    id: number;
    name: string;
    description: string;
    icon?: string;
    category: string;
    category_icon: string;
    rarity: string;
    rarity_color: string;
    points: number;
}

interface UserAchievement {
    id: number;
    user: User;
    achievement: Achievement;
    earned_at: string;
}

interface Congratulation {
    id: number;
    user: User;
    message?: string;
    created_at: string;
}

interface Celebration {
    id: number;
    user_achievement: UserAchievement;
    message: string;
    congratulations_count: number;
    post_id?: number;
    created_at: string;
}

interface Props {
    celebration: Celebration;
    currentUser?: User;
}

const props = defineProps<Props>();

const showCongratulations = ref(false);
const congratulations = ref<Congratulation[]>([]);
const congratulationMessage = ref('');
const loading = ref(false);
const hasCongratulated = ref(false);

const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60);

    if (diffInHours < 1) {
        return 'Just now';
    } else if (diffInHours < 24) {
        return `${Math.floor(diffInHours)}h ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
        });
    }
};

const toggleCongratulation = async () => {
    if (loading.value) return;

    loading.value = true;

    try {
        if (hasCongratulated.value) {
            await axios.delete(`/api/achievement-celebrations/${props.celebration.id}/congratulations`);
            hasCongratulated.value = false;
        } else {
            await axios.post(`/api/achievement-celebrations/${props.celebration.id}/congratulations`, {
                message: congratulationMessage.value || null,
            });
            hasCongratulated.value = true;
            congratulationMessage.value = '';
        }

        // Refresh congratulations if showing
        if (showCongratulations.value) {
            await loadCongratulations();
        }
    } catch (error) {
        console.error('Error toggling congratulation:', error);
    } finally {
        loading.value = false;
    }
};

const addCongratulation = async () => {
    if (loading.value || !congratulationMessage.value.trim()) return;

    loading.value = true;

    try {
        await axios.post(`/api/achievement-celebrations/${props.celebration.id}/congratulations`, {
            message: congratulationMessage.value,
        });

        hasCongratulated.value = true;
        congratulationMessage.value = '';
        await loadCongratulations();
    } catch (error) {
        console.error('Error adding congratulation:', error);
    } finally {
        loading.value = false;
    }
};

const loadCongratulations = async () => {
    try {
        const response = await axios.get(`/api/achievement-celebrations/${props.celebration.id}/congratulations`);
        congratulations.value = response.data.data;

        // Check if current user has congratulated
        if (props.currentUser) {
            hasCongratulated.value = congratulations.value.some((c) => c.user.id === props.currentUser?.id);
        }
    } catch (error) {
        console.error('Error loading congratulations:', error);
    }
};

const showAchievementDetails = () => {
    // Navigate to achievement details page
    router.visit(`/achievements/${props.celebration.user_achievement.achievement.id}`);
};

const viewPost = () => {
    if (props.celebration.post_id) {
        router.visit(`/posts/${props.celebration.post_id}`);
    }
};

onMounted(() => {
    if (showCongratulations.value) {
        loadCongratulations();
    }
});
</script>

<style scoped>
.achievement-celebration {
    @apply mb-4 rounded-lg border border-gray-200 bg-white p-6 shadow-md;
}

.celebration-header {
    @apply mb-4 flex items-start justify-between;
}

.user-info {
    @apply flex items-center space-x-3;
}

.user-avatar {
    @apply h-12 w-12 rounded-full object-cover;
}

.user-name {
    @apply font-semibold text-gray-900;
}

.celebration-time {
    @apply text-sm text-gray-500;
}

.achievement-info {
    @apply max-w-md flex-1;
}

.celebration-content {
    @apply space-y-4;
}

.celebration-message {
    @apply leading-relaxed text-gray-700;
}

.celebration-actions {
    @apply flex items-center space-x-4;
}

.congratulate-btn {
    @apply flex items-center space-x-2 rounded-lg border border-gray-300 px-4 py-2 transition-colors hover:bg-gray-50;
}

.congratulate-btn.congratulated {
    @apply border-red-300 bg-red-50 text-red-700;
}

.congratulate-btn .count {
    @apply rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600;
}

.view-congratulations-btn,
.view-post-btn {
    @apply flex items-center space-x-2 rounded-lg px-4 py-2 text-gray-600 transition-colors hover:bg-gray-50;
}

.congratulations-section {
    @apply mt-6 border-t border-gray-200 pt-6;
}

.congratulations-header {
    @apply mb-4;
}

.congratulations-header h4 {
    @apply mb-3 font-semibold text-gray-900;
}

.add-congratulation {
    @apply flex space-x-2;
}

.congratulation-input {
    @apply flex-1 resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-blue-500;
}

.send-congratulation-btn {
    @apply rounded-lg bg-blue-600 px-3 py-2 text-white transition-colors hover:bg-blue-700;
}

.congratulations-list {
    @apply space-y-3;
}

.congratulation-item {
    @apply flex items-start space-x-3;
}

.congratulation-avatar {
    @apply h-8 w-8 flex-shrink-0 rounded-full object-cover;
}

.congratulation-content {
    @apply min-w-0 flex-1;
}

.congratulation-header {
    @apply mb-1 flex items-center space-x-2;
}

.congratulation-user {
    @apply text-sm font-medium text-gray-900;
}

.congratulation-time {
    @apply text-xs text-gray-500;
}

.congratulation-message {
    @apply text-sm text-gray-700;
}

.no-congratulations {
    @apply py-4 text-center text-gray-500;
}
</style>

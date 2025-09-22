<template>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md">
        <!-- Campaign Image -->
        <div class="relative h-48 bg-gradient-to-r from-blue-500 to-purple-600">
            <img
                v-if="campaign.media_urls && campaign.media_urls[0]"
                :src="campaign.media_urls[0]"
                :alt="campaign.title"
                class="h-full w-full object-cover"
            />
            <div class="absolute left-4 top-4">
                <span :class="statusClasses" class="rounded-full px-2 py-1 text-xs font-medium">
                    {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                </span>
            </div>
            <div class="absolute right-4 top-4">
                <span class="rounded-full bg-white/90 px-2 py-1 text-xs font-medium text-gray-800">
                    {{ campaign.type.charAt(0).toUpperCase() + campaign.type.slice(1) }}
                </span>
            </div>
        </div>

        <!-- Campaign Content -->
        <div class="p-6">
            <h3 class="mb-2 line-clamp-2 text-lg font-semibold text-gray-900">
                {{ campaign.title }}
            </h3>

            <p class="mb-4 line-clamp-3 text-sm text-gray-600">
                {{ campaign.description }}
            </p>

            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700"> ${{ formatAmount(campaign.raised_amount) }} raised </span>
                    <span class="text-sm text-gray-500"> {{ Math.round(campaign.progress_percentage) }}% </span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200">
                    <div
                        class="h-2 rounded-full bg-green-500 transition-all duration-300"
                        :style="{ width: `${Math.min(100, campaign.progress_percentage)}%` }"
                    ></div>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span class="text-xs text-gray-500"> Goal: ${{ formatAmount(campaign.goal_amount) }} </span>
                    <span class="text-xs text-gray-500"> {{ remainingDays }} days left </span>
                </div>
            </div>

            <!-- Campaign Stats -->
            <div class="mb-4 grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">{{ campaign.donations_count }}</div>
                    <div class="text-xs text-gray-500">Donors</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">{{ campaign.peer_fundraisers_count }}</div>
                    <div class="text-xs text-gray-500">Fundraisers</div>
                </div>
            </div>

            <!-- Creator Info -->
            <div class="mb-4 flex items-center">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-300">
                    <span class="text-xs font-medium text-gray-600">
                        {{ campaign.creator.name.charAt(0).toUpperCase() }}
                    </span>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ campaign.creator.name }}</p>
                    <p v-if="campaign.institution" class="text-xs text-gray-500">
                        {{ campaign.institution.name }}
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-2">
                <button
                    @click="$emit('view', campaign)"
                    class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                >
                    View Details
                </button>
                <button
                    v-if="canEdit"
                    @click="$emit('edit', campaign)"
                    class="rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200"
                >
                    Edit
                </button>
                <button
                    v-if="canDelete"
                    @click="$emit('delete', campaign)"
                    class="rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-700 transition-colors hover:bg-red-200"
                >
                    Delete
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Campaign {
    id: number;
    title: string;
    description: string;
    goal_amount: number;
    raised_amount: number;
    progress_percentage: number;
    status: string;
    type: string;
    start_date: string;
    end_date: string;
    creator: {
        id: number;
        name: string;
    };
    institution?: {
        id: number;
        name: string;
    };
    donations_count: number;
    peer_fundraisers_count: number;
    media_urls?: string[];
}

const props = defineProps<{
    campaign: Campaign;
    canEdit?: boolean;
    canDelete?: boolean;
}>();

defineEmits<{
    view: [campaign: Campaign];
    edit: [campaign: Campaign];
    delete: [campaign: Campaign];
}>();

const statusClasses = computed(() => {
    const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium';

    switch (props.campaign.status) {
        case 'active':
            return `${baseClasses} bg-green-100 text-green-800`;
        case 'draft':
            return `${baseClasses} bg-gray-100 text-gray-800`;
        case 'completed':
            return `${baseClasses} bg-blue-100 text-blue-800`;
        case 'paused':
            return `${baseClasses} bg-yellow-100 text-yellow-800`;
        case 'cancelled':
            return `${baseClasses} bg-red-100 text-red-800`;
        default:
            return `${baseClasses} bg-gray-100 text-gray-800`;
    }
});

const remainingDays = computed(() => {
    const endDate = new Date(props.campaign.end_date);
    const today = new Date();
    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return Math.max(0, diffDays);
});

function formatAmount(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}
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

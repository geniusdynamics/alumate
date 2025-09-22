<template>
    <AppLayout :title="campaign.title">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ campaign.title }}
                </h2>
                <div class="flex space-x-2">
                    <button v-if="canEdit" @click="editCampaign" class="rounded-md bg-gray-100 px-4 py-2 font-medium text-gray-700 hover:bg-gray-200">
                        Edit Campaign
                    </button>
                    <button
                        v-if="campaign.allow_peer_fundraising"
                        @click="createPeerFundraiser"
                        class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
                    >
                        Start Fundraising
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="space-y-8 lg:col-span-2">
                        <!-- Campaign Header -->
                        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                            <div v-if="campaign.media_urls && campaign.media_urls[0]" class="h-64 bg-gray-200">
                                <img :src="campaign.media_urls[0]" :alt="campaign.title" class="h-full w-full object-cover" />
                            </div>

                            <div class="p-6">
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <span :class="statusClasses" class="rounded-full px-3 py-1 text-sm font-medium">
                                            {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            {{ campaign.type.charAt(0).toUpperCase() + campaign.type.slice(1) }} Campaign
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Created by {{ campaign.creator.name }}
                                        <span v-if="campaign.institution"> • {{ campaign.institution.name }} </span>
                                    </div>
                                </div>

                                <h1 class="mb-4 text-3xl font-bold text-gray-900">{{ campaign.title }}</h1>
                                <p class="mb-6 text-lg text-gray-700">{{ campaign.description }}</p>

                                <div v-if="campaign.story" class="prose max-w-none">
                                    <div class="whitespace-pre-wrap">{{ campaign.story }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Campaign Updates -->
                        <div v-if="campaign.updates && campaign.updates.length > 0" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Campaign Updates</h3>
                            <div class="space-y-6">
                                <div v-for="update in campaign.updates" :key="update.id" class="border-l-4 border-blue-500 pl-4">
                                    <div class="mb-2 flex items-start justify-between">
                                        <h4 class="font-medium text-gray-900">{{ update.title }}</h4>
                                        <span class="text-sm text-gray-500">
                                            {{ formatDate(update.published_at) }}
                                        </span>
                                    </div>
                                    <p class="text-gray-700">{{ update.content }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Peer Fundraisers -->
                        <div
                            v-if="campaign.allow_peer_fundraising && campaign.peer_fundraisers && campaign.peer_fundraisers.length > 0"
                            class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Peer Fundraisers</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <PeerFundraiserCard
                                    v-for="fundraiser in campaign.peer_fundraisers.slice(0, 4)"
                                    :key="fundraiser.id"
                                    :fundraiser="fundraiser"
                                />
                            </div>
                            <div v-if="campaign.peer_fundraisers.length > 4" class="mt-4 text-center">
                                <button class="font-medium text-blue-600 hover:text-blue-700">
                                    View All Fundraisers ({{ campaign.peer_fundraisers.length }})
                                </button>
                            </div>
                        </div>

                        <!-- Analytics (for campaign creators) -->
                        <div v-if="canViewAnalytics">
                            <CampaignAnalytics :campaign="campaign" />
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Progress Widget -->
                        <CampaignProgress :campaign="campaign" />

                        <!-- Donation Form -->
                        <DonationForm :campaign="campaign" :is-authenticated="!!$page.props.auth.user" @donated="onDonated" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import CampaignAnalytics from '@/Components/Fundraising/CampaignAnalytics.vue';
import CampaignProgress from '@/Components/Fundraising/CampaignProgress.vue';
import DonationForm from '@/Components/Fundraising/DonationForm.vue';
import PeerFundraiserCard from '@/Components/Fundraising/PeerFundraiserCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Campaign {
    id: number;
    title: string;
    description: string;
    story?: string;
    goal_amount: number;
    raised_amount: number;
    progress_percentage: number;
    status: string;
    type: string;
    allow_peer_fundraising: boolean;
    created_by: number;
    creator: {
        id: number;
        name: string;
    };
    institution?: {
        id: number;
        name: string;
    };
    media_urls?: string[];
    updates?: any[];
    peer_fundraisers?: any[];
}

const props = defineProps<{
    campaign: Campaign;
}>();

const page = usePage();

const canEdit = computed(() => {
    const user = page.props.auth?.user;
    return user && (user.id === props.campaign.created_by || user.roles?.includes('admin') || user.roles?.includes('institution_admin'));
});

const canViewAnalytics = computed(() => {
    return canEdit.value;
});

const statusClasses = computed(() => {
    const baseClasses = 'px-3 py-1 rounded-full text-sm font-medium';

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

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function editCampaign() {
    // Navigate to edit page or open modal
    window.location.href = `/campaigns/${props.campaign.id}/edit`;
}

function createPeerFundraiser() {
    // Navigate to peer fundraiser creation or open modal
    window.location.href = `/campaigns/${props.campaign.id}/peer-fundraisers/create`;
}

function onDonated(donation: any) {
    // Refresh the page or update the campaign data
    window.location.reload();
}
</script>















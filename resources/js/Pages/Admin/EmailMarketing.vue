<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Email Marketing</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Create and manage email campaigns for alumni engagement</p>
                    </div>
                    <div class="flex space-x-3">
                        <button
                            @click="showTemplateModal = true"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            Templates
                        </button>
                        <button
                            @click="showAutomationModal = true"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Automation
                        </button>
                        <button
                            @click="showCampaignModal = true"
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            New Campaign
                        </button>
                    </div>
                </div>
            </div>

            <!-- Analytics Overview -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Campaigns</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ analytics.total_campaigns || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sent Campaigns</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ analytics.total_sent || 0 }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900">
                                <svg class="h-4 w-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Open Rate</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ (analytics.average_open_rate || 0).toFixed(1) }}%</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900">
                                <svg class="h-4 w-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Click Rate</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ (analytics.average_click_rate || 0).toFixed(1) }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                        <div class="max-w-lg flex-1">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search campaigns..."
                                    class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 leading-5 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                                />
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <select
                                v-model="selectedType"
                                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-base text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">All Types</option>
                                <option value="newsletter">Newsletter</option>
                                <option value="announcement">Announcement</option>
                                <option value="event">Event</option>
                                <option value="fundraising">Fundraising</option>
                                <option value="engagement">Engagement</option>
                            </select>
                            <select
                                v-model="selectedStatus"
                                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-base text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="sending">Sending</option>
                                <option value="sent">Sent</option>
                                <option value="paused">Paused</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaigns List -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Campaigns</h3>
                </div>
                <div class="overflow-hidden">
                    <div v-if="loading" class="p-8 text-center">
                        <div class="inline-flex items-center">
                            <svg class="-ml-1 mr-3 h-5 w-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            Loading campaigns...
                        </div>
                    </div>
                    <div v-else-if="filteredCampaigns.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">No campaigns found</div>
                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div
                            v-for="campaign in filteredCampaigns"
                            :key="campaign.id"
                            class="p-6 transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ campaign.name }}
                                        </h4>
                                        <span
                                            :class="getStatusBadgeClass(campaign.status)"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        >
                                            {{ campaign.status }}
                                        </span>
                                        <span
                                            :class="getTypeBadgeClass(campaign.type)"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        >
                                            {{ campaign.type }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ campaign.subject }}
                                    </p>
                                    <div class="mt-2 flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ campaign.total_recipients }} recipients</span>
                                        <span v-if="campaign.open_rate">{{ campaign.open_rate }}% open rate</span>
                                        <span v-if="campaign.click_rate">{{ campaign.click_rate }}% click rate</span>
                                        <span>{{ formatDate(campaign.created_at) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="viewCampaign(campaign)"
                                        class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        View
                                    </button>
                                    <button
                                        v-if="campaign.status === 'draft'"
                                        @click="editCampaign(campaign)"
                                        class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="campaign.status === 'draft'"
                                        @click="sendCampaign(campaign)"
                                        class="inline-flex items-center rounded border border-transparent bg-blue-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-blue-700"
                                    >
                                        Send
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Builder Modal -->
            <CampaignBuilder v-if="showCampaignModal" :campaign="selectedCampaign" @close="closeCampaignModal" @saved="handleCampaignSaved" />
        </div>
    </div>
</template>

<script setup>
import CampaignBuilder from '@/components/EmailMarketing/CampaignBuilder.vue';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

// Reactive data
const campaigns = ref([]);
const analytics = ref({});
const loading = ref(true);
const searchQuery = ref('');
const selectedType = ref('');
const selectedStatus = ref('');
const showCampaignModal = ref(false);
const showTemplateModal = ref(false);
const showAutomationModal = ref(false);
const selectedCampaign = ref(null);

// Computed properties
const filteredCampaigns = computed(() => {
    let filtered = campaigns.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter((campaign) => campaign.name.toLowerCase().includes(query) || campaign.subject.toLowerCase().includes(query));
    }

    if (selectedType.value) {
        filtered = filtered.filter((campaign) => campaign.type === selectedType.value);
    }

    if (selectedStatus.value) {
        filtered = filtered.filter((campaign) => campaign.status === selectedStatus.value);
    }

    return filtered;
});

// Methods
const loadCampaigns = async () => {
    try {
        loading.value = true;
        const response = await fetch('/api/email-campaigns');
        const data = await response.json();
        campaigns.value = data.campaigns.data || [];
    } catch (error) {
        console.error('Failed to load campaigns:', error);
    } finally {
        loading.value = false;
    }
};

const loadAnalytics = async () => {
    try {
        const response = await fetch('/api/email-campaigns/analytics');
        const data = await response.json();
        analytics.value = data.analytics || {};
    } catch (error) {
        console.error('Failed to load analytics:', error);
    }
};

const viewCampaign = (campaign) => {
    router.visit(`/admin/email-marketing/campaigns/${campaign.id}`);
};

const editCampaign = (campaign) => {
    selectedCampaign.value = campaign;
    showCampaignModal.value = true;
};

const sendCampaign = async (campaign) => {
    if (!confirm('Are you sure you want to send this campaign?')) {
        return;
    }

    try {
        const response = await fetch(`/api/email-campaigns/${campaign.id}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (response.ok) {
            await loadCampaigns();
            alert('Campaign sent successfully!');
        } else {
            const error = await response.json();
            alert('Failed to send campaign: ' + error.message);
        }
    } catch (error) {
        console.error('Failed to send campaign:', error);
        alert('Failed to send campaign');
    }
};

const closeCampaignModal = () => {
    showCampaignModal.value = false;
    selectedCampaign.value = null;
};

const handleCampaignSaved = () => {
    closeCampaignModal();
    loadCampaigns();
};

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        sending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        sent: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        paused: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    };
    return classes[status] || classes.draft;
};

const getTypeBadgeClass = (type) => {
    const classes = {
        newsletter: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        announcement: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        event: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        fundraising: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        engagement: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    };
    return classes[type] || classes.newsletter;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Lifecycle
onMounted(() => {
    loadCampaigns();
    loadAnalytics();
});
</script>












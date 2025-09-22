<template>
    <div class="campaign-list">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Fundraising Campaigns</h2>
            <button
                v-if="canCreateCampaign"
                @click="showCreateModal = true"
                class="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
            >
                Create Campaign
            </button>
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select v-model="filters.status" class="w-full rounded-md border border-gray-300 px-3 py-2">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="completed">Completed</option>
                        <option value="paused">Paused</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                    <select v-model="filters.type" class="w-full rounded-md border border-gray-300 px-3 py-2">
                        <option value="">All Types</option>
                        <option value="general">General</option>
                        <option value="scholarship">Scholarship</option>
                        <option value="emergency">Emergency</option>
                        <option value="project">Project</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Search</label>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search campaigns..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                    />
                </div>
                <div class="flex items-end">
                    <button @click="resetFilters" class="px-3 py-2 text-gray-600 hover:text-gray-800">Reset Filters</button>
                </div>
            </div>
        </div>

        <!-- Campaign Grid -->
        <div v-if="loading" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="n in 6" :key="n" class="animate-pulse">
                <div class="h-64 rounded-lg bg-gray-200"></div>
            </div>
        </div>

        <div v-else-if="campaigns.length === 0" class="py-12 text-center">
            <div class="text-lg text-gray-500">No campaigns found</div>
            <p class="mt-2 text-gray-400">Try adjusting your filters or create a new campaign</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <CampaignCard
                v-for="campaign in campaigns"
                :key="campaign.id"
                :campaign="campaign"
                @view="viewCampaign"
                @edit="editCampaign"
                @delete="deleteCampaign"
            />
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center">
            <nav class="flex items-center space-x-2">
                <button
                    v-for="page in visiblePages"
                    :key="page"
                    @click="loadPage(page)"
                    :class="[
                        'rounded-md px-3 py-2 text-sm font-medium',
                        page === pagination.current_page ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100',
                    ]"
                >
                    {{ page }}
                </button>
            </nav>
        </div>

        <!-- Create Campaign Modal -->
        <CreateCampaignModal v-if="showCreateModal" @close="showCreateModal = false" @created="onCampaignCreated" />
    </div>
</template>

<script setup lang="ts">
import { debounce } from 'lodash';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import CampaignCard from './CampaignCard.vue';
import CreateCampaignModal from './CreateCampaignModal.vue';

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
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    canCreateCampaign?: boolean;
}>();

const campaigns = ref<Campaign[]>([]);
const loading = ref(false);
const showCreateModal = ref(false);

const filters = reactive({
    status: '',
    type: '',
    search: '',
});

const pagination = ref<Pagination>({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
});

const visiblePages = computed(() => {
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const pages = [];

    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

const debouncedLoadCampaigns = debounce(loadCampaigns, 300);

watch(
    filters,
    () => {
        pagination.value.current_page = 1;
        debouncedLoadCampaigns();
    },
    { deep: true },
);

onMounted(() => {
    loadCampaigns();
});

async function loadCampaigns() {
    loading.value = true;

    try {
        const params = new URLSearchParams({
            page: pagination.value.current_page.toString(),
            per_page: pagination.value.per_page.toString(),
        });

        Object.entries(filters).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });

        const response = await fetch(`/api/fundraising-campaigns?${params}`);
        const data = await response.json();

        campaigns.value = data.data;
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
            total: data.total,
        };
    } catch (error) {
        console.error('Failed to load campaigns:', error);
    } finally {
        loading.value = false;
    }
}

function loadPage(page: number) {
    pagination.value.current_page = page;
    loadCampaigns();
}

function resetFilters() {
    Object.keys(filters).forEach((key) => {
        filters[key] = '';
    });
}

function viewCampaign(campaign: Campaign) {
    window.location.href = `/campaigns/${campaign.id}`;
}

function editCampaign(campaign: Campaign) {
    window.location.href = `/campaigns/${campaign.id}/edit`;
}

async function deleteCampaign(campaign: Campaign) {
    if (!confirm('Are you sure you want to delete this campaign?')) {
        return;
    }

    try {
        await fetch(`/api/fundraising-campaigns/${campaign.id}`, {
            method: 'DELETE',
        });

        loadCampaigns();
    } catch (error) {
        console.error('Failed to delete campaign:', error);
    }
}

function onCampaignCreated() {
    showCreateModal.value = false;
    loadCampaigns();
}
</script>

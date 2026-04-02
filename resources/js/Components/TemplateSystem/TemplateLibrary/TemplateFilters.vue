<script setup lang="ts">
import { ref } from 'vue';
import type { TemplateFilters, AudienceType, CampaignType, TemplateCategory } from '@/Types';

const emit = defineEmits<{
    change: [filters: TemplateFilters];
}>();

const category = ref<TemplateCategory | undefined>(undefined);
const audienceType = ref<AudienceType | undefined>(undefined);
const campaignType = ref<CampaignType | undefined>(undefined);
const sortBy = ref('name');
const sortDirection = ref<'asc' | 'desc'>('asc');

function applyFilters() {
    emit('change', {
        category: category.value,
        audience_type: audienceType.value,
        campaign_type: campaignType.value,
        sort_by: sortBy.value,
        sort_direction: sortDirection.value,
    });
}

function resetFilters() {
    category.value = undefined;
    audienceType.value = undefined;
    campaignType.value = undefined;
    sortBy.value = 'name';
    sortDirection.value = 'asc';
    applyFilters();
}
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Category -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select
                    v-model="category"
                    @change="applyFilters"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Categories</option>
                    <option value="individual">Individual</option>
                    <option value="institution">Institution</option>
                    <option value="employer">Employer</option>
                </select>
            </div>

            <!-- Audience Type -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Audience</label>
                <select
                    v-model="audienceType"
                    @change="applyFilters"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Audiences</option>
                    <option value="individual">Individual</option>
                    <option value="institution">Institution</option>
                    <option value="employer">Employer</option>
                </select>
            </div>

            <!-- Campaign Type -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Campaign</label>
                <select
                    v-model="campaignType"
                    @change="applyFilters"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">All Campaigns</option>
                    <option value="onboarding">Onboarding</option>
                    <option value="event_promotion">Event Promotion</option>
                    <option value="networking">Networking</option>
                    <option value="career_services">Career Services</option>
                    <option value="recruiting">Recruiting</option>
                    <option value="donation">Donation</option>
                    <option value="leadership">Leadership</option>
                    <option value="marketing">Marketing</option>
                </select>
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort By</label>
                <select
                    v-model="sortBy"
                    @change="applyFilters"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="name">Name</option>
                    <option value="usage_count">Most Used</option>
                    <option value="created_at">Newest</option>
                </select>
            </div>

            <!-- Sort Direction & Reset -->
            <div class="flex items-end gap-2">
                <button
                    @click="sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'; applyFilters()"
                    class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    {{ sortDirection === 'asc' ? '↑ Asc' : '↓ Desc' }}
                </button>
                <button
                    @click="resetFilters"
                    class="px-3 py-1.5 text-xs font-medium rounded-md text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>

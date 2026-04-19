<template>
    <AppLayout title="Fundraising Campaigns">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Fundraising Campaigns</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <CampaignList :can-create-campaign="canCreateCampaign" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import CampaignList from '@/Components/Fundraising/CampaignList.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const canCreateCampaign = computed(() => {
    const user = page.props.auth?.user;
    return user && (user.roles?.includes('admin') || user.roles?.includes('institution_admin') || user.roles?.includes('alumni'));
});
</script>















<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    jobs: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const location = ref(props.filters.location);

watch([search, location], ([value, value2]) => {
    Inertia.get(route('jobs.public.index'), { search: value, location: value2 }, { preserveState: true, replace: true });
});

const form = useForm({
    recommended_id: '',
});

const recommend = (jobId) => {
    form.post(route('jobs.recommend', jobId));
};
</script>

<template>
    <Head title="Jobs" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Jobs
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-4 flex justify-between">
                    <input type="text" v-model="search" placeholder="Search..." class="border px-2 py-1 rounded">
                    <input type="text" v-model="location" placeholder="Location..." class="border px-2 py-1 rounded">
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-for="job in jobs.data" :key="job.id" class="mb-4 p-4 border rounded">
                            <h3 class="text-lg font-semibold">{{ job.title }}</h3>
                            <p class="text-sm text-gray-600">{{ job.employer.company_name }} - {{ job.location }}</p>
                            <p>{{ job.description }}</p>
                            <button @click="apply(job.id)" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Apply
                            </button>
                            <div class="mt-2">
                                <input type="text" v-model="form.recommended_id" placeholder="Enter user ID to recommend" />
                                <button @click="recommend(job.id)" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Recommend
                                </button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a v-for="link in jobs.links" :key="link.label" :href="link.url" v-html="link.label" class="px-2 py-1 border" :class="{ 'bg-gray-200': link.active }"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    job: Object,
    applications: Array,
});

const form = useForm({});

const markAsHired = (applicationId) => {
    form.post(route('applications.hire', applicationId));
};
</script>

<template>
    <Head :title="`Applications for ${job.title}`" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Applications for {{ job.title }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <div v-for="application in applications" :key="application.id" class="mb-4 rounded border p-4">
                            <h3 class="text-lg font-semibold">{{ application.graduate.name }}</h3>
                            <p>{{ application.cover_letter }}</p>
                            <button
                                @click="markAsHired(application.id)"
                                class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700"
                            >
                                Mark as Hired
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














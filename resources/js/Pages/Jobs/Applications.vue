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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Applications for {{ job.title }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-for="application in applications" :key="application.id" class="mb-4 p-4 border rounded">
                            <h3 class="text-lg font-semibold">{{ application.graduate.name }}</h3>
                            <p>{{ application.cover_letter }}</p>
                            <button @click="markAsHired(application.id)" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Mark as Hired
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

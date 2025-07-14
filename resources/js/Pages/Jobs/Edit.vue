<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import UpdateJobForm from '@/Pages/Jobs/Partials/UpdateJobForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    job: Object,
});
</script>

<template>
    <Head title="Edit Job" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Job
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <UpdateJobForm :job="job" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

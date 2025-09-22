<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateJobForm from '@/Pages/Jobs/Partials/CreateJobForm.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Create Job" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Create Job</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <CreateJobForm />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

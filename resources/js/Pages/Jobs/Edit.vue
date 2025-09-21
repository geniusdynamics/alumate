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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Job</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <UpdateJobForm :job="job" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

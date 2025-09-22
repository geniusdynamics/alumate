<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProfileCompletionProgress from '@/Pages/Graduates/Partials/ProfileCompletionProgress.vue';
import UpdateGraduateForm from '@/Pages/Graduates/Partials/UpdateGraduateForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    graduate: Object,
    courses: Array,
});
</script>

<template>
    <Head :title="`Edit ${graduate.name} - Graduate Profile`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Graduate: {{ graduate.name }}</h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('graduates.show', graduate.id)"
                        class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700"
                    >
                        View Profile
                    </Link>
                    <Link :href="route('graduates.index')" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                <!-- Profile Completion Status -->
                <ProfileCompletionProgress :graduate="graduate" />

                <!-- Edit Form -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <UpdateGraduateForm :graduate="graduate" :courses="courses" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

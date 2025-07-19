<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import UpdateGraduateForm from '@/Pages/Graduates/Partials/UpdateGraduateForm.vue';
import ProfileCompletionProgress from '@/Pages/Graduates/Partials/ProfileCompletionProgress.vue';
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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Graduate: {{ graduate.name }}
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('graduates.show', graduate.id)" 
                          class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                        View Profile
                    </Link>
                    <Link :href="route('graduates.index')" 
                          class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Profile Completion Status -->
                <ProfileCompletionProgress :graduate="graduate" />

                <!-- Edit Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <UpdateGraduateForm :graduate="graduate" :courses="courses" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
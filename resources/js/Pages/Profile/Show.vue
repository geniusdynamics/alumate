<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    graduate: Object,
    profile: Object,
    institution: Object,
    hired: Boolean,
});
</script>

<template>
    <Head title="My Profile" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <p>Institution: {{ institution.name }}</p>
                        <p v-if="graduate.previous_institution">
                            Previous Institution: {{ graduate.previous_institution.name }}
                        </p>
                        <span v-if="hired" class="bg-green-500 text-white font-bold py-1 px-2 rounded-full text-xs">
                            Hired
                        </span>
                        <div v-if="profile.project_gallery">
                            <h3 class="text-lg font-semibold mt-4">Project Gallery</h3>
                            <div class="grid grid-cols-3 gap-4 mt-2">
                                <div v-for="project in profile.project_gallery" :key="project.title">
                                    <img :src="project.image_url" :alt="project.title" class="w-full h-auto rounded">
                                    <p class="text-sm font-semibold mt-1">{{ project.title }}</p>
                                </div>
                            </div>
                        </div>
                        <!-- Display other profile information here -->
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

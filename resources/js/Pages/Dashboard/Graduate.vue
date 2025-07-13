<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    classmates: Array,
    jobs: Array,
    announcements: Array,
});
</script>

<template>
    <Head title="Graduate Dashboard" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Graduate Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold">Announcements</h3>
                        <div v-for="announcement in announcements" :key="announcement.id" class="mt-2 p-4 bg-white border rounded">
                            <h4 class="font-semibold">{{ announcement.title }}</h4>
                            <p>{{ announcement.content }}</p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Classmates</h3>
                        <ul>
                            <li v-for="classmate in classmates" :key="classmate.id">
                                {{ classmate.name }}
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mt-8">
                    <h3 class="text-lg font-semibold">Relevant Jobs</h3>
                    <div v-for="job in jobs" :key="job.id" class="mt-2 p-4 bg-white border rounded">
                        <h4 class="font-semibold">{{ job.title }}</h4>
                        <p>{{ job.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

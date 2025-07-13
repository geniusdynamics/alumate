<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    graduate: Object,
    profile: Object,
    institution: Object,
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
                        <!-- Display other profile information here -->
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

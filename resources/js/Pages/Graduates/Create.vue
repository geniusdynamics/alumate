<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import CreateGraduateForm from '@/Pages/Graduates/Partials/CreateGraduateForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    courses: Array,
});
</script>

<template>
    <Head title="Create Graduate" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create New Graduate
                </h2>
                <Link :href="route('graduates.index')" 
                      class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                    Back to Graduates
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <CreateGraduateForm :courses="courses" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
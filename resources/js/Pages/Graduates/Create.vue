<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
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
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Create New Graduate</h2>
                <Link :href="route('graduates.index')" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                    Back to Graduates
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <CreateGraduateForm :courses="courses" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


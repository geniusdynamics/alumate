<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import UpdateGraduateForm from '@/Pages/Graduates/Partials/UpdateGraduateForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    graduate: Object,
    courses: Array,
});
</script>

<template>
    <Head title="Edit Graduate" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Graduate
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <UpdateGraduateForm :graduate="graduate" :courses="courses" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

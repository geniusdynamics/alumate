<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import UpdateProfileForm from '@/Pages/Profile/Partials/UpdateProfileForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    graduate: Object,
    profile: Object,
});
</script>

<template>
    <Head title="Edit Profile" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Profile</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <UpdateProfileForm :graduate="graduate" :profile="profile" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














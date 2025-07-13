<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    phone: '',
});

const submit = () => {
    form.post(route('tutors.store'));
};
</script>

<template>
    <Head title="Create Tutor" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Tutor
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="name">Name</label>
                                <input id="name" type="text" v-model="form.name" required autofocus />
                            </div>
                            <div class="mt-4">
                                <label for="email">Email</label>
                                <input id="email" type="email" v-model="form.email" required />
                            </div>
                            <div class="mt-4">
                                <label for="phone">Phone</label>
                                <input id="phone" type="text" v-model="form.phone" />
                            </div>
                            <div class="flex items-center justify-end mt-4">
                                <button type="submit">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

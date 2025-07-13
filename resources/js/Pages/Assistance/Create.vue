<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    subject: '',
    message: '',
});

const submit = () => {
    form.post(route('assistance.store'));
};
</script>

<template>
    <Head title="Request Assistance" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Request Assistance
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="subject">Subject</label>
                                <input id="subject" type="text" v-model="form.subject" required autofocus />
                            </div>
                            <div class="mt-4">
                                <label for="message">Message</label>
                                <textarea id="message" v-model="form.message" required></textarea>
                            </div>
                            <div class="flex items-center justify-end mt-4">
                                <button type="submit">Send</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

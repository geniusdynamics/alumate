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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Request Assistance</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="subject">Subject</label>
                                <input id="subject" type="text" v-model="form.subject" required autofocus />
                            </div>
                            <div class="mt-4">
                                <label for="message">Message</label>
                                <textarea id="message" v-model="form.message" required></textarea>
                            </div>
                            <div class="mt-4 flex items-center justify-end">
                                <button type="submit">Send</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    content: '',
    message_type: 'text',
    file: null,
});

const submit = () => {
    form.post(route('announcements.store'));
};
</script>

<template>
    <Head title="Create Announcement" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Create Announcement</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="title">Title</label>
                                <input id="title" type="text" v-model="form.title" required autofocus />
                            </div>
                            <div class="mt-4">
                                <label for="message_type">Message Type</label>
                                <select id="message_type" v-model="form.message_type">
                                    <option value="text">Text</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <div v-if="form.message_type === 'text'" class="mt-4">
                                <label for="content">Content</label>
                                <textarea id="content" v-model="form.content" required></textarea>
                            </div>
                            <div v-else class="mt-4">
                                <label for="file">PDF File</label>
                                <input type="file" @input="form.file = $event.target.files[0]" />
                            </div>
                            <div class="mt-4 flex items-center justify-end">
                                <button type="submit">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














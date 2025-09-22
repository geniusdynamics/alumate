<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    file: null,
});

const submit = () => {
    form.post(route('courses.import.store'));
};
</script>

<template>
    <Head title="Import Courses" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Import Courses</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="file">Excel File</label>
                                <input type="file" @input="form.file = $event.target.files[0]" />
                            </div>
                            <button type="submit" :disabled="form.processing">Import</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

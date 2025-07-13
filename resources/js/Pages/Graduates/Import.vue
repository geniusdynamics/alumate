<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    file: null,
});

const submit = () => {
    form.post(route('graduates.import.store'));
};
</script>

<template>
    <Head title="Import Graduates" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Import Graduates
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
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

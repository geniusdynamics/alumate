<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    institution_name: '',
    degree: '',
    field_of_study: '',
    start_year: '',
    end_year: '',
});

const submit = () => {
    form.post(route('education.store'));
};
</script>

<template>
    <Head title="Add Education History" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Add Education History
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="institution_name">Institution Name</label>
                                <input id="institution_name" type="text" v-model="form.institution_name" required autofocus />
                            </div>
                            <div class="mt-4">
                                <label for="degree">Degree</label>
                                <input id="degree" type="text" v-model="form.degree" required />
                            </div>
                            <div class="mt-4">
                                <label for="field_of_study">Field of Study</label>
                                <input id="field_of_study" type="text" v-model="form.field_of_study" required />
                            </div>
                            <div class="mt-4">
                                <label for="start_year">Start Year</label>
                                <input id="start_year" type="number" v-model="form.start_year" required />
                            </div>
                            <div class="mt-4">
                                <label for="end_year">End Year</label>
                                <input id="end_year" type="number" v-model="form.end_year" required />
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

<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    description: '',
    location: '',
    salary: '',
});

const submit = () => {
    form.post(route('jobs.store'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Create Job</h2>

            <p class="mt-1 text-sm text-gray-600">
                Create a new job posting.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="title">Title</label>
                <input id="title" type="text" v-model="form.title" required autofocus />
            </div>
            <div>
                <label for="description">Description</label>
                <textarea id="description" v-model="form.description" required></textarea>
            </div>
            <div>
                <label for="location">Location</label>
                <input id="location" type="text" v-model="form.location" />
            </div>
            <div>
                <label for="salary">Salary</label>
                <input id="salary" type="text" v-model="form.salary" />
            </div>

            <div class="flex items-center gap-4">
                <button type="submit">Save</button>
            </div>
        </form>
    </section>
</template>

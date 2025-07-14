<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    job: Object,
});

const form = useForm({
    title: props.job.title,
    description: props.job.description,
    location: props.job.location,
    salary: props.job.salary,
});

const submit = () => {
    form.patch(route('jobs.update', props.job.id));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Job</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update the job posting.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="title">Title</label>
                <input id="title" type="text" v-model="form.title" required />
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

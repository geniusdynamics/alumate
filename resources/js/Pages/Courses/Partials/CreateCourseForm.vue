<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
});

const submit = () => {
    form.post(route('courses.store'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Create Course</h2>

            <p class="mt-1 text-sm text-gray-600">
                Create a new course.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="name">Name</label>
                <input id="name" type="text" v-model="form.name" required autofocus />
            </div>
            <div>
                <label for="description">Description</label>
                <textarea id="description" v-model="form.description"></textarea>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit">Save</button>
            </div>
        </form>
    </section>
</template>

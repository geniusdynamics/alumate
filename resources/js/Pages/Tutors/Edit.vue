<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tutor: Object,
});

const form = useForm({
    name: props.tutor.name,
    email: props.tutor.email,
    phone: props.tutor.phone,
});

const submit = () => {
    form.patch(route('tutors.update', props.tutor.id));
};
</script>

<template>
    <Head title="Edit Tutor" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Tutor</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
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

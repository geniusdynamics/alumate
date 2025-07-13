<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    institution: Object,
});

const form = useForm({
    name: props.institution.name,
    address: props.institution.address,
    contact_information: props.institution.contact_information,
});

const submit = () => {
    form.patch(route('institution.update'));
};
</script>

<template>
    <Head title="Edit Institution Details" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Institution Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div>
                                <label for="name">Name</label>
                                <input id="name" type="text" v-model="form.name" required autofocus />
                            </div>
                            <div class="mt-4">
                                <label for="address">Address</label>
                                <input id="address" type="text" v-model="form.address" />
                            </div>
                            <div class="mt-4">
                                <label for="contact_information">Contact Information</label>
                                <input id="contact_information" type="text" v-model="form.contact_information" />
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

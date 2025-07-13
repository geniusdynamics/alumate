<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    id: '',
    name: '',
    address: '',
    contact_information: '',
    plan: '',
});

const submit = () => {
    form.post(route('institutions.store'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Create Institution</h2>

            <p class="mt-1 text-sm text-gray-600">
                Create a new institution.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="id">ID</label>
                <input id="id" type="text" v-model="form.id" required autofocus />
            </div>
            <div>
                <label for="name">Name</label>
                <input id="name" type="text" v-model="form.name" required />
            </div>
            <div>
                <label for="address">Address</label>
                <input id="address" type="text" v-model="form.address" />
            </div>
            <div>
                <label for="contact_information">Contact Information</label>
                <input id="contact_information" type="text" v-model="form.contact_information" />
            </div>
            <div>
                <label for="plan">Plan</label>
                <input id="plan" type="text" v-model="form.plan" />
            </div>

            <div class="flex items-center gap-4">
                <button type="submit">Save</button>
            </div>
        </form>
    </section>
</template>

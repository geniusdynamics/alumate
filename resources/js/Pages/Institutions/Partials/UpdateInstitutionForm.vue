<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    institution: Object,
});

const form = useForm({
    name: props.institution.name,
    address: props.institution.address,
    contact_information: props.institution.contact_information,
    plan: props.institution.plan,
});

const submit = () => {
    form.patch(route('institutions.update', props.institution.id));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Institution</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update the institution's information.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
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

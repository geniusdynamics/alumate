<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    graduate: Object,
    courses: Array,
});

const form = useForm({
    name: props.graduate.name,
    email: props.graduate.email,
    phone: props.graduate.phone,
    address: props.graduate.address,
    graduation_year: props.graduate.graduation_year,
    course_id: props.graduate.course_id,
});

const submit = () => {
    form.patch(route('graduates.update', props.graduate.id));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Graduate</h2>

            <p class="mt-1 text-sm text-gray-600">
                Update the graduate's information.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="name">Name</label>
                <input id="name" type="text" v-model="form.name" required />
            </div>
            <div>
                <label for="email">Email</label>
                <input id="email" type="email" v-model="form.email" required />
            </div>
            <div>
                <label for="phone">Phone</label>
                <input id="phone" type="text" v-model="form.phone" />
            </div>
            <div>
                <label for="address">Address</label>
                <input id="address" type="text" v-model="form.address" />
            </div>
            <div>
                <label for="graduation_year">Graduation Year</label>
                <input id="graduation_year" type="number" v-model="form.graduation_year" required />
            </div>
            <div>
                <label for="course_id">Course</label>
                <select id="course_id" v-model="form.course_id" required>
                    <option v-for="course in courses" :key="course.id" :value="course.id">
                        {{ course.name }}
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit">Save</button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    graduate: Object,
});

const form = useForm({
    employment_status: props.graduate.employment_status || 'unemployed',
    current_job_title: props.graduate.current_job_title || '',
    current_company: props.graduate.current_company || '',
    current_salary: props.graduate.current_salary || '',
    employment_start_date: props.graduate.employment_start_date || '',
});

const submit = () => {
    form.patch(route('graduates.employment.update', props.graduate.id));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Update Employment Status</h2>
            <p class="mt-1 text-sm text-gray-600">
                Update the graduate's current employment information.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div>
                <label for="employment_status" class="block text-sm font-medium text-gray-700">Employment Status *</label>
                <select id="employment_status" v-model="form.employment_status" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="unemployed">Unemployed</option>
                    <option value="employed">Employed</option>
                    <option value="self_employed">Self Employed</option>
                    <option value="further_studies">Further Studies</option>
                    <option value="other">Other</option>
                </select>
                <div v-if="form.errors.employment_status" class="text-red-600 text-sm mt-1">{{ form.errors.employment_status }}</div>
            </div>

            <div v-if="form.employment_status === 'employed' || form.employment_status === 'self_employed'" class="space-y-4">
                <div>
                    <label for="current_job_title" class="block text-sm font-medium text-gray-700">Job Title</label>
                    <input id="current_job_title" type="text" v-model="form.current_job_title" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div v-if="form.errors.current_job_title" class="text-red-600 text-sm mt-1">{{ form.errors.current_job_title }}</div>
                </div>

                <div v-if="form.employment_status === 'employed'">
                    <label for="current_company" class="block text-sm font-medium text-gray-700">Company</label>
                    <input id="current_company" type="text" v-model="form.current_company" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div v-if="form.errors.current_company" class="text-red-600 text-sm mt-1">{{ form.errors.current_company }}</div>
                </div>

                <div>
                    <label for="current_salary" class="block text-sm font-medium text-gray-700">Annual Salary</label>
                    <input id="current_salary" type="number" min="0" v-model="form.current_salary" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div v-if="form.errors.current_salary" class="text-red-600 text-sm mt-1">{{ form.errors.current_salary }}</div>
                </div>

                <div>
                    <label for="employment_start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input id="employment_start_date" type="date" v-model="form.employment_start_date" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <div v-if="form.errors.employment_start_date" class="text-red-600 text-sm mt-1">{{ form.errors.employment_start_date }}</div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" :disabled="form.processing" 
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                    {{ form.processing ? 'Updating...' : 'Update Employment Status' }}
                </button>
            </div>
        </form>
    </section>
</template>
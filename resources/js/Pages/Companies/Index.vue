<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    employers: Array,
});

const form = useForm({});

const approve = (employerId) => {
    form.post(route('companies.approve', employerId));
};

const reject = (employerId) => {
    form.delete(route('companies.reject', employerId));
};
</script>

<template>
    <Head title="Approve Companies" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Approve Companies
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-for="employer in employers" :key="employer.id" class="mb-4 p-4 border rounded">
                            <h3 class="text-lg font-semibold">{{ employer.company_name }}</h3>
                            <p>{{ employer.user.name }} - {{ employer.user.email }}</p>
                            <button @click="approve(employer.id)" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Approve
                            </button>
                            <button @click="reject(employer.id)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded ml-2">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

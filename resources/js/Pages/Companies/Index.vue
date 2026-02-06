<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Approve Companies</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <div v-for="employer in employers" :key="employer.id" class="mb-4 rounded border p-4">
                            <h3 class="text-lg font-semibold">{{ employer.company_name }}</h3>
                            <p>{{ employer.user.name }} - {{ employer.user.email }}</p>
                            <button @click="approve(employer.id)" class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-700">
                                Approve
                            </button>
                            <button @click="reject(employer.id)" class="ml-2 rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














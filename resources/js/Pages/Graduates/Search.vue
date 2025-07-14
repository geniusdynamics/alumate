<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    graduates: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const institution = ref(props.filters.institution);
const course = ref(props.filters.course);
const year = ref(props.filters.year);

watch([search, institution, course, year, employment_status], ([value, value2, value3, value4, value5]) => {
    Inertia.get(route('graduates.search'), { search: value, institution: value2, course: value3, year: value4, employment_status: value5 }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Search Graduates" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Search Graduates
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-4 flex justify-between">
                    <input type="text" v-model="search" placeholder="Search..." class="border px-2 py-1 rounded">
                    <input type="text" v-model="institution" placeholder="Institution..." class="border px-2 py-1 rounded">
                    <input type="text" v-model="course" placeholder="Course..." class="border px-2 py-1 rounded">
                    <input type="number" v-model="year" placeholder="Year..." class="border px-2 py-1 rounded">
                    <select v-model="employment_status" class="border px-2 py-1 rounded">
                        <option value="">All</option>
                        <option value="employed">Employed</option>
                        <option value="unemployed">Unemployed</option>
                        <option value="self-employed">Self-employed</option>
                    </select>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-for="graduate in graduates.data" :key="graduate.id" class="mb-4 p-4 border rounded">
                            <h3 class="text-lg font-semibold">{{ graduate.name }}</h3>
                            <p>{{ graduate.email }}</p>
                            <p>{{ graduate.tenant.name }} - {{ graduate.course.name }} ({{ graduate.graduation_year }})</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

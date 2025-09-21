<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    graduates: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const institution = ref(props.filters.institution);
const course = ref(props.filters.course);
const year = ref(props.filters.year);
const employment_status = ref(props.filters.employment_status);

watch([search, institution, course, year, employment_status], ([value, value2, value3, value4, value5]) => {
    router.get(
        route('graduates.search'),
        { search: value, institution: value2, course: value3, year: value4, employment_status: value5 },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Search Graduates" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Search Graduates</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-4 flex justify-between">
                    <input type="text" v-model="search" placeholder="Search..." class="rounded border px-2 py-1" />
                    <input type="text" v-model="institution" placeholder="Institution..." class="rounded border px-2 py-1" />
                    <input type="text" v-model="course" placeholder="Course..." class="rounded border px-2 py-1" />
                    <input type="number" v-model="year" placeholder="Year..." class="rounded border px-2 py-1" />
                    <select v-model="employment_status" class="rounded border px-2 py-1">
                        <option value="">All</option>
                        <option value="employed">Employed</option>
                        <option value="unemployed">Unemployed</option>
                        <option value="self-employed">Self-employed</option>
                    </select>
                </div>
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 bg-white p-6">
                        <div v-for="graduate in graduates.data" :key="graduate.id" class="mb-4 rounded border p-4">
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

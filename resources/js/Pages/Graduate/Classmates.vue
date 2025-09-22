<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    classmates: Object,
    graduationYears: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const graduationYear = ref(props.filters.graduation_year || '');
const employmentStatus = ref(props.filters.employment_status || '');

const applyFilters = () => {
    router.get(
        route('graduate.classmates'),
        {
            search: search.value,
            graduation_year: graduationYear.value,
            employment_status: employmentStatus.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    graduationYear.value = '';
    employmentStatus.value = '';
    applyFilters();
};

const getEmploymentStatusClass = (status) => {
    const classes = {
        employed: 'bg-green-100 text-green-800',
        unemployed: 'bg-red-100 text-red-800',
        self_employed: 'bg-blue-100 text-blue-800',
        student: 'bg-purple-100 text-purple-800',
        other: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'Not provided';
};

const connectWithClassmate = (classmate) => {
    // Placeholder for connection functionality
    alert(`Connection request sent to ${classmate.user.name}! (Feature to be implemented)`);
};

const sendMessage = (classmate) => {
    // Placeholder for messaging functionality
    alert(`Message feature with ${classmate.user.name} to be implemented`);
};
</script>

<template>
    <Head title="My Classmates" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My Classmates</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Search and Filters -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Find Your Classmates</h3>
                    <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="search" class="mb-1 block text-sm font-medium text-gray-700">Search</label>
                            <input
                                id="search"
                                type="text"
                                v-model="search"
                                placeholder="Name or email..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="graduation_year" class="mb-1 block text-sm font-medium text-gray-700">Graduation Year</label>
                            <select
                                id="graduation_year"
                                v-model="graduationYear"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Years</option>
                                <option v-for="year in graduationYears" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="employment_status" class="mb-1 block text-sm font-medium text-gray-700">Employment Status</label>
                            <select
                                id="employment_status"
                                v-model="employmentStatus"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="employed">Employed</option>
                                <option value="unemployed">Unemployed</option>
                                <option value="self_employed">Self-Employed</option>
                                <option value="student">Student</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="applyFilters" class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700">
                            Search Classmates
                        </button>
                        <button @click="clearFilters" class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-400">
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Classmates Grid -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="classmate in classmates.data"
                        :key="classmate.id"
                        class="overflow-hidden rounded-lg bg-white shadow transition-shadow hover:shadow-md"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-300">
                                        <span class="text-lg font-medium text-gray-700">
                                            {{ classmate.user?.name?.charAt(0) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ classmate.user?.name }}
                                    </h3>
                                    <p class="text-sm text-gray-600">Class of {{ classmate.graduation_year }}</p>
                                </div>
                            </div>

                            <!-- Employment Status -->
                            <div class="mt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-500">Employment Status</span>
                                    <span
                                        v-if="classmate.employment_status?.status"
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            getEmploymentStatusClass(classmate.employment_status.status),
                                        ]"
                                    >
                                        {{ classmate.employment_status.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                    <span v-else class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-800"> NOT SPECIFIED </span>
                                </div>
                            </div>

                            <!-- Current Employment -->
                            <div v-if="classmate.employment_status?.company" class="mt-3">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium">Current:</span>
                                    {{ classmate.employment_status.job_title }} at {{ classmate.employment_status.company }}
                                </p>
                            </div>

                            <!-- Skills -->
                            <div v-if="classmate.skills && classmate.skills.length > 0" class="mt-3">
                                <p class="mb-1 text-xs text-gray-500">Skills:</p>
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="skill in classmate.skills.slice(0, 3)"
                                        :key="skill"
                                        class="inline-flex rounded bg-blue-100 px-2 py-1 text-xs text-blue-800"
                                    >
                                        {{ skill }}
                                    </span>
                                    <span v-if="classmate.skills.length > 3" class="inline-flex rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">
                                        +{{ classmate.skills.length - 3 }} more
                                    </span>
                                </div>
                            </div>

                            <!-- Academic Performance -->
                            <div v-if="classmate.academic_records?.gpa" class="mt-3">
                                <p class="text-sm text-gray-600"><span class="font-medium">GPA:</span> {{ classmate.academic_records.gpa }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex gap-2">
                                <button
                                    @click="connectWithClassmate(classmate)"
                                    class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    Connect
                                </button>
                                <button
                                    @click="sendMessage(classmate)"
                                    class="flex-1 rounded-md bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300"
                                >
                                    Message
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="classmates.links" class="rounded-lg bg-white px-4 py-3 shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <Link
                                v-if="classmates.prev_page_url"
                                :href="classmates.prev_page_url"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="classmates.next_page_url"
                                :href="classmates.next_page_url"
                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing {{ classmates.from }} to {{ classmates.to }} of {{ classmates.total }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                    <Link
                                        v-for="link in classmates.links"
                                        :key="link.label"
                                        :href="link.url"
                                        :class="[
                                            'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                                            link.active
                                                ? 'z-10 border-indigo-500 bg-indigo-50 text-indigo-600'
                                                : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50',
                                        ]"
                                        v-html="link.label"
                                    >
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="classmates.data.length === 0" class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="p-6 text-center">
                        <div class="mb-4 text-lg text-gray-500">No classmates found</div>
                        <p class="mb-4 text-gray-400">
                            {{
                                Object.values(filters).some((f) => f)
                                    ? 'Try adjusting your search criteria.'
                                    : 'No classmates have made their profiles public yet.'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Networking Tips -->
                <div class="rounded-md border border-blue-200 bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Networking Tips</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-inside list-disc space-y-1">
                                    <li>Connect with classmates in your field of interest</li>
                                    <li>Share job opportunities and career advice</li>
                                    <li>Collaborate on projects and skill development</li>
                                    <li>Maintain professional relationships for future opportunities</li>
                                    <li>Attend alumni events and networking sessions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>














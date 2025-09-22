<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900">Alumni Directory</h1>
                    <p class="mt-4 text-lg text-gray-600">Connect with our accomplished alumni community</p>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Search and Filters -->
            <div class="mb-8 rounded-lg bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="search" class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <input
                            id="search"
                            v-model="searchForm.search"
                            type="text"
                            placeholder="Search by name..."
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>

                    <div>
                        <label for="course" class="mb-2 block text-sm font-medium text-gray-700">Course</label>
                        <select
                            id="course"
                            v-model="searchForm.course_id"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All Courses</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="institution" class="mb-2 block text-sm font-medium text-gray-700">Institution</label>
                        <select
                            id="institution"
                            v-model="searchForm.institution_id"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All Institutions</option>
                            <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                {{ institution.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="year" class="mb-2 block text-sm font-medium text-gray-700">Graduation Year</label>
                        <select
                            id="year"
                            v-model="searchForm.graduation_year"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @change="applyFilters"
                        >
                            <option value="">All Years</option>
                            <option v-for="year in graduationYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <p class="text-sm text-gray-600">Showing {{ alumni.data.length }} of {{ alumni.total }} alumni</p>
                    <button @click="clearFilters" class="text-sm text-blue-600 hover:text-blue-800">Clear Filters</button>
                </div>
            </div>

            <!-- Alumni Grid -->
            <div v-if="alumni.data.length > 0" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="alumnus in alumni.data" :key="alumnus.id" class="rounded-lg bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                    <div class="mb-4 flex items-center">
                        <img
                            :src="alumnus.user.avatar || '/default-avatar.png'"
                            :alt="alumnus.user.name"
                            class="h-16 w-16 rounded-full object-cover"
                        />
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ alumnus.user.name }}</h3>
                            <p class="text-sm text-gray-600">{{ alumnus.course?.name }}</p>
                            <p class="text-sm text-gray-500">Class of {{ alumnus.graduation_year }}</p>
                        </div>
                    </div>

                    <div class="mb-4 space-y-2">
                        <div v-if="alumnus.current_job_title" class="flex items-center text-sm text-gray-600">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                ></path>
                            </svg>
                            {{ alumnus.current_job_title }}
                        </div>

                        <div v-if="alumnus.current_company" class="flex items-center text-sm text-gray-600">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10"
                                ></path>
                            </svg>
                            {{ alumnus.current_company }}
                        </div>

                        <div v-if="alumnus.current_location" class="flex items-center text-sm text-gray-600">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                ></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ alumnus.current_location }}
                        </div>
                    </div>

                    <!-- Login prompt for connections -->
                    <div class="border-t border-gray-200 pt-4">
                        <Link
                            :href="route('login')"
                            class="block w-full rounded-md bg-blue-600 px-4 py-2 text-center text-white transition-colors hover:bg-blue-700"
                        >
                            Login to Connect
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                    ></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No alumni found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria.</p>
            </div>

            <!-- Pagination -->
            <div v-if="alumni.data.length > 0" class="mt-8">
                <nav class="flex items-center justify-between">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <Link
                            v-if="alumni.prev_page_url"
                            :href="alumni.prev_page_url"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="alumni.next_page_url"
                            :href="alumni.next_page_url"
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">Showing {{ alumni.from }} to {{ alumni.to }} of {{ alumni.total }} results</p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                                <Link
                                    v-if="alumni.prev_page_url"
                                    :href="alumni.prev_page_url"
                                    class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="alumni.next_page_url"
                                    :href="alumni.next_page_url"
                                    class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </nav>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="bg-blue-600 py-12">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="mb-4 text-3xl font-bold text-white">Join Our Alumni Network</h2>
                <p class="mb-8 text-xl text-blue-100">Connect with fellow graduates, share your success story, and discover new opportunities.</p>
                <div class="space-x-4">
                    <Link
                        :href="route('register')"
                        class="rounded-lg bg-white px-8 py-3 font-semibold text-blue-600 transition-colors hover:bg-gray-100"
                    >
                        Join Now
                    </Link>
                    <Link
                        :href="route('login')"
                        class="rounded-lg border-2 border-white px-8 py-3 font-semibold text-white transition-colors hover:bg-white hover:text-blue-600"
                    >
                        Sign In
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { CourseOption, DirectoryResponse, InstitutionOption } from '@/Types';
import { Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { reactive } from 'vue';

interface Props {
    alumni: DirectoryResponse;
    courses: CourseOption[];
    institutions: InstitutionOption[];
    graduationYears: number[];
    filters: Record<string, any>;
    auth_required: boolean;
}

const props = defineProps<Props>();

const searchForm = reactive({
    search: props.filters.search || '',
    course_id: props.filters.course_id || '',
    institution_id: props.filters.institution_id || '',
    graduation_year: props.filters.graduation_year || '',
    location: props.filters.location || '',
    industry: props.filters.industry || '',
});

const debouncedSearch = debounce(() => {
    applyFilters();
}, 300);

const applyFilters = () => {
    router.get(route('alumni.public.directory'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    Object.keys(searchForm).forEach((key) => {
        searchForm[key] = '';
    });
    applyFilters();
};
</script>



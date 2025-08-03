<template>
    <AppLayout title="Alumni Directory">
        <Head title="Alumni Directory" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Alumni Directory</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with fellow alumni from your institution and beyond</p>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-6">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search Alumni
                            </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by name, email, or keywords..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Course Filter -->
                            <div>
                                <label for="course" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Course
                                </label>
                                <select
                                    id="course"
                                    v-model="searchForm.course_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Institution Filter -->
                            <div>
                                <label for="institution" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Institution
                                </label>
                                <select
                                    id="institution"
                                    v-model="searchForm.institution_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Graduation Year Filter -->
                            <div>
                                <label for="graduation_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Graduation Year
                                </label>
                                <select
                                    id="graduation_year"
                                    v-model="searchForm.graduation_year"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Years</option>
                                    <option v-for="year in graduationYears" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>

                            <!-- Location Filter -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Location
                                </label>
                                <input
                                    id="location"
                                    v-model="searchForm.location"
                                    type="text"
                                    placeholder="City, Country"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <!-- Industry Filter -->
                            <div>
                                <label for="industry" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Industry
                                </label>
                                <input
                                    id="industry"
                                    v-model="searchForm.industry"
                                    type="text"
                                    placeholder="Technology, Finance..."
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button
                                type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                            >
                                Apply Filters
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md font-medium transition-colors"
                            >
                                Clear All
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ alumni.total }} Alumni Found
                    </h2>
                </div>

                <!-- Alumni Grid -->
                <div class="p-6">
                    <div v-if="alumni.data.length === 0" class="text-center py-12">
                        <UsersIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No alumni found</h3>
                        <p class="text-gray-500 dark:text-gray-400">Try adjusting your search criteria</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <AlumniCard
                            v-for="alumnus in alumni.data"
                            :key="alumnus.id"
                            :alumnus="alumnus"
                            @connect-requested="handleConnectRequest"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="alumni.last_page > 1" class="mt-8">
                        <Pagination :links="alumni.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AlumniCard from '@/Components/AlumniCard.vue'
import Pagination from '@/Components/Pagination.vue'
import { UsersIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    alumni: Object,
    courses: Array,
    institutions: Array,
    graduationYears: Array,
    filters: Object,
})

const searchForm = reactive({
    search: props.filters.search || '',
    course_id: props.filters.course_id || '',
    institution_id: props.filters.institution_id || '',
    graduation_year: props.filters.graduation_year || '',
    location: props.filters.location || '',
    industry: props.filters.industry || '',
})

const applyFilters = () => {
    router.get(route('alumni.directory'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    Object.keys(searchForm).forEach(key => {
        searchForm[key] = ''
    })
    applyFilters()
}

const handleConnectRequest = (alumniId) => {
    // Handle connection request
    router.post(route('api.connections.request'), {
        user_id: alumniId
    }, {
        preserveState: true,
        onSuccess: () => {
            // Show success message or update UI
        }
    })
}
</script>

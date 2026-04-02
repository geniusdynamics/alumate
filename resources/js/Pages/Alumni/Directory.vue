<template>
    <AppLayout title="Alumni Directory">
        <Head title="Alumni Directory" />

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Alumni Directory</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Connect with fellow alumni from your institution and beyond</p>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Search Alumni </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by name, email, or keywords..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Row -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                            <!-- Course Filter -->
                            <div>
                                <label for="course" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Course </label>
                                <select
                                    id="course"
                                    v-model="searchForm.course_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Courses</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">
                                        {{ course.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Institution Filter -->
                            <div>
                                <label for="institution" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Institution </label>
                                <select
                                    id="institution"
                                    v-model="searchForm.institution_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Graduation Year Filter -->
                            <div>
                                <label for="graduation_year" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Graduation Year
                                </label>
                                <select
                                    id="graduation_year"
                                    v-model="searchForm.graduation_year"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Years</option>
                                    <option v-for="year in graduationYears" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>
                            </div>

                            <!-- Location Filter -->
                            <div>
                                <label for="location" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Location </label>
                                <input
                                    id="location"
                                    v-model="searchForm.location"
                                    type="text"
                                    placeholder="City, Country"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <!-- Industry Filter -->
                            <div>
                                <label for="industry" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Industry </label>
                                <input
                                    id="industry"
                                    v-model="searchForm.industry"
                                    type="text"
                                    placeholder="Technology, Finance..."
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700">
                                Apply Filters
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-400"
                            >
                                Clear All
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ alumni.total }} Alumni Found</h2>
                </div>

                <!-- Alumni Grid -->
                <div class="p-6">
                    <div v-if="alumni.data.length === 0" class="py-12 text-center">
                        <UsersIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No alumni found</h3>
                        <p class="text-gray-500 dark:text-gray-400">Try adjusting your search criteria</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <AlumniCard
                            v-for="alumnus in alumniList"
                            :key="alumnus.id"
                            :alumnus="alumnus"
                            @connect-requested="handleConnectRequest"
                            @profile-viewed="viewAlumniProfile"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="alumni.last_page > 1" class="mt-8">
                        <Pagination :links="alumni.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Request Modal -->
        <ConnectionRequestModal
            v-if="showConnectionModal"
            :alumni="selectedAlumniForConnection"
            @send-request="sendConnectionRequest"
            @close="closeConnectionModal"
        />

        <!-- Alumni Profile Modal -->
        <AlumniProfile v-if="showProfileModal" :alumni="selectedAlumni" @close="closeProfileModal" @connect-requested="handleConnectRequest" />

        <!-- User Flow Integration -->
        <UserFlowIntegration />

        <!-- Real-time Updates -->
        <RealTimeUpdates :show-connection-status="true" :show-activity-feed="true" />

        <!-- Cross-feature Connections -->
        <CrossFeatureConnections context="alumni-directory" :context-data="{ alumni: alumniList }" />
    </AppLayout>
</template>

<script setup lang="ts">
import AlumniCard from '@/Components/AlumniCard.vue';
import AlumniProfile from '@/Components/AlumniProfile.vue';
import ConnectionRequestModal from '@/Components/ConnectionRequestModal.vue';
import CrossFeatureConnections from '@/Components/CrossFeatureConnections.vue';
import Pagination from '@/Components/Pagination.vue';
import RealTimeUpdates from '@/Components/RealTimeUpdates.vue';
import UserFlowIntegration from '@/Components/UserFlowIntegration.vue';
import { useRealTimeUpdates } from '@/Composables/useRealTimeUpdates';
import AppLayout from '@/Layouts/AppLayout.vue';
import userFlowIntegration from '@/Services/UserFlowIntegration';
import { UsersIcon } from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';

const props = defineProps({
    alumni: Object,
    courses: Array,
    institutions: Array,
    graduationYears: Array,
    filters: Object,
});

const searchForm = reactive({
    search: props.filters.search || '',
    course_id: props.filters.course_id || '',
    institution_id: props.filters.institution_id || '',
    graduation_year: props.filters.graduation_year || '',
    location: props.filters.location || '',
    industry: props.filters.industry || '',
});

const showConnectionModal = ref(false);
const showProfileModal = ref(false);
const selectedAlumni = ref(null);
const selectedAlumniForConnection = ref(null);
const alumniList = reactive([...props.alumni.data]);

// Real-time updates
const realTimeUpdates = useRealTimeUpdates();

onMounted(() => {
    // Set up real-time connection updates
    realTimeUpdates.onConnectionRequest((connection) => {
        userFlowIntegration.showNotification('New connection request received!', 'info');
    });

    // Set up user flow integration callbacks
    userFlowIntegration.on('connectionRequested', (connection) => {
        // Update alumni card to show pending status
        const alumni = alumniList.find((a) => a.id === connection.connected_user_id);
        if (alumni) {
            alumni.connection_status = 'pending';
        }
    });

    userFlowIntegration.on('connectionAccepted', (connection) => {
        // Update alumni card to show connected status
        const alumni = alumniList.find((a) => a.id === connection.user_id || a.id === connection.connected_user_id);
        if (alumni) {
            alumni.connection_status = 'connected';
        }
    });
});

const applyFilters = () => {
    router.get(route('alumni.directory'), searchForm, {
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

const handleConnectRequest = async (alumni) => {
    selectedAlumniForConnection.value = alumni;
    showConnectionModal.value = true;
};

const sendConnectionRequest = async (message = '') => {
    if (selectedAlumniForConnection.value) {
        try {
            await userFlowIntegration.sendConnectionRequestAndUpdate(selectedAlumniForConnection.value.id, message);
            showConnectionModal.value = false;
            selectedAlumniForConnection.value = null;
        } catch (error) {
            console.error('Failed to send connection request:', error);
        }
    }
};

const viewAlumniProfile = (alumni) => {
    selectedAlumni.value = alumni;
    showProfileModal.value = true;
};

const closeProfileModal = () => {
    showProfileModal.value = false;
    selectedAlumni.value = null;
};

const closeConnectionModal = () => {
    showConnectionModal.value = false;
    selectedAlumniForConnection.value = null;
};
</script>



















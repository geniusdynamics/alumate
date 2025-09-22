<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />

        <!-- Mobile Hamburger Menu -->
        <MobileHamburgerMenu class="lg:hidden" />

        <!-- Pull to Refresh -->
        <PullToRefresh @refresh="refreshDashboard" class="theme-bg-secondary min-h-screen">
            <!-- Mobile Header -->
            <div class="safe-area-top border-b border-gray-200 bg-white shadow-sm lg:hidden dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600">
                            <span class="text-sm font-bold text-white">
                                {{ getAppInitials($page.props.app?.name) }}
                            </span>
                        </div>
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $page.props.app?.name || 'Alumni Platform' }}
                        </h1>
                    </div>
                    <div class="flex items-center space-x-2">
                        <ThemeToggle variant="simple" />
                        <button class="touch-target p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                            <BellIcon class="h-6 w-6" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="mobile-container lg:py-12" data-tour="dashboard">
                <div class="mx-auto max-w-7xl lg:px-6">
                    <div class="card-mobile lg:rounded-lg lg:bg-white lg:shadow-sm">
                        <div class="lg:p-6">
                            <!-- Welcome Section -->
                            <div class="mb-6">
                                <h2 class="mb-2 text-2xl font-bold text-gray-900 lg:text-3xl dark:text-white">Welcome back!</h2>
                                <div class="mb-4 flex items-center space-x-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600">
                                        <span class="font-medium text-white">
                                            {{ getUserInitials($page.props.auth.user.name) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ $page.props.auth.user.name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $page.props.auth.user.email }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Dashboard Widgets -->
                            <div class="mobile-grid mb-8 gap-4 lg:grid-cols-1 lg:gap-6 xl:grid-cols-2">
                                <!-- Quick Actions - Always visible -->
                                <QuickActionsWidget />

                                <!-- Social Activity Widget -->
                                <SocialActivityWidget :limit="5" />

                                <!-- Alumni Suggestions Widget -->
                                <AlumniSuggestionsWidget :limit="3" />

                                <!-- Job Recommendations Widget -->
                                <JobRecommendationsWidget :limit="3" />

                                <!-- Events Widget -->
                                <EventsWidget :limit="3" />
                            </div>

                            <!-- Role-based content -->
                            <div class="mobile-grid gap-4 lg:grid-cols-2 lg:gap-6 xl:grid-cols-3">
                                <!-- Super Admin Features -->
                                <div
                                    v-if="hasRole('super-admin')"
                                    class="card-mobile border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20"
                                >
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-blue-900 dark:text-blue-100">Super Admin Actions</h3>
                                        <BuildingOfficeIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <Link :href="route('institutions.index')" class="btn-mobile-primary w-full text-center">
                                            Manage Institutions
                                        </Link>
                                        <Link :href="route('users.index')" class="btn-mobile-primary w-full text-center"> Manage Users </Link>
                                        <Link href="/analytics" class="btn-mobile-primary w-full text-center"> View Analytics </Link>
                                        <Link href="/companies" class="btn-mobile-primary w-full text-center"> Approve Employers </Link>
                                    </div>
                                </div>

                                <!-- Institution Admin Features -->
                                <div
                                    v-if="hasRole('institution-admin')"
                                    class="card-mobile border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20"
                                >
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-green-900 dark:text-green-100">Institution Admin</h3>
                                        <AcademicCapIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <Link
                                            :href="route('graduates.index')"
                                            class="btn-mobile w-full bg-green-600 text-center text-white hover:bg-green-700"
                                        >
                                            Manage Graduates
                                        </Link>
                                        <Link
                                            :href="route('courses.index')"
                                            class="btn-mobile w-full bg-green-600 text-center text-white hover:bg-green-700"
                                        >
                                            Manage Courses
                                        </Link>
                                        <Link
                                            :href="route('institution-admin.import-export')"
                                            class="btn-mobile w-full bg-green-600 text-center text-white hover:bg-green-700"
                                        >
                                            Import/Export Data
                                        </Link>
                                        <Link
                                            :href="route('institution-admin.analytics')"
                                            class="btn-mobile w-full bg-green-600 text-center text-white hover:bg-green-700"
                                        >
                                            View Analytics
                                        </Link>
                                    </div>
                                </div>

                                <!-- Employer Features -->
                                <div
                                    v-if="hasRole('employer')"
                                    class="card-mobile border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20"
                                >
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-purple-900 dark:text-purple-100">Employer</h3>
                                        <BriefcaseIcon class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <PlusIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Post Job Openings</span>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <DocumentTextIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">View Applications</span>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <MagnifyingGlassIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Search Graduates</span>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <BuildingOfficeIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Manage Company Profile</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Graduate Features -->
                                <div
                                    v-if="hasRole('graduate')"
                                    class="card-mobile border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-900/20"
                                >
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-orange-900 dark:text-orange-100">Graduate</h3>
                                        <UserIcon class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <BriefcaseIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Browse Job Openings</span>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <DocumentCheckIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Apply for Jobs</span>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <UserCircleIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Update Profile</span>
                                        </div>
                                        <div class="flex items-center space-x-3 rounded-lg bg-white p-3 dark:bg-gray-800">
                                            <UsersIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">View Classmates</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Success Message -->
                            <div class="card-mobile mt-8 border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-800">
                                            <span class="text-lg">🎉</span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="mb-1 text-sm font-medium text-green-900 dark:text-green-100">Congratulations!</h4>
                                        <p class="text-sm text-green-700 dark:text-green-300">
                                            Your Alumni Platform is running successfully with modern social features, mobile optimization, and
                                            comprehensive user management.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PullToRefresh>
    </AppLayout>
</template>

<script setup>
import AlumniSuggestionsWidget from '@/Components/DashboardWidgets/AlumniSuggestionsWidget.vue';
import EventsWidget from '@/Components/DashboardWidgets/EventsWidget.vue';
import JobRecommendationsWidget from '@/Components/DashboardWidgets/JobRecommendationsWidget.vue';
import QuickActionsWidget from '@/Components/DashboardWidgets/QuickActionsWidget.vue';
import SocialActivityWidget from '@/Components/DashboardWidgets/SocialActivityWidget.vue';
import MobileHamburgerMenu from '@/Components/MobileHamburgerMenu.vue';
import PullToRefresh from '@/Components/PullToRefresh.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    AcademicCapIcon,
    BellIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    DocumentCheckIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    UserCircleIcon,
    UserIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
});

// Helper function to check user roles
const hasRole = (role) => {
    return props.auth?.user?.roles?.some((userRole) => userRole.name === role) || false;
};

// Helper function to get user initials
const getUserInitials = (name) => {
    if (!name) return 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Helper function to get app initials
const getAppInitials = (name) => {
    if (!name) return 'AP';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Refresh dashboard data
const refreshDashboard = async () => {
    // Simulate refresh delay
    await new Promise((resolve) => setTimeout(resolve, 1000));

    // In a real app, you would reload data here
    window.location.reload();
};
</script>



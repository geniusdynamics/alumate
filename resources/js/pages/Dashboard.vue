<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard" />
        
        <!-- Mobile Hamburger Menu -->
        <MobileHamburgerMenu class="lg:hidden" />
        
        <!-- Pull to Refresh -->
        <PullToRefresh @refresh="refreshDashboard" class="min-h-screen theme-bg-secondary">
            <!-- Mobile Header -->
            <div class="lg:hidden bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 safe-area-top">
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center space-x-3">
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">
                                {{ getAppInitials($page.props.app?.name) }}
                            </span>
                        </div>
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $page.props.app?.name || 'Alumni Platform' }}
                        </h1>
                    </div>
                    <div class="flex items-center space-x-2">
                        <ThemeToggle variant="simple" />
                        <button class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 touch-target">
                            <BellIcon class="h-6 w-6" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="mobile-container lg:py-12" data-tour="dashboard">
                <div class="max-w-7xl mx-auto lg:px-6">
                    <div class="card-mobile lg:bg-white lg:shadow-sm lg:rounded-lg">
                        <div class="lg:p-6">
                            <!-- Welcome Section -->
                            <div class="mb-6">
                                <h2 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                    Welcome back!
                                </h2>
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="h-12 w-12 bg-blue-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-medium">
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
                            <div class="mobile-grid lg:grid-cols-1 xl:grid-cols-2 gap-4 lg:gap-6 mb-8">
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
                            <div class="mobile-grid lg:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
                                <!-- Super Admin Features -->
                                <div v-if="hasRole('super-admin')" class="card-mobile bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-blue-900 dark:text-blue-100">Super Admin Actions</h3>
                                        <BuildingOfficeIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <Link
                                            :href="route('institutions.index')"
                                            class="btn-mobile-primary w-full text-center"
                                        >
                                            Manage Institutions
                                        </Link>
                                        <Link
                                            :href="route('users.index')"
                                            class="btn-mobile-primary w-full text-center"
                                        >
                                            Manage Users
                                        </Link>
                                        <Link
                                            href="/analytics"
                                            class="btn-mobile-primary w-full text-center"
                                        >
                                            View Analytics
                                        </Link>
                                        <Link
                                            href="/companies"
                                            class="btn-mobile-primary w-full text-center"
                                        >
                                            Approve Employers
                                        </Link>
                                    </div>
                                </div>

                                <!-- Institution Admin Features -->
                                <div v-if="hasRole('institution-admin')" class="card-mobile bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-green-900 dark:text-green-100">Institution Admin</h3>
                                        <AcademicCapIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <Link
                                            :href="route('graduates.index')"
                                            class="btn-mobile w-full text-center bg-green-600 hover:bg-green-700 text-white"
                                        >
                                            Manage Graduates
                                        </Link>
                                        <Link
                                            :href="route('courses.index')"
                                            class="btn-mobile w-full text-center bg-green-600 hover:bg-green-700 text-white"
                                        >
                                            Manage Courses
                                        </Link>
                                        <Link
                                            :href="route('institution-admin.import-export')"
                                            class="btn-mobile w-full text-center bg-green-600 hover:bg-green-700 text-white"
                                        >
                                            Import/Export Data
                                        </Link>
                                        <Link
                                            :href="route('institution-admin.analytics')"
                                            class="btn-mobile w-full text-center bg-green-600 hover:bg-green-700 text-white"
                                        >
                                            View Analytics
                                        </Link>
                                    </div>
                                </div>

                                <!-- Employer Features -->
                                <div v-if="hasRole('employer')" class="card-mobile bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800">
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-purple-900 dark:text-purple-100">Employer</h3>
                                        <BriefcaseIcon class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <PlusIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Post Job Openings</span>
                                        </div>
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <DocumentTextIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">View Applications</span>
                                        </div>
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <MagnifyingGlassIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Search Graduates</span>
                                        </div>
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <BuildingOfficeIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Manage Company Profile</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Graduate Features -->
                                <div v-if="hasRole('graduate')" class="card-mobile bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800">
                                    <div class="card-mobile-header">
                                        <h3 class="card-mobile-title text-orange-900 dark:text-orange-100">Graduate</h3>
                                        <UserIcon class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <BriefcaseIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Browse Job Openings</span>
                                        </div>
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <DocumentCheckIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Apply for Jobs</span>
                                        </div>
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <UserCircleIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">Update Profile</span>
                                        </div>
                                        <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <UsersIcon class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                            <span class="text-sm text-gray-900 dark:text-white">View Classmates</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Success Message -->
                            <div class="mt-8 card-mobile bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-8 w-8 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center">
                                            <span class="text-lg">🎉</span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-green-900 dark:text-green-100 mb-1">
                                            Congratulations!
                                        </h4>
                                        <p class="text-sm text-green-700 dark:text-green-300">
                                            Your Alumni Platform is running successfully with modern social features, mobile optimization, and comprehensive user management.
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
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import MobileHamburgerMenu from '@/components/MobileHamburgerMenu.vue'
import PullToRefresh from '@/components/PullToRefresh.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import QuickActionsWidget from '@/Components/DashboardWidgets/QuickActionsWidget.vue'
import SocialActivityWidget from '@/Components/DashboardWidgets/SocialActivityWidget.vue'
import AlumniSuggestionsWidget from '@/Components/DashboardWidgets/AlumniSuggestionsWidget.vue'
import JobRecommendationsWidget from '@/Components/DashboardWidgets/JobRecommendationsWidget.vue'
import EventsWidget from '@/Components/DashboardWidgets/EventsWidget.vue'
import {
    BellIcon,
    BuildingOfficeIcon,
    AcademicCapIcon,
    BriefcaseIcon,
    UserIcon,
    PlusIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    DocumentCheckIcon,
    UserCircleIcon,
    UsersIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    auth: Object,
})

// Helper function to check user roles
const hasRole = (role) => {
    return props.auth?.user?.roles?.some(userRole => userRole.name === role) || false
}

// Helper function to get user initials
const getUserInitials = (name) => {
    if (!name) return 'U'
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// Helper function to get app initials
const getAppInitials = (name) => {
    if (!name) return 'AP'
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// Refresh dashboard data
const refreshDashboard = async () => {
    // Simulate refresh delay
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // In a real app, you would reload data here
    window.location.reload()
}
</script>
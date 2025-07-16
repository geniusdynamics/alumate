<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Dashboard" />
        
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ $page.props.app?.name || 'Laravel' }}
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ $page.props.auth.user.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h2 class="text-2xl font-bold mb-4">
                            Welcome to your Dashboard!
                        </h2>
                        
                        <div class="mb-6">
                            <p class="text-lg text-gray-600 mb-2">
                                Hello, {{ $page.props.auth.user.name }}!
                            </p>
                            <p class="text-gray-500">
                                Email: {{ $page.props.auth.user.email }}
                            </p>
                        </div>

                        <!-- Role-based content -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Super Admin Features -->
                            <div v-if="hasRole('super-admin')" class="bg-blue-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-blue-900 mb-3">Super Admin Actions</h3>
                                <div class="space-y-3">
                                    <Link
                                        :href="route('institutions.index')"
                                        class="block w-full text-left px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                    >
                                        Manage Institutions
                                    </Link>
                                    <Link
                                        :href="route('users.index')"
                                        class="block w-full text-left px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                    >
                                        Manage Users
                                    </Link>
                                    <Link
                                        href="/analytics"
                                        class="block w-full text-left px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                    >
                                        View Analytics
                                    </Link>
                                    <Link
                                        href="/companies"
                                        class="block w-full text-left px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                    >
                                        Approve Employers
                                    </Link>
                                </div>
                            </div>

                            <!-- Institution Admin Features -->
                            <div v-if="hasRole('institution-admin')" class="bg-green-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-green-900 mb-3">Institution Admin</h3>
                                <ul class="space-y-2 text-green-700">
                                    <li>• Manage Courses</li>
                                    <li>• Manage Graduates</li>
                                    <li>• Import Data</li>
                                    <li>• View Reports</li>
                                </ul>
                            </div>

                            <!-- Employer Features -->
                            <div v-if="hasRole('employer')" class="bg-purple-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-purple-900 mb-3">Employer</h3>
                                <ul class="space-y-2 text-purple-700">
                                    <li>• Post Job Openings</li>
                                    <li>• View Applications</li>
                                    <li>• Search Graduates</li>
                                    <li>• Manage Company Profile</li>
                                </ul>
                            </div>

                            <!-- Graduate Features -->
                            <div v-if="hasRole('graduate')" class="bg-orange-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-orange-900 mb-3">Graduate</h3>
                                <ul class="space-y-2 text-orange-700">
                                    <li>• Browse Job Openings</li>
                                    <li>• Apply for Jobs</li>
                                    <li>• Update Profile</li>
                                    <li>• View Classmates</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-600">
                                🎉 <strong>Congratulations!</strong> Your Laravel + Vue.js application is now running successfully with multi-tenancy, role-based authentication, and a complete user management system.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    auth: Object,
});

// Helper function to check user roles
const hasRole = (role) => {
    return props.auth?.user?.roles?.some(userRole => userRole.name === role) || false;
};
</script>
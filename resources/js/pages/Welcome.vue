<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <Head title="Graduate Tracking System" />
        
        <div class="relative min-h-screen">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <h1 class="text-2xl font-bold text-gray-900">Graduate Tracking System</h1>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out"
                            >
                                Go to Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium"
                                >
                                    Sign In
                                </Link>
                                <Link
                                    :href="route('register')"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition duration-150 ease-in-out"
                                >
                                    Get Started
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <!-- Hero Section -->
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                        Connect Graduates
                        <span class="text-indigo-600">with Opportunities</span>
                    </h2>
                    <p class="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                        A comprehensive platform for TVET institutions to track graduate career progress and connect them with employers.
                    </p>
                </div>

                <!-- Quick Access Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Super Admin</h3>
                                <p class="text-sm text-gray-500">System-wide management</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Link :href="route('super-admin.dashboard')" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Access Dashboard →
                            </Link>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Institution</h3>
                                <p class="text-sm text-gray-500">Manage graduates & courses</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Link :href="route('institution-admin.dashboard')" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Access Dashboard →
                            </Link>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Employer</h3>
                                <p class="text-sm text-gray-500">Post jobs & find talent</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Link :href="route('employer.dashboard')" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Access Dashboard →
                            </Link>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-orange-100 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Graduate</h3>
                                <p class="text-sm text-gray-500">Find jobs & track progress</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Link :href="route('graduate.dashboard')" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                Access Dashboard →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Development Info -->
                <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Development Access</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Demo Accounts -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Demo Accounts</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-blue-900">Super Admin</p>
                                        <p class="text-sm text-blue-700">admin@system.com</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-blue-600 font-mono">password</p>
                                        <Link :href="route('login')" class="text-xs text-blue-600 hover:text-blue-800">Login →</Link>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-green-900">Institution Admin</p>
                                        <p class="text-sm text-green-700">admin@tech-institute.edu</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-green-600 font-mono">password</p>
                                        <Link :href="route('login')" class="text-xs text-green-600 hover:text-green-800">Login →</Link>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-purple-900">Graduate</p>
                                        <p class="text-sm text-purple-700">john.smith@student.edu</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-purple-600 font-mono">password</p>
                                        <Link :href="route('login')" class="text-xs text-purple-600 hover:text-purple-800">Login →</Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Links</h3>
                            <div class="space-y-2">
                                <Link :href="route('super-admin.dashboard')" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span class="font-medium text-gray-900">Super Admin Dashboard</span>
                                    <span class="text-sm text-gray-500 block">System management & analytics</span>
                                </Link>
                                <Link :href="route('login')" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span class="font-medium text-gray-900">Login Page</span>
                                    <span class="text-sm text-gray-500 block">Sign in to your account</span>
                                </Link>
                                <Link :href="route('register')" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span class="font-medium text-gray-900">Registration</span>
                                    <span class="text-sm text-gray-500 block">Create a new account</span>
                                </Link>
                                <a href="/testing" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span class="font-medium text-gray-900">Testing Suite</span>
                                    <span class="text-sm text-gray-500 block">User acceptance testing</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Server Status -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                Development server is running
                            </p>
                            <p class="text-xs text-green-600">
                                Laravel: http://127.0.0.1:8080 | Vite: http://localhost:5173
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>
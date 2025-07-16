<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Welcome" />
        
        <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 selection:bg-red-500 selection:text-white">
            <div class="max-w-7xl mx-auto p-6 lg:p-8">
                <div class="flex justify-center">
                    <h1 class="text-6xl font-bold text-gray-900 mb-8">
                        {{ $page.props.app?.name || 'Laravel' }}
                    </h1>
                </div>

                <div class="flex justify-center space-x-4 mb-8">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Register
                        </Link>
                    </template>
                </div>

                <div class="text-center">
                    <p class="text-lg text-gray-600 mb-4">
                        Welcome to the Graduate Tracking System
                    </p>
                    <p class="text-gray-500">
                        Connect graduates with employers and track career progress.
                    </p>
                </div>

                <!-- Demo Users Info -->
                <div class="mt-12 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Demo Accounts</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-blue-900">Super Admin</h3>
                            <p class="text-sm text-blue-700">skimani@polytrack.ga</p>
                            <p class="text-xs text-blue-600">5esurE_skimani</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-green-900">Institution Admin</h3>
                            <p class="text-sm text-green-700">ikimani@polytrack.ga</p>
                            <p class="text-xs text-green-600">5esurE_ikimani</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-purple-900">Employer</h3>
                            <p class="text-sm text-purple-700">ekimani@polytrack.ga</p>
                            <p class="text-xs text-purple-600">5esurE_ekimani</p>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-orange-900">Graduate</h3>
                            <p class="text-sm text-orange-700">gkimani@polytrack.ga</p>
                            <p class="text-xs text-orange-600">5esurE_gkimani</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>
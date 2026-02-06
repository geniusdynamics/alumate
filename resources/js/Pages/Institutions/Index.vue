<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Institutions" />

        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center space-x-8">
                        <Link href="/dashboard" class="text-xl font-semibold text-gray-900">
                            {{ $page.props.app?.name || 'Alumate' }}
                        </Link>
                        <div class="hidden space-x-4 md:flex">
                            <Link href="/dashboard" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                Dashboard
                            </Link>
                            <Link href="/institutions" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white"> Institutions </Link>
                            <Link href="/users" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"> Users </Link>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ $page.props.auth.user.name }}</span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900">Institution Management</h2>
                            <Link
                                :href="route('institutions.create')"
                                class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-700"
                            >
                                Add Institution
                            </Link>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
                            <div class="rounded-lg bg-blue-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500">
                                            <span class="font-bold text-white">{{ institutions.length }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-blue-600">Total Institutions</p>
                                        <p class="text-2xl font-bold text-blue-900">{{ institutions.length }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg bg-green-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500">
                                            <span class="font-bold text-white">A</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-green-600">Active Institutions</p>
                                        <p class="text-2xl font-bold text-green-900">{{ institutions.length }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg bg-purple-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-500">
                                            <span class="font-bold text-white">U</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-purple-600">Total Users</p>
                                        <p class="text-2xl font-bold text-purple-900">-</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg bg-orange-50 p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-500">
                                            <span class="font-bold text-white">G</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-orange-600">Total Graduates</p>
                                        <p class="text-2xl font-bold text-orange-900">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Institutions Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Institution</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Contact Information
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="institution in institutions" :key="institution.id" class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300">
                                                        <span class="text-sm font-medium text-gray-700">
                                                            {{ institution.name.charAt(0).toUpperCase() }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ institution.name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">ID: {{ institution.id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ institution.contact_information || 'Not provided' }}</div>
                                            <div class="text-sm text-gray-500">{{ institution.address || 'No address' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">
                                                {{ institution.plan || 'Basic' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ new Date(institution.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <Link
                                                    :href="route('institutions.edit', institution.id)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Edit
                                                </Link>
                                                <button @click="deleteInstitution(institution)" class="text-red-600 hover:text-red-900">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div v-if="institutions.length === 0" class="py-12 text-center">
                                <div class="text-gray-500">
                                    <p class="text-lg font-medium">No institutions found</p>
                                    <p class="mt-2">Get started by creating your first institution.</p>
                                    <Link
                                        :href="route('institutions.create')"
                                        class="mt-4 inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-700"
                                    >
                                        Create First Institution
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    institutions: Array,
});

const deleteInstitution = (institution) => {
    if (confirm(`Are you sure you want to delete ${institution.name}? This action cannot be undone.`)) {
        router.delete(route('institutions.destroy', institution.id));
    }
};
</script>

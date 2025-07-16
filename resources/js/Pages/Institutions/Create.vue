<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Create Institution" />
        
        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <Link href="/dashboard" class="text-xl font-semibold text-gray-900">
                            {{ $page.props.app?.name || 'Laravel' }}
                        </Link>
                        <div class="hidden md:flex space-x-4">
                            <Link href="/dashboard" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                Dashboard
                            </Link>
                            <Link href="/institutions" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                Institutions
                            </Link>
                        </div>
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
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">
                                Create New Institution
                            </h2>
                            <Link
                                :href="route('institutions.index')"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                ← Back to Institutions
                            </Link>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="id" class="block text-sm font-medium text-gray-700">
                                    Institution ID *
                                </label>
                                <input
                                    id="id"
                                    v-model="form.id"
                                    type="text"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="e.g., mku, uon, kyu"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Unique identifier for the institution (lowercase, no spaces)
                                </p>
                                <div v-if="form.errors.id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.id }}
                                </div>
                            </div>

                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Institution Name *
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="e.g., Mount Kenya University"
                                    required
                                />
                                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">
                                    Address
                                </label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="3"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Institution physical address"
                                ></textarea>
                                <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <div>
                                <label for="contact_information" class="block text-sm font-medium text-gray-700">
                                    Contact Information
                                </label>
                                <input
                                    id="contact_information"
                                    v-model="form.contact_information"
                                    type="text"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Phone, email, or website"
                                />
                                <div v-if="form.errors.contact_information" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.contact_information }}
                                </div>
                            </div>

                            <div>
                                <label for="plan" class="block text-sm font-medium text-gray-700">
                                    Plan
                                </label>
                                <select
                                    id="plan"
                                    v-model="form.plan"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="">Select a plan</option>
                                    <option value="Basic">Basic</option>
                                    <option value="Standard">Standard</option>
                                    <option value="Premium">Premium</option>
                                    <option value="Enterprise">Enterprise</option>
                                </select>
                                <div v-if="form.errors.plan" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.plan }}
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                                <Link
                                    :href="route('institutions.index')"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    :class="{ 'opacity-50': form.processing }"
                                >
                                    {{ form.processing ? 'Creating...' : 'Create Institution' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    id: '',
    name: '',
    address: '',
    contact_information: '',
    plan: 'Basic',
});

const submit = () => {
    form.post(route('institutions.store'));
};
</script>
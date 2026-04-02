<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Edit Institution" />

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
                            <Link href="/institutions" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
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
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900">Edit Institution: {{ institution.name }}</h2>
                            <Link :href="route('institutions.index')" class="text-gray-600 hover:text-gray-900"> â† Back to Institutions </Link>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700"> Institution Name * </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                    required
                                />
                                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700"> Address </label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                ></textarea>
                                <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <div>
                                <label for="contact_information" class="block text-sm font-medium text-gray-700"> Contact Information </label>
                                <input
                                    id="contact_information"
                                    v-model="form.contact_information"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                />
                                <div v-if="form.errors.contact_information" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.contact_information }}
                                </div>
                            </div>

                            <div>
                                <label for="plan" class="block text-sm font-medium text-gray-700"> Plan </label>
                                <select
                                    id="plan"
                                    v-model="form.plan"
                                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
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

                            <div class="flex items-center justify-end space-x-4 border-t pt-6">
                                <Link
                                    :href="route('institutions.index')"
                                    class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-700"
                                    :class="{ 'opacity-50': form.processing }"
                                >
                                    {{ form.processing ? 'Updating...' : 'Update Institution' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    institution: Object,
});

const form = useForm({
    name: props.institution.name,
    address: props.institution.address,
    contact_information: props.institution.contact_information,
    plan: props.institution.plan,
});

const submit = () => {
    form.put(route('institutions.update', props.institution.id));
};
</script>


<template>
    <div class="min-h-screen bg-gray-100">
        <Head title="Create User" />

        <!-- Navigation -->
        <nav class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center space-x-8">
                        <Link href="/dashboard" class="text-xl font-semibold text-gray-900">
                            {{ $page.props.app?.name || 'Graduate Tracking' }}
                        </Link>
                        <div class="hidden space-x-4 md:flex">
                            <Link href="/dashboard" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                                Dashboard
                            </Link>
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
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900">Create New User</h2>
                            <Link
                                :href="route('users.index')"
                                class="inline-flex items-center rounded-md border border-transparent bg-gray-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                            >
                                Back to Users
                            </Link>
                        </div>

                        <form @submit.prevent="submit" enctype="multipart/form-data">
                            <!-- Basic Information -->
                            <div class="mb-8">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Basic Information</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                        <input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            :class="{ 'border-red-500': form.errors.name }"
                                            required
                                        />
                                        <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.name }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            :class="{ 'border-red-500': form.errors.email }"
                                            required
                                        />
                                        <div v-if="form.errors.email" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.email }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                        <input
                                            id="phone"
                                            v-model="form.phone"
                                            type="tel"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            :class="{ 'border-red-500': form.errors.phone }"
                                        />
                                        <div v-if="form.errors.phone" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.phone }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                                        <select
                                            id="role"
                                            v-model="form.role"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            :class="{ 'border-red-500': form.errors.role }"
                                            required
                                        >
                                            <option value="">Select a role</option>
                                            <option v-for="role in roles" :key="role.id" :value="role.name">
                                                {{ role.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.role" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.role }}
                                        </div>
                                    </div>

                                    <div v-if="$page.props.auth.user.roles && $page.props.auth.user.roles[0].name === 'super-admin'">
                                        <label for="institution_id" class="block text-sm font-medium text-gray-700">Institution</label>
                                        <select
                                            id="institution_id"
                                            v-model="form.institution_id"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            :class="{ 'border-red-500': form.errors.institution_id }"
                                        >
                                            <option value="">Select an institution</option>
                                            <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                                {{ institution.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.institution_id" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.institution_id }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="avatar" class="block text-sm font-medium text-gray-700">Profile Picture</label>
                                        <input
                                            id="avatar"
                                            @change="handleAvatarChange"
                                            type="file"
                                            accept="image/*"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            :class="{ 'border-red-500': form.errors.avatar }"
                                        />
                                        <div v-if="form.errors.avatar" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.avatar }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-8">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Password</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                        <input
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            :class="{ 'border-red-500': form.errors.password }"
                                            required
                                        />
                                        <div v-if="form.errors.password" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.password }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                        <input
                                            id="password_confirmation"
                                            v-model="form.password_confirmation"
                                            type="password"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Preferences -->
                            <div class="mb-8">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Preferences</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                        <select
                                            id="timezone"
                                            v-model="form.timezone"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                        >
                                            <option value="UTC">UTC</option>
                                            <option value="America/New_York">Eastern Time</option>
                                            <option value="America/Chicago">Central Time</option>
                                            <option value="America/Denver">Mountain Time</option>
                                            <option value="America/Los_Angeles">Pacific Time</option>
                                            <option value="Europe/London">London</option>
                                            <option value="Europe/Paris">Paris</option>
                                            <option value="Asia/Tokyo">Tokyo</option>
                                            <option value="Australia/Sydney">Sydney</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                                        <select
                                            id="language"
                                            v-model="form.language"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                        >
                                            <option value="en">English</option>
                                            <option value="es">Spanish</option>
                                            <option value="fr">French</option>
                                            <option value="de">German</option>
                                            <option value="ja">Japanese</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Data -->
                            <div class="mb-8">
                                <h3 class="mb-4 text-lg font-medium text-gray-900">Additional Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                                        <textarea
                                            id="bio"
                                            v-model="form.profile_data.bio"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                            placeholder="Brief description about the user..."
                                        ></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div>
                                            <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                                            <input
                                                id="location"
                                                v-model="form.profile_data.location"
                                                type="text"
                                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                                placeholder="City, Country"
                                            />
                                        </div>

                                        <div>
                                            <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                            <input
                                                id="website"
                                                v-model="form.profile_data.website"
                                                type="url"
                                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500"
                                                placeholder="https://example.com"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-3">
                                <Link
                                    :href="route('users.index')"
                                    class="inline-flex items-center rounded-md border border-transparent bg-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition duration-150 ease-in-out hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-700 disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Creating...</span>
                                    <span v-else>Create User</span>
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
    roles: Array,
    institutions: Array,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: '',
    institution_id: '',
    avatar: null,
    timezone: 'UTC',
    language: 'en',
    profile_data: {
        bio: '',
        location: '',
        website: '',
    },
    preferences: {
        notifications: {
            email: true,
            sms: false,
            push: true,
        },
        privacy: {
            profile_visible: true,
            show_email: false,
            show_phone: false,
        },
        dashboard: {
            theme: 'light',
            compact_mode: false,
        },
    },
});

const handleAvatarChange = (event) => {
    form.avatar = event.target.files[0];
};

const submit = () => {
    form.post(route('users.store'));
};
</script>


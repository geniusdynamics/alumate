<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <Head title="Join Alumate" />

        <!-- Header -->
        <header class="bg-white/80 shadow-sm backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-6">
                    <Link href="/" class="flex items-center">
                        <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                ></path>
                            </svg>
                        </div>
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-xl font-bold text-transparent">Alumate</span>
                    </Link>
                    <Link
                        :href="route('login')"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:text-gray-900"
                    >
                        Already have an account? Sign In
                    </Link>
                </div>
            </div>
        </header>

        <div class="flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Join the Alumni Network</h2>
                    <p class="mt-2 text-sm text-gray-600">Choose your role to get started with Alumate</p>
                </div>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
                <div class="border border-white/20 bg-white/80 px-4 py-8 shadow-xl backdrop-blur-sm sm:rounded-2xl sm:px-10">
                    <!-- Role Selection -->
                    <div v-if="!selectedRole" class="space-y-6">
                        <div class="mb-8 text-center">
                            <h3 class="mb-2 text-lg font-medium text-gray-900">I want to join as:</h3>
                            <p class="text-sm text-gray-600">Select the option that best describes you</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <!-- Alumni/Graduate Option -->
                            <button
                                @click="selectRole('graduate')"
                                class="group relative rounded-xl border-2 border-orange-200 bg-gradient-to-br from-orange-50 to-red-50 p-6 text-left transition-all duration-200 hover:border-orange-300 hover:shadow-lg"
                            >
                                <div class="flex items-center">
                                    <div
                                        class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-red-500"
                                    >
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 group-hover:text-orange-700">Alumni/Graduate</h4>
                                        <p class="mt-1 text-sm text-gray-600">I'm a graduate looking for career opportunities and networking</p>
                                    </div>
                                    <svg
                                        class="h-5 w-5 text-orange-500 group-hover:text-orange-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </button>

                            <!-- Employer Option -->
                            <button
                                @click="selectRole('employer')"
                                class="group relative rounded-xl border-2 border-purple-200 bg-gradient-to-br from-purple-50 to-indigo-50 p-6 text-left transition-all duration-200 hover:border-purple-300 hover:shadow-lg"
                            >
                                <div class="flex items-center">
                                    <div
                                        class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-500"
                                    >
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 group-hover:text-purple-700">Employer</h4>
                                        <p class="mt-1 text-sm text-gray-600">I represent a company looking to hire talented graduates</p>
                                    </div>
                                    <svg
                                        class="h-5 w-5 text-purple-500 group-hover:text-purple-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </button>

                            <!-- Institution Option -->
                            <button
                                @click="selectRole('institution')"
                                class="group relative rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-6 text-left transition-all duration-200 hover:border-green-300 hover:shadow-lg"
                            >
                                <div class="flex items-center">
                                    <div
                                        class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-500"
                                    >
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 group-hover:text-green-700">Educational Institution</h4>
                                        <p class="mt-1 text-sm text-gray-600">I represent an institution wanting to track our graduates</p>
                                    </div>
                                    <svg
                                        class="h-5 w-5 text-green-500 group-hover:text-green-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Registration Form -->
                    <div v-if="selectedRole" class="space-y-6">
                        <!-- Back Button -->
                        <div class="mb-6 flex items-center">
                            <button
                                @click="selectedRole = null"
                                class="flex items-center text-sm text-gray-600 transition duration-150 ease-in-out hover:text-gray-900"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Back to role selection
                            </button>
                        </div>

                        <!-- Role Header -->
                        <div class="mb-6 text-center">
                            <div class="mb-4 flex justify-center">
                                <div :class="getRoleHeaderClass()">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getRoleIcon()"></path>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ getRoleTitle() }}</h3>
                            <p class="text-sm text-gray-600">{{ getRoleDescription() }}</p>
                        </div>

                        <!-- Redirect to Employer Registration -->
                        <div v-if="selectedRole === 'employer'" class="text-center">
                            <div class="mb-6 rounded-xl border border-purple-200 bg-purple-50 p-6">
                                <div class="mb-4 flex items-center justify-center">
                                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <h4 class="mb-2 text-lg font-semibold text-purple-900">Enhanced Registration Process</h4>
                                <p class="mb-4 text-sm text-purple-700">
                                    Employers require additional verification and company information. You'll be redirected to our comprehensive
                                    employer registration form.
                                </p>
                                <Link
                                    :href="route('employer.register')"
                                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 px-6 py-3 font-medium text-white transition duration-150 ease-in-out hover:from-purple-600 hover:to-indigo-600"
                                >
                                    Continue to Employer Registration
                                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <!-- Standard Registration Form -->
                        <form v-else @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Full Name *</label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-150 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        placeholder="Enter your full name"
                                    />
                                    <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email Address *</label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-150 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                        required
                                        autocomplete="username"
                                        placeholder="Enter your email address"
                                    />
                                    <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.email }}
                                    </div>
                                </div>

                                <!-- Institution Selection for Graduates -->
                                <div v-if="selectedRole === 'graduate'">
                                    <label for="institution" class="mb-2 block text-sm font-medium text-gray-700">Institution</label>
                                    <select
                                        id="institution"
                                        v-model="form.institution_id"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-150 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="">Select your institution (optional)</option>
                                        <option value="tech-institute">Tech Institute of Excellence</option>
                                        <option value="business-college">Business Leadership College</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">You can update this later in your profile</p>
                                </div>

                                <!-- Institution Name for Institution Admins -->
                                <div v-if="selectedRole === 'institution'">
                                    <label for="institution_name" class="mb-2 block text-sm font-medium text-gray-700">Institution Name *</label>
                                    <input
                                        id="institution_name"
                                        v-model="form.institution_name"
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-150 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                        required
                                        placeholder="Enter your institution name"
                                    />
                                    <div v-if="form.errors.institution_name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.institution_name }}
                                    </div>
                                </div>

                                <div>
                                    <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Password *</label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-150 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Create a secure password"
                                    />
                                    <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.password }}
                                    </div>
                                </div>

                                <div>
                                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">Confirm Password *</label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition duration-150 ease-in-out focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Confirm your password"
                                    />
                                    <div v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.password_confirmation }}
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="rounded-lg bg-gray-50 p-4">
                                <label class="flex items-start">
                                    <input
                                        type="checkbox"
                                        v-model="form.terms_accepted"
                                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    <span class="ml-3 text-sm text-gray-700">
                                        I agree to the
                                        <Link href="/terms" class="font-medium text-indigo-600 hover:text-indigo-500">Terms of Service</Link> and
                                        <Link href="/privacy" class="font-medium text-indigo-600 hover:text-indigo-500">Privacy Policy</Link> *
                                    </span>
                                </label>
                                <div v-if="form.errors.terms_accepted" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.terms_accepted }}
                                </div>
                            </div>

                            <!-- Approval Notice -->
                            <div v-if="selectedRole !== 'graduate'" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Approval Required</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p v-if="selectedRole === 'institution'">
                                                Your institution account will be pending approval by our super admin team. You'll receive an email
                                                notification once your account is verified.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <Link :href="route('login')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                                    Already have an account? Sign in
                                </Link>

                                <button
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-lg border border-transparent bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-3 text-sm font-semibold tracking-wide text-white shadow-lg transition duration-150 ease-in-out hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    <span v-if="form.processing">Creating Account...</span>
                                    <span v-else>Create Account</span>
                                    <svg v-if="!form.processing" class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
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
import { ref } from 'vue';

const selectedRole = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    institution_id: '',
    institution_name: '',
    terms_accepted: false,
});

const selectRole = (role) => {
    selectedRole.value = role;
    form.role = role;
};

const getRoleHeaderClass = () => {
    const classes = {
        graduate: 'w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center',
        employer: 'w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-xl flex items-center justify-center',
        institution: 'w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center',
    };
    return classes[selectedRole.value] || classes.graduate;
};

const getRoleIcon = () => {
    const icons = {
        graduate: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        employer:
            'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6',
        institution:
            'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    };
    return icons[selectedRole.value] || icons.graduate;
};

const getRoleTitle = () => {
    const titles = {
        graduate: 'Alumni/Graduate Registration',
        employer: 'Employer Registration',
        institution: 'Institution Registration',
    };
    return titles[selectedRole.value] || 'Registration';
};

const getRoleDescription = () => {
    const descriptions = {
        graduate: 'Join our alumni network to discover career opportunities and connect with peers',
        employer: 'Register your company to access our talented graduate pool',
        institution: 'Register your institution to track graduate success and manage alumni relations',
    };
    return descriptions[selectedRole.value] || 'Complete your registration';
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>


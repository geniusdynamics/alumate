<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <Head title="Join Alumate" />

        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-md shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <Link href="/" class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Alumate</span>
                    </Link>
                    <Link
                        :href="route('login')"
                        class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg text-sm font-medium transition duration-150 ease-in-out"
                    >
                        Already have an account? Sign In
                    </Link>
                </div>
            </div>
        </header>

        <div class="flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">
                        Join the Alumni Network
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Choose your role to get started with Alumate
                    </p>
                </div>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
                <div class="bg-white/80 backdrop-blur-sm py-8 px-4 shadow-xl sm:rounded-2xl sm:px-10 border border-white/20">

                    <!-- Role Selection -->
                    <div v-if="!selectedRole" class="space-y-6">
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">I want to join as:</h3>
                            <p class="text-sm text-gray-600">Select the option that best describes you</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <!-- Alumni/Graduate Option -->
                            <button
                                @click="selectRole('graduate')"
                                class="group relative p-6 bg-gradient-to-br from-orange-50 to-red-50 border-2 border-orange-200 rounded-xl hover:border-orange-300 hover:shadow-lg transition-all duration-200 text-left"
                            >
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 group-hover:text-orange-700">Alumni/Graduate</h4>
                                        <p class="text-sm text-gray-600 mt-1">I'm a graduate looking for career opportunities and networking</p>
                                    </div>
                                    <svg class="w-5 h-5 text-orange-500 group-hover:text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </button>

                            <!-- Employer Option -->
                            <button
                                @click="selectRole('employer')"
                                class="group relative p-6 bg-gradient-to-br from-purple-50 to-indigo-50 border-2 border-purple-200 rounded-xl hover:border-purple-300 hover:shadow-lg transition-all duration-200 text-left"
                            >
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-xl flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 group-hover:text-purple-700">Employer</h4>
                                        <p class="text-sm text-gray-600 mt-1">I represent a company looking to hire talented graduates</p>
                                    </div>
                                    <svg class="w-5 h-5 text-purple-500 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </button>

                            <!-- Institution Option -->
                            <button
                                @click="selectRole('institution')"
                                class="group relative p-6 bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl hover:border-green-300 hover:shadow-lg transition-all duration-200 text-left"
                            >
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 group-hover:text-green-700">Educational Institution</h4>
                                        <p class="text-sm text-gray-600 mt-1">I represent an institution wanting to track our graduates</p>
                                    </div>
                                    <svg class="w-5 h-5 text-green-500 group-hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Registration Form -->
                    <div v-if="selectedRole" class="space-y-6">
                        <!-- Back Button -->
                        <div class="flex items-center mb-6">
                            <button
                                @click="selectedRole = null"
                                class="flex items-center text-sm text-gray-600 hover:text-gray-900 transition duration-150 ease-in-out"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Back to role selection
                            </button>
                        </div>

                        <!-- Role Header -->
                        <div class="text-center mb-6">
                            <div class="flex justify-center mb-4">
                                <div :class="getRoleHeaderClass()">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getRoleIcon()"></path>
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ getRoleTitle() }}</h3>
                            <p class="text-sm text-gray-600">{{ getRoleDescription() }}</p>
                        </div>

                        <!-- Redirect to Employer Registration -->
                        <div v-if="selectedRole === 'employer'" class="text-center">
                            <div class="bg-purple-50 border border-purple-200 rounded-xl p-6 mb-6">
                                <div class="flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-purple-900 mb-2">Enhanced Registration Process</h4>
                                <p class="text-sm text-purple-700 mb-4">
                                    Employers require additional verification and company information.
                                    You'll be redirected to our comprehensive employer registration form.
                                </p>
                                <Link
                                    :href="route('employer.register')"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-medium rounded-lg hover:from-purple-600 hover:to-indigo-600 transition duration-150 ease-in-out"
                                >
                                    Continue to Employer Registration
                                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <!-- Standard Registration Form -->
                        <form v-else @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        placeholder="Enter your full name"
                                    />
                                    <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out"
                                        required
                                        autocomplete="username"
                                        placeholder="Enter your email address"
                                    />
                                    <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">
                                        {{ form.errors.email }}
                                    </div>
                                </div>

                                <!-- Institution Selection for Graduates -->
                                <div v-if="selectedRole === 'graduate'">
                                    <label for="institution" class="block text-sm font-medium text-gray-700 mb-2">Institution</label>
                                    <select
                                        id="institution"
                                        v-model="form.institution_id"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out"
                                    >
                                        <option value="">Select your institution (optional)</option>
                                        <option value="tech-institute">Tech Institute of Excellence</option>
                                        <option value="business-college">Business Leadership College</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">You can update this later in your profile</p>
                                </div>

                                <!-- Institution Name for Institution Admins -->
                                <div v-if="selectedRole === 'institution'">
                                    <label for="institution_name" class="block text-sm font-medium text-gray-700 mb-2">Institution Name *</label>
                                    <input
                                        id="institution_name"
                                        v-model="form.institution_name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out"
                                        required
                                        placeholder="Enter your institution name"
                                    />
                                    <div v-if="form.errors.institution_name" class="text-sm text-red-600 mt-1">
                                        {{ form.errors.institution_name }}
                                    </div>
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Create a secure password"
                                    />
                                    <div v-if="form.errors.password" class="text-sm text-red-600 mt-1">
                                        {{ form.errors.password }}
                                    </div>
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out"
                                        required
                                        autocomplete="new-password"
                                        placeholder="Confirm your password"
                                    />
                                    <div v-if="form.errors.password_confirmation" class="text-sm text-red-600 mt-1">
                                        {{ form.errors.password_confirmation }}
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <label class="flex items-start">
                                    <input
                                        type="checkbox"
                                        v-model="form.terms_accepted"
                                        class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    <span class="ml-3 text-sm text-gray-700">
                                        I agree to the <Link href="/terms" class="text-indigo-600 hover:text-indigo-500 font-medium">Terms of Service</Link>
                                        and <Link href="/privacy" class="text-indigo-600 hover:text-indigo-500 font-medium">Privacy Policy</Link> *
                                    </span>
                                </label>
                                <div v-if="form.errors.terms_accepted" class="text-sm text-red-600 mt-1">
                                    {{ form.errors.terms_accepted }}
                                </div>
                            </div>

                            <!-- Approval Notice -->
                            <div v-if="selectedRole !== 'graduate'" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Approval Required</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p v-if="selectedRole === 'institution'">
                                                Your institution account will be pending approval by our super admin team.
                                                You'll receive an email notification once your account is verified.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <Link
                                    :href="route('login')"
                                    class="text-sm text-gray-600 hover:text-gray-900 font-medium"
                                >
                                    Already have an account? Sign in
                                </Link>

                                <button
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 border border-transparent rounded-lg font-semibold text-sm text-white tracking-wide hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition ease-in-out duration-150 shadow-lg"
                                >
                                    <span v-if="form.processing">Creating Account...</span>
                                    <span v-else>Create Account</span>
                                    <svg v-if="!form.processing" class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

<script setup>
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
        institution: 'w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center'
    };
    return classes[selectedRole.value] || classes.graduate;
};

const getRoleIcon = () => {
    const icons = {
        graduate: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        employer: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6',
        institution: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
    };
    return icons[selectedRole.value] || icons.graduate;
};

const getRoleTitle = () => {
    const titles = {
        graduate: 'Alumni/Graduate Registration',
        employer: 'Employer Registration',
        institution: 'Institution Registration'
    };
    return titles[selectedRole.value] || 'Registration';
};

const getRoleDescription = () => {
    const descriptions = {
        graduate: 'Join our alumni network to discover career opportunities and connect with peers',
        employer: 'Register your company to access our talented graduate pool',
        institution: 'Register your institution to track graduate success and manage alumni relations'
    };
    return descriptions[selectedRole.value] || 'Complete your registration';
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Alumate - Alumni Management Platform" />

        <div class="relative min-h-screen">
            <!-- Header -->
            <header class="sticky top-0 z-50 bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between py-6">
                        <div class="flex items-center">
                            <div class="flex flex-shrink-0 items-center">
                                <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                        ></path>
                                    </svg>
                                </div>
                                <h1 class="text-2xl font-bold text-gray-900">Alumate</h1>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700"
                            >
                                Go to Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 transition duration-150 ease-in-out hover:text-gray-900"
                                >
                                    Sign In
                                </Link>
                                <Link
                                    :href="route('register')"
                                    class="rounded-md bg-blue-600 px-6 py-2 text-sm font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700"
                                >
                                    Get Started
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <!-- Hero Section -->
                <div class="mb-20 text-center">
                    <div class="mb-8">
                        <span class="mb-6 inline-flex items-center rounded-full bg-blue-100 px-4 py-2 text-sm font-medium text-blue-800">
                            🎓 Connecting Alumni with Opportunities
                        </span>
                    </div>
                    <h2 class="mb-6 text-5xl font-bold text-gray-900 sm:text-6xl">
                        Empower Your
                        <span class="text-blue-600">Alumni Network</span>
                    </h2>
                    <p class="mx-auto mt-6 max-w-3xl text-xl leading-relaxed text-gray-600">
                        The comprehensive platform that bridges the gap between educational institutions, graduates, and employers. Track career
                        progress, facilitate meaningful connections, and unlock opportunities for everyone.
                    </p>
                    <div class="mt-10 flex flex-col justify-center gap-4 sm:flex-row">
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-8 py-4 text-lg font-medium text-white transition duration-150 ease-in-out hover:bg-blue-700"
                        >
                            Start Your Journey
                            <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </Link>
                        <button
                            @click="scrollToFeatures"
                            class="inline-flex items-center rounded-md border-2 border-gray-300 bg-white px-8 py-4 text-lg font-medium text-gray-700 transition duration-150 ease-in-out hover:bg-gray-50"
                        >
                            Learn More
                            <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Value Proposition for Each User Type -->
                <div id="features" class="mb-20">
                    <div class="mb-16 text-center">
                        <h3 class="mb-4 text-3xl font-bold text-gray-900">Designed for Everyone in the Alumni Ecosystem</h3>
                        <p class="mx-auto max-w-3xl text-xl text-gray-600">
                            Whether you're an alumni looking for opportunities, an institution tracking success, or an employer seeking talent,
                            Alumate has the tools you need.
                        </p>
                    </div>

                    <div class="mb-16 grid grid-cols-1 gap-12 lg:grid-cols-2">
                        <!-- For Alumni -->
                        <div class="rounded-2xl border border-orange-100 bg-gradient-to-br from-orange-50 to-red-50 p-8">
                            <div class="mb-6 flex items-center">
                                <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-red-500">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        ></path>
                                    </svg>
                                </div>
                                <h4 class="text-2xl font-bold text-gray-900">For Alumni</h4>
                            </div>
                            <p class="mb-6 text-lg text-gray-700">
                                Take control of your career journey with tools designed to help you grow, connect, and succeed.
                            </p>
                            <ul class="mb-8 space-y-3">
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-orange-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Access exclusive job opportunities from verified employers</span>
                                </li>
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-orange-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Track your career progress and showcase achievements</span>
                                </li>
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-orange-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Connect with classmates and expand your professional network</span>
                                </li>
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-orange-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Get personalized career recommendations and mentorship</span>
                                </li>
                            </ul>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center rounded-lg bg-gradient-to-r from-orange-500 to-red-500 px-6 py-3 font-medium text-white transition duration-150 ease-in-out hover:from-orange-600 hover:to-red-600"
                            >
                                Join as Alumni
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </Link>
                        </div>

                        <!-- For Employers -->
                        <div class="rounded-2xl border border-purple-100 bg-gradient-to-br from-purple-50 to-indigo-50 p-8">
                            <div class="mb-6 flex items-center">
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
                                <h4 class="text-2xl font-bold text-gray-900">For Employers</h4>
                            </div>
                            <p class="mb-6 text-lg text-gray-700">
                                Tap into a vast network of skilled graduates and find the perfect candidates for your organization.
                            </p>
                            <ul class="mb-8 space-y-3">
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-purple-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Access a curated pool of verified, skilled graduates</span>
                                </li>
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-purple-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Post targeted job opportunities with smart matching</span>
                                </li>
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-purple-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Streamline recruitment with integrated application management</span>
                                </li>
                                <li class="flex items-start">
                                    <svg
                                        class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-purple-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Build long-term partnerships with educational institutions</span>
                                </li>
                            </ul>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 px-6 py-3 font-medium text-white transition duration-150 ease-in-out hover:from-purple-600 hover:to-indigo-600"
                            >
                                Join as Employer
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <!-- For Institutions -->
                    <div class="rounded-2xl border border-green-100 bg-gradient-to-br from-green-50 to-emerald-50 p-8">
                        <div class="mb-6 flex items-center">
                            <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-500">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                    ></path>
                                </svg>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900">For Educational Institutions</h4>
                        </div>
                        <p class="mb-6 text-lg text-gray-700">
                            Strengthen your institution's reputation by tracking graduate success and maintaining lifelong connections.
                        </p>
                        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <svg class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Track graduate employment rates and career progression</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Generate comprehensive reports for accreditation</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Facilitate alumni networking and engagement</span>
                                </li>
                            </ul>
                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <svg class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Connect with industry partners for better outcomes</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Improve curriculum based on industry feedback</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="mr-3 mt-1 h-5 w-5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700">Showcase success stories to attract new students</span>
                                </li>
                            </ul>
                        </div>
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center rounded-lg bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-3 font-medium text-white transition duration-150 ease-in-out hover:from-green-600 hover:to-emerald-600"
                        >
                            Register Your Institution
                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Development Access -->
                <div class="mb-8 rounded-2xl border border-gray-200 bg-white/80 p-8 shadow-xl backdrop-blur-sm">
                    <div class="mb-6 flex items-center">
                        <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-gray-600 to-gray-800">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                ></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900">Development Access</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <!-- Demo Accounts -->
                        <div>
                            <h3 class="mb-4 flex items-center text-lg font-semibold text-gray-900">
                                <svg class="mr-2 h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                    ></path>
                                </svg>
                                Demo Accounts
                            </h3>
                            <div class="space-y-3">
                                <div
                                    class="flex items-center justify-between rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-blue-100 p-4"
                                >
                                    <div>
                                        <p class="font-semibold text-blue-900">Super Admin</p>
                                        <p class="font-mono text-sm text-blue-700">admin@system.com</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="rounded bg-blue-200 px-2 py-1 font-mono text-xs text-blue-600">password</p>
                                        <Link :href="route('login')" class="text-sm font-medium text-blue-600 hover:text-blue-800">Login →</Link>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-green-100 p-4"
                                >
                                    <div>
                                        <p class="font-semibold text-green-900">Institution Admin</p>
                                        <p class="font-mono text-sm text-green-700">admin@tech-institute.edu</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="rounded bg-green-200 px-2 py-1 font-mono text-xs text-green-600">password</p>
                                        <Link :href="route('login')" class="text-sm font-medium text-green-600 hover:text-green-800">Login →</Link>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between rounded-xl border border-orange-200 bg-gradient-to-r from-orange-50 to-orange-100 p-4"
                                >
                                    <div>
                                        <p class="font-semibold text-orange-900">Graduate</p>
                                        <p class="font-mono text-sm text-orange-700">john.smith@student.edu</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="rounded bg-orange-200 px-2 py-1 font-mono text-xs text-orange-600">password</p>
                                        <Link :href="route('login')" class="text-sm font-medium text-orange-600 hover:text-orange-800">Login →</Link>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-purple-100 p-4"
                                >
                                    <div>
                                        <p class="font-semibold text-purple-900">Employer</p>
                                        <p class="font-mono text-sm text-purple-700">techcorp@company.com</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="rounded bg-purple-200 px-2 py-1 font-mono text-xs text-purple-600">password</p>
                                        <Link :href="route('login')" class="text-sm font-medium text-purple-600 hover:text-purple-800">Login →</Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div>
                            <h3 class="mb-4 flex items-center text-lg font-semibold text-gray-900">
                                <svg class="mr-2 h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                                    ></path>
                                </svg>
                                Quick Links
                            </h3>
                            <div class="space-y-3">
                                <Link
                                    :href="route('super-admin.dashboard')"
                                    class="block rounded-xl border border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 p-4 transition-all duration-200 hover:from-gray-100 hover:to-gray-200"
                                >
                                    <span class="flex items-center font-semibold text-gray-900">
                                        <svg class="mr-2 h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                            ></path>
                                        </svg>
                                        Super Admin Dashboard
                                    </span>
                                    <span class="mt-1 block text-sm text-gray-600">System management & analytics</span>
                                </Link>
                                <Link
                                    :href="route('login')"
                                    class="block rounded-xl border border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 p-4 transition-all duration-200 hover:from-gray-100 hover:to-gray-200"
                                >
                                    <span class="flex items-center font-semibold text-gray-900">
                                        <svg class="mr-2 h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                                            ></path>
                                        </svg>
                                        Login Page
                                    </span>
                                    <span class="mt-1 block text-sm text-gray-600">Sign in to your account</span>
                                </Link>
                                <Link
                                    :href="route('register')"
                                    class="block rounded-xl border border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 p-4 transition-all duration-200 hover:from-gray-100 hover:to-gray-200"
                                >
                                    <span class="flex items-center font-semibold text-gray-900">
                                        <svg class="mr-2 h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                            ></path>
                                        </svg>
                                        Registration
                                    </span>
                                    <span class="mt-1 block text-sm text-gray-600">Create a new account</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Server Status -->
                <div class="rounded-xl border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 p-6 shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-4 w-4 animate-pulse rounded-full bg-green-400 shadow-lg"></div>
                        </div>
                        <div class="ml-4">
                            <p class="flex items-center text-lg font-semibold text-green-800">
                                <svg class="mr-2 h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"
                                    ></path>
                                </svg>
                                Development server is running
                            </p>
                            <p class="mt-1 font-mono text-sm text-green-600">Laravel: http://127.0.0.1:8080 | Vite: http://127.0.0.1:5176</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

const scrollToFeatures = () => {
    const element = document.getElementById('features');
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

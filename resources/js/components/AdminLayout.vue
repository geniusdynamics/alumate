<template>
    <div class="min-h-screen bg-gray-900 text-white">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 border-r border-gray-700 bg-gray-800">
            <!-- Logo -->
            <div class="flex h-16 items-center justify-center border-b border-gray-700 px-4">
                <div class="flex items-center space-x-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500">
                        <span class="text-sm font-bold text-white">A</span>
                    </div>
                    <div>
                        <div class="font-semibold text-white">{{ appName }}</div>
                        <div class="text-xs text-gray-400">{{ userRole }}</div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3">
                <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Navigation</div>
                <div class="space-y-1">
                    <NavLink v-for="item in navigationItems" :key="item.name" :href="item.href" :active="item.active" :icon="item.icon">
                        {{ item.name }}
                    </NavLink>
                </div>
            </nav>

            <!-- User Menu -->
            <div class="absolute bottom-0 left-0 right-0 border-t border-gray-700 p-4">
                <div class="mb-3 flex items-center space-x-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-600">
                        <UserIcon class="h-5 w-5 text-gray-300" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-white">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="truncate text-xs text-gray-400">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
                <button
                    @click="logout"
                    class="flex w-full items-center space-x-2 rounded-md px-3 py-2 text-sm text-gray-300 transition-colors hover:bg-gray-700 hover:text-white"
                >
                    <ArrowRightOnRectangleIcon class="h-4 w-4" />
                    <span>Logout</span>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="pl-64">
            <!-- Top Bar -->
            <header class="flex h-16 items-center justify-between border-b border-gray-700 bg-gray-800 px-6">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-white">{{ pageTitle }}</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="rounded-md p-2 text-gray-400 hover:text-white">
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </button>
                    <button class="rounded-md p-2 text-gray-400 hover:text-white">
                        <BellIcon class="h-5 w-5" />
                    </button>
                    <button class="rounded-md p-2 text-gray-400 hover:text-white">
                        <Cog6ToothIcon class="h-5 w-5" />
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import NavLink from '@/Components/NavLink.vue';
import { ArrowRightOnRectangleIcon, BellIcon, Cog6ToothIcon, MagnifyingGlassIcon, UserIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';

defineProps({
    appName: {
        type: String,
        default: 'Alumate',
    },
    userRole: {
        type: String,
        default: 'Admin Panel',
    },
    pageTitle: {
        type: String,
        required: true,
    },
    navigationItems: {
        type: Array,
        required: true,
    },
});

const logout = () => {
    router.post(route('logout'));
};
</script>












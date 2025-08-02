<template>
    <div class="min-h-screen bg-gray-900 text-white">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 border-r border-gray-700">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 px-4 border-b border-gray-700">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">A</span>
                    </div>
                    <div>
                        <div class="text-white font-semibold">{{ appName }}</div>
                        <div class="text-xs text-gray-400">{{ userRole }}</div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                    Navigation
                </div>
                <div class="space-y-1">
                    <NavLink
                        v-for="item in navigationItems"
                        :key="item.name"
                        :href="item.href"
                        :active="item.active"
                        :icon="item.icon"
                    >
                        {{ item.name }}
                    </NavLink>
                </div>
            </nav>

            <!-- User Menu -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700">
                <div class="flex items-center space-x-3 mb-3">
                    <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                        <UserIcon class="w-5 h-5 text-gray-300" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white truncate">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-xs text-gray-400 truncate">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
                <button
                    @click="logout"
                    class="w-full flex items-center space-x-2 px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-700 rounded-md transition-colors"
                >
                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                    <span>Logout</span>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="pl-64">
            <!-- Top Bar -->
            <header class="bg-gray-800 border-b border-gray-700 h-16 flex items-center justify-between px-6">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-white">{{ pageTitle }}</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 text-gray-400 hover:text-white rounded-md">
                        <MagnifyingGlassIcon class="w-5 h-5" />
                    </button>
                    <button class="p-2 text-gray-400 hover:text-white rounded-md">
                        <BellIcon class="w-5 h-5" />
                    </button>
                    <button class="p-2 text-gray-400 hover:text-white rounded-md">
                        <Cog6ToothIcon class="w-5 h-5" />
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
import { Link, router } from '@inertiajs/vue3'
import { 
    UserIcon, 
    ArrowRightOnRectangleIcon,
    MagnifyingGlassIcon,
    BellIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline'
import NavLink from '@/Components/NavLink.vue'

defineProps({
    appName: {
        type: String,
        default: 'Alumate'
    },
    userRole: {
        type: String,
        default: 'Admin Panel'
    },
    pageTitle: {
        type: String,
        required: true
    },
    navigationItems: {
        type: Array,
        required: true
    }
})

const logout = () => {
    router.post(route('logout'))
}
</script>

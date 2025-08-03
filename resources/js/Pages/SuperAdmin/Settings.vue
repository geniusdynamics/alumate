<template>
    <AdminLayout
        app-name="Alumate"
        user-role="Super Admin"
        page-title="System Settings"
        :navigation-items="navigationItems"
    >
        <Head title="System Settings" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">System Settings</h2>
            <p class="text-gray-400">Configure application settings, feature flags, and system preferences.</p>
        </div>

        <!-- Application Settings -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Application Info -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Application Information</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Application Name</span>
                            <span class="text-blue-400 font-semibold">{{ systemSettings.application_settings.app_name }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Version</span>
                            <span class="text-green-400 font-semibold">{{ systemSettings.application_settings.app_version }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Environment</span>
                            <span class="text-purple-400 font-semibold">{{ systemSettings.application_settings.environment }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Debug Mode</span>
                            <span :class="systemSettings.application_settings.debug_mode ? 'text-yellow-400' : 'text-green-400'" class="font-semibold">
                                {{ systemSettings.application_settings.debug_mode ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Flags -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Feature Flags</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="(enabled, feature) in systemSettings.feature_flags" :key="feature" class="flex justify-between items-center">
                            <span class="text-gray-300 capitalize">{{ feature.replace('_', ' ') }}</span>
                            <button 
                                @click="toggleFeature(feature)"
                                :class="enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700'"
                                class="px-3 py-1 rounded-md text-sm font-medium text-white transition-colors"
                            >
                                {{ enabled ? 'Enabled' : 'Disabled' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Configuration and Maintenance -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- System Configuration -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">System Configuration</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Max Upload Size</span>
                            <span class="text-blue-400 font-semibold">{{ systemSettings.system_configuration.max_upload_size }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Memory Limit</span>
                            <span class="text-green-400 font-semibold">{{ systemSettings.system_configuration.memory_limit }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Execution Time</span>
                            <span class="text-purple-400 font-semibold">{{ systemSettings.system_configuration.execution_time }}s</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Mode -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Maintenance Mode</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Status</span>
                            <span :class="systemSettings.maintenance_mode.enabled ? 'text-red-400' : 'text-green-400'" class="font-semibold">
                                {{ systemSettings.maintenance_mode.enabled ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Message</span>
                            <span class="text-gray-400 text-sm">{{ systemSettings.maintenance_mode.message }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Duration</span>
                            <span class="text-yellow-400 font-semibold">{{ systemSettings.maintenance_mode.estimated_duration }}</span>
                        </div>
                        <div class="pt-2">
                            <button 
                                @click="toggleMaintenanceMode"
                                :class="systemSettings.maintenance_mode.enabled ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700'"
                                class="w-full px-4 py-2 rounded-md text-sm font-medium text-white transition-colors"
                            >
                                {{ systemSettings.maintenance_mode.enabled ? 'Disable Maintenance' : 'Enable Maintenance' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Actions -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">System Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Clear Cache
                    </button>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Optimize Database
                    </button>
                    <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Generate Sitemap
                    </button>
                    <button class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Update Search Index
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Components/AdminLayout.vue'

const props = defineProps({
    systemSettings: Object,
})

const navigationItems = computed(() => [
    {
        name: 'Dashboard',
        href: route('super-admin.dashboard'),
        icon: 'HomeIcon',
        active: route().current('super-admin.dashboard')
    },
    {
        name: 'Analytics',
        href: route('super-admin.analytics'),
        icon: 'ChartBarIcon',
        active: route().current('super-admin.analytics')
    },
    {
        name: 'Users',
        href: route('super-admin.users'),
        icon: 'UsersIcon',
        active: route().current('super-admin.users')
    },
    {
        name: 'Content',
        href: route('super-admin.content'),
        icon: 'DocumentTextIcon',
        active: route().current('super-admin.content')
    },
    {
        name: 'Activity',
        href: route('super-admin.activity'),
        icon: 'ChartPieIcon',
        active: route().current('super-admin.activity')
    },
    {
        name: 'Database',
        href: route('super-admin.database'),
        icon: 'CircleStackIcon',
        active: route().current('super-admin.database')
    },
    {
        name: 'Security',
        href: route('security.dashboard'),
        icon: 'ShieldCheckIcon',
        active: route().current('security.dashboard')
    },
    {
        name: 'Performance',
        href: route('super-admin.performance'),
        icon: 'ChartBarIcon',
        active: route().current('super-admin.performance')
    },
    {
        name: 'Notifications',
        href: route('super-admin.notifications'),
        icon: 'BellIcon',
        active: route().current('super-admin.notifications')
    },
    {
        name: 'Settings',
        href: route('super-admin.settings'),
        icon: 'CogIcon',
        active: route().current('super-admin.settings')
    }
])

const toggleFeature = (feature) => {
    // This would make an API call to toggle the feature
    console.log(`Toggling feature: ${feature}`)
}

const toggleMaintenanceMode = () => {
    // This would make an API call to toggle maintenance mode
    console.log('Toggling maintenance mode')
}
</script>

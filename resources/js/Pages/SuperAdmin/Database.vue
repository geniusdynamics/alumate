<template>
    <AdminLayout
        app-name="Alumate"
        user-role="Super Admin"
        page-title="Database Management"
        :navigation-items="navigationItems"
    >
        <Head title="Database Management" />

        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white mb-2">Database Management</h2>
            <p class="text-gray-400">Monitor database performance, manage backups, and view table statistics.</p>
        </div>

        <!-- Database Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <DarkStatCard
                title="Database Size"
                :value="databaseStats.database_size"
                icon="CircleStackIcon"
                color="blue"
            />
            <DarkStatCard
                title="Total Tables"
                :value="Object.keys(databaseStats.table_stats).length"
                icon="TableCellsIcon"
                color="green"
            />
            <DarkStatCard
                title="Avg Query Time"
                :value="databaseStats.query_performance.avg_query_time"
                icon="ClockIcon"
                color="yellow"
            />
            <DarkStatCard
                title="Slow Queries"
                :value="databaseStats.query_performance.slow_queries"
                icon="ExclamationTriangleIcon"
                color="red"
            />
        </div>

        <!-- Database Stats and Backup Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Table Statistics -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Table Statistics</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="(count, table) in databaseStats.table_stats" :key="table" class="flex justify-between items-center">
                            <span class="text-gray-300 capitalize">{{ table.replace('_', ' ') }}</span>
                            <span class="text-blue-400 font-semibold">{{ count.toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Status -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h3 class="text-lg font-medium text-white">Backup & Maintenance</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Last Backup</span>
                            <span class="text-green-400 font-semibold">{{ formatBackupStatus() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Next Backup</span>
                            <span class="text-blue-400 font-semibold">{{ databaseStats.maintenance_schedule.next_backup }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-300">Next Maintenance</span>
                            <span class="text-purple-400 font-semibold">{{ databaseStats.maintenance_schedule.next_maintenance }}</span>
                        </div>
                        <div class="pt-4 space-y-2">
                            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Create Backup Now
                            </button>
                            <button class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Optimize Database
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Query Performance -->
        <div class="bg-gray-800 border border-gray-700 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-700">
                <h3 class="text-lg font-medium text-white">Query Performance</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400">{{ databaseStats.query_performance.avg_query_time }}</div>
                        <div class="text-sm text-gray-400">Average Query Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-400">{{ databaseStats.query_performance.slow_queries }}</div>
                        <div class="text-sm text-gray-400">Slow Queries</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ databaseStats.query_performance.total_queries }}</div>
                        <div class="text-sm text-gray-400">Total Queries</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import DarkStatCard from '@/components/DarkStatCard.vue'

const props = defineProps({
    databaseStats: Object,
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

const formatBackupStatus = () => {
    // This would show actual last backup time
    return '2 hours ago'
}
</script>

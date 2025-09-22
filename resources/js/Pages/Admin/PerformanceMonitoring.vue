<template>
    <AdminLayout :page-title="'Performance Monitoring'" :navigation-items="navigationItems">
        <PerformanceMonitoring />
    </AdminLayout>
</template>

<script setup lang="ts">
<<<<<<< HEAD
import PerformanceMonitoring from '@/Components/Admin/PerformanceMonitoring.vue';
=======
import PerformanceMonitoring from '@/Components/admin/PerformanceMonitoring.vue';
>>>>>>> origin/db1
import AdminLayout from '@/Components/AdminLayout.vue';
import { ChartBarIcon, CogIcon, CpuChipIcon, ServerIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const navigationItems = [
    {
        name: 'Dashboard',
        href: '/admin/dashboard',
        icon: ChartBarIcon,
        active: false,
    },
    {
        name: 'Performance',
        href: '/admin/performance',
        icon: CpuChipIcon,
        active: true,
    },
    {
        name: 'System Health',
        href: '/admin/system-health',
        icon: ServerIcon,
        active: false,
    },
    {
        name: 'Analytics',
        href: '/admin/analytics',
        icon: ChartBarIcon,
        active: false,
    },
    {
        name: 'Users',
        href: '/admin/users',
        icon: UserGroupIcon,
        active: false,
    },
    {
        name: 'Settings',
        href: '/admin/settings',
        icon: CogIcon,
        active: false,
    },
];
</script>












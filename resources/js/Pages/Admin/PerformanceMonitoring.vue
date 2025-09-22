<template>
    <AdminLayout :page-title="'Performance Monitoring'" :navigation-items="navigationItems">
        <PerformanceMonitoring />
    </AdminLayout>
</template>

<script setup lang="ts">
import PerformanceMonitoring from '@/components/Admin/PerformanceMonitoring.vue';
import AdminLayout from '@/components/AdminLayout.vue';
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












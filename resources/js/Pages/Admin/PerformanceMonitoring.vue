<template>
  <AdminLayout
    :page-title="'Performance Monitoring'"
    :navigation-items="navigationItems"
  >
    <PerformanceMonitoring />
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue'
import PerformanceMonitoring from '@/Components/Admin/PerformanceMonitoring.vue'
import { 
  ChartBarIcon,
  CpuChipIcon,
  ServerIcon,
  ClockIcon,
  UserGroupIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

const navigationItems = [
  {
    name: 'Dashboard',
    href: '/admin/dashboard',
    icon: ChartBarIcon,
    active: false
  },
  {
    name: 'Performance',
    href: '/admin/performance',
    icon: CpuChipIcon,
    active: true
  },
  {
    name: 'System Health',
    href: '/admin/system-health',
    icon: ServerIcon,
    active: false
  },
  {
    name: 'Analytics',
    href: '/admin/analytics',
    icon: ChartBarIcon,
    active: false
  },
  {
    name: 'Users',
    href: '/admin/users',
    icon: UserGroupIcon,
    active: false
  },
  {
    name: 'Settings',
    href: '/admin/settings',
    icon: CogIcon,
    active: false
  }
]
</script>
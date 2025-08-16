<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface NavGroup {
    label: string;
    items: NavItem[];
}

const props = defineProps<{
    items: NavItem[];
    grouped?: boolean;
}>();

const page = usePage<SharedData>();

// Group navigation items logically
const groupedItems = computed(() => {
    if (!props.grouped) {
        return [{ label: 'Platform', items: props.items }];
    }

    const groups: NavGroup[] = [
        {
            label: 'Main',
            items: props.items.filter(item => 
                ['Dashboard'].includes(item.title)
            )
        },
        {
            label: 'Social & Networking',
            items: props.items.filter(item => 
                ['Social Timeline', 'Alumni Directory'].includes(item.title)
            )
        },
        {
            label: 'Career & Jobs',
            items: props.items.filter(item => 
                ['Career Center', 'Job Dashboard'].includes(item.title)
            )
        },
        {
            label: 'Community',
            items: props.items.filter(item => 
                ['Events', 'Success Stories'].includes(item.title)
            )
        },
        {
            label: 'Personal',
            items: props.items.filter(item => 
                ['My Profile', 'Admin Dashboard', 'Employer Dashboard'].includes(item.title)
            )
        }
    ].filter(group => group.items.length > 0);

    return groups;
});

// Check if current route matches item href
const isActiveRoute = (href: string) => {
    const currentPath = page.url;
    // Exact match or starts with the href (for nested routes)
    return currentPath === href || (href !== '/dashboard' && currentPath.startsWith(href));
};

// Get tour attribute for navigation items
const getTourAttribute = (title: string) => {
    const tourMap: Record<string, string> = {
        'Social Timeline': 'social-timeline',
        'Alumni Directory': 'alumni-directory',
        'Career Center': 'career-center',
        'Job Dashboard': 'job-dashboard',
        'Events': 'events',
        'Success Stories': 'success-stories',
        'Dashboard': 'dashboard'
    };
    return tourMap[title] || null;
};
</script>

<template>
    <div class="space-y-2">
        <SidebarGroup 
            v-for="group in groupedItems" 
            :key="group.label" 
            class="px-2 py-0"
        >
            <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in group.items" :key="item.title">
                    <SidebarMenuButton 
                        as-child 
                        :is-active="isActiveRoute(item.href)" 
                        :tooltip="item.title"
                        :aria-label="`Navigate to ${item.title}`"
                        class="focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200"
                    >
                        <Link 
                            :href="item.href"
                            :aria-current="isActiveRoute(item.href) ? 'page' : undefined"
                            :data-tour="getTourAttribute(item.title)"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground focus:outline-none"
                        >
                            <component 
                                :is="item.icon" 
                                class="h-4 w-4 flex-shrink-0"
                                :aria-hidden="true"
                            />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </div>
</template>

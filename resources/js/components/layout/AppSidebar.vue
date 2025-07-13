<script setup lang="ts">
import { Sidebar, SidebarContent, SidebarFooter, SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePage, Link } from '@inertiajs/vue3';
import AppLogo from '@/components/common/AppLogo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ChevronUp, Home, Users, Settings, Shield } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const menuItems = [
    {
        title: 'Dashboard',
        icon: Home,
        href: route('dashboard'),
        active: route().current('dashboard')
    },
    {
        title: 'Users',
        icon: Users,
        href: route('users.index'),
        active: route().current('users.*'),
        permission: 'view users'
    },
    {
        title: 'Roles',
        icon: Shield,
        href: route('roles.index'),
        active: route().current('roles.*'),
        permission: 'view roles'
    },
    {
        title: 'Institutions',
        icon: Home,
        href: route('institutions.index'),
        active: route().current('institutions.*'),
        permission: 'view institutions'
    },
    {
        title: 'Courses',
        icon: Home, // You can change this icon
        href: route('courses.index'),
        active: route().current('courses.*'),
        permission: 'manage courses'
    },
    {
        title: 'Graduates',
        icon: Users,
        href: route('graduates.index'),
        active: route().current('graduates.*'),
        permission: 'manage graduates'
    },
    {
        title: 'My Profile',
        icon: Users,
        href: route('profile.show'),
        active: route().current('profile.*'),
        permission: 'update profile'
    },
    {
        title: 'Jobs',
        icon: Home, // You can change this icon
        href: route('jobs.public.index'),
        active: route().current('jobs.public.index'),
        permission: 'view jobs'
    },
    {
        title: 'My Applications',
        icon: Home, // You can change this icon
        href: route('my.applications'),
        active: route().current('my.applications'),
        permission: 'view applications'
    },
    {
        title: 'Settings',
        icon: Settings,
        href: route('settings.profile'),
        active: route().current('settings.*')
    }
];

const filteredMenuItems = computed(() => {
    return menuItems.filter(item => {
        if (!item.permission) return true;
        return user.value?.permissions?.includes(item.permission) || user.value?.roles?.some((role: any) => role.permissions?.includes(item.permission));
    });
});
</script>

<template>
    <Sidebar>
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg">
                        <AppLogo :show-text="false" class="h-8 w-8" />
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-semibold">Starter Kit</span>
                            <span class="truncate text-xs">Laravel + Vue</span>
                        </div>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        
        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>Application</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filteredMenuItems" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>
        
        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton size="lg">
                                <Avatar class="h-8 w-8 rounded-lg">
                                    <AvatarFallback class="rounded-lg">
                                        {{ user?.name?.charAt(0).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ user?.name }}</span>
                                    <span class="truncate text-xs">{{ user?.email }}</span>
                                </div>
                                <ChevronUp class="ml-auto size-4" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-56" align="end" side="top">
                            <DropdownMenuItem :as="Link" :href="route('settings.profile')">
                                Settings
                            </DropdownMenuItem>
                            <DropdownMenuItem :as="Link" :href="route('logout')" method="post">
                                Logout
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>


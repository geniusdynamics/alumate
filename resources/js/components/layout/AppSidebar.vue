<script setup lang="ts">
import { Sidebar, SidebarContent, SidebarFooter, SidebarGroup, SidebarGroupContent, SidebarGroupLabel, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePage, Link } from '@inertiajs/vue3';
import AppLogo from '@/components/common/AppLogo.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ChevronUp, Home, Users, Settings, Shield, Bell, MessageCircle, UserPlus, Briefcase, Calendar, Star, Target, BarChart3, FileText, PieChart, Database, Heart, GraduationCap, Trophy } from 'lucide-vue-next';
import NotificationDropdown from '@/components/NotificationDropdown.vue';
import { computed } from 'vue';

// Icon aliases for backward compatibility
const ChartBarIcon = BarChart3;
const DocumentTextIcon = FileText;
const ChartPieIcon = PieChart;
const CircleStackIcon = Database;

const page = usePage();
const user = computed(() => page.props.auth?.user);
const notifications = computed(() => page.props.auth?.notifications);

// Main navigation items
const mainMenuItems = [
    {
        title: 'Dashboard',
        icon: Home,
        href: route('dashboard'),
        active: route().current('dashboard'),
        tourTarget: 'dashboard'
    },
    // Social Features
    {
        title: 'Social Timeline',
        icon: MessageCircle,
        href: route('social.timeline'),
        active: route().current('social.*'),
        permission: 'view social',
        tourTarget: 'social-timeline'
    },
    {
        title: 'Alumni Directory',
        icon: UserPlus,
        href: route('alumni.directory'),
        active: route().current('alumni.directory'),
        permission: 'view alumni',
        tourTarget: 'alumni-directory'
    },
    {
        title: 'Alumni Recommendations',
        icon: Users,
        href: route('alumni.recommendations'),
        active: route().current('alumni.recommendations'),
        permission: 'view alumni'
    },
    {
        title: 'Career Timeline',
        icon: Target,
        href: route('career.timeline'),
        active: route().current('career.timeline'),
        permission: 'view career',
        tourTarget: 'career-timeline'
    },
    {
        title: 'Mentorship Hub',
        icon: Users,
        href: route('career.mentorship-hub'),
        active: route().current('career.mentorship-hub'),
        permission: 'view career'
    },
    {
        title: 'Job Dashboard',
        icon: Briefcase,
        href: route('jobs.dashboard'),
        active: route().current('jobs.dashboard') || route().current('jobs.recommendations'),
        permission: 'view jobs',
        tourTarget: 'job-dashboard'
    },
    {
        title: 'Events Discovery',
        icon: Calendar,
        href: route('events.discovery'),
        active: route().current('events.discovery'),
        permission: 'view events',
        tourTarget: 'events'
    },
    {
        title: 'My Events',
        icon: Calendar,
        href: route('events.my-events'),
        active: route().current('events.my-events'),
        permission: 'view events'
    },
    {
        title: 'Fundraising',
        icon: Heart,
        href: route('campaigns.index'),
        active: route().current('campaigns.*'),
        permission: 'view fundraising',
        tourTarget: 'fundraising'
    },
    {
        title: 'Scholarships',
        icon: GraduationCap,
        href: route('scholarships.index'),
        active: route().current('scholarships.*'),
        permission: 'view scholarships',
        tourTarget: 'scholarships'
    },
    {
        title: 'Success Stories',
        icon: Star,
        href: route('stories.index'),
        active: route().current('stories.*'),
        permission: 'view stories',
        tourTarget: 'success-stories'
    },
    {
        title: 'Achievements',
        icon: Trophy,
        href: route('achievements.index'),
        active: route().current('achievements.*'),
        permission: 'view achievements',
        tourTarget: 'achievements'
    }
];

// Administrative items
const adminMenuItems = [
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
        title: 'Jobs',
        icon: Briefcase,
        href: route('jobs.public.index'),
        active: route().current('jobs.public.index'),
        permission: 'view jobs'
    }
];

// Super Admin items
const superAdminMenuItems = [
    {
        title: 'Super Admin Dashboard',
        icon: Home,
        href: route('super-admin.dashboard'),
        active: route().current('super-admin.dashboard'),
        role: 'super-admin'
    },
    {
        title: 'System Analytics',
        icon: ChartBarIcon,
        href: route('super-admin.analytics'),
        active: route().current('super-admin.analytics'),
        role: 'super-admin'
    },
    {
        title: 'Content Management',
        icon: DocumentTextIcon,
        href: route('super-admin.content'),
        active: route().current('super-admin.content'),
        role: 'super-admin'
    },
    {
        title: 'Activity Monitoring',
        icon: ChartPieIcon,
        href: route('super-admin.activity'),
        active: route().current('super-admin.activity'),
        role: 'super-admin'
    },
    {
        title: 'Database Management',
        icon: CircleStackIcon,
        href: route('super-admin.database'),
        active: route().current('super-admin.database'),
        role: 'super-admin'
    },
    {
        title: 'Performance Monitoring',
        icon: ChartBarIcon,
        href: route('super-admin.performance'),
        active: route().current('super-admin.performance'),
        role: 'super-admin'
    },
    {
        title: 'Notification Management',
        icon: Bell,
        href: route('super-admin.notifications'),
        active: route().current('super-admin.notifications'),
        role: 'super-admin'
    },
    {
        title: 'System Settings',
        icon: Settings,
        href: route('super-admin.settings'),
        active: route().current('super-admin.settings'),
        role: 'super-admin'
    },
    {
        title: 'Security Dashboard',
        icon: Shield,
        href: route('security.dashboard'),
        active: route().current('security.*'),
        role: 'super-admin'
    },
    {
        title: 'Super Admins',
        icon: Users,
        href: route('super-admins.index'),
        active: route().current('super-admins.*'),
        permission: 'manage super admins'
    }
];

// Personal and other items
const personalMenuItems = [
    {
        title: 'My Profile',
        icon: Users,
        href: route('profile.show'),
        active: route().current('profile.*'),
        permission: 'update profile'
    },
    {
        title: 'My Applications',
        icon: Briefcase,
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

// Legacy items (to be organized later)
const legacyMenuItems = [
    {
        title: 'Merge Records',
        icon: Users,
        href: route('merge.index'),
        active: route().current('merge.*'),
        permission: 'merge records'
    },
    {
        title: 'Tutors',
        icon: Users,
        href: route('tutors.index'),
        active: route().current('tutors.*'),
        permission: 'manage tutors'
    },
    {
        title: 'Edit Institution',
        icon: Settings,
        href: route('institution.edit'),
        active: route().current('institution.edit'),
        permission: 'manage institution'
    },
    {
        title: 'Education History',
        icon: Users,
        href: route('education.index'),
        active: route().current('education.*'),
        permission: 'manage education'
    },
    {
        title: 'Request Assistance',
        icon: Users,
        href: route('assistance.index'),
        active: route().current('assistance.*'),
        permission: 'request assistance'
    },
    {
        title: 'Approve Companies',
        icon: Users,
        href: route('companies.index'),
        active: route().current('companies.*'),
        permission: 'approve companies'
    },
    {
        title: 'Search Graduates',
        icon: Users,
        href: route('graduates.search'),
        active: route().current('graduates.search'),
        permission: 'view graduates'
    }
];

const filterMenuItems = (items: any[]) => {
    return items.filter(item => {
        // Check role-based access
        if (item.role) {
            return user.value?.roles?.some((role: any) => role.name === item.role);
        }
        
        // Check permission-based access
        if (item.permission) {
            return user.value?.permissions?.includes(item.permission) || 
                   user.value?.roles?.some((role: any) => role.permissions?.includes(item.permission));
        }
        
        // If no permission or role specified, show to all authenticated users
        return true;
    });
};
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
                        <NotificationDropdown />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        
        <SidebarContent>
            <!-- Main Navigation -->
            <SidebarGroup>
                <SidebarGroupLabel>Main</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filterMenuItems(mainMenuItems)" :key="item.title">
                            <SidebarMenuButton 
                                :as="Link" 
                                :href="item.href" 
                                :is-active="item.active"
                                :data-tour="item.tourTarget"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Administrative -->
            <SidebarGroup v-if="filterMenuItems(adminMenuItems).length > 0">
                <SidebarGroupLabel>Administration</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filterMenuItems(adminMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Super Admin -->
            <SidebarGroup v-if="filterMenuItems(superAdminMenuItems).length > 0">
                <SidebarGroupLabel>Super Admin</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filterMenuItems(superAdminMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Personal -->
            <SidebarGroup>
                <SidebarGroupLabel>Personal</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filterMenuItems(personalMenuItems)" :key="item.title">
                            <SidebarMenuButton :as="Link" :href="item.href" :is-active="item.active">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Legacy (if needed) -->
            <SidebarGroup v-if="filterMenuItems(legacyMenuItems).length > 0">
                <SidebarGroupLabel>Other</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in filterMenuItems(legacyMenuItems)" :key="item.title">
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


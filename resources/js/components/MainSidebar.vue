<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    BookOpen, 
    Folder, 
    LayoutGrid, 
    Users, 
    Briefcase, 
    Calendar, 
    Trophy, 
    MessageSquare,
    MapPin,
    GraduationCap,
    Heart
} from 'lucide-vue-next';
import SidebarLogo from './SidebarLogo.vue';
import HelpButton from './onboarding/HelpButton.vue';

const page = usePage();
const user = page.props.auth?.user;

// Main navigation items for alumni platform
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Social Timeline',
        href: '/social/timeline',
        icon: MessageSquare,
    },
    {
        title: 'Alumni Directory',
        href: '/alumni/directory',
        icon: Users,
    },
    {
        title: 'Career Center',
        href: '/career/timeline',
        icon: GraduationCap,
    },
    {
        title: 'Job Dashboard',
        href: '/jobs/dashboard',
        icon: Briefcase,
    },
    {
        title: 'Events',
        href: '/events',
        icon: Calendar,
    },
    {
        title: 'Success Stories',
        href: '/stories',
        icon: Trophy,
    },
];

// Additional navigation items based on user role
const roleBasedNavItems: NavItem[] = [];

// Add role-specific navigation items
if (user?.roles?.some((role: any) => role.name === 'institution-admin')) {
    roleBasedNavItems.push({
        title: 'Admin Dashboard',
        href: '/institution-admin/dashboard',
        icon: LayoutGrid,
    });
}

if (user?.roles?.some((role: any) => role.name === 'employer')) {
    roleBasedNavItems.push({
        title: 'Employer Dashboard',
        href: '/employer/dashboard',
        icon: Briefcase,
    });
}

if (user?.roles?.some((role: any) => role.name === 'graduate')) {
    roleBasedNavItems.push({
        title: 'My Profile',
        href: '/graduate/profile',
        icon: Heart,
    });
}

// Combine main nav with role-based items
const allMainNavItems = [...mainNavItems, ...roleBasedNavItems];

const footerNavItems: NavItem[] = [
    {
        title: 'Documentation',
        href: '/docs',
        icon: BookOpen,
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: Folder,
    },
];
</script>

<template>
    <Sidebar data-tour="main-navigation">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <SidebarLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <NavMain :items="allMainNavItems" :grouped="true" />
        </SidebarContent>
        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <div class="flex items-center justify-between px-2 py-2">
                <NavUser data-tour="profile" />
                <HelpButton />
            </div>
        </SidebarFooter>
    </Sidebar>
</template>
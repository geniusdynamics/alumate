<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Settings, Shield, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const breadcrumbs: BreadcrumbItemType[] = [{ title: 'Dashboard' }];

const quickActions = [
    {
        title: 'Manage Users',
        description: 'View and manage system users',
        icon: Users,
        href: route('users.index'),
        permission: 'view users',
    },
    {
        title: 'Manage Roles',
        description: 'Configure roles and permissions',
        icon: Shield,
        href: route('roles.index'),
        permission: 'view roles',
    },
    {
        title: 'Settings',
        description: 'Update your profile and preferences',
        icon: Settings,
        href: route('settings.profile'),
        permission: null,
    },
];

const canAccess = (permission: string | null) => {
    if (!permission) return true;
    return user.value?.permissions?.includes(permission) || user.value?.roles?.some((role: any) => role.permissions?.includes(permission));
};

const availableActions = computed(() => {
    return quickActions.filter((action) => canAccess(action.permission));
});
</script>

<template>
    <DefaultLayout title="Dashboard" :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-muted-foreground mt-2">Welcome back, {{ user?.name }}! Here's what's happening.</p>
            </div>

            <!-- Quick Stats -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Welcome Back</CardTitle>
                        <Users class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ user?.name }}</div>
                        <p class="text-muted-foreground text-xs">
                            {{ user?.email }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Your Roles</CardTitle>
                        <Shield class="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ user?.roles?.length || 0 }}</div>
                        <p class="text-muted-foreground text-xs">Active roles assigned</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                    <CardDescription> Common tasks and shortcuts to get you started. </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="action in availableActions"
                            :key="action.title"
                            class="hover:bg-accent hover:text-accent-foreground flex items-center space-x-4 rounded-lg border p-4 transition-colors"
                        >
                            <component :is="action.icon" class="text-primary h-8 w-8" />
                            <div class="flex-1 space-y-1">
                                <h4 class="text-sm leading-none font-medium">{{ action.title }}</h4>
                                <p class="text-muted-foreground text-sm">{{ action.description }}</p>
                            </div>
                            <Button :as="Link" :href="action.href" size="sm" variant="outline"> Go </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Getting Started -->
            <Card>
                <CardHeader>
                    <CardTitle>Laravel + Vue.js Starter Kit</CardTitle>
                    <CardDescription> This application is built with modern technologies and best practices. </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-muted-foreground text-sm">Features included in this starter kit:</p>
                    <div class="grid gap-2 md:grid-cols-2">
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">Laravel 11 with modern PHP features</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">Vue 3 with Composition API and TypeScript</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">Inertia.js for seamless SPA experience</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">Tailwind CSS for beautiful design</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">Shadcn/ui components</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">Spatie Laravel Permission for roles</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">DataTable component with pagination</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-primary h-1.5 w-1.5 rounded-full"></span>
                            <span class="text-sm">Organized component structure</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </DefaultLayout>
</template>
